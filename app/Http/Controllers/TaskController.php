<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    // F3.4 : Liste des tâches d'un projet
    public function index(Request $request, $project_id) {
        $project = $request->user()->projects()->find($project_id);
        if (!$project) {
            return response()->json(['success' => false, 'message' => 'Projet non trouvé ou non autorisé'], 404);
        }

        // ✅ CORRECTION : charger l'estimation avec chaque tâche
        $tasks = $project->tasks()->with('estimation')->get();

        return response()->json(['success' => true, 'data' => $tasks]);
    }

    // F3.1 : Ajouter une tâche
    public function store(Request $request, $project_id) {
        $project = $request->user()->projects()->find($project_id);
        if (!$project) {
            return response()->json(['success' => false, 'message' => 'Projet non trouvé ou non autorisé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'complexity' => 'sometimes|string',
            'status' => 'sometimes|in:a_faire,en_cours,terminee',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // ✅ SOLUTION DÉFINITIVE : Instanciation manuelle pour contourner les restrictions $fillable
        $task = new Task();
        $task->project_id = $project->id;
        $task->user_id = $project->user_id; // ✅ Hérite automatiquement de l'utilisateur du projet
        $task->name = $request->name;
        $task->description = $request->description;
        $task->status = $request->status ?? 'a_faire';
        $task->complexity = $request->complexity ?? 'moyenne';
        $task->save(); // Sauvegarde directe en base de données
        
        return response()->json(['success' => true, 'data' => $task], 201);
    }

    // F3.2 : Modifier une tâche
    public function update(Request $request, $project_id, $task_id) {
        $project = $request->user()->projects()->find($project_id);
        if (!$project) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        // Recherche explicite pour éviter les erreurs de Route Model Binding
        $task = Task::where('project_id', $project->id)->find($task_id);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Tâche non trouvée'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:a_faire,en_cours,terminee',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Mise à jour sécurisée des champs autorisés
        $task->update($request->only(['name', 'description', 'status']));
        
        return response()->json(['success' => true, 'data' => $task]);
    }

    // F3.3 : Supprimer une tâche
    public function destroy(Request $request, $project_id, $task_id) {
        $project = $request->user()->projects()->find($project_id);
        if (!$project) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        // Recherche explicite pour éviter les erreurs de Route Model Binding
        $task = Task::where('project_id', $project->id)->find($task_id);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Tâche non trouvée'], 404);
        }

        $task->delete();
        
        return response()->json(['success' => true, 'message' => 'Tâche supprimée avec succès.']);
    }
}