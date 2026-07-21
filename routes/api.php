<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\EstimationController;

// ==========================================
// ROUTES PUBLIQUES (sans authentification)
// ==========================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// ==========================================
// ROUTES PROTÉGÉES (avec authentification)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Projets
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::put('/projects/{id}', [ProjectController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);

    // Tâches
    Route::get('/projects/{project_id}/tasks', [TaskController::class, 'index']);
    Route::post('/projects/{project_id}/tasks', [TaskController::class, 'store']);
    Route::put('/projects/{project_id}/tasks/{task_id}', [TaskController::class, 'update']);
    Route::delete('/projects/{project_id}/tasks/{task_id}', [TaskController::class, 'destroy']);

    // Estimation IA
    Route::post('/projects/{project_id}/tasks/{task_id}/estimate', [EstimationController::class, 'predict']);
});