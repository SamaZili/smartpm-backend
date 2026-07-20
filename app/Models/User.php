<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Champs autorisés pour la création/mise à jour en masse
    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'email_verified_at',
        'email_verification_token',
    ];

    // Champs cachés (ne pas renvoyer dans les réponses JSON)
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_token',
    ];

    // Casts des types de données
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ==========================================
    // ✅ AJOUT CRUCIAL : La relation avec les projets
    // ==========================================
    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}