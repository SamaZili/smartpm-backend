<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
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

    public function register(RegisterRequest $request)
    {
        $user = $this->userRepository->create($request->validated());

        try {
            Mail::to($user->email)->send(new VerifyEmailMail($user));
        } catch (\Throwable $e) {
            Log::error('Échec envoi email de vérification pour ' . $user->email . ' : ' . $e->getMessage());
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'message_code' => 'REGISTRATION_SUCCESS',
                'user' => $user,
                'token' => $token,
            ],
        ], 201);
    }

    public function login(LoginRequest $request)
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

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'data' => ['message_code' => 'LOGOUT_SUCCESS'],
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'message_code' => 'PROFILE_FETCHED',
                'user' => $request->user(),
            ],
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request)
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

    public function verifyEmail(Request $request)
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