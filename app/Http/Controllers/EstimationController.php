<?php

namespace App\Http\Controllers;

use App\Models\Estimation;
use App\Models\Task;
use App\Models\Project;
use App\Repositories\TaskRepository;
use App\Repositories\EstimationRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EstimationController extends Controller
{
    protected TaskRepository $taskRepository;
    protected EstimationRepository $estimationRepository;

    public function __construct(TaskRepository $taskRepository, EstimationRepository $estimationRepository)
    {
        $this->taskRepository = $taskRepository;
        $this->estimationRepository = $estimationRepository;
    }

    /**
     * Prédire l'effort pour une tâche via l'IA
     */
    public function predict(Request $request, $project_id, $task_id): JsonResponse
    {
        // 1. Vérifier projet + tâche
        $project = $request->user()->projects()->find($project_id);
        if (!$project) {
            return response()->json([
                'error_code' => 'PROJECT_NOT_FOUND',
                'message' => 'Projet non trouvé ou non autorisé'
            ], Response::HTTP_NOT_FOUND);
        }

        $task = $this->taskRepository->findByIdAndProject($task_id, $project);
        if (!$task) {
            return response()->json([
                'error_code' => 'TASK_NOT_FOUND',
                'message' => 'Tâche non trouvée dans ce projet'
            ], Response::HTTP_NOT_FOUND);
        }

        // Vérification des champs (REMARQUE 1 & 2 : error_code au lieu de string en dur)
        if (empty($task->name) && empty($task->description)) {
            return response()->json([
                'error_code' => 'TASK_FIELDS_REQUIRED',
                'message' => "Veuillez d'abord remplir le nom et la description de la tâche."
            ], Response::HTTP_BAD_REQUEST);
        }

        // 2. Appel FastAPI
        try {
            $response = Http::timeout(5)->post('http://127.0.0.1:8001/predict', [
                'title'       => $task->name, // ou $task->title selon le nom de ta colonne en BDD
                'description' => $task->description,
            ]);
        } catch (\Exception $e) {
            Log::error('FastAPI connexion échouée : ' . $e->getMessage());
            return response()->json([
                'error_code' => 'IA_SERVICE_UNAVAILABLE',
                'message' => "Impossible de contacter le service IA. Vérifiez qu'il tourne bien sur le port 8001."
            ], Response::HTTP_SERVICE_UNAVAILABLE); // REMARQUE 3 : Constante au lieu de 503
        }

        if (!$response->successful()) {
            Log::error('FastAPI a répondu une erreur : ' . $response->status() . ' - ' . $response->body());
            return response()->json([
                'error_code' => 'IA_SERVICE_ERROR',
                'message' => "Erreur du service d'estimation IA"
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // REMARQUE 3 : Constante au lieu de 500
        }

        $data = $response->json();

        // 3. Vérification de la réponse de l'IA
        if (!isset($data['predicted_effort_hours'])) {
            Log::error('Réponse FastAPI inattendue : ' . json_encode($data));
            return response()->json([
                'error_code' => 'INVALID_IA_RESPONSE',
                'message' => "Réponse invalide du service IA."
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // REMARQUE 3 : Constante au lieu de 500
        }

        // 4. Sauvegarde en base
        try {
            $estimation = $this->estimationRepository->createEstimation(
                $task,
                (float) $data['predicted_effort_hours'],
                0.85
            );
        } catch (\Exception $e) {
            Log::error('Erreur sauvegarde estimation : ' . $e->getMessage());
            return response()->json([
                'error_code' => 'DATABASE_SAVE_ERROR',
                'message' => 'Erreur interne du serveur lors de la sauvegarde du résultat.',
                'debug' => app()->environment('local') ? $e->getMessage() : null,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // 5. Retourner le résultat au frontend
        return response()->json([
            'message' => 'Estimation réalisée avec succès via IA.',
            'estimation' => $estimation,
        ], Response::HTTP_CREATED); // REMARQUE 3 : Constante au lieu de 201
    }

    /**
     * Afficher une estimation spécifique
     */
    public function show($id): JsonResponse
    {
        try {
            $estimation = Estimation::with(['task', 'project'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $estimation
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estimation non trouvée'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Lister toutes les estimations d'un projet
     */
    public function index($projectId): JsonResponse
    {
        try {
            $estimations = Estimation::where('project_id', $projectId)
                ->with('task')
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $estimations
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des estimations'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mettre à jour une estimation
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $estimation = Estimation::findOrFail($id);
            
            $data = $request->validate([
                'predicted_effort' => 'nullable|numeric|min:0',
                'confidence_score' => 'nullable|numeric|between:0,1',
                'complexity' => 'nullable|numeric|min:1|max:10',
                'priority' => 'nullable|string|in:low,medium,high',
                'team_size' => 'nullable|integer|min:1',
            ]);
            
            $estimation->update($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Estimation mise à jour avec succès',
                'data' => $estimation
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprimer une estimation
     */
    public function destroy($id): JsonResponse
    {
        try {
            $estimation = Estimation::findOrFail($id);
            $estimation->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Estimation supprimée avec succès'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}