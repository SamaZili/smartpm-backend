<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectId = $this->route('project'); // Récupère l'ID du projet depuis l'URL
        
        return [
            'name' => 'sometimes|string|max:255|unique:projects,name,' . $projectId,
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:en_cours,termine',
        ];
    }
}