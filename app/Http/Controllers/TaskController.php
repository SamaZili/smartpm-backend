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
        if (!$project) return response()->json(['message' => 'Projet non trouvé ou non autorisé'], 404);

        return response()->json($project->tasks);
    }

    // F3.1 : Ajouter une tâche
    public function store(Request $request, $project_id) {
        $project = $request->user()->projects()->find($project_id);
        if (!$project) return response()->json(['message' => 'Projet non trouvé ou non autorisé'], 404);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|string',
            'complexity' => 'sometimes|string',
            'size' => 'sometimes|string',
            'status' => 'sometimes|in:a_faire,en_cours,terminee',
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

        $task = $project->tasks()->create($request->all());
        return response()->json($task, 201);
    }

    // F3.2 : Modifier une tâche
    public function update(Request $request, $project_id, Task $task) {
        $project = $request->user()->projects()->find($project_id);
        if (!$project || $task->project_id !== $project->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:a_faire,en_cours,terminee',
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

        $task->update($request->all());
        return response()->json($task);
    }

    // F3.3 : Supprimer une tâche
    public function destroy(Request $request, $project_id, Task $task) {
        $project = $request->user()->projects()->find($project_id);
        if (!$project || $task->project_id !== $project->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $task->delete();
        return response()->json(['message' => 'Tâche supprimée avec succès.']);
    }
}