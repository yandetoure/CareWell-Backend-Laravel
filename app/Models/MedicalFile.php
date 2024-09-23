<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MedicalFile extends Model
{


    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'identification_number',
        'user-id'
    ];

    /**
     * Boot function to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically generate identification_number when creating a new user
        static::creating(function ($user) {
            $user->identification_number = self::generateUniqueIdentificationNumber();
        });
    }

    /**
     * Generate a unique identification number.
     *
     * @return string
     */
    private static function generateUniqueIdentificationNumber()
    {
        $identification_number = Str::random(10); // Génère une chaîne aléatoire de 10 caractères

        // Vérifie que l'identification_number est unique
        while (self::where('identification_number', $identification_number)->exists()) {
            $identification_number = Str::random(10);
        }

        return $identification_number;
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}