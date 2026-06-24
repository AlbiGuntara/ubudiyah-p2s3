<?php
namespace App\Http\Services;

use App\Models\AuditLog;
use Illuminate\Pagination\LengthAwarePaginator;

class AuditLogService
{
    public function getLogs(array $filters = []): LengthAwarePaginator
    {
        $query = AuditLog::with('user')->latest();

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['aktivitas'])) {
            $query->where('aktivitas', 'like', '%' . $filters['aktivitas'] . '%');
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate(20);
    }
}
