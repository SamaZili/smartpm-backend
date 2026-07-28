<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header {
            background: #10b981;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 40px 30px;
            color: #374151;
        }
        .button {
            display: inline-block;
            padding: 14px 32px;
            background-color: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏗️ SmartPM</h1>
            <p style="margin: 10px 0 0 0; font-size: 14px;">Réinitialisation de mot de passe</p>
        </div>
        
        <div class="content">
            <p>Bonjour <strong>{{ $user->name }}</strong>,</p>
            
            <p>Vous avez demandé à réinitialiser votre mot de passe pour votre compte SmartPM.</p>
            
            <p style="text-align: center;">
                <a href="{{ $resetLink }}" class="button">
                    🔐 Réinitialiser mon mot de passe
                </a>
            </p>
            
            <p style="font-size: 14px; color: #6b7280; margin-top: 30px;">
                ⚠️ Ce lien est valable pendant 60 minutes.<br>
                Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet email.
            </p>
        </div>
        
        <div class="footer">
            © 2026 SmartPM. Tous droits réservés.
        </div>
    </div>
</body>
</html>