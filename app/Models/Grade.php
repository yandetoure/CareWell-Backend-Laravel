<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Grade extends Model
{
    use HasFactory;
    
    
    protected $fillable = [
        'user_id',
        'name',
    ];
    
    public function user(){
        return $this->hasMany(User::class);
    }
}
