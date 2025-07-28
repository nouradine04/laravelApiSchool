<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\ParentModel;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $students = [
            [
                'student_name' => 'Alice Dupont',
                'student_email' => 'alice.dupont@student.com',
                'parent_name' => 'Robert Dupont',
                'parent_email' => 'robert.dupont@parent.com',
                'class_id' => 1,
                'birth_date' => '2010-05-15',
                'birth_place' => 'Paris',
                'gender' => 'F',
            ],
            [
                'student_name' => 'Thomas Leroy',
                'student_email' => 'thomas.leroy@student.com',
                'parent_name' => 'Catherine Leroy',
                'parent_email' => 'catherine.leroy@parent.com',
                'class_id' => 1,
                'birth_date' => '2010-08-22',
                'birth_place' => 'Lyon',
                'gender' => 'M',
            ],
            [
                'student_name' => 'Emma Rousseau',
                'student_email' => 'emma.rousseau@student.com',
                'parent_name' => 'Michel Rousseau',
                'parent_email' => 'michel.rousseau@parent.com',
                'class_id' => 2,
                'birth_date' => '2010-12-03',
                'birth_place' => 'Marseille',
                'gender' => 'F',
            ],
            [
                'student_name' => 'Lucas Garnier',
                'student_email' => 'lucas.garnier@student.com',
                'parent_name' => 'Sylvie Garnier',
                'parent_email' => 'sylvie.garnier@parent.com',
                'class_id' => 2,
                'birth_date' => '2010-03-18',
                'birth_place' => 'Toulouse',
                'gender' => 'M',
            ],
        ];

        foreach ($students as $studentData) {
            // Créer l'utilisateur étudiant
            $studentUser = User::create([
                'name' => $studentData['student_name'],
                'email' => $studentData['student_email'],
                'password' => Hash::make('password123'),
            ]);
            $studentUser->assignRole('student');

            // Créer l'utilisateur parent
            $parentUser = User::create([
                'name' => $studentData['parent_name'],
                'email' => $studentData['parent_email'],
                'password' => Hash::make('password123'),
                'phone' => '01' . rand(10000000, 99999999),
            ]);
            $parentUser->assignRole('parent');

            // Créer le parent
            $parent = ParentModel::create([
                'user_id' => $parentUser->id,
                'profession' => 'Profession du parent',
                'workplace' => 'Lieu de travail',
            ]);

            // Créer l'étudiant
            Student::create([
                'user_id' => $studentUser->id,
                'parent_id' => $parent->id,
                'class_id' => $studentData['class_id'],
                'student_number' => '2024' . str_pad($studentUser->id, 4, '0', STR_PAD_LEFT),
                'birth_date' => $studentData['birth_date'],
                'birth_place' => $studentData['birth_place'],
                'gender' => $studentData['gender'],
                'documents' => ['Acte de naissance', 'Certificat de scolarité'],
                'enrollment_date' => now()->subMonths(rand(1, 6)),
            ]);
        }
    }
}
