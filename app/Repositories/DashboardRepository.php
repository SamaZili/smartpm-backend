<?php

namespace App\Repositories;

use App\Models\Project;
use App\Models\Task;
use App\Models\Estimation;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;

class DashboardRepository
{
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    // F5.1 : Statistiques des projets
    public function getProjectStats()
    {
        return [
            'total' => Project::where('user_id', $this->user->id)->count(),
            'by_status' => Project::where('user_id', $this->user->id)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get()
        ];
    }

    // F5.2 : Statistiques des tâches
    public function getTaskStats()
    {
        return [
            'total' => Task::whereHas('project', fn($q) => $q->where('user_id', $this->user->id))->count(),
            'by_status' => Task::whereHas('project', function ($query) {
                    $query->where('user_id', $this->user->id);
                })
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get()
        ];
    }

    // F5.3 : Temps estimé total (Requête JOIN robuste)
    public function getTotalEstimatedEffort(): float
    {
        $total = Estimation::join('tasks', 'estimations.task_id', '=', 'tasks.id')
            ->join('projects', 'tasks.project_id', '=', 'projects.id')
            ->where('projects.user_id', $this->user->id)
            ->sum('estimations.predicted_effort');

        return round($total ?? 0, 2);
    }
}