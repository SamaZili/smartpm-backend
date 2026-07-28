<?php

namespace App\Repositories;

use App\Models\Project;
use App\Models\Task;
use App\Models\Estimation;
use Illuminate\Foundation\Auth\User;

class DashboardRepository
{
    /**
     * Nombre de projets de l'utilisateur
     */
    public function getProjectStats(User $user)
    {
        return Project::where('user_id', $user->id)->count();
    }

    /**
     * Nombre de tâches de l'utilisateur
     */
    public function getTaskStats(User $user)
    {
        return Task::whereHas('project', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();
    }

    /**
     * Effort total estimé (CORRECTION DE L'ERREUR SQL ICI)
     */
    public function getTotalEstimatedEffort(User $user)
    {
        // On somme les 'estimated_hours' des estimations qui appartiennent 
        // à des tâches elles-mêmes appartenant aux projets de l'utilisateur.
        $totalEffort = Estimation::whereHas('task.project', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->sum('estimated_hours'); // <-- Nom correct de la colonne

        return (float) $totalEffort;
    }
}