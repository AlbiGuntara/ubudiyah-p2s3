<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelanggaran', function (Blueprint $table) {
            $table->integer('sisa_sanksi')->default(0)->after('jumlah');
        });

        // Backfill: set sisa_sanksi = jumlah * 100 for all existing pelanggaran
        DB::table('pelanggaran')->update([
            'sisa_sanksi' => DB::raw('jumlah * 100'),
        ]);

        // For named santri: distribute existing shalawat_tertulis FIFO across pelanggaran
        $pembinaans = DB::table('pembinaan')
            ->whereNotNull('santri_id')
            ->where('shalawat_tertulis', '>', 0)
            ->get();

        foreach ($pembinaans as $pembinaan) {
            $remaining = (int) $pembinaan->shalawat_tertulis;
            $pelanggarans = DB::table('pelanggaran')
                ->where('santri_id', $pembinaan->santri_id)
                ->orderBy('tanggal')
                ->orderBy('id')
                ->get();

            foreach ($pelanggarans as $pelanggaran) {
                if ($remaining <= 0) {
                    break;
                }
                $sisa = (int) $pelanggaran->sisa_sanksi;
                $settle = min($sisa, $remaining);
                DB::table('pelanggaran')
                    ->where('id', $pelanggaran->id)
                    ->update(['sisa_sanksi' => $sisa - $settle]);
                $remaining -= $settle;
            }
        }

        // For anonymous: distribute existing shalawat_tertulis FIFO across pelanggaran
        $anonPembinaans = DB::table('pembinaan')
            ->whereNull('santri_id')
            ->whereNotNull('asrama_id')
            ->where('shalawat_tertulis', '>', 0)
            ->get();

        foreach ($anonPembinaans as $pembinaan) {
            $remaining = (int) $pembinaan->shalawat_tertulis;
            $pelanggarans = DB::table('pelanggaran')
                ->whereNull('santri_id')
                ->where('asrama_id', $pembinaan->asrama_id)
                ->orderBy('tanggal')
                ->orderBy('id')
                ->get();

            foreach ($pelanggarans as $pelanggaran) {
                if ($remaining <= 0) {
                    break;
                }
                $sisa = (int) $pelanggaran->sisa_sanksi;
                $settle = min($sisa, $remaining);
                DB::table('pelanggaran')
                    ->where('id', $pelanggaran->id)
                    ->update(['sisa_sanksi' => $sisa - $settle]);
                $remaining -= $settle;
            }
        }
    }

    public function down(): void
    {
        Schema::table('pelanggaran', function (Blueprint $table) {
            $table->dropColumn('sisa_sanksi');
        });
    }
};
