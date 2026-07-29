<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projects = Project::where('user_id', $request->user()->id)->latest()->get();
        
        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $project = Project::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'en_cours',
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $project
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        // Trouver le projet
        $project = Project::where('user_id', $request->user()->id)->find($id);
        
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé'
            ], 404);
        }

        // Validation
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|nullable|string',
        ]);

        // Mise à jour
        $project->update($validated);

        return response()->json([
            'success' => true,
            'data' => $project,
            'message' => 'Projet mis à jour'
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $project = Project::where('user_id', $request->user()->id)->find($id);
        
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé'
            ], 404);
        }

        $project->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Projet supprimé'
        ]);
    }
}