<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Les attributs qui sont assignables en masse.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'email_verified_at',
        'email_verification_token', // <-- CETTE LIGNE DOIT ÊTRE PRÉSENTE
 // <-- Ajouté pour le cahier des charges
    ];

    /**
     * Les attributs qui doivent être cachés pour la sérialisation.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Les attributs qui doivent être convertis (cast).
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relation : Un chef de projet gère plusieurs projets (Module 2).
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}