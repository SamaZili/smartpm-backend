<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Repositories\ProjectRepository;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    // Injection de dépendance du Repository
    protected ProjectRepository $projectRepository;

    public function __construct(ProjectRepository $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    // F2.4 : Liste des projets
    public function index(Request $request)
    {
        $projects = $this->projectRepository->getAllForUser($request->user());
        return response()->json($projects);
    }

    // F2.1 : Ajouter un projet (La validation est faite automatiquement par StoreProjectRequest)
    public function store(StoreProjectRequest $request)
    {
        $project = $this->projectRepository->create($request->validated(), $request->user());
        return response()->json($project, 201);
    }

    // F2.5 : Détail d'un projet
    public function show(Request $request, Project $project)
    {
        $userProject = $this->projectRepository->findByIdAndUser($project->id, $request->user());
        
        if (!$userProject) {
            return response()->json(['message' => 'Non autorisé ou projet non trouvé'], 403);
        }
        
        return response()->json($userProject);
    }

    // F2.2 : Modifier un projet
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $userProject = $this->projectRepository->findByIdAndUser($project->id, $request->user());
        
        if (!$userProject) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $updatedProject = $this->projectRepository->update($userProject, $request->validated());
        return response()->json($updatedProject);
    }

    // F2.3 : Supprimer un projet
    public function destroy(Request $request, Project $project)
    {
        $userProject = $this->projectRepository->findByIdAndUser($project->id, $request->user());
        
        if (!$userProject) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $this->projectRepository->delete($userProject);
        return response()->json(['message' => 'Projet supprimé avec succès.']);
    }
}