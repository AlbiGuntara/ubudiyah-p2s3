<?php
namespace App\Http\Controllers;

use App\Http\Services\AuditLogService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): Response
    {
        $logs = $this->auditLogService->getLogs($request->only(['user_id', 'aktivitas', 'date_from', 'date_to']));

        return Inertia::render('audit/index', [
            'logs' => $logs,
        ]);
    }
}
