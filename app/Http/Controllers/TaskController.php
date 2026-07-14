<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Repositories\TaskRepository;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    // Injection de dépendance du Repository
    protected TaskRepository $taskRepository;

    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * F3.4 : Liste des tâches d'un projet spécifique
     */
    public function index(Request $request, $project_id)
    {
        $project = $request->user()->projects()->find($project_id);
        
        if (!$project) {
            return response()->json(['message' => 'Projet non trouvé ou non autorisé'], 404);
        }

        $tasks = $this->taskRepository->getAllForProject($project);
        return response()->json($tasks);
    }

    /**
     * F3.1 : Ajouter une nouvelle tâche à un projet
     */
    public function store(StoreTaskRequest $request, $project_id)
    {
        $project = $request->user()->projects()->find($project_id);
        
        if (!$project) {
            return response()->json(['message' => 'Projet non trouvé ou non autorisé'], 404);
        }

        $task = $this->taskRepository->create($request->validated(), $project);
        return response()->json($task, 201);
    }

    /**
     * F3.2 : Modifier une tâche existante
     */
    public function update(UpdateTaskRequest $request, $project_id, Task $task)
    {
        $project = $request->user()->projects()->find($project_id);
        
        // Vérifier que le projet appartient à l'utilisateur ET que la tâche appartient à ce projet
        if (!$project || $task->project_id !== $project->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $updatedTask = $this->taskRepository->update($task, $request->validated());
        return response()->json($updatedTask);
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

        $this->taskRepository->delete($task);
        return response()->json(['message' => 'Tâche supprimée avec succès.']);
    }
}