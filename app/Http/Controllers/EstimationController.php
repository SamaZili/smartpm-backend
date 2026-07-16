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

        if (empty($task->name) && empty($task->description)) {
            return response()->json([
                'error_code' => 'TASK_FIELDS_REQUIRED',
                'message' => "Veuillez d'abord remplir le nom et la description de la tâche."
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
                'message' => "Impossible de contacter le service IA."
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (!$response->successful()) {
            Log::error('FastAPI a répondu une erreur : ' . $response->status() . ' - ' . $response->body());
            return response()->json([
                'error_code' => 'IA_SERVICE_ERROR',
                'message' => "Erreur du service d'estimation IA."
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $data = $response->json();

        if (!isset($data['predicted_effort_hours'])) {
            Log::error('Réponse FastAPI inattendue : ' . json_encode($data));
            return response()->json([
                'error_code' => 'INVALID_IA_RESPONSE',
                'message' => "Réponse invalide du service IA."
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
                'message' => 'Erreur interne lors de la sauvegarde.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message_code' => 'ESTIMATION_SUCCESS', // Ajout d'un code de succès pour être cohérent
            'message' => 'Estimation réalisée avec succès via IA.',
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
                'message_code' => 'ESTIMATIONS_FETCHED',
                'data' => $estimations
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Erreur récupération estimations : ' . $e->getMessage());
            return response()->json([
                'error_code' => 'ESTIMATIONS_FETCH_FAILED',
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
            ]);
            
            $estimation->update($data);
            
            return response()->json([
                'message_code' => 'ESTIMATION_UPDATED',
                'message' => 'Estimation mise à jour avec succès',
                'data' => $estimation
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error_code' => 'ESTIMATION_NOT_FOUND',
                'message' => 'Estimation non trouvée'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour estimation : ' . $e->getMessage());
            return response()->json([
                'error_code' => 'ESTIMATION_UPDATE_FAILED',
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
                'message_code' => 'ESTIMATION_DELETED',
                'message' => 'Estimation supprimée avec succès'
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error_code' => 'ESTIMATION_NOT_FOUND',
                'message' => 'Estimation non trouvée'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error('Erreur suppression estimation : ' . $e->getMessage());
            return response()->json([
                'error_code' => 'ESTIMATION_DELETE_FAILED',
                'message' => 'Erreur lors de la suppression'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}