<?php

namespace App\Http\Controllers;

use App\Repositories\TaskRepository;
use App\Repositories\EstimationRepository;
use Illuminate\Http\Request;

class EstimationController extends Controller
{
    protected TaskRepository $taskRepository;
    protected EstimationRepository $estimationRepository;

    public function __construct(
        TaskRepository $taskRepository,
        EstimationRepository $estimationRepository
    ) {
        $this->taskRepository = $taskRepository;
        $this->estimationRepository = $estimationRepository;
    }

    // F4.2, F4.3, F4.4, F4.5 : Lancer une estimation et l'historiser
    public function predict(Request $request, $project_id, $task_id)
    {
        // 1. Récupérer le projet et la tâche via le TaskRepository (sécurité incluse)
        $project = $request->user()->projects()->find($project_id);
        if (!$project) {
            return response()->json(['message' => 'Projet non trouvé ou non autorisé'], 404);
        }

        $task = $this->taskRepository->findByIdAndProject($task_id, $project);
        if (!$task) {
            return response()->json(['message' => 'Tâche non trouvée dans ce projet'], 404);
        }

        // 2. Vérifier que les champs Desharnais sont bien remplis
        if (!$task->transactions || !$task->entities || !$task->team_exp || !$task->manager_exp) {
            return response()->json([
                'message' => 'Veuillez d\'abord remplir les caractéristiques de la tâche (transactions, entities, team_exp, manager_exp) avant de lancer l\'estimation.'
            ], 400);
        }

        // 3. Appel à l'API FastAPI (SIMULATION pour l'instant)
        // Formule fictive pour tester le flux complet du backend
        $predictedEffort = ($task->transactions * 0.5) + ($task->entities * 1.2) + ($task->team_exp * 0.1);
        $confidence = 0.85; 

        // 4. Historiser l'estimation via le Repository (F4.5)
        $estimation = $this->estimationRepository->createEstimation($task, $predictedEffort, $confidence);

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
        $project = $request->user()->projects()->find($project_id);
        if (!$project) {
            return response()->json(['message' => 'Projet non trouvé ou non autorisé'], 404);
        }

        $task = $this->taskRepository->findByIdAndProject($task_id, $project);
        if (!$task) {
            return response()->json(['message' => 'Tâche non trouvée'], 404);
        }
        
        return response()->json($this->estimationRepository->getHistory($task));
    }
}