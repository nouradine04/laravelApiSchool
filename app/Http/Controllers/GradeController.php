<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $query = Grade::with(['student.user', 'subject', 'teacher.user', 'classe']);

        // Filtres pour les enseignants (seulement leurs notes)
        if (Auth::user()->hasRole('teacher')) {
            $query->where('teacher_id', Auth::user()->teacher->id);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('period')) {
            $query->where('period', $request->period);
        }

        $grades = $query->orderBy('date', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $grades
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'period' => 'required|string|in:Trimestre 1,Trimestre 2,Trimestre 3',
            'grade_type' => 'required|string|max:50',
            'value' => 'required|numeric|min:0',
            'max_value' => 'required|numeric|min:1',
            'date' => 'required|date',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier que la note ne dépasse pas la note maximale
        if ($request->value > $request->max_value) {
            return response()->json([
                'success' => false,
                'message' => 'La note ne peut pas dépasser la note maximale'
            ], 422);
        }

        // Vérifier que l'enseignant enseigne cette matière
        if (Auth::user()->hasRole('teacher')) {
            $teacher = Auth::user()->teacher;
            if (!$teacher->subjects()->where('subject_id', $request->subject_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à noter cette matière'
                ], 403);
            }
            $teacherId = $teacher->id;
        } else {
            $teacherId = $request->teacher_id ?? Auth::user()->teacher->id;
        }

        $grade = Grade::create([
            'student_id' => $request->student_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => $teacherId,
            'class_id' => $request->class_id,
            'period' => $request->period,
            'grade_type' => $request->grade_type,
            'value' => $request->value,
            'max_value' => $request->max_value,
            'date' => $request->date,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Note ajoutée avec succès',
            'data' => $grade->load(['student.user', 'subject', 'teacher.user', 'classe'])
        ], 201);
    }

    public function show($id)
    {
        $grade = Grade::with(['student.user', 'subject', 'teacher.user', 'classe'])
            ->findOrFail($id);

        // Vérifier les permissions
        if (Auth::user()->hasRole('teacher') && $grade->teacher_id !== Auth::user()->teacher->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $grade
        ]);
    }

    public function update(Request $request, $id)
    {
        $grade = Grade::findOrFail($id);

        // Vérifier les permissions
        if (Auth::user()->hasRole('teacher') && $grade->teacher_id !== Auth::user()->teacher->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'value' => 'sometimes|numeric|min:0',
            'max_value' => 'sometimes|numeric|min:1',
            'grade_type' => 'sometimes|string|max:50',
            'date' => 'sometimes|date',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier que la note ne dépasse pas la note maximale
        $maxValue = $request->max_value ?? $grade->max_value;
        $value = $request->value ?? $grade->value;

        if ($value > $maxValue) {
            return response()->json([
                'success' => false,
                'message' => 'La note ne peut pas dépasser la note maximale'
            ], 422);
        }

        $grade->update($request->only([
            'value', 'max_value', 'grade_type', 'date', 'comment'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Note mise à jour avec succès',
            'data' => $grade->load(['student.user', 'subject', 'teacher.user', 'classe'])
        ]);
    }

    public function destroy($id)
    {
        $grade = Grade::findOrFail($id);

        // Vérifier les permissions
        if (Auth::user()->hasRole('teacher') && $grade->teacher_id !== Auth::user()->teacher->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $grade->delete();

        return response()->json([
            'success' => true,
            'message' => 'Note supprimée avec succès'
        ]);
    }

    public function getStudentGrades($studentId, Request $request)
    {
        $student = Student::findOrFail($studentId);

        $query = Grade::with(['subject', 'teacher.user'])
            ->where('student_id', $studentId);

        if ($request->has('period')) {
            $query->where('period', $request->period);
        }

        $grades = $query->orderBy('date', 'desc')->get();

        // Calculer les moyennes par matière
        $averagesBySubject = $grades->groupBy('subject_id')->map(function ($subjectGrades) {
            $totalPoints = 0;
            $totalCoefficient = 0;

            foreach ($subjectGrades as $grade) {
                $percentage = ($grade->value / $grade->max_value) * 20; // Convertir sur 20
                $coefficient = $grade->subject->coefficient;
                $totalPoints += $percentage * $coefficient;
                $totalCoefficient += $coefficient;
            }

            return $totalCoefficient > 0 ? round($totalPoints / $totalCoefficient, 2) : 0;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'student' => $student->load(['user', 'classe']),
                'grades' => $grades,
                'averages_by_subject' => $averagesBySubject,
                'general_average' => $averagesBySubject->avg()
            ]
        ]);
    }
}
