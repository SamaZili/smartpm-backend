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

    public function predict(Request $request, $project_id, $task_id): JsonResponse
    {
        $project = $request->user()->projects()->find($project_id);
        if (!$project) {
            return response()->json(['success' => false, 'error_code' => 'PROJECT_NOT_FOUND'], Response::HTTP_NOT_FOUND);
        }

        $task = $this->taskRepository->findByIdAndProject($task_id, $project);
        if (!$task) {
            return response()->json(['success' => false, 'error_code' => 'TASK_NOT_FOUND'], Response::HTTP_NOT_FOUND);
        }

        if (empty($task->name) && empty($task->description)) {
            return response()->json(['success' => false, 'error_code' => 'TASK_FIELDS_REQUIRED'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $response = Http::timeout(5)->post('http://127.0.0.1:8001/predict', [
                'title'       => $task->name,
                'description' => $task->description ?? '',
            ]);
        } catch (\Exception $e) {
            Log::error('FastAPI connection failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error_code' => 'IA_SERVICE_UNAVAILABLE'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (!$response->successful()) {
            Log::error('FastAPI error: ' . $response->status() . ' - ' . $response->body());
            return response()->json(['success' => false, 'error_code' => 'IA_SERVICE_ERROR'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $data = $response->json();

        if (!isset($data['predicted_effort_hours'])) {
            Log::error('Unexpected FastAPI response: ' . json_encode($data));
            return response()->json(['success' => false, 'error_code' => 'INVALID_IA_RESPONSE'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $estimation = $this->estimationRepository->createEstimation(
                $task,
                (float) $data['predicted_effort_hours'],
                $data['confidence_score'] ?? 0.85
            );
        } catch (\Exception $e) {
            Log::error('Error saving estimation: ' . $e->getMessage());
            return response()->json(['success' => false, 'error_code' => 'DATABASE_SAVE_ERROR'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'success' => true,
            'data' => $estimation,
        ], Response::HTTP_CREATED);
    }

    public function index($projectId): JsonResponse
    {
        try {
            $estimations = Estimation::where('project_id', $projectId)
                ->with('task')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['success' => true, 'data' => $estimations], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Error fetching estimations: ' . $e->getMessage());
            return response()->json(['success' => false, 'error_code' => 'ESTIMATIONS_FETCH_FAILED'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}