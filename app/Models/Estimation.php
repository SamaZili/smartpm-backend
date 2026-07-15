<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Estimation extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'predicted_effort',
        'confidence_score',
        'estimated_hours',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}