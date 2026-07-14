<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La sécurité d'accès est gérée par les routes (auth:sanctum)
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|string|max:255',
            'complexity' => 'sometimes|string|max:255',
            'size' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:a_faire,en_cours,terminee',
            // Champs Desharnais pour l'IA (Module 4)
            'transactions' => 'sometimes|integer|min:0',
            'entities' => 'sometimes|integer|min:0',
            'team_exp' => 'sometimes|integer|min:0',
            'manager_exp' => 'sometimes|integer|min:0',
        ];
    }
}