<?php

namespace App\Http\Controllers;

use App\Repositories\TaskRepository;
use App\Repositories\EstimationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function predict(Request $request, $project_id, $task_id)
    {
        // 1. Vérifier projet + tâche
        $project = $request->user()->projects()->find($project_id);
        if (!$project) {
            return response()->json(['message' => 'Projet non trouvé ou non autorisé'], 404);
        }

        $task = $this->taskRepository->findByIdAndProject($task_id, $project);
        if (!$task) {
            return response()->json(['message' => 'Tâche non trouvée dans ce projet'], 404);
        }

        if (empty($task->name) && empty($task->description)) {
            return response()->json([
                'message' => "Veuillez d'abord remplir le nom et la description de la tâche."
            ], 400);
        }

        // 2. Appel FastAPI — séparé du reste pour isoler l'erreur réseau
        try {
            $response = Http::timeout(5)->post('http://127.0.0.1:8001/predict', [
                'title' => $task->name,
                'description' => $task->description,
            ]);
        } catch (\Exception $e) {
            Log::error('FastAPI connexion échouée : ' . $e->getMessage());
            return response()->json([
                'message' => "Impossible de contacter le service IA. Vérifiez qu'il tourne bien sur le port 8001."
            ], 503);
        }

        if (!$response->successful()) {
            Log::error('FastAPI a répondu une erreur : ' . $response->status() . ' - ' . $response->body());
            return response()->json(['message' => "Erreur du service d'estimation IA"], 500);
        }

        $data = $response->json();

        // 3. Vérifier que la clé attendue existe vraiment dans la réponse FastAPI
        if (!isset($data['predicted_effort_hours'])) {
            Log::error('Réponse FastAPI inattendue : ' . json_encode($data));
            return response()->json([
                'message' => "Réponse invalide du service IA (clé 'predicted_effort_hours' absente)."
            ], 500);
        }

        // 4. Sauvegarde en base — isolée pour distinguer une erreur SQL d'une erreur réseau
        try {
            $estimation = $this->estimationRepository->createEstimation(
                $task,
                (float) $data['predicted_effort_hours'],
                0.85
            );
        } catch (\Exception $e) {
            Log::error('Erreur sauvegarde estimation : ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur interne du serveur lors de la sauvegarde du résultat.',
                'debug' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json([
            'message' => 'Estimation réalisée avec succès via IA.',
            'estimation' => $estimation,
            'task' => [
                'id' => $task->id,
                'name' => $task->name,
            ],
        ], 201);
    }
}