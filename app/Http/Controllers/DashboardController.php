<?php

namespace App\Http\Controllers;

use App\Repositories\DashboardRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected DashboardRepository $dashboardRepository;

    // Injection de dépendance automatique par Laravel
    public function __construct(DashboardRepository $dashboardRepository)
    {
        $this->dashboardRepository = $dashboardRepository;
    }

    // F5.1, F5.2, F5.3 : Statistiques globales pour le chef de projet connecté
    public function index(Request $request)
    {
        $user = Auth::user();

        return response()->json([
            'projects' => $this->dashboardRepository->getProjectStats($user),
            'tasks' => $this->dashboardRepository->getTaskStats($user),
            'total_estimated_effort' => $this->dashboardRepository->getTotalEstimatedEffort($user)
        ]);
    }
}