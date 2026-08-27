<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Models\User;
use App\Models\Task;
use Illuminate\Support\Collection;

class NotificationRepository
{
    public function create(User $recipient, Task $task, string $type, string $message): Notification
    {
        return Notification::create([
            'user_id' => $recipient->id,
            'task_id' => $task->id,
            'type' => $type,
            'message' => $message,
        ]);
    }

    public function getForUser(User $user): Collection
    {
        return Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get();
    }

    public function unreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)->where('is_read', false)->count();
    }

    public function markRead(Notification $notification): void
    {
        $notification->update(['is_read' => true]);
    }

    public function markAllRead(User $user): void
    {
        Notification::where('user_id', $user->id)->where('is_read', false)->update(['is_read' => true]);
    }
}