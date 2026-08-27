<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaskStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $chef;
    public Task $task;
    public User $developer;
    public string $status;

    public function __construct(User $chef, Task $task, User $developer, string $status)
    {
        $this->chef = $chef;
        $this->task = $task;
        $this->developer = $developer;
        $this->status = $status;
    }

    public function build()
    {
        $labels = [
            'accepted' => 'a accepté',
            'in_progress' => 'a commencé',
            'completed' => 'a terminé',
        ];
        $action = $labels[$this->status] ?? 'a mis à jour';

        return $this->subject("SmartPM - {$this->developer->name} {$action} la tâche : {$this->task->name}")
                    ->html($this->buildHtml($action));
    }

    protected function buildHtml(string $action): string
    {
        $estimation = $this->task->estimation
            ? $this->task->estimation->predicted_effort . ' heures'
            : 'Non estimée';

        return "
        <!DOCTYPE html>
        <html>
        <head><meta charset='UTF-8'></head>
        <body style='font-family: Arial, sans-serif; background: #f8fafc; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px;'>
                <h2 style='color: #10b981;'>🏗️ SmartPM - Mise à jour de tâche</h2>
                <p>Bonjour {$this->chef->name},</p>
                <p><strong>{$this->developer->name}</strong> {$action} la tâche :</p>
                <div style='background: #f0fdf4; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0;'>
                    <strong>✅ {$this->task->name}</strong>
                    <p style='color: #64748b; margin-top: 8px;'>📊 Effort estimé par IA : {$estimation}</p>
                </div>
                <p>Connectez-vous à SmartPM pour voir le détail.</p>
            </div>
        </body>
        </html>
        ";
    }
}