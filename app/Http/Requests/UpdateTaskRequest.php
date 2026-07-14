<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'type' => 'sometimes|string|max:255',
            'complexity' => 'sometimes|string|max:255',
            'size' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:a_faire,en_cours,terminee',
            // Champs Desharnais
            'transactions' => 'sometimes|integer|min:0',
            'entities' => 'sometimes|integer|min:0',
            'team_exp' => 'sometimes|integer|min:0',
            'manager_exp' => 'sometimes|integer|min:0',
        ];
    }
}