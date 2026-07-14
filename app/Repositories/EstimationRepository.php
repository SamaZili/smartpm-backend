<?php

namespace App\Repositories;

use App\Models\Estimation;
use App\Models\Task;

class EstimationRepository
{
    // Créer et historiser une nouvelle estimation pour une tâche
    public function createEstimation(Task $task, float $predictedEffort, ?float $confidenceScore = null): Estimation
    {
        return Estimation::create([
            'task_id' => $task->id,
            'predicted_effort' => round($predictedEffort, 2),
            'confidence_score' => $confidenceScore,
        ]);
    }

    // Récupérer l'historique des estimations d'une tâche
    public function getHistory(Task $task)
    {
        return $task->estimations()->latest()->get();
    }
}