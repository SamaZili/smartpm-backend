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
        // 1. Vérifier le projet
        $project = $request->user()->projects()->find($project_id);
        if (!$project) {
            return response()->json([
                'success' => false,
                'error_code' => 'PROJECT_NOT_FOUND',
                'message' => 'Le projet spécifié est introuvable.',
            ], Response::HTTP_NOT_FOUND);
        }

        // 2. Vérifier la tâche
        $task = $this->taskRepository->findByIdAndProject($task_id, $project);
        if (!$task) {
            return response()->json([
                'success' => false,
                'error_code' => 'TASK_NOT_FOUND',
                'message' => 'La tâche spécifiée est introuvable.',
            ], Response::HTTP_NOT_FOUND);
        }

        // 3. Vérifier les champs requis
        if (empty($task->name) && empty($task->description)) {
            return response()->json([
                'success' => false,
                'error_code' => 'TASK_FIELDS_REQUIRED',
                'message' => 'La tâche doit avoir un nom ou une description.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // 4. Appel au service FastAPI
        try {
            $response = Http::timeout(5)->post('http://127.0.0.1:8001/predict', [
                'title'       => $task->name,
                'description' => $task->description,
            ]);
        } catch (\Exception $e) {
            Log::error('FastAPI connexion échouée : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error_code' => 'IA_SERVICE_UNAVAILABLE',
                'message' => 'Le service d\'IA est indisponible.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (!$response->successful()) {
            Log::error('FastAPI a répondu une erreur : ' . $response->status() . ' - ' . $response->body());
            return response()->json([
                'success' => false,
                'error_code' => 'IA_SERVICE_ERROR',
                'message' => 'Erreur interne du service d\'IA.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $data = $response->json();

        if (!isset($data['predicted_effort_hours'])) {
            Log::error('Réponse FastAPI inattendue : ' . json_encode($data));
            return response()->json([
                'success' => false,
                'error_code' => 'INVALID_IA_RESPONSE',
                'message' => 'La réponse de l\'IA est invalide.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // 5. Sauvegarde en base de données
        try {
            $estimation = $this->estimationRepository->createEstimation(
                $task,
                (float) $data['predicted_effort_hours'],
                0.85 // Score de confiance par défaut (ou à récupérer de l'IA si disponible)
            );
        } catch (\Exception $e) {
            Log::error('Erreur sauvegarde estimation : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error_code' => 'DATABASE_SAVE_ERROR',
                'message' => 'Échec de la sauvegarde de l\'estimation.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // 6. Succès : Retour au format attendu par le frontend (ApiResponse)
        return response()->json([
            'success' => true,
            'message_code' => 'ESTIMATION_SUCCESS',
            'data' => $estimation,
        ], Response::HTTP_CREATED);
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
                'error_code' => 'ESTIMATION_NOT_FOUND',
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
            Log::error('Erreur récupération estimations : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error_code' => 'ESTIMATIONS_FETCH_FAILED',
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
                'data' => $estimation
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'ESTIMATION_NOT_FOUND',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour estimation : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error_code' => 'ESTIMATION_UPDATE_FAILED',
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
                'message_code' => 'ESTIMATION_DELETED',
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'ESTIMATION_NOT_FOUND',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error('Erreur suppression estimation : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error_code' => 'ESTIMATION_DELETE_FAILED',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}cd C:\Users\DELL\smartpm-backend
git add app/Http/Controllers/EstimationController.php
git commit -m "fix: standardize EstimationController responses to match frontend ApiResponse contract (success/data wrapper)"
git push origin feature/nlp-laravel-integration