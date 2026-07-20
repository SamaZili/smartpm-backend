<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\EstimationController;
use App\Http\Controllers\DashboardController;

// ==========================================
// Routes Publiques (Authentification)
// ==========================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/email/verify', [AuthController::class, 'verifyEmail']);

// ==========================================
// Routes Protégées (Nécessite un Token Sanctum)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // --- Auth ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    
    // --- Projets ---
    Route::apiResource('projects', ProjectController::class);
    
    // --- Tâches ---
    Route::get('projects/{project_id}/tasks', [TaskController::class, 'index']);
    Route::post('projects/{project_id}/tasks', [TaskController::class, 'store']);
    Route::put('projects/{project_id}/tasks/{task_id}', [TaskController::class, 'update']);
    Route::delete('projects/{project_id}/tasks/{task_id}', [TaskController::class, 'destroy']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

    // --- Estimation IA (Module 4) ---
    // Les paramètres {project_id} et {task_id} correspondent aux arguments du contrôleur
    Route::post('projects/{project_id}/tasks/{task_id}/estimate', [EstimationController::class, 'predict']);
    Route::get('projects/{project_id}/tasks/{task_id}/estimations', [EstimationController::class, 'history']);
    
    // --- Dashboard (Module 5) ---
    Route::get('dashboard', [DashboardController::class, 'index']);
});