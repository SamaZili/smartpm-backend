<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    public function index(Request $request, $project_id): JsonResponse
    {
        try {
            $project = Project::where('user_id', $request->user()->id)
                             ->find($project_id);
            
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Projet non trouvé'
                ], 404);
            }

            $tasks = $project->tasks()->latest()->get();
            
            return response()->json([
                'success' => true,
                'data' => $tasks
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur index tâches: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    public function store(Request $request, $project_id): JsonResponse
    {
        try {
            $project = Project::where('user_id', $request->user()->id)
                             ->find($project_id);
            
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Projet non trouvé'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'nullable|string',
                'complexity' => 'nullable|string',
            ]);

            $data = [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'] ?? 'a_faire',
                'complexity' => $validated['complexity'] ?? 'moyenne',
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
            ];

            $task = Task::create($data);

            return response()->json([
                'success' => true,
                'data' => $task
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Erreur création tâche: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $project_id, $task_id): JsonResponse
    {
        try {
            // Trouver le projet
            $project = Project::where('user_id', $request->user()->id)
                             ->find($project_id);
            
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Projet non trouvé'
                ], 404);
            }

            // Trouver la tâche
            $task = Task::where('project_id', $project_id)
                       ->find($task_id);
            
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tâche non trouvée'
                ], 404);
            }

            // Validation
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'status' => 'sometimes|string|in:a_faire,en_cours,terminee',
                'complexity' => 'sometimes|string',
            ]);

            // Mise à jour
            $task->update($validated);

            return response()->json([
                'success' => true,
                'data' => $task
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur update tâche: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $project_id, $task_id): JsonResponse
    {
        try {
            $project = Project::where('user_id', $request->user()->id)
                             ->find($project_id);
            
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Projet non trouvé'
                ], 404);
            }

            $task = Task::where('project_id', $project_id)
                       ->find($task_id);
            
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tâche non trouvée'
                ], 404);
            }

            $task->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tâche supprimée'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur suppression tâche: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}