<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Estimation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EstimationController extends Controller
{
    // F4.2, F4.3, F4.4, F4.5 : Lancer une estimation et l'historiser
    public function predict(Request $request, $project_id, $task_id)
    {
        // 1. Vérifier que la tâche appartient bien au projet de l'utilisateur connecté
        $task = $request->user()->projects()->findOrFail($project_id)->tasks()->findOrFail($task_id);

        // 2. Vérifier que les champs Desharnais sont bien remplis
        if (!$task->transactions || !$task->entities || !$task->team_exp || !$task->manager_exp) {
            return response()->json([
                'message' => 'Veuillez d\'abord remplir les caractéristiques de la tâche (transactions, entities, team_exp, manager_exp) avant de lancer l\'estimation.'
            ], 400);
        }

        // 3. Appel à l'API FastAPI (SIMULATION pour l'instant)
        // Quand ton API FastAPI sera prête, remplace cette partie par :
        // $response = Http::post('http://127.0.0.1:8000/predict', [
        //     'transactions' => $task->transactions,
        //     'entities' => $task->entities,
        //     'team_exp' => $task->team_exp,
        //     'manager_exp' => $task->manager_exp,
        // ]);
        // $data = $response->json();
        // $predicted_effort = $data['predicted_effort'];
        // $confidence = $data['confidence_score'] ?? null;

        // --- SIMULATION (à remplacer plus tard) ---
        // Formule fictive pour tester le flux complet du backend
        $predicted_effort = ($task->transactions * 0.5) + ($task->entities * 1.2) + ($task->team_exp * 0.1);
        $confidence = 0.85; 
        // ------------------------------------------

        // 4. Historiser l'estimation dans la base de données (F4.5)
        $estimation = Estimation::create([
            'task_id' => $task->id,
            'predicted_effort' => round($predicted_effort, 2),
            'confidence_score' => $confidence,
        ]);

        // 5. Retourner le résultat au frontend
        return response()->json([
            'message' => 'Estimation réalisée avec succès.',
            'estimation' => $estimation,
            'task' => [
                'id' => $task->id,
                'name' => $task->name,
                'transactions' => $task->transactions,
                'entities' => $task->entities,
            ]
        ], 201);
    }

    // Récupérer l'historique des estimations d'une tâche
    public function history(Request $request, $project_id, $task_id)
    {
        $task = $request->user()->projects()->findOrFail($project_id)->tasks()->findOrFail($task_id);
        
        return response()->json($task->estimations()->latest()->get());
    }
}