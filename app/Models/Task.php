<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'type', 'complexity', 'size', 'status', 'project_id'];

    // Une tâche appartient à un projet
    public function project() {
        return $this->belongsTo(Project::class);
    }
    
    // Préparation pour le Module 4 (Estimations IA)
    public function estimations() {
        return $this->hasMany(Estimation::class);
    }
}