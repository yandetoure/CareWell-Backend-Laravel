<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'name',
        'description',
        'price',
    ];


    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function results(){
        return $this->hasMany(Result::class); // Association avec les résultats
    }
}
