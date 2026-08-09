<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $resetLink;

    public function __construct(User $user, string $resetLink)
    {
        $this->user = $user;
        $this->resetLink = $resetLink;
    }

    public function build()
    {
        return $this->subject('Réinitialisation de votre mot de passe - SmartPM')
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
                <h2 style='color: #10b981;'>🏗️ SmartPM - Réinitialisation</h2>
                <p>Bonjour {$this->user->name},</p>
                <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$this->resetLink}' style='background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>
                        Réinitialiser mon mot de passe
                    </a>
                </p>
                <p style='color: #64748b; font-size: 12px;'>Ce lien expire dans 60 minutes.</p>
                <p style='color: #64748b; font-size: 12px;'>Si vous n'avez pas fait cette demande, ignorez cet email.</p>
            </div>
        </body>
        </html>
        ";
    }
}