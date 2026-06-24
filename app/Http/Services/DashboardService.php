<?php
namespace App\Http\Services;

use App\Models\Pelanggaran;
use App\Models\Santri;
use App\Models\Daerah;
use App\Models\Asrama;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getStats(): array
    {
        return [
            'pelanggaran_hari_ini' => Pelanggaran::whereDate('tanggal', today())->count(),
            'santri_melanggar_hari_ini' => Pelanggaran::whereDate('tanggal', today())
                ->whereNotNull('santri_id')
                ->distinct('santri_id')
                ->count('santri_id'),
            'pelanggaran_bulan_ini' => Pelanggaran::whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)
                ->count(),
            'santri_melanggar_bulan_ini' => Pelanggaran::whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)
                ->whereNotNull('santri_id')
                ->distinct('santri_id')
                ->count('santri_id'),
        ];
    }

    public function getHarianChart(): array
    {
        $data = Pelanggaran::select(DB::raw('DATE(tanggal) as date'), DB::raw('count(*) as total'))
            ->whereDate('tanggal', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date'),
            'data' => $data->pluck('total'),
        ];
    }

    public function getBulananChart(): array
    {
        $data = Pelanggaran::select(DB::raw('MONTH(tanggal) as month'), DB::raw('YEAR(tanggal) as year'), DB::raw('count(*) as total'))
            ->whereDate('tanggal', '>=', now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(fn($item) => [
                'label' => $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT),
                'total' => $item->total,
            ]);

        return [
            'labels' => $data->pluck('label'),
            'data' => $data->pluck('total'),
        ];
    }

    public function getDaerahChart(): array
    {
        $data = Daerah::withCount(['asrama as total_pelanggaran' => function ($q) {
            $q->withCount(['santri as count' => function ($q2) {
                $q2->withCount('pelanggaran');
            }]);
        }])->get();

        $result = DB::table('pelanggaran')
            ->join('asrama', 'pelanggaran.asrama_id', '=', 'asrama.id')
            ->join('daerah', 'asrama.daerah_id', '=', 'daerah.id')
            ->select('daerah.nama_daerah', DB::raw('count(*) as total'))
            ->groupBy('daerah.id', 'daerah.nama_daerah')
            ->get();

        return [
            'labels' => $result->pluck('nama_daerah'),
            'data' => $result->pluck('total'),
        ];
    }

    public function getAsramaChart(): array
    {
        $result = DB::table('pelanggaran')
            ->join('asrama', 'pelanggaran.asrama_id', '=', 'asrama.id')
            ->select('asrama.nomor', DB::raw('count(*) as total'))
            ->groupBy('asrama.id', 'asrama.nomor')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'labels' => $result->pluck('nomor'),
            'data' => $result->pluck('total'),
        ];
    }

    public function getTopDaerah(): array
    {
        return DB::table('pelanggaran')
            ->join('asrama', 'pelanggaran.asrama_id', '=', 'asrama.id')
            ->join('daerah', 'asrama.daerah_id', '=', 'daerah.id')
            ->select('daerah.nama_daerah', DB::raw('count(*) as total'))
            ->groupBy('daerah.id', 'daerah.nama_daerah')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function getTopAsrama(): array
    {
        return DB::table('pelanggaran')
            ->join('asrama', 'pelanggaran.asrama_id', '=', 'asrama.id')
            ->select('asrama.nomor', DB::raw('count(*) as total'))
            ->groupBy('asrama.id', 'asrama.nomor')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function getTopSantri(): array
    {
        return DB::table('pelanggaran')
            ->join('santri', 'pelanggaran.santri_id', '=', 'santri.id')
            ->select('santri.nama', 'santri.nis', DB::raw('count(*) as total'), DB::raw('SUM(pelanggaran.jumlah) * 100 as total_shalawat'))
            ->whereNotNull('pelanggaran.santri_id')
            ->groupBy('santri.id', 'santri.nama', 'santri.nis')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
