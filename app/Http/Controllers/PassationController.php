<?php

namespace App\Http\Controllers;

use App\Models\Passation;
use App\Models\Salle;
use App\Models\PassationEditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PassationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // For normal user: only their own passations
    public function index(Request $request)
    {
        $user = Auth::user();
        $salle_id = $request->input('salle_id');
        $search = $request->input('search');

        $query = Passation::with(['user', 'salle']);

        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        if ($salle_id) {
            $query->where('salle_id', $salle_id);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom_patient', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('ip', 'like', "%{$search}%");
            });
        }

        $passations = $query->latest()->get();
        $salles = Salle::all();

        return view('passations.index', compact('passations', 'salles', 'salle_id', 'search'));
    }

    // New method for admin to see all passations without user filtering
    public function indexAll(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            abort(403, 'Accès refusé.');
        }

        $salle_id = $request->input('salle_id');
        $search = $request->input('search');

        $query = Passation::with(['user', 'salle']);

        if ($salle_id) {
            $query->where('salle_id', $salle_id);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom_patient', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('ip', 'like', "%{$search}%");
            });
        }

        $passations = $query->latest()->get();
        $salles = Salle::all();

        return view('passations.index', compact('passations', 'salles', 'salle_id', 'search'));
    }

    public function create()
    {
        return redirect()->route('passations.index');
    }

    public function store(Request $request)
    {
        $rules = [
            'nom_patient'    => 'required|string|max:255',
            'prenom'         => 'nullable|string|max:255',
            'cin'            => 'nullable|string|max:255',
            'ip'             => 'nullable|string|max:255',
            'description'    => 'nullable|string',
            'file_attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,gif,txt|max:10240', // 10MB max
            'salle_id'       => 'required|exists:salles,id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check for IP conflict in the same salle
        $existingPassation = Passation::where('ip', $request->ip)
                                    ->where('salle_id', $request->salle_id)
                                    ->first();

        // Only return error if user hasn't confirmed the conflict
        if ($existingPassation && !$request->input('confirm_conflict')) {
            return response()->json(['errors' => ['ip' => 'Cette IP existe déjà pour un patient dans cette salle.']], 422);
        }

        $data = $request->only([
            'nom_patient', 'prenom', 'cin', 'ip',
            'description', 'salle_id'
        ]);
        $data['user_id'] = Auth::id();
        
        // Always set date_passation to current system date
        $data['date_passation'] = now();

        // Handle file upload
        if ($request->hasFile('file_attachment')) {
            $file = $request->file('file_attachment');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('passations', $fileName, 'public');
            $data['file_attachment'] = $fileName;
        }

        Passation::create($data);

        return response()->json(['success' => 'Passation enregistrée avec succès.'], 200);
    }

    public function show(Passation $passation)
    {
        if (Auth::id() !== $passation->user_id && Auth::user()->role !== 'admin') {
            abort(403, 'Accès refusé.');
        }

        return view('passations.show', compact('passation'));
    }

    public function edit(Passation $passation)
    {
        return redirect()->route('passations.index');
    }

    public function update(Request $request, Passation $passation)
    {
        $user = auth()->user();

        // Check if user has permission to edit this passation
        if (Auth::id() !== $passation->user_id && Auth::user()->role !== 'admin') {
            abort(403, 'Accès refusé.');
        }

        // Check time restriction for non-admin users
        if ($user->role !== 'admin' && now()->diffInMinutes($passation->created_at) > 30) {
            return redirect()->route('passations.index')
                ->withErrors(['modification' => 'Vous ne pouvez pas modifier cette passation après 30 minutes.'])
                ->with('error', 'Modification interdite: délai de 30 minutes dépassé.');
        }

        $rules = [
            'nom_patient'    => 'required|string|max:255',
            'prenom'         => 'nullable|string|max:255',
            'cin'            => 'nullable|string|max:255',
            'ip'             => 'nullable|string|max:255',
            'description'    => 'nullable|string',
            'file_attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,gif,txt|max:10240', // 10MB max
            'salle_id'       => 'required|exists:salles,id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->route('passations.index')
                ->withErrors($validator)
                ->withInput()
                ->with('form', 'edit')
                ->with('edit_id', $passation->id);
        }

        $changes = [];
        foreach ($request->only([
            'nom_patient', 'prenom', 'cin', 'ip',
            'description', 'salle_id'
        ]) as $field => $newValue) {
            $oldValue = $passation->$field;
            if ($oldValue != $newValue) {
                $changes[] = [
                    'passation_id' => $passation->id,
                    'user_id' => Auth::id(),
                    'field' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Handle file upload
        $updateData = $request->only([
            'nom_patient', 'prenom', 'cin', 'ip',
            'description', 'salle_id'
        ]);
        
        // Always keep date_passation as current system date for updates
        $updateData['date_passation'] = now();

        if ($request->hasFile('file_attachment')) {
            // Delete old file if exists
            if ($passation->file_attachment) {
                Storage::disk('public')->delete('passations/' . $passation->file_attachment);
            }
            
            $file = $request->file('file_attachment');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('passations', $fileName, 'public');
            $updateData['file_attachment'] = $fileName;
            
            // Log file change
            $changes[] = [
                'passation_id' => $passation->id,
                'user_id' => Auth::id(),
                'field' => 'file_attachment',
                'old_value' => $passation->file_attachment ?? 'N/A',
                'new_value' => $fileName,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $passation->update($updateData);

        if (!empty($changes)) {
            PassationEditLog::insert($changes);
        }

        return redirect()->route('passations.index')->with('success', 'Passation mise à jour.');
    }

    public function destroy(Passation $passation)
    {
        // Only admins can delete passations
        if (Auth::user()->role === 'admin') {
            // Delete associated file if exists
            if ($passation->file_attachment) {
                Storage::disk('public')->delete('passations/' . $passation->file_attachment);
            }
            
            $passation->delete();
            return redirect()->route('passations.index')->with('success', 'Passation supprimée.');
        }

        return redirect()->route('passations.index')->with('error', 'Non autorisé.');
    }

    /**
     * Download file attachment
     */
    public function downloadFile(Passation $passation)
    {
        if (!$passation->file_attachment) {
            abort(404, 'Fichier non trouvé.');
        }

        $filePath = 'passations/' . $passation->file_attachment;
        
        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'Fichier non trouvé sur le serveur.');
        }

        // Get file extension to determine content type
        $extension = pathinfo($passation->file_attachment, PATHINFO_EXTENSION);
        $contentType = $this->getContentType($extension);
        
        $file = Storage::disk('public')->get($filePath);
        $headers = [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $passation->file_attachment . '"',
        ];
        return response($file, 200, $headers);
    }

    /**
     * Get content type based on file extension
     */
    private function getContentType($extension)
    {
        $contentTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'txt' => 'text/plain',
        ];

        return $contentTypes[strtolower($extension)] ?? 'application/octet-stream';
    }

    /**
     * Delete file attachment
     */
    public function deleteFile(Passation $passation)
    {
        if (!$passation->file_attachment) {
            return response()->json(['error' => 'Aucun fichier à supprimer.'], 400);
        }

        // Check permissions
        if (Auth::id() !== $passation->user_id && Auth::user()->role !== 'admin') {
            abort(403, 'Accès refusé.');
        }

        // Check time restriction for non-admin users
        if (Auth::user()->role !== 'admin' && now()->diffInMinutes($passation->created_at) > 30) {
            return response()->json(['error' => 'Vous ne pouvez pas modifier cette passation après 30 minutes.'], 403);
        }

        $oldFileName = $passation->file_attachment;
        
        // Delete file from storage
        Storage::disk('public')->delete('passations/' . $oldFileName);
        
        // Update passation record
        $passation->update(['file_attachment' => null]);
        
        // Log the change
        PassationEditLog::create([
            'passation_id' => $passation->id,
            'user_id' => Auth::id(),
            'field' => 'file_attachment',
            'old_value' => $oldFileName,
            'new_value' => null,
        ]);

        return response()->json(['success' => 'Fichier supprimé avec succès.']);
    }

    public function dashboard(Request $request)
    {
        $query = Passation::with(['user', 'salle']);

        $salle_id = $request->input('salle_id');
        $search = $request->input('search');

        if (!is_null($salle_id) && $salle_id !== '') {
            $query->where('salle_id', $salle_id);
        }

        if (!is_null($search) && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nom_patient', 'like', '%' . $search . '%')
                ->orWhere('prenom', 'like', '%' . $search . '%')
                ->orWhere('ip', 'like', '%' . $search . '%');  // <-- added here
            });
        }


        $passations = $query->latest()->get();
        $salles = Salle::all();

        return view('dashboard', compact('passations', 'salles', 'salle_id', 'search'));
    }

    /**
     * Search all passations for the create modal (not filtered by user)
     */
    public function searchAllPassations(Request $request)
    {
        $search = $request->input("search");

        $query = Passation::with(["user", "salle"]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where("nom_patient", "like", "%{$search}%")
                  ->orWhere("prenom", "like", "%{$search}%")
                  ->orWhere("ip", "like", "%{$search}%");
            });
        }

        $passations = $query->latest()->get();

        // Group by IP and get the most recent passation for each IP
        $groupedPassations = $passations->groupBy("ip")->map(function ($passationsByIp) {
            $latestPassation = $passationsByIp->sortByDesc("date_passation")->first();
            // Ensure salle information is always loaded
            $latestPassation->load("salle");
            return $latestPassation;
        });

        return response()->json([
            "success" => true,
            "passations" => $groupedPassations->values()
        ]);
    }

    
}
