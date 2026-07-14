<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    // Les champs autorisés à être modifiés en masse
    protected $fillable = [
        'name',
        'description',
        'type',
        'complexity',
        'size',
        'status',
        'project_id',
        'transactions',    // <-- AJOUTÉ pour l'IA
        'entities',        // <-- AJOUTÉ pour l'IA
        'team_exp',        // <-- AJOUTÉ pour l'IA
        'manager_exp',     // <-- AJOUTÉ pour l'IA
    ];

    public function project() {
        return $this->belongsTo(Project::class);
    }
    
    public function estimations() {
        return $this->hasMany(Estimation::class);
    }
}