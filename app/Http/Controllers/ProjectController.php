<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Repositories\ProjectRepository;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    protected ProjectRepository $projectRepository;

    public function __construct(ProjectRepository $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    public function index(Request $request)
    {
        $projects = $this->projectRepository->getAllForUser($request->user());
        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    // ✅ CORRECTION : Format de réponse standardisé
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectRepository->create($request->validated(), $request->user());
        return response()->json([
            'success' => true,
            'data' => $project
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $userProject = $this->projectRepository->findByIdAndUser($project->id, $request->user());
        
        if (!$userProject) {
            return response()->json([
                'success' => false,
                'error_code' => 'PROJECT_NOT_FOUND'
            ], Response::HTTP_FORBIDDEN);
        }
        
        return response()->json([
            'success' => true,
            'data' => $userProject
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $userProject = $this->projectRepository->findByIdAndUser($project->id, $request->user());
        
        if (!$userProject) {
            return response()->json([
                'success' => false,
                'error_code' => 'PROJECT_NOT_FOUND'
            ], Response::HTTP_FORBIDDEN);
        }

        $updatedProject = $this->projectRepository->update($userProject, $request->validated());
        return response()->json([
            'success' => true,
            'data' => $updatedProject
        ]);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $userProject = $this->projectRepository->findByIdAndUser($project->id, $request->user());
        
        if (!$userProject) {
            return response()->json([
                'success' => false,
                'error_code' => 'PROJECT_NOT_FOUND'
            ], Response::HTTP_FORBIDDEN);
        }

        $this->projectRepository->delete($userProject);
        return response()->json([
            'success' => true,
            'message' => 'Projet supprimé avec succès.'
        ]);
    }
}