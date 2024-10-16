<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Création d'utilisateurs fictifs
        $user1 = User::create([
            'first_name' => 'Ndeye Yande',
            'last_name' => 'Toure',
            'email' => 'ndeye@gmail.com',
            'password' => Hash::make('password'), 
            'adress' => 'Almadies',
            'phone_number' => '+2214567890',
            'day_of_birth' => '1990-01-01',
            'status' => true,
        ]);
        $user1->assignRole('Admin'); 

        $user2 = User::create([
            'first_name' => 'Celine',
            'last_name' => 'Mendy',
            'email' => 'linece@gmail.com.com',
            'password' => Hash::make('password'), 
            'adress' => 'Cité Keur Gorgui',
            'phone_number' => '+0987654321',
            'day_of_birth' => '1995-02-15',
            'status' => false,
        ]);
        $user2->assignRole('Secretary'); 

        $user3 = User::create([
            'first_name' => 'Moussa',
            'last_name' => 'Sagna',
            'email' => 'sagna@gmail.com.com',
            'password' => Hash::make('password'), 
            'adress' => 'Yeumbeul Nord',
            'phone_number' => '+1234567890',
            'day_of_birth' => '1989-01-01',
            'status' => true,
        ]);
        $user3->assignRole('Doctor'); 


    $user4 = User::create([
        'first_name' => 'Mareme',
        'last_name' => 'Thiaw',
        'email' => 'thiaw@gmail.com.com',
        'password' => Hash::make('password'), 
        'adress' => 'Parcelles Unité 16',
        'phone_number' => '+1234567890',
        'day_of_birth' => '1994-05-01',
        'status' => true,
    ]);
    $user4->assignRole('Accountant'); 
}

}