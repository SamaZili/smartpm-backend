<?php

namespace App\Http\Controllers;

use App\Repositories\DashboardRepository;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected DashboardRepository $dashboardRepository;

    public function __construct(DashboardRepository $dashboardRepository)
    {
        // On injecte le repository avec l'utilisateur actuellement connecté
        $this->dashboardRepository = new DashboardRepository(auth()->user());
    }

    // F5.1, F5.2, F5.3 : Statistiques globales pour le chef de projet connecté
    public function index(Request $request)
    {
        return response()->json([
            'projects' => $this->dashboardRepository->getProjectStats(),
            'tasks' => $this->dashboardRepository->getTaskStats(),
            'total_estimated_effort' => $this->dashboardRepository->getTotalEstimatedEffort()
        ]);
    }
}