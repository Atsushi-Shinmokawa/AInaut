<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    /**
     * ダッシュボード表示
     */
    public function index(Request $request): Response
    {
        $userId = (string) $request->user()->id;
        $data = $this->dashboardService->getDashboardData($userId);

        return Inertia::render('Dashboard', $data);
    }
}
