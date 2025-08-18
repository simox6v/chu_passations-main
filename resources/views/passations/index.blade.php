@extends('layouts.app')

@section('content')
<style>
  /* Rich text editor improvements */
  #richTextEditor {
    font-family: inherit;
    line-height: 1.5;
  }
  
  #richTextEditor:empty:before {
    content: attr(placeholder);
    color: #9CA3AF;
    font-style: italic;
  }
  
  #richTextEditor ul, #richTextEditor ol {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
  }
  
  #richTextEditor li {
    margin: 0.25rem 0;
  }
  
  #richTextEditor strong {
    font-weight: 600;
  }
  
  #richTextEditor em {
    font-style: italic;
  }
  
  #richTextEditor u {
    text-decoration: underline;
  }
  
  /* Modal scrolling improvements */
  #createModal .overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: #CBD5E1 #F1F5F9;
  }
  
  #createModal .overflow-y-auto::-webkit-scrollbar {
    width: 8px;
  }
  
  #createModal .overflow-y-auto::-webkit-scrollbar-track {
    background: #F1F5F9;
    border-radius: 4px;
  }
  
  #createModal .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #CBD5E1;
    border-radius: 4px;
  }
  
  #createModal .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #94A3B8;
  }
  
  /* Fixed header styling */
  #createModal .flex-shrink-0 {
    position: sticky;
    top: 0;
    z-index: 10;
    background: white;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  }
  
  /* Responsive improvements */
  @media (max-width: 640px) {
    .w-\[80\%\] {
      width: 95%;
    }
    
    .h-\[80\%\] {
      height: 90%;
    }
    
    .grid-cols-1.sm\:grid-cols-2 {
      grid-template-columns: 1fr;
    }
    
    .flex.gap-4 {
      flex-direction: column;
    }
  }
  
  /* Hover effects consistency */
  .hover\:bg-gray-200:hover {
    background-color: #E5E7EB;
  }
  
  .hover\:bg-gray-400:hover {
    background-color: #9CA3AF;
  }
  
  .hover\:bg-green-700:hover {
    background-color: #15803D;
  }
  
  .hover\:bg-orange-600:hover {
    background-color: #EA580C;
  }
  
  /* Search results styling */
  #patientSearchResults {
    max-width: 100%;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }
  
  #patientSearchResults .hover\:bg-gray-100:hover {
    background-color: #F3F4F6;
  }
</style>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900">Liste des passations</h1>

        @auth
            @if(in_array(auth()->user()->role, ['admin', 'medecin']))
                <button
                    id="openCreateModal"
                    class="mt-4 sm:mt-0 inline-flex items-center bg-green-600 hover:bg-green-700 text-white font-semibold px-5 py-3 rounded-lg shadow-lg transition duration-200"
                    type="button"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Ajouter une passation
                </button>
            @endif
        @endauth
    </div>

    {{-- Search bar (search by nom, prénom, or IP) --}}
    <form method="GET" action="{{ route('passations.index') }}" class="flex flex-col sm:flex-row items-center gap-3 mb-8">
        <input
            type="text"
            name="search"
            placeholder="Rechercher par nom, prénom ou IP..."
            value="{{ request('search') }}"
            class="w-full sm:w-1/3 border border-gray-300 rounded-md px-4 py-3 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            aria-label="Recherche par nom, prénom ou IP"
        >

        <select
            name="salle_id"
            class="w-full sm:w-1/4 border border-gray-300 rounded-md px-4 py-2 focus:ring focus:ring-blue-200"
            aria-label="Filtrer par salle"
        >
            <option value="">-- Toutes les salles --</option>
            @foreach($salles as $salle)
                <option value="{{ $salle->id }}" {{ request('salle_id') == $salle->id ? 'selected' : '' }}>
                    {{ $salle->nom ?? 'Salle #' . $salle->id }}
                </option>
            @endforeach
        </select>

        <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg shadow transition duration-200 whitespace-nowrap"
        >
            Filtrer
        </button>
    </form>

    <!-- Table with fixed max height and vertical scroll -->
    <div class="overflow-x-auto bg-white shadow-lg rounded-lg max-h-[500px] overflow-y-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold text-gray-700 uppercase tracking-wider">IP (Identifiant Patient)</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-700 uppercase tracking-wider">Patient</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-700 uppercase tracking-wider">Salle</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-700 uppercase tracking-wider">Médecin</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-700 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($passations as $passation)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-medium">{{ $passation->ip ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-medium">
                            {{ $passation->nom_patient }} {{ $passation->prenom }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $passation->salle->nom ?? 'Non spécifiée' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-700 relative">
                            <p class="font-medium flex items-center justify-between">
                                {{ $passation->user->name ?? 'Inconnu' }}

                                <div class="flex items-center space-x-2">
                                    @if($passation->file_attachment)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800" title="Pièce jointe disponible">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                        </svg>
                                        Fichier
                                    </span>
                                    @endif

                                    @if($passation->editLogs->count() > 0 && auth()->user()->role === 'admin')
                                    <button
                                        type="button"
                                        aria-label="Voir les modifications"
                                        title="Voir les modifications"
                                        class="w-6 h-6 flex-shrink-0 cursor-pointer"
                                        data-passation-id="{{ $passation->id }}"
                                        onclick="showEditLogs(event, {{ $passation->id }})"
                                    >
                                        <img src="{{ asset('images/edited.png') }}" alt="Modifié" class="w-full h-full object-contain" draggable="false" />
                                    </button>
                                    @endif
                                </div>
                            </p>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                            {{ \Carbon\Carbon::parse($passation->date_passation)->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center space-x-3 flex justify-center items-center">
                            <!-- Voir popup -->
                            <button
                                type="button"
                                class="openShowModalBtn w-7 h-7 sm:w-6 sm:h-6 hover:scale-110 transition-transform duration-150"
                                data-id="{{ $passation->id }}"
                                title="Voir"
                                aria-label="Voir les détails de la passation"
                            >
                                <img src="{{ asset('images/show.png') }}" alt="Voir" class="w-full h-full object-contain">
                            </button>

                            @if(auth()->user()->id === $passation->user_id || auth()->user()->role === 'admin')
                                <!-- Modifier -->
                                <button
                                  type="button"
                                  class="openEditModalBtn w-7 h-7 sm:w-6 sm:h-6 hover:scale-110 transition-transform duration-150"
                                  data-id="{{ $passation->id }}"
                                  title="Modifier"
                                  aria-label="Modifier la passation"
                                >
                                  <img src="{{ asset('images/edit.png') }}" alt="Modifier" class="w-full h-full object-contain">
                                </button>
                            @endif

                            @if(auth()->user()->role === 'admin')
                                <!-- Supprimer -->
                                <form action="{{ route('passations.destroy', $passation) }}" method="POST" class="inline-block"
                                    onsubmit="return confirm('Confirmer la suppression ?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Supprimer" aria-label="Supprimer la passation">
                                        <img src="{{ asset('images/deleted.png') }}" alt="Supprimer" class="w-7 h-7 sm:w-6 sm:h-6 hover:scale-110 transition-transform duration-150 object-contain">
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400 italic">Aucune passation trouvée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div
            id="editLogsPopup"
            tabindex="0"
            class="hidden fixed z-50 max-w-xs max-h-60 overflow-auto rounded-md bg-white border border-gray-300 shadow-lg text-xs text-gray-800 p-3 sm:max-w-sm"
            role="dialog" aria-modal="true" aria-labelledby="editLogsPopupTitle"
            style="box-shadow: 0 5px 15px rgba(0,0,0,0.2);"
        >
            <h3 id="editLogsPopupTitle" class="font-semibold px-3 py-2 border-b border-gray-200 bg-gray-50">Modifications</h3>
            <ul id="editLogsPopupList" class="list-disc list-inside space-y-1"></ul>
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div
  id="createModal"
  class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4"
  role="dialog" aria-modal="true" aria-labelledby="createModalTitle"
>
  <div
    class="bg-white rounded-lg shadow-lg w-[80%] h-[80%] relative flex flex-col max-h-screen overflow-hidden"
  >
    <!-- Fixed Header -->
    <div class="flex-shrink-0 p-6 pb-4 border-b border-gray-200 bg-white">
      <button
        id="closeCreateModal"
        class="absolute top-3 right-3 text-gray-700 hover:text-gray-900 text-2xl font-bold"
        title="Fermer"
        aria-label="Fermer la fenêtre"
        type="button"
      >&times;</button>

      <h2 id="createModalTitle" class="text-xl font-semibold">Créer une nouvelle passation</h2>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto p-6 pt-4">
      <form
        id="createPassationForm"
        method="POST"
        action="{{ route('passations.store') }}"
        enctype="multipart/form-data"
        class="space-y-6"
      >
      @csrf
      
      <!-- Top row: Salle selection and Patient search -->
      <div class="flex gap-4 mb-6">
        <div class="flex-1">
          <label for="salle_id" class="block text-sm font-medium text-gray-700 mb-2">Salle</label>
          <select
            name="salle_id"
            id="salle_id"
            required
            class="w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200"
          >
            <option value="">-- Sélectionner une salle --</option>
            @foreach($salles as $salle)
              <option value="{{ $salle->id }}">{{ $salle->nom }} ({{ $salle->nombre_lits ?? 'N/A' }} lits)</option>
            @endforeach
          </select>
          <p id="salleWarning" class="hidden text-red-600 text-sm font-semibold mt-2"></p>
          @error('salle_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex-1 relative">
          <label for="search_patient" class="block text-sm font-medium text-gray-700 mb-2">Rechercher un patient</label>
          <input
            type="text"
            id="search_patient"
            class="w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200"
            placeholder="Rechercher par nom, prénom ou IP..."
            autocomplete="off"
          >
          <div
            id="patientSearchResults"
            class="absolute z-10 max-h-48 overflow-auto border border-gray-300 rounded mt-1 bg-white shadow-lg hidden w-full"
          ></div>
        </div>
      </div>

      <!-- Patient information fields -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div>
          <label for="nom_patient" class="block text-sm font-medium text-gray-700">Nom</label>
          <input
            type="text"
            name="nom_patient"
            id="nom_patient"
            required
            class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200"
          >
          <p id="nom_patient-error" class="text-red-600 text-sm mt-1 hidden"></p>
          @error('nom_patient') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label for="prenom" class="block text-sm font-medium text-gray-700">Prénom</label>
          <input
            type="text"
            name="prenom"
            id="prenom"
            class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200"
          >
          <p id="prenom-error" class="text-red-600 text-sm mt-1 hidden"></p>
          @error('prenom') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div>
          <label for="cin" class="block text-sm font-medium text-gray-700">CIN</label>
          <input
            type="text"
            name="cin"
            id="cin"
            class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200"
          >
          @error('cin') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label for="ip" class="block text-sm font-medium text-gray-700">IP (Identifiant Patient)</label>
          <input
            type="text"
            name="ip"
            id="ip"
            required
            class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200"
          >
          @error('ip') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
          <p id="ipExistsMsg" class="text-red-600 mt-1 hidden"></p>
          <p id="ipConflictMsg" class="text-orange-600 mt-1 hidden"></p>
        </div>
      </div>



      <!-- Rich text editor for description -->
      <div class="mb-4">
        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Consignes</label>
        <div class="border border-gray-300 rounded-md">
          <div id="toolbar" class="border-b border-gray-300 p-2 bg-gray-50 rounded-t-md">
            <button type="button" onclick="formatText('bold')" class="px-2 py-1 border rounded hover:bg-gray-200" title="Gras">
              <strong>B</strong>
            </button>
            <button type="button" onclick="formatText('italic')" class="px-2 py-1 border rounded hover:bg-gray-200 ml-1" title="Italique">
              <em>I</em>
            </button>
            <button type="button" onclick="formatText('underline')" class="px-2 py-1 border rounded hover:bg-gray-200 ml-1" title="Souligné">
              <u>U</u>
            </button>
            <button type="button" onclick="formatText('insertUnorderedList')" class="px-2 py-1 border rounded hover:bg-gray-200 ml-1" title="Liste à puces">
              •
            </button>
            <button type="button" onclick="formatText('insertOrderedList')" class="px-2 py-1 border rounded hover:bg-gray-200 ml-1" title="Liste numérotée">
              1.
            </button>
          </div>
          <div
            id="richTextEditor"
            contenteditable="true"
            class="p-3 min-h-[200px] focus:outline-none focus:ring-2 focus:ring-blue-500 overflow-auto"
            style="max-height: 300px;"
            placeholder="Saisir les consignes..."
          ></div>
        </div>
        <textarea name="description" id="description" class="hidden"></textarea>
        @error('description') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
      </div>

      <!-- File attachment -->
      <div class="mb-4">
        <label for="file_attachment" class="block text-sm font-medium text-gray-700 mb-2">Pièce jointe</label>
        <input
          type="file"
          name="file_attachment"
          id="file_attachment"
          accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt"
          class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
        >
        <p class="text-xs text-gray-500 mt-1">Formats acceptés: PDF, DOC, DOCX, JPG, PNG, GIF, TXT (max 10MB)</p>
        <p id="file_attachment-error" class="text-red-600 text-sm mt-1 hidden"></p>
        @error('file_attachment') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
      </div>

      <div class="flex justify-end space-x-3 pt-4 border-t">
        <button
          type="button"
          id="cancelCreate"
          class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
        >
          Annuler
        </button>
        <button
          type="submit"
          id="submitCreate"
          class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
        >
          Enregistrer
        </button>
      </div>
    </form>
    
    <!-- Scroll indicator -->
    <div class="text-center text-xs text-gray-400 mt-4 pb-2">
      <svg class="w-4 h-4 mx-auto mb-1 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
      </svg>
      <span>Faites défiler pour voir plus de contenu</span>
    </div>
    </div>
  </div>
</div>

{{-- Edit Modals --}}
@foreach($passations as $passation)

  <div
    id="editModal-{{ $passation->id }}"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4"
    role="dialog" aria-modal="true" aria-labelledby="editModalTitle-{{ $passation->id }}"
  >
    <div class="bg-white rounded-lg shadow-lg w-[80%] h-[80%] relative flex flex-col max-h-screen overflow-hidden">
      <!-- Fixed Header -->
      <div class="flex-shrink-0 p-6 pb-4 border-b border-gray-200 bg-white">
        <button
          class="closeEditModal absolute top-3 right-3 text-gray-700 hover:text-gray-900 text-2xl font-bold"
          data-id="{{ $passation->id }}"
          title="Fermer"
          aria-label="Fermer la fenêtre"
          type="button"
        >&times;</button>

        <h2 id="editModalTitle-{{ $passation->id }}" class="text-xl font-semibold">Modifier la passation</h2>
      </div>

      <!-- Scrollable Content -->
      <div class="flex-1 overflow-y-auto p-6 pt-4">

      {{-- Time restriction warning --}}
      @if(auth()->user()->role !== 'admin' && now()->diffInMinutes($passation->created_at) > 30)
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
          <strong class="font-bold">Modification interdite!</strong>
          <span class="block sm:inline">Vous ne pouvez plus modifier cette passation (délai de 30 minutes dépassé).</span>
        </div>
      @endif

      {{-- Edit form with Consignes label --}}
      <form method="POST" action="{{ route('passations.update', $passation) }}" enctype="multipart/form-data" class="space-y-6">
        @if ($errors->has('modification'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
          <strong class="font-bold">Erreur!</strong>
          <span class="block sm:inline">{{ $errors->first('modification') }}</span>
        </div>
        @endif
        @csrf
        @method('PUT')

        @php
          $isTimeExpired = auth()->user()->role !== 'admin' && now()->diffInMinutes($passation->created_at) > 30;
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="nom_patient_{{ $passation->id }}" class="block text-sm font-medium text-gray-700">Nom</label>
            <input
              type="text"
              name="nom_patient"
              id="nom_patient_{{ $passation->id }}"
              value="{{ old('nom_patient', $passation->nom_patient) }}"
              class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 {{ $isTimeExpired ? 'bg-gray-100' : '' }}"
              {{ $isTimeExpired ? 'disabled' : 'required' }}
            >
          </div>

          <div>
            <label for="prenom_{{ $passation->id }}" class="block text-sm font-medium text-gray-700">Prénom</label>
            <input
              type="text"
              name="prenom"
              id="prenom_{{ $passation->id }}"
              value="{{ old('prenom', $passation->prenom) }}"
              class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 {{ $isTimeExpired ? 'bg-gray-100' : '' }}"
              {{ $isTimeExpired ? 'disabled' : '' }}
            >
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="cin_{{ $passation->id }}" class="block text-sm font-medium text-gray-700">CIN</label>
            <input
              type="text"
              name="cin"
              id="cin_{{ $passation->id }}"
              value="{{ old('cin', $passation->cin) }}"
              class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 {{ $isTimeExpired ? 'bg-gray-100' : '' }}"
              {{ $isTimeExpired ? 'disabled' : '' }}
            >
          </div>

          <div>
            <label for="ip_{{ $passation->id }}" class="block text-sm font-medium text-gray-700">IP (Identifiant Patient)</label>
            <input
              type="text"
              name="ip"
              id="ip_{{ $passation->id }}"
              value="{{ old('ip', $passation->ip) }}"
              class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 {{ $isTimeExpired ? 'bg-gray-100' : '' }}"
              {{ $isTimeExpired ? 'disabled' : '' }}
            >
          </div>
        </div>

        <!-- Rich text editor for description in edit form -->
        <div>
          <label for="description_{{ $passation->id }}" class="block text-sm font-medium text-gray-700 mb-2">Consignes</label>
          @if(!$isTimeExpired)
          <div class="border border-gray-300 rounded-md">
            <div id="toolbar_{{ $passation->id }}" class="border-b border-gray-300 p-2 bg-gray-50 rounded-t-md">
              <button type="button" onclick="formatTextEdit('bold', {{ $passation->id }})" class="px-2 py-1 border rounded hover:bg-gray-200" title="Gras">
                <strong>B</strong>
              </button>
              <button type="button" onclick="formatTextEdit('italic', {{ $passation->id }})" class="px-2 py-1 border rounded hover:bg-gray-200 ml-1" title="Italique">
                <em>I</em>
              </button>
              <button type="button" onclick="formatTextEdit('underline', {{ $passation->id }})" class="px-2 py-1 border rounded hover:bg-gray-200 ml-1" title="Souligné">
                <u>U</u>
              </button>
              <button type="button" onclick="formatTextEdit('insertUnorderedList', {{ $passation->id }})" class="px-2 py-1 border rounded hover:bg-gray-200 ml-1" title="Liste à puces">
                •
              </button>
              <button type="button" onclick="formatTextEdit('insertOrderedList', {{ $passation->id }})" class="px-2 py-1 border rounded hover:bg-gray-200 ml-1" title="Liste numérotée">
                1.
              </button>
            </div>
            <div
              id="richTextEditor_{{ $passation->id }}"
              contenteditable="true"
              class="p-3 min-h-[200px] focus:outline-none focus:ring-2 focus:ring-blue-500 overflow-auto"
              style="max-height: 300px;"
              placeholder="Saisir les consignes..."
            >{!! old('description', $passation->description) !!}</div>
          </div>
          @else
          <div class="border border-gray-300 rounded-md bg-gray-100">
            <div class="p-3 min-h-[200px] text-gray-500">
              {!! old('description', $passation->description) !!}
            </div>
          </div>
          @endif
          <textarea name="description" id="description_{{ $passation->id }}" class="hidden">{{ old('description', $passation->description) }}</textarea>
        </div>

        <!-- File attachment in edit modal -->
        <div>
          <label for="file_attachment_{{ $passation->id }}" class="block text-sm font-medium text-gray-700 mb-2">Pièce jointe</label>
          
          @if($passation->file_attachment)
            <div class="mb-2 p-2 bg-gray-50 border rounded-md">
              <p class="text-sm text-gray-600 mb-2">Fichier actuel:</p>
              <div class="flex items-center space-x-2">
                <span class="text-sm font-medium">{{ $passation->file_attachment }}</span>
                <a href="{{ route('passations.download', $passation) }}" 
                   class="text-blue-600 hover:text-blue-800 text-sm underline">
                  Télécharger
                </a>
                @if(!$isTimeExpired)
                <button type="button" 
                        onclick="deleteFile({{ $passation->id }})"
                        class="text-red-600 hover:text-red-800 text-sm underline">
                  Supprimer
                </button>
                @endif
              </div>
            </div>
          @endif
          
          <input
            type="file"
            name="file_attachment"
            id="file_attachment_{{ $passation->id }}"
            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt"
            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 {{ $isTimeExpired ? 'bg-gray-100' : '' }}"
            {{ $isTimeExpired ? 'disabled' : '' }}
          >
          <p class="text-xs text-gray-500 mt-1">Formats acceptés: PDF, DOC, DOCX, JPG, PNG, GIF, TXT (max 10MB)</p>
          @error('file_attachment') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>



        <div>
          <label for="salle_id_{{ $passation->id }}" class="block text-sm font-medium text-gray-700">Salle</label>
          <select
            name="salle_id"
            id="salle_id_{{ $passation->id }}"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 {{ $isTimeExpired ? 'bg-gray-100' : '' }}"
            {{ $isTimeExpired ? 'disabled' : 'required' }}
          >
            <option value="">-- Sélectionner une salle --</option>
            @foreach($salles as $salle)
              <option value="{{ $salle->id }}" {{ old('salle_id', $passation->salle_id) == $salle->id ? 'selected' : '' }}>
                {{ $salle->nom }} ({{ $salle->nombre_lits ?? 'N/A' }} lits)
              </option>
            @endforeach
          </select>
        </div>

        <div class="flex justify-end space-x-3">
          <button type="button" class="modal-close px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Annuler</button>
          <button 
            type="submit" 
            class="px-4 py-2 {{ $isTimeExpired ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700' }} text-white rounded"
            {{ $isTimeExpired ? 'disabled' : '' }}
          >
            Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
@endforeach

{{-- Show Modals --}}
@foreach($passations as $passation)
  <div
    id="showModal-{{ $passation->id }}"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4"
    role="dialog" aria-modal="true" aria-labelledby="showModalTitle-{{ $passation->id }}"
  >
    <div class="bg-white rounded-lg shadow-lg w-[80%] h-[80%] p-6 relative flex flex-col max-h-screen overflow-hidden">
      <button
        class="closeShowModal absolute top-3 right-3 text-gray-700 hover:text-gray-900 text-2xl font-bold"
        data-id="{{ $passation->id }}"
        title="Fermer"
        aria-label="Fermer la fenêtre"
        type="button"
      >&times;</button>

      <h2 id="showModalTitle-{{ $passation->id }}" class="text-xl font-semibold mb-4">Détails de la passation</h2>

      <div class="flex-1 overflow-auto pr-2 space-y-4 text-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <p><strong>IP (Identifiant Patient) :</strong> {{ $passation->ip ?? 'N/A' }}</p>
          <p><strong>Patient :</strong> {{ $passation->nom_patient }} {{ $passation->prenom }}</p>
          <p><strong>CIN :</strong> {{ $passation->cin ?? 'N/A' }}</p>
          <p><strong>Date de la passation :</strong> {{ \Carbon\Carbon::parse($passation->date_passation)->format('d/m/Y H:i') }}</p>
          <p><strong>Salle :</strong> {{ $passation->salle->nom ?? 'Non spécifiée' }}</p>
          <p><strong>Médecin :</strong> {{ $passation->user->name ?? 'Inconnu' }}</p>
        </div>

        {{-- Consignes section with gap and proper styling --}}
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg shadow p-4 leading-relaxed text-gray-700">
          <p class="font-semibold mb-3 text-lg">Consignes :</p>
          <div class="prose prose-sm max-w-none">
            {!! $passation->description ?? '<p class="text-gray-500 italic">Aucune consigne</p>' !!}
          </div>
        </div>

        {{-- File attachment section --}}
        @if($passation->file_attachment)
        <div class="mt-6 bg-green-50 border border-green-200 rounded-lg shadow p-4">
          <p class="font-semibold mb-3 text-lg text-green-800">Pièce jointe :</p>
          <div class="flex items-center space-x-3">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
            </svg>
            <span class="text-green-700 font-medium">{{ $passation->file_attachment }}</span>
            <a href="{{ route('passations.download', $passation) }}" 
               class="inline-flex items-center px-3 py-1 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition-colors">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              Télécharger
            </a>
          </div>
        </div>
        @endif
      </div>
      </div>
    </div>
  </div>
@endforeach

<script>
  // Open Edit modal when clicking "Modifier"
  document.querySelectorAll('.openEditModalBtn').forEach(button => {
    button.addEventListener('click', () => {
      const id = button.dataset.id;
      document.getElementById('editModal-' + id).classList.remove('hidden');
    });
  });

  // Close Edit modals
  document.querySelectorAll('.closeEditModal').forEach(button => {
    button.addEventListener('click', () => {
      const id = button.dataset.id;
      document.getElementById('editModal-' + id).classList.add('hidden');
    });
  });

  // Close Edit modals when clicking outside modal content
  document.querySelectorAll('[id^=editModal-]').forEach(modal => {
    modal.addEventListener('click', (e) => {
      if(e.target === modal) {
        modal.classList.add('hidden');
      }
    });
  });

  // Open Show modal when clicking "Voir"
  document.querySelectorAll('.openShowModalBtn').forEach(button => {
    button.addEventListener('click', () => {
      const id = button.dataset.id;
      document.getElementById('showModal-' + id).classList.remove('hidden');
    });
  });

  // Close Show modals
  document.querySelectorAll('.closeShowModal').forEach(button => {
    button.addEventListener('click', () => {
      const id = button.dataset.id;
      document.getElementById('showModal-' + id).classList.add('hidden');
    });
  });

  // Close Show modals when clicking outside modal content
  document.querySelectorAll('[id^=showModal-]').forEach(modal => {
    modal.addEventListener('click', (e) => {
      if(e.target === modal) {
        modal.classList.add('hidden');
      }
    });
  });

  // Open Create modal when clicking the green "Ajouter" button
  document.getElementById('openCreateModal')?.addEventListener('click', () => {
    document.getElementById('createModal').classList.remove('hidden');
  });

  // Close Create modal when clicking close button
  document.getElementById('closeCreateModal')?.addEventListener('click', () => {
    document.getElementById('createModal').classList.add('hidden');
    resetCreateForm();
  });

  // Close Create modal when clicking outside modal content
  document.getElementById('createModal')?.addEventListener('click', (e) => {
    if(e.target.id === 'createModal') {
      document.getElementById('createModal').classList.add('hidden');
      resetCreateForm();
    }
  });

  // Cancel button
  document.getElementById('cancelCreate')?.addEventListener('click', () => {
    document.getElementById('createModal').classList.add('hidden');
    resetCreateForm();
  });

  // Close modal on cancel button click
  document.querySelectorAll('.modal-close').forEach(btn => {
    btn.addEventListener('click', () => {
      btn.closest('.fixed').classList.add('hidden');
    });
  });

  // Rich text editor functions
  function formatText(command) {
    document.execCommand(command, false, null);
    document.getElementById('richTextEditor').focus();
  }

  // Rich text editor functions for edit forms
  function formatTextEdit(command, passationId) {
    document.execCommand(command, false, null);
    document.getElementById('richTextEditor_' + passationId).focus();
  }

  // Sync rich text editor content with hidden textarea
  document.getElementById('richTextEditor').addEventListener('input', function() {
    document.getElementById('description').value = this.innerHTML;
  });

  // Sync rich text editor content for edit forms
  @foreach($passations as $passation)
    @if(now()->diffInMinutes($passation->created_at) <= 30 || Auth::user()->role === 'admin')
      document.getElementById('richTextEditor_{{ $passation->id }}')?.addEventListener('input', function() {
        document.getElementById('description_{{ $passation->id }}').value = this.innerHTML;
      });
    @endif
  @endforeach

  let userConfirmedConflict = false;

  // Form submission handler
  document.getElementById("createPassationForm").addEventListener("submit", async function(e) {
    e.preventDefault();

    // Clear previous errors
    document.querySelectorAll(".text-red-600").forEach(el => el.textContent = "");
    document.getElementById("ipExistsMsg").classList.add("hidden");
    document.getElementById("ipConflictMsg").classList.add("hidden");
    document.getElementById("salleWarning").classList.add("hidden");

    // Sync rich text content before submission
    const richTextContent = document.getElementById("richTextEditor").innerHTML;
    document.getElementById("description").value = richTextContent;

    const formData = new FormData(this);
    
    // Add confirmation flag if user has confirmed a conflict
    if (userConfirmedConflict) {
      formData.append('confirm_conflict', '1');
    }

    try {
      const response = await fetch(this.action, {
        method: "POST",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content"),
        },
        body: formData,
      });

      const data = await response.json();

      if (!response.ok) {
        // Handle validation errors
        if (data.errors) {
          for (const field in data.errors) {
            const errorElement = document.getElementById(`${field}-error`);
            if (errorElement) {
              errorElement.textContent = data.errors[field][0];
            }
          }
          // Specific handling for IP conflict message from backend
          if (data.errors.ip && data.errors.ip[0] === "Cette IP existe déjà pour un patient dans cette salle.") {
            document.getElementById("ipExistsMsg").textContent = data.errors.ip[0];
            document.getElementById("ipExistsMsg").classList.remove("hidden");
            document.getElementById("submitCreate").disabled = true;
          }
        }
        throw new Error(data.message || "Erreur lors de la création de la passation.");
      } else {
        // Handle success
        alert(data.success);
        document.getElementById("createModal").classList.add("hidden");
        resetCreateForm();
        location.reload(); // Reload the page to show the new passation
      }
    } catch (error) {
      console.error("Error:", error);
      // alert("Une erreur est survenue: " + error.message);
    }
  });

  // Reset form function
  function resetCreateForm() {
    document.getElementById('createPassationForm').reset();
    document.getElementById('richTextEditor').innerHTML = '';
    document.getElementById('description').value = '';
    document.getElementById('file_attachment').value = '';
    document.getElementById('patientSearchResults').classList.add('hidden');
    document.getElementById('ipExistsMsg').classList.add('hidden');
    document.getElementById('ipConflictMsg').classList.add('hidden');
    document.getElementById('salleWarning').classList.add('hidden');
    document.getElementById('submitCreate').disabled = false;
    userConfirmedConflict = false;
    
    // Remove confirmation buttons if they exist
    const confirmConflict = document.getElementById('confirmConflict');
    const confirmConflictSameSalle = document.getElementById('confirmConflictSameSalle');
    if (confirmConflict) confirmConflict.remove();
    if (confirmConflictSameSalle) confirmConflictSameSalle.remove();
  }

  // Patient search functionality
  const salles = {
    @foreach($salles as $salle)
      "{{ $salle->id }}": {!! json_encode($salle->nom) !!}@if(!$loop->last),@endif
    @endforeach
  };

  let allPatientsData = {};
  let searchTimeout;

  // Search all passations via AJAX
  async function searchAllPassations(query = '') {
    try {
      const response = await fetch(`{{ route('passations.searchAll') }}?search=${encodeURIComponent(query)}`, {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
      });

      if (!response.ok) {
        throw new Error('Network response was not ok');
      }

      const data = await response.json();
      
      if (data.success) {
        // Update allPatientsData with search results
        allPatientsData = {};
        data.passations.forEach(passation => {
          allPatientsData[passation.ip] = {
            nom_patient: passation.nom_patient,
            prenom: passation.prenom,
            cin: passation.cin,
            ip: passation.ip,
            salle_id: passation.salle_id,
            salle_nom: passation.salle ? passation.salle.nom : 'N/A'
          };
        });
        
        updateSearchResults(query);
      }
    } catch (error) {
      console.error('Error searching passations:', error);
      // Show error message to user
      const resultsDiv = document.getElementById('patientSearchResults');
      resultsDiv.innerHTML = '<p class="p-2 text-red-500 text-sm">Erreur lors de la recherche. Veuillez réessayer.</p>';
      resultsDiv.classList.remove('hidden');
    }
  }

  // Update search results display
  function updateSearchResults(query) {
    const resultsDiv = document.getElementById('patientSearchResults');
    
    if (!query.trim()) {
      resultsDiv.classList.add('hidden');
      return;
    }

    const filtered = Object.entries(allPatientsData).filter(([ip, patient]) => {
      const searchLower = query.toLowerCase();
      return ip.toLowerCase().includes(searchLower) ||
             patient.nom_patient.toLowerCase().includes(searchLower) ||
             (patient.prenom && patient.prenom.toLowerCase().includes(searchLower)) ||
             (patient.cin && patient.cin.toLowerCase().includes(searchLower));
    });

    if (filtered.length === 0) {
      resultsDiv.innerHTML = '<p class="p-2 text-gray-500 text-sm">Aucun patient trouvé</p>';
      resultsDiv.classList.remove('hidden');
      return;
    }

    // Sort results by relevance (exact matches first)
    filtered.sort(([ipA, patientA], [ipB, patientB]) => {
      const queryLower = query.toLowerCase();
      const aExact = ipA.toLowerCase() === queryLower || 
                     patientA.nom_patient.toLowerCase() === queryLower ||
                     (patientA.prenom && patientA.prenom.toLowerCase() === queryLower);
      const bExact = ipB.toLowerCase() === queryLower || 
                     patientB.nom_patient.toLowerCase() === queryLower ||
                     (patientB.prenom && patientB.prenom.toLowerCase() === queryLower);
      
      if (aExact && !bExact) return -1;
      if (!aExact && bExact) return 1;
      return 0;
    });

    resultsDiv.innerHTML = filtered.map(([ip, patient]) => `
      <div class="cursor-pointer p-3 hover:bg-blue-50 border-b last:border-b-0 transition-colors duration-150" 
           data-ip="${ip}" 
           role="button" 
           tabindex="0"
           onmouseenter="this.style.backgroundColor='#EBF8FF'"
           onmouseleave="this.style.backgroundColor=''">
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <div class="font-semibold text-gray-900">${patient.nom_patient} ${patient.prenom || ''}</div>
            <div class="text-sm text-gray-600">IP: ${patient.ip} • CIN: ${patient.cin || 'N/A'}</div>
            <div class="text-xs text-gray-500">Salle: ${patient.salle_nom || 'N/A'}</div>
          </div>
          <div class="text-blue-600 text-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </div>
        </div>
      </div>
    `).join('');
    resultsDiv.classList.remove('hidden');
  }

  // Patient search input handler
  document.getElementById('search_patient').addEventListener('input', function(e) {
    const query = e.target.value.trim();
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      if (query.length >= 1) {
        searchAllPassations(query);
      } else if (query.length === 0) {
        // Show all patients when search is empty
        searchAllPassations('');
      } else {
        document.getElementById('patientSearchResults').classList.add('hidden');
      }
    }, 200); // Reduced delay for better responsiveness
  });

  // Load initial patient data when modal opens
  document.getElementById('openCreateModal')?.addEventListener('click', async function() {
    // Load all patients initially for better search performance
    await searchAllPassations('');
  });

  // Select patient from search results
  document.getElementById('patientSearchResults').addEventListener('click', function(e) {
    const target = e.target.closest('[data-ip]');
    if (!target) return;

    selectPatient(target.getAttribute('data-ip'));
  });

  // Function to select a patient
  function selectPatient(ip) {
    const patient = allPatientsData[ip];
    if (!patient) return;

    // Fill form with patient data
    document.getElementById('nom_patient').value = patient.nom_patient;
    document.getElementById('prenom').value = patient.prenom || '';
    document.getElementById('cin').value = patient.cin || '';
    document.getElementById('ip').value = patient.ip;
    document.getElementById('salle_id').value = patient.salle_id;
    
    // Clear search
    document.getElementById('search_patient').value = '';
    document.getElementById('patientSearchResults').classList.add('hidden');
    
    // Show success message
    showPatientSelectedMessage(patient);
    
    // Check for conflicts
    checkForConflicts();
  }

  // Show patient selected message
  function showPatientSelectedMessage(patient) {
    const searchInput = document.getElementById('search_patient');
    const originalPlaceholder = searchInput.placeholder;
    
    searchInput.placeholder = `Patient sélectionné: ${patient.nom_patient} ${patient.prenom || ''}`;
    searchInput.classList.add('bg-green-50', 'border-green-300');
    
    setTimeout(() => {
      searchInput.placeholder = originalPlaceholder;
      searchInput.classList.remove('bg-green-50', 'border-green-300');
    }, 3000);
  }

  // IP input validation
  document.getElementById('ip').addEventListener('input', function() {
    checkForConflicts();
  });

  // Salle selection validation
  document.getElementById('salle_id').addEventListener('change', function() {
    checkForConflicts();
  });

  // Check for IP conflicts and existing patients
  function checkForConflicts() {
    const ipValue = document.getElementById('ip').value.trim();
    const selectedSalleId = document.getElementById('salle_id').value;
    const ipExistsMsg = document.getElementById('ipExistsMsg');
    const ipConflictMsg = document.getElementById('ipConflictMsg');
    const salleWarning = document.getElementById('salleWarning');
    const submitBtn = document.getElementById('submitCreate');

    // Reset messages and confirmation state
    ipExistsMsg.classList.add('hidden');
    ipConflictMsg.classList.add('hidden');
    salleWarning.classList.add('hidden');
    submitBtn.disabled = false;
    userConfirmedConflict = false;
    
    // Remove existing confirmation buttons
    const confirmConflict = document.getElementById('confirmConflict');
    const confirmConflictSameSalle = document.getElementById('confirmConflictSameSalle');
    if (confirmConflict) confirmConflict.remove();
    if (confirmConflictSameSalle) confirmConflictSameSalle.remove();

    if (!ipValue || !selectedSalleId) return;

    const existingPatient = allPatientsData[ipValue];
    
    if (existingPatient) {
      if (existingPatient.salle_id != selectedSalleId) {
        // Patient exists in different salle
        salleWarning.textContent = `Ce patient existe déjà dans la salle: ${existingPatient.salle_nom}`;
        salleWarning.classList.remove('hidden');
        
        ipConflictMsg.textContent = 'Veuillez confirmer si vous souhaitez créer une nouvelle passation pour ce patient dans une salle différente.';
        ipConflictMsg.classList.remove('hidden');
        submitBtn.disabled = true;
        
        // Add confirmation button
        if (!document.getElementById('confirmConflict')) {
          const confirmBtn = document.createElement('button');
          confirmBtn.id = 'confirmConflict';
          confirmBtn.type = 'button';
          confirmBtn.className = 'mt-2 px-3 py-1 bg-orange-500 text-white rounded text-sm hover:bg-orange-600';
          confirmBtn.textContent = 'Confirmer la création';
          confirmBtn.onclick = function() {
            submitBtn.disabled = false;
            ipConflictMsg.classList.add('hidden');
            userConfirmedConflict = true;
            this.remove();
          };
          ipConflictMsg.appendChild(confirmBtn);
        }
      } else {
        // Patient exists in same salle
        salleWarning.textContent = `Ce patient existe déjà dans cette salle: ${existingPatient.salle_nom}`;
        salleWarning.classList.remove('hidden');
        
        ipConflictMsg.textContent = 'Veuillez confirmer si vous souhaitez créer une nouvelle passation pour ce patient dans la même salle.';
        ipConflictMsg.classList.remove('hidden');
        submitBtn.disabled = true;
        
        // Add confirmation button
        if (!document.getElementById('confirmConflictSameSalle')) {
          const confirmBtn = document.createElement('button');
          confirmBtn.id = 'confirmConflictSameSalle';
          confirmBtn.type = 'button';
          confirmBtn.className = 'mt-2 px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600';
          confirmBtn.textContent = 'Confirmer la création dans la même salle';
          confirmBtn.onclick = function() {
            submitBtn.disabled = false;
            ipConflictMsg.classList.add('hidden');
            salleWarning.classList.add('hidden');
            userConfirmedConflict = true;
            this.remove();
          };
          ipConflictMsg.appendChild(confirmBtn);
        }
      }
    }
  }

  // Hide search results when clicking outside
  document.addEventListener('click', function(e) {
    if (!e.target.closest('#search_patient') && !e.target.closest('#patientSearchResults')) {
      document.getElementById('patientSearchResults').classList.add('hidden');
    }
  });

  // Keyboard navigation for search results
  document.getElementById('search_patient').addEventListener('keydown', function(e) {
    const resultsDiv = document.getElementById('patientSearchResults');
    const visibleResults = resultsDiv.querySelectorAll('[data-ip]:not(.hidden)');
    
    if (e.key === 'ArrowDown' && visibleResults.length > 0) {
      e.preventDefault();
      const firstResult = visibleResults[0];
      firstResult.focus();
      firstResult.classList.add('bg-blue-100');
    }
  });

  // Keyboard navigation within search results
  document.getElementById('patientSearchResults').addEventListener('keydown', function(e) {
    const currentResult = e.target.closest('[data-ip]');
    if (!currentResult) return;

    const allResults = Array.from(this.querySelectorAll('[data-ip]'));
    const currentIndex = allResults.indexOf(currentResult);

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      const nextResult = allResults[currentIndex + 1];
      if (nextResult) {
        currentResult.classList.remove('bg-blue-100');
        nextResult.focus();
        nextResult.classList.add('bg-blue-100');
      }
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      const prevResult = allResults[currentIndex - 1];
      if (prevResult) {
        currentResult.classList.remove('bg-blue-100');
        prevResult.focus();
        prevResult.classList.add('bg-blue-100');
      } else {
        // Go back to search input
        document.getElementById('search_patient').focus();
        currentResult.classList.remove('bg-blue-100');
      }
    } else if (e.key === 'Enter') {
      e.preventDefault();
      selectPatient(currentResult.getAttribute('data-ip'));
    } else if (e.key === 'Escape') {
      resultsDiv.classList.add('hidden');
      document.getElementById('search_patient').focus();
    }
  });

  // Remove highlight when result loses focus
  document.getElementById('patientSearchResults').addEventListener('focusout', function(e) {
    const result = e.target.closest('[data-ip]');
    if (result) {
      setTimeout(() => {
        if (!result.contains(document.activeElement)) {
          result.classList.remove('bg-blue-100');
        }
      }, 100);
    }
  });
</script>
<script>
  const passationEditLogs = {
    @foreach($passations as $passation)
      "{{ $passation->id }}": [
        @foreach($passation->editLogs->sortByDesc('created_at') as $log)
          {
            field: {!! json_encode(ucfirst($log->field)) !!},
            user: {!! json_encode($log->user->name) !!},
            old_value: {!! json_encode($log->old_value ?? 'N/A') !!},
            new_value: {!! json_encode($log->new_value ?? 'N/A') !!},
            date: {!! json_encode($log->created_at->format('d/m/Y H:i')) !!}
          }@if(!$loop->last),@endif
        @endforeach
      ]@if(!$loop->last),@endif
    @endforeach
  };

  const popup = document.getElementById('editLogsPopup');
  const popupList = document.getElementById('editLogsPopupList');

  function showEditLogs(event, id) {
    event.stopPropagation();

    const logs = passationEditLogs[id];
    if (!logs || logs.length === 0) {
      popup.classList.add('hidden');
      return;
    }

    popupList.innerHTML = logs.map(log => `
      <li>
        <strong>${log.field}</strong> modifié par <em>${log.user}</em><br>
        de "<code class='font-mono break-all'>${log.old_value}</code>" à "<code class='font-mono break-all'>${log.new_value}</code>"<br>
        <small class="text-gray-500">${log.date}</small>
      </li>
    `).join('');

    const btnRect = (event.currentTarget || event.target).getBoundingClientRect();
    const popupWidth = popup.offsetWidth || 320;
    const popupHeight = popup.offsetHeight || 240;

    let left = btnRect.right + 8 + window.scrollX;
    if (left + popupWidth > window.scrollX + window.innerWidth) {
      left = btnRect.left - popupWidth - 8 + window.scrollX;
    }

    let top = btnRect.top + window.scrollY;
    if (top + popupHeight > window.scrollY + window.innerHeight) {
      top = window.scrollY + window.innerHeight - popupHeight - 8;
    }

    popup.style.left = `${left}px`;
    popup.style.top = `${top}px`;
    popup.style.position = 'absolute';

    popup.classList.remove('hidden');
    popup.focus();
  }

  // Close popup on outside click
  document.addEventListener('click', () => {
    popup.classList.add('hidden');
  });

  // Prevent closing popup if clicking inside popup
  popup.addEventListener('click', e => {
    e.stopPropagation();
  });

  // File deletion function
  async function deleteFile(passationId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce fichier ?')) {
      return;
    }

    try {
      const response = await fetch(`/passations/${passationId}/file`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      const data = await response.json();

      if (response.ok) {
        alert(data.success);
        location.reload(); // Reload to refresh the UI
      } else {
        alert(data.error || 'Erreur lors de la suppression du fichier.');
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Une erreur est survenue lors de la suppression du fichier.');
    }
  }

  // Scroll indicator functionality for create modal
  document.addEventListener('DOMContentLoaded', function() {
    const createModal = document.getElementById('createModal');
    const scrollableContent = createModal?.querySelector('.overflow-y-auto');
    const scrollIndicator = createModal?.querySelector('.text-center.text-xs.text-gray-400');
    
    if (scrollableContent && scrollIndicator) {
      scrollableContent.addEventListener('scroll', function() {
        const isAtBottom = this.scrollTop + this.clientHeight >= this.scrollHeight - 10;
        if (isAtBottom) {
          scrollIndicator.style.opacity = '0.3';
        } else {
          scrollIndicator.style.opacity = '1';
        }
      });
    }
  });
</script>
@endsection