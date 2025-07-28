<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\Classe;
use App\Models\Grade;
use App\Models\ReportCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStats()
    {
        $stats = [
            'students_count' => Student::where('is_active', true)->count(),
            'teachers_count' => Teacher::where('is_active', true)->count(),
            'classes_count' => Classe::where('is_active', true)->count(),
            'grades_count' => Grade::count(),
            'report_cards_count' => ReportCard::count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function getClassStats()
    {
        $classStats = Classe::with(['students'])
            ->where('is_active', true)
            ->get()
            ->map(function ($classe) {
                // Calculer la moyenne générale de la classe
                $studentIds = $classe->students->pluck('id');

                $classGrades = Grade::whereIn('student_id', $studentIds)
                    ->with('subject')
                    ->get();

                $classAverage = 0;
                if ($classGrades->isNotEmpty()) {
                    $totalPoints = 0;
                    $totalCoefficient = 0;

                    foreach ($classGrades->groupBy('student_id') as $studentGrades) {
                        foreach ($studentGrades->groupBy('subject_id') as $subjectGrades) {
                            $subject = $subjectGrades->first()->subject;
                            $subjectTotal = 0;
                            $subjectCount = 0;

                            foreach ($subjectGrades as $grade) {
                                $percentage = ($grade->value / $grade->max_value) * 20;
                                $subjectTotal += $percentage;
                                $subjectCount++;
                            }

                            if ($subjectCount > 0) {
                                $subjectAverage = $subjectTotal / $subjectCount;
                                $totalPoints += $subjectAverage * $subject->coefficient;
                                $totalCoefficient += $subject->coefficient;
                            }
                        }
                    }

                    $classAverage = $totalCoefficient > 0 ? round($totalPoints / $totalCoefficient, 2) : 0;
                }

                return [
                    'id' => $classe->id,
                    'name' => $classe->name,
                    'level' => $classe->level,
                    'students_count' => $classe->students->count(),
                    'capacity' => $classe->capacity,
                    'average' => $classAverage,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $classStats
        ]);
    }

    public function getGradeStats(Request $request)
    {
        $period = $request->get('period', 'Trimestre 1');

        // Statistiques des notes par période
        $gradeStats = Grade::where('period', $period)
            ->with(['subject'])
            ->get()
            ->groupBy('subject_id')
            ->map(function ($subjectGrades, $subjectId) {
                $subject = $subjectGrades->first()->subject;
                $grades = $subjectGrades->pluck('value');

                return [
                    'subject_name' => $subject->name,
                    'grades_count' => $grades->count(),
                    'average' => round($grades->avg(), 2),
                    'min' => $grades->min(),
                    'max' => $grades->max(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $gradeStats
        ]);
    }

    public function getRecentActivity()
    {
        // Dernières notes saisies
        $recentGrades = Grade::with(['student.user', 'subject', 'teacher.user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($grade) {
                return [
                    'type' => 'grade',
                    'message' => "Note ajoutée: {$grade->student->user->name} - {$grade->subject->name} ({$grade->value}/{$grade->max_value})",
                    'date' => $grade->created_at,
                ];
            });

        // Derniers bulletins générés
        $recentReportCards = ReportCard::with(['student.user'])
            ->orderBy('generated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($reportCard) {
                return [
                    'type' => 'report_card',
                    'message' => "Bulletin généré: {$reportCard->student->user->name} - {$reportCard->period}",
                    'date' => $reportCard->generated_at,
                ];
            });

        // Derniers étudiants inscrits
        $recentStudents = Student::with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($student) {
                return [
                    'type' => 'student',
                    'message' => "Nouvel étudiant inscrit: {$student->user->name}",
                    'date' => $student->created_at,
                ];
            });

        $activities = collect()
            ->merge($recentGrades)
            ->merge($recentReportCards)
            ->merge($recentStudents)
            ->sortByDesc('date')
            ->take(15)
            ->values();

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    public function getMonthlyStats()
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = [
                'month' => $date->format('M Y'),
                'students' => Student::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'grades' => Grade::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'report_cards' => ReportCard::whereYear('generated_at', $date->year)
                    ->whereMonth('generated_at', $date->month)
                    ->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $months
        ]);
    }
}
