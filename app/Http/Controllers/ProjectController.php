<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $projects = Project::where('user_id', $request->user()->id)
                              ->latest()
                              ->get();
            
            return response()->json([
                'success' => true,
                'data' => $projects
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur index projets: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            \Log::error('Erreur création projet: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $project = Project::where('user_id', $request->user()->id)->findOrFail($id);
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'status' => 'sometimes|string',
            ]);
            $project->update($validated);

            return response()->json(['success' => true, 'data' => $project]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Projet non trouvé'], 404);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $project = Project::where('user_id', $request->user()->id)->findOrFail($id);
            $project->delete();
            return response()->json(['success' => true, 'message' => 'Projet supprimé']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Projet non trouvé'], 404);
        }
    }
}