<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estimation extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'predicted_effort',
        'confidence_score',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}