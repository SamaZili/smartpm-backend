<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Repositories\TaskRepository;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    protected TaskRepository $taskRepository;

    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * F3.4 : Liste des tâches d'un projet spécifique
     */
    public function index(Request $request, $project_id): JsonResponse
    {
        $project = $request->user()->projects()->find($project_id);
        
        if (!$project) {
            return response()->json([
                'success' => false,
                'error_code' => 'PROJECT_NOT_FOUND',
                'message' => 'Projet non trouvé ou non autorisé'
            ], Response::HTTP_NOT_FOUND);
        }

        $tasks = $this->taskRepository->getAllForProject($project);
        
        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    /**
     * F3.1 : Ajouter une nouvelle tâche à un projet
     */
    public function store(StoreTaskRequest $request, $project_id): JsonResponse
    {
        $project = $request->user()->projects()->find($project_id);
        
        if (!$project) {
            return response()->json([
                'success' => false,
                'error_code' => 'PROJECT_NOT_FOUND',
                'message' => 'Projet non trouvé ou non autorisé'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validated();
        
        // Sécurité : Forcer l'ID de l'utilisateur connecté
        $data['user_id'] = $request->user()->id;
        
        // Valeurs par défaut si le frontend ne les envoie pas
        $data['status'] = $data['status'] ?? 'a_faire';
        $data['complexity'] = $data['complexity'] ?? 'moyenne';

        $task = $this->taskRepository->create($data, $project);
        
        return response()->json([
            'success' => true,
            'data' => $task
        ], Response::HTTP_CREATED);
    }

    /**
     * F3.2 : Modifier une tâche existante
     */
    public function update(UpdateTaskRequest $request, $project_id, Task $task): JsonResponse
    {
        $project = $request->user()->projects()->find($project_id);
        
        if (!$project || $task->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'error_code' => 'UNAUTHORIZED',
                'message' => 'Non autorisé'
            ], Response::HTTP_FORBIDDEN);
        }

        $updatedTask = $this->taskRepository->update($task, $request->validated());
        
        return response()->json([
            'success' => true,
            'data' => $updatedTask
        ]);
    }

    /**
     * F3.3 : Supprimer une tâche
     */
    public function destroy(Request $request, $project_id, Task $task): JsonResponse
    {
        $project = $request->user()->projects()->find($project_id);
        
        if (!$project || $task->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'error_code' => 'UNAUTHORIZED',
                'message' => 'Non autorisé'
            ], Response::HTTP_FORBIDDEN);
        }

        $this->taskRepository->delete($task);
        
        return response()->json([
            'success' => true,
            'message' => 'Tâche supprimée avec succès.'
        ]);
    }
}