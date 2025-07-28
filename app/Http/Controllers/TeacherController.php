<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $query = Teacher::with(['user', 'subjects', 'classes']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $teachers = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $teachers
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'specialization' => 'required|string|max:255',
            'hire_date' => 'required|date',
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'exists:subjects,id',
            'class_ids' => 'nullable|array',
            'class_ids.*' => 'exists:classes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $password = Str::random(8);

            // Créer l'utilisateur
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($password),
                'phone' => $request->phone,
                'address' => $request->address,
            ]);
            $user->assignRole('teacher');

            // Créer l'enseignant
            $teacher = Teacher::create([
                'user_id' => $user->id,
                'teacher_number' => $this->generateTeacherNumber(),
                'specialization' => $request->specialization,
                'hire_date' => $request->hire_date,
            ]);

            // Associer les matières
            $teacher->subjects()->attach($request->subject_ids);

            // Associer les classes si fournies
            if ($request->has('class_ids')) {
                $teacher->classes()->attach($request->class_ids);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Enseignant créé avec succès',
                'data' => [
                    'teacher' => $teacher->load(['user', 'subjects', 'classes']),
                    'credentials' => [
                        'email' => $user->email,
                        'password' => $password
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'enseignant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $teacher = Teacher::with(['user', 'subjects', 'classes', 'grades'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $teacher
        ]);
    }

    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $teacher->user_id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'specialization' => 'sometimes|string|max:255',
            'hire_date' => 'sometimes|date',
            'subject_ids' => 'sometimes|array',
            'subject_ids.*' => 'exists:subjects,id',
            'class_ids' => 'sometimes|array',
            'class_ids.*' => 'exists:classes,id',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Mettre à jour l'utilisateur
            $teacher->user->update($request->only(['name', 'email', 'phone', 'address']));

            // Mettre à jour l'enseignant
            $teacher->update($request->only(['specialization', 'hire_date', 'is_active']));

            // Mettre à jour les associations
            if ($request->has('subject_ids')) {
                $teacher->subjects()->sync($request->subject_ids);
            }

            if ($request->has('class_ids')) {
                $teacher->classes()->sync($request->class_ids);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Enseignant mis à jour avec succès',
                'data' => $teacher->load(['user', 'subjects', 'classes'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);

        DB::beginTransaction();

        try {
            $teacher->user->delete(); // Cascade delete
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Enseignant supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateTeacherNumber()
    {
        $year = date('Y');
        $lastTeacher = Teacher::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastTeacher ? (int)substr($lastTeacher->teacher_number, -4) + 1 : 1;

        return 'T' . $year . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
