<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['name' => 'Mathématiques', 'code' => 'MATH', 'coefficient' => 4.0, 'level' => 'Collège'],
            ['name' => 'Français', 'code' => 'FR', 'coefficient' => 4.0, 'level' => 'Collège'],
            ['name' => 'Anglais', 'code' => 'ANG', 'coefficient' => 3.0, 'level' => 'Collège'],
            ['name' => 'Histoire-Géographie', 'code' => 'HG', 'coefficient' => 3.0, 'level' => 'Collège'],
            ['name' => 'Sciences Physiques', 'code' => 'PC', 'coefficient' => 2.0, 'level' => 'Collège'],
            ['name' => 'Sciences de la Vie et de la Terre', 'code' => 'SVT', 'coefficient' => 2.0, 'level' => 'Collège'],
            ['name' => 'Éducation Physique et Sportive', 'code' => 'EPS', 'coefficient' => 1.0, 'level' => 'Collège'],
            ['name' => 'Arts Plastiques', 'code' => 'ART', 'coefficient' => 1.0, 'level' => 'Collège'],
            ['name' => 'Musique', 'code' => 'MUS', 'coefficient' => 1.0, 'level' => 'Collège'],
            ['name' => 'Technologie', 'code' => 'TECH', 'coefficient' => 2.0, 'level' => 'Collège'],
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }
    }
}
