<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class CleanAuditLogs extends Command
{
    protected $signature = 'audit:clean';
    protected $description = 'Hapus audit log yang lebih dari 12 bulan';

    public function handle(): int
    {
        $cutoff = now()->subMonths(12);
        $deleted = AuditLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Berhasil menghapus {$deleted} audit log yang lebih dari 12 bulan.");

        return self::SUCCESS;
    }
}
