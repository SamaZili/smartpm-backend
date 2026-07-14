<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    // F2.4 : Liste des projets du chef de projet connecté
    public function index(Request $request) {
        $projects = $request->user()->projects()->latest()->get();
        return response()->json($projects);
    }

    // F2.1 : Ajouter un projet
    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:projects,name',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:en_cours,termine',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Création automatique liée à l'utilisateur connecté
        $project = $request->user()->projects()->create($request->all());
        return response()->json($project, 201);
    }

    // F2.5 : Détail d'un projet (avec vérification de sécurité)
    public function show(Request $request, Project $project) {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        return response()->json($project);
    }

    // F2.2 : Modifier un projet
    public function update(Request $request, Project $project) {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:projects,name,' . $project->id,
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:en_cours,termine',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $project->update($request->all());
        return response()->json($project);
    }

    // F2.3 : Supprimer un projet
    public function destroy(Request $request, Project $project) {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        $project->delete();
        return response()->json(['message' => 'Projet supprimé avec succès.']);
    }
}