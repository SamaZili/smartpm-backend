<?php

namespace App\Repositories;

use App\Models\Task;
use App\Models\Project;

class TaskRepository
{
    // Récupérer toutes les tâches d'un projet spécifique
    public function getAllForProject(Project $project)
    {
        return $project->tasks()->latest()->get();
    }

    // Créer une nouvelle tâche dans un projet
    public function create(array $data, Project $project): Task
    {
        // La relation Eloquent gère automatiquement le project_id
        return $project->tasks()->create($data);
    }

    // Trouver une tâche par son ID dans un projet spécifique
    public function findByIdAndProject(int $id, Project $project): ?Task
    {
        return $project->tasks()->find($id);
    }

    // Mettre à jour une tâche
    public function update(Task $task, array $data): Task
    {
        $task->update($data);
        return $task;
    }

    // Supprimer une tâche
    public function delete(Task $task): bool
    {
        return $task->delete();
    }
}