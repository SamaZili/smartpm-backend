<?php

namespace App\Repositories;

use App\Models\Project;
use Illuminate\Foundation\Auth\User;

class ProjectRepository
{
    // Récupérer tous les projets d'un utilisateur
    public function getAllForUser(User $user)
    {
        return $user->projects()->latest()->get();
    }

    // Créer un nouveau projet pour un utilisateur
    public function create(array $data, User $user): Project
    {
        return $user->projects()->create($data);
    }

    // Trouver un projet par son ID (et vérifier qu'il appartient à l'utilisateur)
    public function findByIdAndUser(int $id, User $user): ?Project
    {
        return $user->projects()->find($id);
    }

    // Mettre à jour un projet
    public function update(Project $project, array $data): Project
    {
        $project->update($data);
        return $project;
    }

    // Supprimer un projet
    public function delete(Project $project): bool
    {
        return $project->delete();
    }
}