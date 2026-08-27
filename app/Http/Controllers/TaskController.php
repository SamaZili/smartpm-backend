<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Mail\TaskAssignedMail;
use App\Mail\TaskStatusChangedMail;
use App\Repositories\TaskRepository;
use App\Repositories\NotificationRepository;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    protected TaskRepository $taskRepository;
    protected NotificationRepository $notificationRepository;

    public function __construct(TaskRepository $taskRepository, NotificationRepository $notificationRepository)
    {
        $this->taskRepository = $taskRepository;
        $this->notificationRepository = $notificationRepository;
    }

    public function index(Request $request, $project_id): JsonResponse
    {
        $project = $request->user()->projects()->find($project_id);

        if (!$project) {
            return response()->json(['success' => false, 'error_code' => 'PROJECT_NOT_FOUND'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['success' => true, 'data' => $this->taskRepository->getAllForProject($project)]);
    }

    public function store(StoreTaskRequest $request, $project_id): JsonResponse
    {
        $project = $request->user()->projects()->find($project_id);

        if (!$project) {
            return response()->json(['success' => false, 'error_code' => 'PROJECT_NOT_FOUND'], Response::HTTP_NOT_FOUND);
        }

        try {
            $task = $this->taskRepository->create($request->validated(), $project);

            if ($task->assigned_to) {
                $this->notifyAssignedDeveloper($task);
            }

            return response()->json(['success' => true, 'data' => $task->load('assignedTo')], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Erreur création tâche: ' . $e->getMessage());
            return response()->json(['success' => false, 'error_code' => 'TASK_CREATION_FAILED'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdateTaskRequest $request, $project_id, Task $task): JsonResponse
    {
        $project = $request->user()->projects()->find($project_id);

        if (!$project || $task->project_id !== $project->id) {
            return response()->json(['success' => false, 'error_code' => 'UNAUTHORIZED'], Response::HTTP_FORBIDDEN);
        }

        try {
            $oldAssignedTo = $task->assigned_to;
            $updatedTask = $this->taskRepository->update($task, $request->validated());

            if ($updatedTask->assigned_to && $updatedTask->assigned_to !== $oldAssignedTo) {
                $this->notifyAssignedDeveloper($updatedTask);
            }

            return response()->json(['success' => true, 'data' => $updatedTask->load('assignedTo')]);
        } catch (\Exception $e) {
            Log::error('Erreur update tâche: ' . $e->getMessage());
            return response()->json(['success' => false, 'error_code' => 'TASK_UPDATE_FAILED'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Request $request, $project_id, Task $task): JsonResponse
    {
        $project = $request->user()->projects()->find($project_id);

        if (!$project || $task->project_id !== $project->id) {
            return response()->json(['success' => false, 'error_code' => 'UNAUTHORIZED'], Response::HTTP_FORBIDDEN);
        }

        $this->taskRepository->delete($task);

        return response()->json(['success' => true, 'data' => ['message_code' => 'TASK_DELETED']]);
    }

    public function myTasks(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->taskRepository->getAssignedToUser($request->user()),
        ]);
    }

    /** ✅ Le développeur change le statut → le chef de projet est notifié (app + email) */
    public function updateAssignmentStatus(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'assignment_status' => 'required|in:pending,accepted,in_progress,completed',
        ]);

        if ($task->assigned_to !== $request->user()->id) {
            return response()->json(['success' => false, 'error_code' => 'NOT_ASSIGNED_TO_YOU'], Response::HTTP_FORBIDDEN);
        }

        $task->assignment_status = $request->assignment_status;
        $task->save();

        $this->notifyChefDeProjet($task, $request->user(), $request->assignment_status);

        return response()->json(['success' => true, 'data' => $task->load('assignedTo')]);
    }

    /** 📥 Notification au développeur quand une tâche lui est assignée */
    protected function notifyAssignedDeveloper(Task $task): void
    {
        try {
            $developer = $task->assignedTo;
            if ($developer) {
                $this->notificationRepository->create(
                    $developer,
                    $task,
                    'task_assigned',
                    "📥 Nouvelle tâche assignée : « {$task->name} »."
                );
                Mail::to($developer->email)->send(new TaskAssignedMail($developer, $task));
            }
        } catch (\Throwable $e) {
            Log::warning('Notification assignation non envoyée: ' . $e->getMessage());
        }
    }

    /** 🔔 Notification au chef de projet quand le développeur agit */
    protected function notifyChefDeProjet(Task $task, $developer, string $status): void
    {
        try {
            $chef = $task->project?->user;
            if (!$chef) return;

            $messages = [
                'accepted' => "✅ {$developer->name} a accepté la tâche « {$task->name} ».",
                'in_progress' => "🔄 {$developer->name} a commencé la tâche « {$task->name} ».",
                'completed' => "🎉 {$developer->name} a terminé la tâche « {$task->name} ».",
            ];

            $this->notificationRepository->create(
                $chef,
                $task,
                'task_' . $status,
                $messages[$status] ?? "Mise à jour de la tâche « {$task->name} » par {$developer->name}."
            );

            Mail::to($chef->email)->send(new TaskStatusChangedMail($chef, $task, $developer, $status));
        } catch (\Throwable $e) {
            Log::warning('Notification chef de projet non envoyée: ' . $e->getMessage());
        }
    }
}