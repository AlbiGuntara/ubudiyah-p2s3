<?php
namespace App\Http\Controllers;

use App\Http\Services\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {
        $this->middleware('permission:view_dashboard');
    }

    public function index(): Response
    {
        return Inertia::render('dashboard/index', [
            'stats' => $this->dashboardService->getStats(),
            'harianChart' => $this->dashboardService->getHarianChart(),
            'bulananChart' => $this->dashboardService->getBulananChart(),
            'daerahChart' => $this->dashboardService->getDaerahChart(),
            'topDaerah' => $this->dashboardService->getTopDaerah(),
            'topAsrama' => $this->dashboardService->getTopAsrama(),
            'topSantri' => $this->dashboardService->getTopSantri(),
        ]);
    }
}
