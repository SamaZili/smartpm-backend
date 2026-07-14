<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * F3.4 : Liste des tâches d'un projet spécifique
     */
    public function index(Request $request, $project_id)
    {
        // Vérifier que le projet appartient bien à l'utilisateur connecté
        $project = $request->user()->projects()->find($project_id);
        
        if (!$project) {
            return response()->json(['message' => 'Projet non trouvé ou non autorisé'], 404);
        }

        return response()->json($project->tasks()->latest()->get());
    }

    /**
     * F3.1 : Ajouter une nouvelle tâche à un projet
     */
    public function store(Request $request, $project_id)
    {
        $project = $request->user()->projects()->find($project_id);
        
        if (!$project) {
            return response()->json(['message' => 'Projet non trouvé ou non autorisé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|string|max:255',
            'complexity' => 'sometimes|string|max:255',
            'size' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:a_faire,en_cours,terminee',
            // Champs Desharnais pour l'IA (Module 4)
            'transactions' => 'sometimes|integer|min:0',
            'entities' => 'sometimes|integer|min:0',
            'team_exp' => 'sometimes|integer|min:0',
            'manager_exp' => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Création de la tâche liée au projet
        $task = $project->tasks()->create($validator->validated());
        
        return response()->json($task, 201);
    }

    /**
     * F3.2 : Modifier une tâche existante
     */
    public function update(Request $request, $project_id, Task $task)
    {
        $project = $request->user()->projects()->find($project_id);
        
        // Vérifier que le projet appartient à l'utilisateur ET que la tâche appartient à ce projet
        if (!$project || $task->project_id !== $project->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Validation incluant explicitement les champs Desharnais
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'type' => 'sometimes|string|max:255',
            'complexity' => 'sometimes|string|max:255',
            'size' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:a_faire,en_cours,terminee',
            // Champs Desharnais (C'est ici que le blocage était précédemment)
            'transactions' => 'sometimes|integer|min:0',
            'entities' => 'sometimes|integer|min:0',
            'team_exp' => 'sometimes|integer|min:0',
            'manager_exp' => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task->update($validator->validated());
        
        return response()->json($task);
    }

    /**
     * F3.3 : Supprimer une tâche
     */
    public function destroy(Request $request, $project_id, Task $task)
    {
        $project = $request->user()->projects()->find($project_id);
        
        if (!$project || $task->project_id !== $project->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $task->delete();
        
        return response()->json(['message' => 'Tâche supprimée avec succès.']);
    }
}