<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController; // <-- AJOUTER

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    
    Route::apiResource('projects', ProjectController::class);
    
    // Routes pour les tâches (imbriquées sous les projets)
    Route::get('projects/{project_id}/tasks', [TaskController::class, 'index']);
    Route::post('projects/{project_id}/tasks', [TaskController::class, 'store']);
    Route::put('projects/{project_id}/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('projects/{project_id}/tasks/{task}', [TaskController::class, 'destroy']);
});