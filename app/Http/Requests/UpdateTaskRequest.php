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
            'description' => 'nullable|string',
            'status' => 'sometimes|in:a_faire,en_cours,terminee',
            'complexity' => 'sometimes|string|max:255',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
            'assignment_status' => 'sometimes|nullable|in:pending,accepted,in_progress,completed',
            'due_date' => 'sometimes|nullable|date',
            'priority' => 'sometimes|nullable|in:low,medium,high,urgent',
        ];
    }
}