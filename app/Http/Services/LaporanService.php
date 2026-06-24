<?php
namespace App\Http\Services;

use App\Models\Pelanggaran;
use Illuminate\Support\Facades\DB;

class LaporanService
{
    public function laporanBulanan(int $bulan, int $tahun, ?int $daerahId = null, ?int $asramaId = null, ?string $iksass = null): array
    {
        $query = Pelanggaran::sumberPetugas()
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun);

        if ($daerahId) {
            $query->whereHas('asrama', fn($q) => $q->where('daerah_id', $daerahId));
        }

        if ($asramaId) {
            $query->where('asrama_id', $asramaId);
        }

        if ($iksass) {
            $query->whereHas('santri', fn($q) => $q->where('iksass', $iksass));
        }

        $jumlahPelanggaran = (clone $query)->count();
        $jumlahSantri = (clone $query)->whereNotNull('santri_id')->distinct()->count('santri_id');

        return [
            'jumlah_pelanggaran' => $jumlahPelanggaran,
            'jumlah_santri' => $jumlahSantri,
        ];
    }

    public function laporanTahunan(int $tahun, ?int $daerahId = null, ?int $asramaId = null, ?string $iksass = null): array
    {
        $query = Pelanggaran::sumberPetugas()
            ->whereYear('tanggal', $tahun);

        if ($daerahId) {
            $query->whereHas('asrama', fn($q) => $q->where('daerah_id', $daerahId));
        }

        if ($asramaId) {
            $query->where('asrama_id', $asramaId);
        }

        if ($iksass) {
            $query->whereHas('santri', fn($q) => $q->where('iksass', $iksass));
        }

        $jumlahPelanggaran = (clone $query)->count();
        $jumlahSantri = (clone $query)->whereNotNull('santri_id')->distinct()->count('santri_id');

        return [
            'jumlah_pelanggaran' => $jumlahPelanggaran,
            'jumlah_santri' => $jumlahSantri,
        ];
    }

    public function laporanTotalPelanggaran(?int $daerahId = null, ?int $asramaId = null): array
    {
        $query = Pelanggaran::query();

        if ($daerahId) {
            $query->whereHas('asrama', fn($q) => $q->where('daerah_id', $daerahId));
        }

        if ($asramaId) {
            $query->where('asrama_id', $asramaId);
        }

        return [
            'total_pelanggaran' => (clone $query)->count(),
            'sumber_petugas' => (clone $query)->where('sumber_pencatatan', 'petugas')->count(),
            'sumber_ketua_kamar' => (clone $query)->where('sumber_pencatatan', 'ketua_kamar')->count(),
        ];
    }

    public function laporanPerDaerah(): array
    {
        return DB::table('pelanggaran')
            ->join('asrama', 'pelanggaran.asrama_id', '=', 'asrama.id')
            ->join('daerah', 'asrama.daerah_id', '=', 'daerah.id')
            ->select(
                'daerah.id as daerah_id',
                'daerah.nama_daerah',
                'asrama.id as asrama_id',
                'asrama.nomor as asrama_nomor',
                DB::raw('count(*) as jumlah_pelanggaran'),
                DB::raw('COUNT(DISTINCT pelanggaran.santri_id) as jumlah_santri')
            )
            ->where('pelanggaran.sumber_pencatatan', 'petugas')
            ->groupBy('daerah.id', 'daerah.nama_daerah', 'asrama.id', 'asrama.nomor')
            ->orderBy('daerah.nama_daerah')
            ->orderByDesc('jumlah_pelanggaran')
            ->get()
            ->groupBy('daerah_id')
            ->map(fn($items) => [
                'nama_daerah' => $items->first()->nama_daerah,
                'asrama' => $items,
            ])
            ->toArray();
    }

    public function laporanPerAsrama(?int $asramaId = null): array
    {
        $query = DB::table('pelanggaran')
            ->join('santri', 'pelanggaran.santri_id', '=', 'santri.id')
            ->select(
                'santri.id as santri_id',
                'santri.nama as santri_nama',
                'santri.nis as santri_nis',
                DB::raw('count(*) as jumlah_pelanggaran'),
                DB::raw('SUM(pelanggaran.jumlah) * 100 as jumlah_shalawat')
            )
            ->whereNotNull('pelanggaran.santri_id')
            ->where('pelanggaran.sumber_pencatatan', 'petugas');

        if ($asramaId) {
            $query->where('pelanggaran.asrama_id', $asramaId);
        }

        return $query->groupBy('santri.id', 'santri.nama', 'santri.nis')
            ->orderByDesc('jumlah_pelanggaran')
            ->get()
            ->toArray();
    }
}
