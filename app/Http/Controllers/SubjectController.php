<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Subject::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        }

        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        $subjects = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $subjects
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:subjects',
            'coefficient' => 'required|numeric|min:0.5|max:10',
            'level' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $subject = Subject::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Matière créée avec succès',
            'data' => $subject
        ], 201);
    }

    public function show($id)
    {
        $subject = Subject::with(['teachers.user', 'classes'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $subject
        ]);
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:10|unique:subjects,code,' . $id,
            'coefficient' => 'sometimes|numeric|min:0.5|max:10',
            'level' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $subject->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Matière mise à jour avec succès',
            'data' => $subject
        ]);
    }

    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);

        // Vérifier s'il y a des notes pour cette matière
        if ($subject->grades()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une matière qui a des notes'
            ], 422);
        }

        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Matière supprimée avec succès'
        ]);
    }
}
