<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedicalHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'medical_files_id',
        'allergy',
        'doctor_id',
    ];
    
    // Déclaration de la relation avec MedicalFile
    public function medicalFile(){
        return $this->belongsTo(MedicalFile::class);
    }
    
}
