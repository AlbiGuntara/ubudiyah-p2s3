<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Apply the 3x pemutihan multiplier to pelanggaran in the
     * Dec 2025 - May 2026 range (the "pemutihan" amnesty period).
     *
     * The pemutihan multiplied pembinaan.sisa_sanksi by 3 but, because the
     * migration 2026_09_07_000001 had reset pelanggaran.sisa_sanksi to
     * jumlah * 100 *after* the pemutihan ran, the per-pelanggaran values were
     * left without the multiplier. This re-applies the 3x multiplier to the
     * affected pelanggaran so that sum(pelanggaran.sisa_sanksi) matches
     * pembinaan.sisa_sanksi again (pembinaan.sisa_sanksi is left untouched).
     *
     * Only period pelanggaran that still have an outstanding sanction
     * (sisa_sanksi > 0) are multiplied; settled ones stay settled.
     */
    public function up(): void
    {
        $start = '2025-12-01';
        $end = '2026-05-31';

        DB::table('pelanggaran')
            ->where('sisa_sanksi', '>', 0)
            ->whereBetween('tanggal', [$start, $end])
            ->update([
                'sisa_sanksi' => DB::raw('sisa_sanksi * 3'),
                'updated_at' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        $start = '2025-12-01';
        $end = '2026-05-31';

        DB::table('pelanggaran')
            ->where('sisa_sanksi', '>', 0)
            ->whereBetween('tanggal', [$start, $end])
            ->update([
                'sisa_sanksi' => DB::raw('FLOOR(sisa_sanksi / 3)'),
                'updated_at' => DB::raw('updated_at'),
            ]);
    }
};
