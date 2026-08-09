<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use Symfony\Component\HttpFoundation\Response;

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
            $user = $this->userRepository->create($request->validated());
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => [
                    'message_code' => 'REGISTRATION_SUCCESS',
                    'user' => $user,
                    'token' => $token,
                ],
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            Log::error('Erreur inscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error_code' => 'SERVER_ERROR',
                'message_code' => 'REGISTRATION_FAILED',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->userRepository->findByEmail($request->email);

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'error_code' => 'INVALID_CREDENTIALS',
                'message_code' => 'INVALID_CREDENTIALS',
            ], Response::HTTP_UNAUTHORIZED);
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
            'data' => [
                'message_code' => 'LOGOUT_SUCCESS',
            ],
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
        try {
            $user = $this->userRepository->updateProfile(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'message_code' => 'PROFILE_UPDATED',
                    'user' => $user,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur update profile: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error_code' => 'PROFILE_UPDATE_FAILED',
                'message_code' => 'PROFILE_UPDATE_FAILED',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $request->validate(['email' => 'required|email|exists:users,email']);
            $user = $this->userRepository->findByEmail($request->email);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'USER_NOT_FOUND',
                    'message_code' => 'USER_NOT_FOUND',
                ], Response::HTTP_NOT_FOUND);
            }

            $token = $this->userRepository->generateResetToken($user);
            $resetLink = config('app.frontend_url', 'http://localhost:5173') . "/reset-password?token={$token}";

            try {
                Mail::to($user->email)->send(new ResetPasswordMail($user, $resetLink));
            } catch (\Throwable $e) {
                Log::warning('Email reset non envoyé: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'message_code' => 'RESET_EMAIL_SENT',
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'VALIDATION_FAILED',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Erreur forgot password: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error_code' => 'SERVER_ERROR',
                'message_code' => 'FORGOT_PASSWORD_FAILED',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $user = $this->userRepository->findByResetToken($request->token);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'INVALID_OR_EXPIRED_TOKEN',
                    'message_code' => 'INVALID_OR_EXPIRED_TOKEN',
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->userRepository->resetPassword($user, $request->password);

            return response()->json([
                'success' => true,
                'data' => [
                    'message_code' => 'PASSWORD_RESET_SUCCESS',
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur reset password: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error_code' => 'SERVER_ERROR',
                'message_code' => 'PASSWORD_RESET_FAILED',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}