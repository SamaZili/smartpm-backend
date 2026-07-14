<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    // Champs modifiables en masse
    protected $fillable = ['name', 'description', 'status', 'user_id'];

    // Un projet appartient à un utilisateur (Chef de projet)
    public function user() {
        return $this->belongsTo(User::class);
    }

    // Un projet contient plusieurs tâches (préparation pour le Module 3)
    public function tasks() {
        return $this->hasMany(Task::class);
    }
}