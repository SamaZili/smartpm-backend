<?php

namespace App\Http\Controllers;

use App\Models\Estimation;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EstimationController extends Controller
{
    /**
     * Prédire l'effort pour une tâche
     *
     * @param Request $request
     * @param int $projectId
     * @param int $taskId
     * @return JsonResponse
     */
    public function predict(Request $request, $projectId, $taskId): JsonResponse
    {
        try {
            // Vérifier si le projet existe
            $project = Project::findOrFail($projectId);
            
            // Vérifier si la tâche existe
            $task = Task::findOrFail($taskId);
            
            // Vérifier que la tâche appartient au projet
            if ($task->project_id != $projectId) {
                return response()->json([
                    'success' => false,
                    'message' => 'La tâche n\'appartient pas à ce projet'
                ], 400);
            }
            
            // Récupérer les données de la requête
            $data = $request->validate([
                'complexity' => 'nullable|numeric|min:1|max:10',
                'priority' => 'nullable|string|in:low,medium,high',
                'team_size' => 'nullable|integer|min:1',
                'description' => 'nullable|string',
            ]);
            
            // Calculer l'estimation prédite
            // Vous pouvez remplacer cette logique par votre algorithme d'IA/ML
            $predictedEffort = $this->calculatePredictedEffort($task, $data);
            $confidenceScore = $this->calculateConfidenceScore($task, $data);
            
            // Créer ou mettre à jour l'estimation
            $estimation = Estimation::updateOrCreate(
                ['task_id' => $taskId],
                [
                    'project_id' => $projectId,
                    'predicted_effort' => $predictedEffort,
                    'confidence_score' => $confidenceScore,
                    'complexity' => $data['complexity'] ?? null,
                    'priority' => $data['priority'] ?? null,
                    'team_size' => $data['team_size'] ?? null,
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Estimation prédite avec succès',
                'data' => [
                    'estimation' => $estimation,
                    'task' => $task
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la prédiction: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
    
    /**
     * Calculer l'effort prédit
     *
     * @param Task $task
     * @param array $data
     * @return float
     */
    private function calculatePredictedEffort(Task $task, array $data): float
    {
        // Logique de calcul de base - à adapter selon vos besoins
        $baseEffort = 8.0; // heures par défaut
        
        // Facteur de complexité
        $complexityFactor = $data['complexity'] ? ($data['complexity'] / 5) : 1.0;
        
        // Facteur de priorité
        $priorityFactor = 1.0;
        if (isset($data['priority'])) {
            $priorityFactor = [
                'low' => 0.8,
                'medium' => 1.0,
                'high' => 1.3
            ][$data['priority']] ?? 1.0;
        }
        
        // Facteur de taille d'équipe
        $teamFactor = isset($data['team_size']) ? max(0.5, 2.0 / $data['team_size']) : 1.0;
        
        // Calcul final
        $predictedEffort = $baseEffort * $complexityFactor * $priorityFactor * $teamFactor;
        
        return round($predictedEffort, 2);
    }
    
    /**
     * Calculer le score de confiance
     *
     * @param Task $task
     * @param array $data
     * @return float
     */
    private function calculateConfidenceScore(Task $task, array $data): float
    {
        // Score de confiance basé sur la complétude des données
        $score = 0.5; // Score de base
        
        if (!empty($data['complexity'])) {
            $score += 0.2;
        }
        
        if (!empty($data['priority'])) {
            $score += 0.1;
        }
        
        if (!empty($data['team_size'])) {
            $score += 0.1;
        }
        
        if (!empty($task->description)) {
            $score += 0.1;
        }
        
        return min(1.0, round($score, 2));
    }
    
    /**
     * Afficher une estimation spécifique
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $estimation = Estimation::with(['task', 'project'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $estimation
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estimation non trouvée'
            ], 404);
        }
    }
    
    /**
     * Lister toutes les estimations d'un projet
     *
     * @param int $projectId
     * @return JsonResponse
     */
    public function index($projectId): JsonResponse
    {
        try {
            $estimations = Estimation::where('project_id', $projectId)
                ->with('task')
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $estimations
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des estimations'
            ], 500);
        }
    }
    
    /**
     * Mettre à jour une estimation
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $estimation = Estimation::findOrFail($id);
            
            $data = $request->validate([
                'predicted_effort' => 'nullable|numeric|min:0',
                'confidence_score' => 'nullable|numeric|between:0,1',
                'complexity' => 'nullable|numeric|min:1|max:10',
                'priority' => 'nullable|string|in:low,medium,high',
                'team_size' => 'nullable|integer|min:1',
            ]);
            
            $estimation->update($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Estimation mise à jour avec succès',
                'data' => $estimation
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }
    
    /**
     * Supprimer une estimation
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $estimation = Estimation::findOrFail($id);
            $estimation->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Estimation supprimée avec succès'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }
}