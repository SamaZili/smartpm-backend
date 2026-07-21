<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vérification de ton email</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; padding: 30px; margin: 0;">
    <div style="max-width:500px; margin:auto; background:white; border-radius:8px; padding:30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="color:#2563eb; margin-top: 0;">Bienvenue sur SmartPM 👋</h2>
        <p>Bonjour {{ $name }},</p>
        <p>Merci de t'être inscrit ! Pour finaliser la création de ton compte, veuillez vérifier ton adresse email en cliquant sur le bouton ci-dessous :</p>
        
        <p style="text-align:center; margin: 30px 0;">
            <a href="{{ $url }}" style="background:#2563eb; color:white; padding:12px 24px; border-radius:6px; text-decoration:none; font-weight:bold; display:inline-block;">
                Vérifier mon email
            </a>
        </p>
        
        <p style="color:#888; font-size:12px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
            Si tu n'as pas créé de compte sur SmartPM, tu peux ignorer cet email en toute sécurité.
        </p>
    </div>
</body>
</html>