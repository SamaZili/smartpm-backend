<?php

namespace App\Repositories;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;

class TaskRepository
{
    public function getAllForProject(Project $project): Collection
    {
        return $project->tasks()
            ->with(['estimation', 'assignedTo'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getAssignedToUser(User $user): Collection
    {
        return Task::where('assigned_to', $user->id)
            ->with(['project', 'estimation'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findById(int $id): ?Task
    {
        return Task::with(['project', 'estimation', 'assignedTo'])->find($id);
    }

    public function findByIdAndProject(int $taskId, Project $project): ?Task
    {
        return Task::where('id', $taskId)
            ->where('project_id', $project->id)
            ->first();
    }

    public function create(array $data, Project $project): Task
    {
        $data['user_id'] = $project->user_id;

        if (!empty($data['assigned_to']) && empty($data['assignment_status'])) {
            $data['assignment_status'] = 'pending';
        }

        return $project->tasks()->create($data);
    }

    public function update(Task $task, array $data): Task
    {
        if (array_key_exists('assigned_to', $data)
            && $data['assigned_to'] !== $task->assigned_to
            && empty($data['assignment_status'])) {
            $data['assignment_status'] = 'pending';
        }

        $task->update($data);
        return $task->fresh();
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }
}