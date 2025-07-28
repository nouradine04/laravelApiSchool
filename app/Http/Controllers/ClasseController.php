<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClasseController extends Controller
{
    public function index(Request $request)
    {
        $query = Classe::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('level', 'like', "%{$search}%");
        }

        if ($request->has('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        $classes = $query->withCount('students')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:classes',
            'level' => 'required|string|max:255',
            'academic_year' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $classe = Classe::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Classe créée avec succès',
            'data' => $classe
        ], 201);
    }

    public function show($id)
    {
        $classe = Classe::with(['students.user', 'teachers.user', 'subjects'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $classe
        ]);
    }

    public function update(Request $request, $id)
    {
        $classe = Classe::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:classes,name,' . $id,
            'level' => 'sometimes|string|max:255',
            'academic_year' => 'sometimes|string|max:255',
            'capacity' => 'sometimes|integer|min:1|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $classe->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Classe mise à jour avec succès',
            'data' => $classe
        ]);
    }

    public function destroy($id)
    {
        $classe = Classe::findOrFail($id);

        // Vérifier s'il y a des étudiants dans cette classe
        if ($classe->students()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une classe qui contient des élèves'
            ], 422);
        }

        $classe->delete();

        return response()->json([
            'success' => true,
            'message' => 'Classe supprimée avec succès'
        ]);
    }
}
