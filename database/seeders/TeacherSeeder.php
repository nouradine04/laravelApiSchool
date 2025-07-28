<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = [
            [
                'name' => 'Marie Dubois',
                'email' => 'marie.dubois@school.com',
                'specialization' => 'Mathématiques',
                'subject_ids' => [1], // Mathématiques
            ],
            [
                'name' => 'Pierre Martin',
                'email' => 'pierre.martin@school.com',
                'specialization' => 'Français',
                'subject_ids' => [2], // Français
            ],
            [
                'name' => 'Sophie Bernard',
                'email' => 'sophie.bernard@school.com',
                'specialization' => 'Anglais',
                'subject_ids' => [3], // Anglais
            ],
            [
                'name' => 'Jean Moreau',
                'email' => 'jean.moreau@school.com',
                'specialization' => 'Histoire-Géographie',
                'subject_ids' => [4], // Histoire-Géographie
            ],
            [
                'name' => 'Claire Petit',
                'email' => 'claire.petit@school.com',
                'specialization' => 'Sciences',
                'subject_ids' => [5, 6], // PC et SVT
            ],
        ];

        foreach ($teachers as $teacherData) {
            // Créer l'utilisateur
            $user = User::create([
                'name' => $teacherData['name'],
                'email' => $teacherData['email'],
                'password' => Hash::make('password123'),
                'phone' => '01' . rand(10000000, 99999999),
                'is_active' => true,
            ]);
            $user->assignRole('teacher');

            // Créer l'enseignant
            $teacher = Teacher::create([
                'user_id' => $user->id,
                'teacher_number' => 'T2024' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                'specialization' => $teacherData['specialization'],
                'hire_date' => now()->subMonths(rand(1, 24)),
                'is_active' => true,
            ]);

            // Associer les matières
            $teacher->subjects()->attach($teacherData['subject_ids']);

            // Associer quelques classes
            $teacher->classes()->attach([1, 2, 3, 4]);
        }
    }
}
