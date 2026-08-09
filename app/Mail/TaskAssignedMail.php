<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaskAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $developer;
    public Task $task;

    public function __construct(User $developer, Task $task)
    {
        $this->developer = $developer;
        $this->task = $task;
    }

    public function build()
    {
        return $this->subject('SmartPM - Nouvelle tâche assignée : ' . $this->task->name)
                    ->html($this->buildHtml());
    }

    protected function buildHtml(): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head><meta charset='UTF-8'></head>
        <body style='font-family: Arial, sans-serif; background: #f8fafc; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px;'>
                <h2 style='color: #10b981;'>🏗️ SmartPM - Nouvelle assignation</h2>
                <p>Bonjour {$this->developer->name},</p>
                <p>Une nouvelle tâche vous a été assignée :</p>
                <div style='background: #f0fdf4; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0;'>
                    <strong>✅ {$this->task->name}</strong>
                    <p style='color: #64748b; margin-top: 8px;'>{$this->task->description}</p>
                </div>
                <p>Connectez-vous à SmartPM pour consulter votre dashboard <strong>Mes Tâches</strong>.</p>
            </div>
        </body>
        </html>
        ";
    }
}