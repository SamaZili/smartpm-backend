<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    // Autoriser tout le monde à tenter cette requête (la sécurité est gérée par Sanctum dans les routes)
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:projects,name',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:en_cours,termine',
        ];
    }
}