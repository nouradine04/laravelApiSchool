<?php

namespace App\Http\Controllers;

use App\Models\ReportCard;
use App\Models\Student;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportCardController extends Controller
{
    public function index(Request $request)
    {
        $query = ReportCard::with(['student.user', 'classe']);

        // Filtres pour les parents/élèves
        if (Auth::user()->hasRole('parent')) {
            $studentIds = Auth::user()->parent->students->pluck('id');
            $query->whereIn('student_id', $studentIds);
        } elseif (Auth::user()->hasRole('student')) {
            $query->where('student_id', Auth::user()->student->id);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('period')) {
            $query->where('period', $request->period);
        }

        if ($request->has('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        $reportCards = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $reportCards
        ]);
    }

    public function generate(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'period' => 'required|string|in:Trimestre 1,Trimestre 2,Trimestre 3',
            'academic_year' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $student = Student::with(['user', 'classe', 'parent.user'])->findOrFail($request->student_id);

        // Récupérer les notes de la période
        $grades = Grade::with(['subject'])
            ->where('student_id', $request->student_id)
            ->where('period', $request->period)
            ->get();

        if ($grades->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune note trouvée pour cette période'
            ], 404);
        }

        // Calculer les moyennes par matière
        $subjectAverages = [];
        $totalPoints = 0;
        $totalCoefficient = 0;

        foreach ($grades->groupBy('subject_id') as $subjectId => $subjectGrades) {
            $subject = $subjectGrades->first()->subject;
            $subjectTotal = 0;
            $subjectCount = 0;

            foreach ($subjectGrades as $grade) {
                $percentage = ($grade->value / $grade->max_value) * 20;
                $subjectTotal += $percentage;
                $subjectCount++;
            }

            $subjectAverage = $subjectCount > 0 ? $subjectTotal / $subjectCount : 0;
            $subjectAverages[] = [
                'subject' => $subject,
                'average' => round($subjectAverage, 2),
                'coefficient' => $subject->coefficient,
                'points' => round($subjectAverage * $subject->coefficient, 2)
            ];

            $totalPoints += $subjectAverage * $subject->coefficient;
            $totalCoefficient += $subject->coefficient;
        }

        $generalAverage = $totalCoefficient > 0 ? round($totalPoints / $totalCoefficient, 2) : 0;

        // Calculer le rang dans la classe
        $classStudents = Student::where('class_id', $student->class_id)->pluck('id');
        $classAverages = [];

        foreach ($classStudents as $classStudentId) {
            $classStudentGrades = Grade::where('student_id', $classStudentId)
                ->where('period', $request->period)
                ->get();

            if ($classStudentGrades->isNotEmpty()) {
                $classStudentAverage = $this->calculateGeneralAverage($classStudentGrades);
                $classAverages[$classStudentId] = $classStudentAverage;
            }
        }

        arsort($classAverages);
        $rank = array_search($request->student_id, array_keys($classAverages)) + 1;

        // Déterminer la mention
        $mention = $this->getMention($generalAverage);

        // Générer l'appréciation
        $appreciation = $this->generateAppreciation($generalAverage, $mention);

        // Vérifier si le bulletin existe déjà
        $existingReportCard = ReportCard::where([
            'student_id' => $request->student_id,
            'period' => $request->period,
            'academic_year' => $request->academic_year,
        ])->first();

        if ($existingReportCard) {
            // Mettre à jour le bulletin existant
            $existingReportCard->update([
                'general_average' => $generalAverage,
                'rank' => $rank,
                'mention' => $mention,
                'appreciation' => $appreciation,
                'generated_at' => now(),
            ]);
            $reportCard = $existingReportCard;
        } else {
            // Créer un nouveau bulletin
            $reportCard = ReportCard::create([
                'student_id' => $request->student_id,
                'class_id' => $student->class_id,
                'period' => $request->period,
                'academic_year' => $request->academic_year,
                'general_average' => $generalAverage,
                'rank' => $rank,
                'mention' => $mention,
                'appreciation' => $appreciation,
                'generated_at' => now(),
            ]);
        }

        // Générer le PDF
        $pdfData = [
            'student' => $student,
            'reportCard' => $reportCard,
            'subjectAverages' => $subjectAverages,
            'totalStudents' => count($classAverages),
        ];

        $pdf = Pdf::loadView('report-card', $pdfData);
        $pdfContent = $pdf->output();

        // Sauvegarder le PDF
        $fileName = "bulletin_{$student->student_number}_{$request->period}_{$request->academic_year}.pdf";
        $filePath = "report-cards/{$fileName}";
        Storage::disk('public')->put($filePath, $pdfContent);

        // Mettre à jour le chemin du PDF
        $reportCard->update(['pdf_path' => $filePath]);

        return response()->json([
            'success' => true,
            'message' => 'Bulletin généré avec succès',
            'data' => $reportCard->load(['student.user', 'classe'])
        ]);
    }

    public function show($id)
    {
        $reportCard = ReportCard::with(['student.user', 'classe'])->findOrFail($id);

        // Vérifier les permissions
        if (Auth::user()->hasRole('parent')) {
            $studentIds = Auth::user()->parent->students->pluck('id');
            if (!$studentIds->contains($reportCard->student_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }
        } elseif (Auth::user()->hasRole('student')) {
            if ($reportCard->student_id !== Auth::user()->student->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $reportCard
        ]);
    }

    public function download($id)
    {
        $reportCard = ReportCard::findOrFail($id);

        // Vérifier les permissions
        if (Auth::user()->hasRole('parent')) {
            $studentIds = Auth::user()->parent->students->pluck('id');
            if (!$studentIds->contains($reportCard->student_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }
        } elseif (Auth::user()->hasRole('student')) {
            if ($reportCard->student_id !== Auth::user()->student->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }
        }

        if (!$reportCard->pdf_path || !Storage::disk('public')->exists($reportCard->pdf_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier PDF non trouvé'
            ], 404);
        }

        return Storage::disk('public')->download($reportCard->pdf_path);
    }

    private function calculateGeneralAverage($grades)
    {
        $totalPoints = 0;
        $totalCoefficient = 0;

        foreach ($grades->groupBy('subject_id') as $subjectGrades) {
            $subject = $subjectGrades->first()->subject;
            $subjectTotal = 0;
            $subjectCount = 0;

            foreach ($subjectGrades as $grade) {
                $percentage = ($grade->value / $grade->max_value) * 20;
                $subjectTotal += $percentage;
                $subjectCount++;
            }

            $subjectAverage = $subjectCount > 0 ? $subjectTotal / $subjectCount : 0;
            $totalPoints += $subjectAverage * $subject->coefficient;
            $totalCoefficient += $subject->coefficient;
        }

        return $totalCoefficient > 0 ? $totalPoints / $totalCoefficient : 0;
    }

    private function getMention($average)
    {
        if ($average >= 16) return 'E';
        if ($average >= 14) return 'TB';
        if ($average >= 12) return 'B';
        if ($average >= 10) return 'AB';
        if ($average >= 8) return 'P';
        return 'I';
    }

    private function generateAppreciation($average, $mention)
    {
        $appreciations = [
            'E' => 'Excellent travail, continuez ainsi !',
            'TB' => 'Très bon niveau, félicitations !',
            'B' => 'Bon travail, peut encore mieux faire.',
            'AB' => 'Travail satisfaisant, des efforts à poursuivre.',
            'P' => 'Résultats passables, plus d\'efforts nécessaires.',
            'I' => 'Résultats insuffisants, un travail sérieux s\'impose.',
        ];

        return $appreciations[$mention] ?? 'Continuez vos efforts.';
    }
}
