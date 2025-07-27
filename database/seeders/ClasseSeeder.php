<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Classe;

class ClasseSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            ['name' => '6ème A', 'level' => '6ème', 'academic_year' => '2024-2025'],
            ['name' => '6ème B', 'level' => '6ème', 'academic_year' => '2024-2025'],
            ['name' => '5ème A', 'level' => '5ème', 'academic_year' => '2024-2025'],
            ['name' => '5ème B', 'level' => '5ème', 'academic_year' => '2024-2025'],
            ['name' => '4ème A', 'level' => '4ème', 'academic_year' => '2024-2025'],
            ['name' => '4ème B', 'level' => '4ème', 'academic_year' => '2024-2025'],
            ['name' => '3ème A', 'level' => '3ème', 'academic_year' => '2024-2025'],
            ['name' => '3ème B', 'level' => '3ème', 'academic_year' => '2024-2025'],
        ];

        foreach ($classes as $classe) {
            Classe::create($classe);
        }
    }
}
