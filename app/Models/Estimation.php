<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estimation extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'project_id',
        'predicted_effort',
        'confidence_score',
    ];

    // Relation avec la tâche
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // Relation avec le projet
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}