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
        $labels = [];
        $data = [];
        $dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

        $raw = Pelanggaran::select(DB::raw('DATE(tanggal) as date'), DB::raw('count(*) as total'))
            ->whereDate('tanggal', '>=', now()->subDays(6))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $dayName = $dayNames[(int) $date->format('w')];
            $labels[] = $dayName;
            $data[] = (int) ($raw[$key]->total ?? 0);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    public function getBulananChart(): array
    {
        $labels = [];
        $data = [];
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $raw = Pelanggaran::select(DB::raw('MONTH(tanggal) as month'), DB::raw('YEAR(tanggal) as year'), DB::raw('count(*) as total'))
            ->whereDate('tanggal', '>=', now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(fn($item) => [
                'key' => $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT),
                'month' => $item->month,
                'total' => $item->total,
            ])
            ->keyBy('key');

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $month = (int) $date->format('n');
            $labels[] = $monthNames[$month - 1];
            $data[] = (int) (isset($raw[$key]) ? $raw[$key]['total'] : 0);
        }

        return [
            'labels' => $labels,
            'data' => $data,
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
            ->join('daerah', 'asrama.daerah_id', '=', 'daerah.id')
            ->select('asrama.nomor', 'daerah.kode', DB::raw('count(*) as total'))
            ->groupBy('asrama.id', 'asrama.nomor', 'daerah.kode')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'label' => 'Asrama ' . substr($item->kode, 0, 1) . '.' . $item->nomor,
                'total' => $item->total,
            ])
            ->toArray();
    }

    public function getTopSantri(): array
    {
        return DB::table('pelanggaran')
            ->join('santri', 'pelanggaran.santri_id', '=', 'santri.id')
            ->leftJoin('asrama', 'santri.asrama_id', '=', 'asrama.id')
            ->leftJoin('daerah', 'asrama.daerah_id', '=', 'daerah.id')
            ->select('santri.nama', 'santri.nis', 'daerah.kode', 'asrama.nomor', DB::raw('count(*) as total'), DB::raw('SUM(pelanggaran.jumlah) * 100 as total_shalawat'))
            ->whereNotNull('pelanggaran.santri_id')
            ->groupBy('santri.id', 'santri.nama', 'santri.nis', 'daerah.kode', 'asrama.nomor')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'nama' => $item->nama,
                'total' => $item->total,
                'asrama_label' => $item->kode && $item->nomor
                    ? '(' . substr($item->kode, 0, 1) . '.' . $item->nomor . ')'
                    : '',
            ])
            ->toArray();
    }
}
