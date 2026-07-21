<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        try {
            // 1. Validation stricte
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'type' => 'required|string|in:chef_de_projet,developer',
            ]);

            // 2. Création de l'utilisateur (maintenant que la colonne 'type' est corrigée, ça passera)
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'type' => $validated['type'],
                'email_verified_at' => now(), // On vérifie automatiquement pour éviter les blocages
            ]);

            // 3. Génération du token
            $token = $user->createToken('auth_token')->plainTextToken;

            // 4. Réponse JSON propre
            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Erreur inscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Déconnecté avec succès']);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ['user' => $request->user()],
        ]);
    }
    public function forgotPassword(Request $request): JsonResponse
{
    try {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();
        
        // Générer un token unique
        $token = Str::random(60);
        
        // Sauvegarder le token avec une date d'expiration (60 min)
        $user->reset_password_token = $token;
        $user->reset_password_token_created_at = now();
        $user->save();

        // Créer le lien de réinitialisation
        $resetLink = "http://localhost:5173/reset-password?token={$token}";

        // Envoyer l'email
        Mail::to($user->email)->send(new ResetPasswordMail($user, $resetLink));

        return response()->json([
            'success' => true,
            'message' => 'Un lien de réinitialisation a été envoyé à votre email.'
        ]);
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Email invalide ou non trouvé.'
        ], 422);
    } catch (\Exception $e) {
        \Log::error('Erreur forgot password: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur: ' . $e->getMessage()
        ], 500);
    }
}
}