<?php
namespace App\Http\Controllers;

use App\Http\Services\AuditLogService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {
        $this->middleware('permission:view_audit');
    }

    public function index(Request $request): Response|JsonResponse
    {
        $filters = $request->only(['search']);
        $perPage = (int) $request->input('per_page', 50);

        $logs = $this->auditLogService->getLogs($filters, $perPage);

        if (! $request->inertia() && $request->wantsJson()) {
            return response()->json($logs);
        }

        return Inertia::render('audit/index', [
            'logs' => $logs,
            'filters' => $filters,
        ]);
    }
}
