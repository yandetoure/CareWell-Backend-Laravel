<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedicalFilePrescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_files_id',
        'prescription_id',
        'is_done',
        'doctor_id',
    ];

        // Déclaration de la relation avec MedicalFile
        public function medicalFile()
        {
            return $this->belongsTo(MedicalFile::class);
        }

        public function prescription() 
        {
            return $this->belongsTo(Prescription::class); 
        }

}
