<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $students = Student::all();
        $subjects = Subject::all();
        $teachers = Teacher::all();
        $periods = ['Trimestre 1', 'Trimestre 2', 'Trimestre 3'];
        $gradeTypes = ['Devoir', 'Composition', 'Interrogation', 'Contrôle'];

        foreach ($students as $student) {
            foreach ($periods as $period) {
                foreach ($subjects->take(6) as $subject) { // 6 matières par étudiant
                    $teacher = $teachers->where('subjects', 'contains', $subject->id)->first()
                        ?? $teachers->random();

                    // Créer 2-4 notes par matière et par période
                    $gradeCount = rand(2, 4);
                    for ($i = 0; $i < $gradeCount; $i++) {
                        Grade::create([
                            'student_id' => $student->id,
                            'subject_id' => $subject->id,
                            'teacher_id' => $teacher->id,
                            'class_id' => $student->class_id,
                            'period' => $period,
                            'grade_type' => $gradeTypes[array_rand($gradeTypes)],
                            'value' => rand(8, 20), // Notes entre 8 et 20
                            'max_value' => 20,
                            'date' => now()->subDays(rand(1, 90)),
                            'comment' => $this->getRandomComment(),
                        ]);
                    }
                }
            }
        }
    }

    private function getRandomComment()
    {
        $comments = [
            'Bon travail',
            'Peut mieux faire',
            'Excellent',
            'Satisfaisant',
            'Efforts à poursuivre',
            'Très bien',
            'Insuffisant',
            'Progrès notable',
            'Travail sérieux',
            'À améliorer',
        ];

        return $comments[array_rand($comments)];
    }
}
