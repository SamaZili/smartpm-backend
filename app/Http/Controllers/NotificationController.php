<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Repositories\NotificationRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    protected NotificationRepository $notificationRepository;

    public function __construct(NotificationRepository $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $this->notificationRepository->getForUser($request->user()),
                'unread_count' => $this->notificationRepository->unreadCount($request->user()),
            ],
        ]);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'error_code' => 'FORBIDDEN'], Response::HTTP_FORBIDDEN);
        }

        $this->notificationRepository->markRead($notification);

        return response()->json(['success' => true, 'data' => ['message_code' => 'NOTIFICATION_READ']]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->notificationRepository->markAllRead($request->user());

        return response()->json(['success' => true, 'data' => ['message_code' => 'ALL_NOTIFICATIONS_READ']]);
    }
}