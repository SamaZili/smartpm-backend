<?php

namespace App\Repositories;

use App\Models\Estimation;
use App\Models\Task;

class EstimationRepository
{
    /**
     * Créer une nouvelle estimation pour une tâche
     */
    public function createEstimation(Task $task, float $predictedEffort, float $confidenceScore): Estimation
    {
        return Estimation::create([
            'task_id'          => $task->id,
            'predicted_effort' => $predictedEffort, // ⚠️ C'est ici : utiliser predicted_effort, PAS estimated_hours
            'confidence_score' => $confidenceScore,
        ]);
    }

    /**
     * Récupérer l'historique des estimations d'une tâche
     */
    public function getHistory(Task $task)
    {
        return Estimation::where('task_id', $task->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}