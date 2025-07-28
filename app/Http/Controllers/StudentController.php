<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Models\ParentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with(['user', 'parent.user', 'classe']);

        // Filtres
        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'class_id' => 'required|exists:classes,id',
            'birth_date' => 'required|date',
            'birth_place' => 'required|string|max:255',
            'gender' => 'required|in:M,F',
            'documents' => 'required|array|min:1',
            'documents.*' => 'string',
            'parent_name' => 'required|string|max:255',
            'parent_email' => 'required|email|unique:users,email',
            'parent_phone' => 'nullable|string|max:20',
            'parent_profession' => 'nullable|string|max:255',
            'parent_workplace' => 'nullable|string|max:255',
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
            // Créer l'utilisateur étudiant
            $studentPassword = Str::random(8);
            $studentUser = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($studentPassword),
                'phone' => $request->phone,
                'address' => $request->address,
            ]);
            $studentUser->assignRole('student');

            // Créer l'utilisateur parent
            $parentPassword = Str::random(8);
            $parentUser = User::create([
                'name' => $request->parent_name,
                'email' => $request->parent_email,
                'password' => Hash::make($parentPassword),
                'phone' => $request->parent_phone,
            ]);
            $parentUser->assignRole('parent');

            // Créer le parent
            $parent = ParentModel::create([
                'user_id' => $parentUser->id,
                'profession' => $request->parent_profession,
                'workplace' => $request->parent_workplace,
            ]);

            // Créer l'étudiant
            $student = Student::create([
                'user_id' => $studentUser->id,
                'parent_id' => $parent->id,
                'class_id' => $request->class_id,
                'student_number' => $this->generateStudentNumber(),
                'birth_date' => $request->birth_date,
                'birth_place' => $request->birth_place,
                'gender' => $request->gender,
                'documents' => $request->documents,
                'enrollment_date' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Étudiant créé avec succès',
                'data' => [
                    'student' => $student->load(['user', 'parent.user', 'classe']),
                    'credentials' => [
                        'student' => [
                            'email' => $studentUser->email,
                            'password' => $studentPassword
                        ],
                        'parent' => [
                            'email' => $parentUser->email,
                            'password' => $parentPassword
                        ]
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'étudiant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $student = Student::with(['user', 'parent.user', 'classe', 'grades.subject', 'reportCards'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $student->user_id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'class_id' => 'sometimes|exists:classes,id',
            'birth_date' => 'sometimes|date',
            'birth_place' => 'sometimes|string|max:255',
            'gender' => 'sometimes|in:M,F',
            'documents' => 'sometimes|array',
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
            $student->user->update($request->only(['name', 'email', 'phone', 'address']));

            // Mettre à jour l'étudiant
            $student->update($request->only([
                'class_id', 'birth_date', 'birth_place', 'gender', 'documents', 'is_active'
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Étudiant mis à jour avec succès',
                'data' => $student->load(['user', 'parent.user', 'classe'])
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
        $student = Student::findOrFail($id);

        DB::beginTransaction();

        try {
            $student->user->delete(); // Cascade delete
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Étudiant supprimé avec succès'
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

    private function generateStudentNumber()
    {
        $year = date('Y');
        $lastStudent = Student::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastStudent ? (int)substr($lastStudent->student_number, -4) + 1 : 1;

        return $year . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
