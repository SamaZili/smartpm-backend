<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\VerifyEmailMail;

class AuthController extends Controller
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // 1. Créer l'utilisateur
            $user = $this->userRepository->create($request->validated());

            // 2. Tenter d'envoyer l'email (sans bloquer l'inscription en cas d'échec)
            try {
                Mail::to($user->email)->send(new VerifyEmailMail($user));
            } catch (\Throwable $e) {
                Log::warning('Échec envoi email de vérification pour ' . $user->email . ' : ' . $e->getMessage());
                // On continue, l'inscription reste un succès
            }

            // 3. Générer le token
            $token = $user->createToken('auth_token')->plainTextToken;

            // 4. Retourner une réponse JSON propre
            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie',
                'data' => [
                    'message_code' => 'REGISTRATION_SUCCESS',
                    'user' => $user,
                    'token' => $token,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erreur critique lors de l\'inscription : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'inscription.',
            ], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->userRepository->findByEmail($request->email);

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'error_code' => 'INVALID_CREDENTIALS',
                'message' => 'Les identifiants fournis sont incorrects.',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'message_code' => 'LOGIN_SUCCESS',
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'data' => ['message_code' => 'LOGOUT_SUCCESS'],
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'message_code' => 'PROFILE_FETCHED',
                'user' => $request->user(),
            ],
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->userRepository->updateProfile($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'message_code' => 'PROFILE_UPDATED',
                'user' => $user,
            ],
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $token = $request->query('token');
        $user = \App\Models\User::where('email_verification_token', $token)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'error_code' => 'INVALID_VERIFICATION_TOKEN',
                'message' => 'Lien de vérification invalide ou expiré.',
            ], 404);
        }

        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->save();

        return response()->json([
            'success' => true,
            'data' => ['message_code' => 'EMAIL_VERIFIED_SUCCESS'],
            'message' => 'Email vérifié avec succès ✅',
        ]);
    }
}