<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Estimation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // F5.1, F5.2, F5.3 : Statistiques globales pour le chef de projet connecté
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        // F5.1 : Nombre total de projets par statut
        $projectsStats = Project::where('user_id', $userId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        // F5.2 : Nombre total de tâches par statut (uniquement pour les projets de l'utilisateur)
        $tasksStats = Task::whereHas('project', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        // F5.3 : Temps estimé total (Requête JOIN simple et robuste)
        $totalEstimatedEffort = Estimation::join('tasks', 'estimations.task_id', '=', 'tasks.id')
            ->join('projects', 'tasks.project_id', '=', 'projects.id')
            ->where('projects.user_id', $userId)
            ->sum('estimations.predicted_effort');

        return response()->json([
            'projects' => [
                'total' => Project::where('user_id', $userId)->count(),
                'by_status' => $projectsStats
            ],
            'tasks' => [
                'total' => Task::whereHas('project', fn($q) => $q->where('user_id', $userId))->count(),
                'by_status' => $tasksStats
            ],
            'total_estimated_effort' => round($totalEstimatedEffort ?? 0, 2)
        ]);
    }
}