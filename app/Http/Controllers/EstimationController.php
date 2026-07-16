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
        $project = $request->user()->projects()->find($project_id);
        if (!$project) {
            return response()->json([
                'error_code' => 'PROJECT_NOT_FOUND',
            ], Response::HTTP_NOT_FOUND);
        }

        $task = $this->taskRepository->findByIdAndProject($task_id, $project);
        if (!$task) {
            return response()->json([
                'error_code' => 'TASK_NOT_FOUND',
            ], Response::HTTP_NOT_FOUND);
        }

        if (empty($task->name) && empty($task->description)) {
            return response()->json([
                'error_code' => 'TASK_FIELDS_REQUIRED',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $response = Http::timeout(5)->post('http://127.0.0.1:8001/predict', [
                'title'       => $task->name,
                'description' => $task->description,
            ]);
        } catch (\Exception $e) {
            Log::error('FastAPI connexion échouée : ' . $e->getMessage());
            return response()->json([
                'error_code' => 'IA_SERVICE_UNAVAILABLE',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (!$response->successful()) {
            Log::error('FastAPI a répondu une erreur : ' . $response->status() . ' - ' . $response->body());
            return response()->json([
                'error_code' => 'IA_SERVICE_ERROR',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $data = $response->json();

        if (!isset($data['predicted_effort_hours'])) {
            Log::error('Réponse FastAPI inattendue : ' . json_encode($data));
            return response()->json([
                'error_code' => 'INVALID_IA_RESPONSE',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

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
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message_code' => 'ESTIMATION_SUCCESS',
            'estimation' => $estimation,
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
                'message_code' => 'ESTIMATION_FOUND',
                'data' => $estimation
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
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
                'message_code' => 'ESTIMATIONS_FETCHED',
                'data' => $estimations
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Erreur récupération estimations : ' . $e->getMessage());
            return response()->json([
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
                'message_code' => 'ESTIMATION_UPDATED',
                'data' => $estimation
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error_code' => 'ESTIMATION_NOT_FOUND',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour estimation : ' . $e->getMessage());
            return response()->json([
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
                'message_code' => 'ESTIMATION_DELETED',
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error_code' => 'ESTIMATION_NOT_FOUND',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error('Erreur suppression estimation : ' . $e->getMessage());
            return response()->json([
                'error_code' => 'ESTIMATION_DELETE_FAILED',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}