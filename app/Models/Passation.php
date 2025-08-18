<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Salle;

class Passation extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_patient',
        'prenom',
        'cin',
        'ip',
        'description',
        'file_attachment',
        'date_passation',
        'user_id',
        'salle_id',
    ];

    protected $dates = ['date_passation'];

    /**
     * Boot the model to enforce mandatory system timestamps
     */
    protected static function boot()
    {
        parent::boot();

        // Enforce current system date/time for creation
        static::creating(function ($model) {
            $model->created_at = now();
            $model->updated_at = now();
            
            // Set date_passation to current system date if not provided
            if (empty($model->date_passation)) {
                $model->date_passation = now();
            }
        });

        // Enforce current system date/time for updates
        static::updating(function ($model) {
            $model->updated_at = now();
        });
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function salle()
    {
        return $this->belongsTo(Salle::class);
    }

    public function editLogs()
    {
        return $this->hasMany(PassationEditLog::class);
    }

}
