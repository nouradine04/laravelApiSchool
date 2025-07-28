<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Créer un administrateur
        $admin = User::create([
            'name' => 'Administrateur Principal',
            'email' => 'admin@school.com',
            'password' => Hash::make('password123'),
            'phone' => '0123456789',
            'address' => 'Adresse de l\'école',
        ]);
        $admin->assignRole('admin');
    }
}
