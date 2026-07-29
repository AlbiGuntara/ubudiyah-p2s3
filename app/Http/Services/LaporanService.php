<?php
namespace App\Http\Services;

use App\Models\Pelanggaran;
use App\Models\Daerah;
use App\Models\Asrama;
use App\Models\DaftarPelanggaran;
use App\Models\Petugas;
use Illuminate\Support\Facades\DB;

class LaporanService
{
    protected function buildBaseQuery(
        ?string $tanggalMulai = null,
        ?string $tanggalSelesai = null,
        ?int $bulan = null,
        ?int $tahun = null,
        ?int $daerahId = null,
        ?int $asramaId = null,
        ?string $iksass = null,
        ?string $sumberPencatatan = null
    ) {
        $query = Pelanggaran::query();

        if ($tanggalMulai && $tanggalSelesai) {
            $query->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai]);
        } elseif ($bulan && $tahun) {
            $query->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
        } elseif ($tahun) {
            $query->whereYear('tanggal', $tahun);
        }

        if ($daerahId) {
            $query->whereHas('asrama', fn($q) => $q->where('daerah_id', $daerahId));
        }

        if ($asramaId) {
            $query->where('asrama_id', $asramaId);
        }

        if ($iksass) {
            $query->whereHas('santri', fn($q) => $q->where('iksass', $iksass));
        }

        if ($sumberPencatatan) {
            $query->where('sumber_pencatatan', $sumberPencatatan);
        }

        return $query;
    }

    public function laporanKomprehensif(
        ?string $tanggalMulai = null,
        ?string $tanggalSelesai = null,
        ?int $bulan = null,
        ?int $tahun = null,
        ?int $daerahId = null,
        ?int $asramaId = null,
        ?string $iksass = null,
        ?string $sumberPencatatan = null
    ): array {
        $base = $this->buildBaseQuery($tanggalMulai, $tanggalSelesai, $bulan, $tahun, $daerahId, $asramaId, $iksass, $sumberPencatatan);

        // Ringkasan
        $totalPelanggaran = (clone $base)
            ->selectRaw("COALESCE(SUM(CASE WHEN santri_id IS NULL THEN jumlah ELSE 1 END), 0) as total")
            ->value('total');
        $totalSantri = (clone $base)->whereNotNull('santri_id')->distinct()->count('santri_id');
        $sumberPetugas = (clone $base)->where('sumber_pencatatan', 'petugas')
            ->selectRaw("COALESCE(SUM(CASE WHEN santri_id IS NULL THEN jumlah ELSE 1 END), 0) as total")
            ->value('total');
        $sumberKetuaKamar = (clone $base)->where('sumber_pencatatan', 'ketua_kamar')
            ->selectRaw("COALESCE(SUM(CASE WHEN santri_id IS NULL THEN jumlah ELSE 1 END), 0) as total")
            ->value('total');

        // Per Daerah
        $perDaerah = DB::table('pelanggaran')
            ->join('asrama', 'pelanggaran.asrama_id', '=', 'asrama.id')
            ->join('daerah', 'asrama.daerah_id', '=', 'daerah.id')
            ->select(
                'daerah.id',
                'daerah.kode',
                'daerah.nama_daerah',
                DB::raw("SUM(CASE WHEN pelanggaran.santri_id IS NULL THEN pelanggaran.jumlah ELSE 1 END) as jumlah_pelanggaran"),
                DB::raw('COUNT(DISTINCT pelanggaran.santri_id) as jumlah_santri')
            )
            ->when($tanggalMulai && $tanggalSelesai, fn($q) => $q->whereBetween('pelanggaran.tanggal', [$tanggalMulai, $tanggalSelesai]))
            ->when($bulan && $tahun, fn($q) => $q->whereMonth('pelanggaran.tanggal', $bulan)->whereYear('pelanggaran.tanggal', $tahun))
            ->when($tahun && !$bulan, fn($q) => $q->whereYear('pelanggaran.tanggal', $tahun))
            ->when($daerahId, fn($q) => $q->where('asrama.daerah_id', $daerahId))
            ->when($asramaId, fn($q) => $q->where('pelanggaran.asrama_id', $asramaId))
            ->when($sumberPencatatan, fn($q) => $q->where('pelanggaran.sumber_pencatatan', $sumberPencatatan))
            ->groupBy('daerah.id', 'daerah.kode', 'daerah.nama_daerah')
            ->orderByDesc('jumlah_pelanggaran')
            ->get();

        // Per Asrama
        $perAsrama = DB::table('pelanggaran')
            ->join('asrama', 'pelanggaran.asrama_id', '=', 'asrama.id')
            ->join('daerah', 'asrama.daerah_id', '=', 'daerah.id')
            ->select(
                'asrama.id',
                'asrama.nomor',
                'daerah.kode as daerah_kode',
                'daerah.nama_daerah',
                DB::raw("SUM(CASE WHEN pelanggaran.santri_id IS NULL THEN pelanggaran.jumlah ELSE 1 END) as jumlah_pelanggaran"),
                DB::raw('COUNT(DISTINCT pelanggaran.santri_id) as jumlah_santri')
            )
            ->when($tanggalMulai && $tanggalSelesai, fn($q) => $q->whereBetween('pelanggaran.tanggal', [$tanggalMulai, $tanggalSelesai]))
            ->when($bulan && $tahun, fn($q) => $q->whereMonth('pelanggaran.tanggal', $bulan)->whereYear('pelanggaran.tanggal', $tahun))
            ->when($tahun && !$bulan, fn($q) => $q->whereYear('pelanggaran.tanggal', $tahun))
            ->when($daerahId, fn($q) => $q->where('asrama.daerah_id', $daerahId))
            ->when($asramaId, fn($q) => $q->where('pelanggaran.asrama_id', $asramaId))
            ->when($sumberPencatatan, fn($q) => $q->where('pelanggaran.sumber_pencatatan', $sumberPencatatan))
            ->groupBy('asrama.id', 'asrama.nomor', 'daerah.kode', 'daerah.nama_daerah')
            ->orderBy('daerah.nama_daerah')
            ->orderByDesc('jumlah_pelanggaran')
            ->get();

        // Per Jenis Pelanggaran
        $perJenis = DB::table('pelanggaran')
            ->join('daftar_pelanggaran', 'pelanggaran.daftar_pelanggaran_id', '=', 'daftar_pelanggaran.id')
            ->select(
                'daftar_pelanggaran.id',
                'daftar_pelanggaran.nama_pelanggaran',
                DB::raw("SUM(CASE WHEN pelanggaran.santri_id IS NULL THEN pelanggaran.jumlah ELSE 1 END) as jumlah_pelanggaran"),
                DB::raw('COUNT(DISTINCT pelanggaran.santri_id) as jumlah_santri')
            )
            ->when($tanggalMulai && $tanggalSelesai, fn($q) => $q->whereBetween('pelanggaran.tanggal', [$tanggalMulai, $tanggalSelesai]))
            ->when($bulan && $tahun, fn($q) => $q->whereMonth('pelanggaran.tanggal', $bulan)->whereYear('pelanggaran.tanggal', $tahun))
            ->when($tahun && !$bulan, fn($q) => $q->whereYear('pelanggaran.tanggal', $tahun))
            ->when($daerahId, fn($q) => $q->whereHas('asrama', fn($q2) => $q2->where('daerah_id', $daerahId)))
            ->when($asramaId, fn($q) => $q->where('pelanggaran.asrama_id', $asramaId))
            ->when($sumberPencatatan, fn($q) => $q->where('pelanggaran.sumber_pencatatan', $sumberPencatatan))
            ->groupBy('daftar_pelanggaran.id', 'daftar_pelanggaran.nama_pelanggaran')
            ->orderByDesc('jumlah_pelanggaran')
            ->get();

        // Per Sumber Pencatatan
        $perSumber = (clone $base)
            ->select(
                'sumber_pencatatan',
                DB::raw("SUM(CASE WHEN santri_id IS NULL THEN jumlah ELSE 1 END) as jumlah_pelanggaran"),
                DB::raw('COUNT(DISTINCT santri_id) as jumlah_santri')
            )
            ->groupBy('sumber_pencatatan')
            ->get()
            ->keyBy('sumber_pencatatan');

        // Per Petugas
        $perPetugas = DB::table('pelanggaran')
            ->join('petugas', 'pelanggaran.petugas_id', '=', 'petugas.id')
            ->select(
                'petugas.id',
                'petugas.jabatan',
                'petugas.tugas',
                DB::raw('COALESCE(petugas.nama, "Petugas Tanpa Nama") as nama_petugas'),
                DB::raw("SUM(CASE WHEN pelanggaran.santri_id IS NULL THEN pelanggaran.jumlah ELSE 1 END) as jumlah_pelanggaran"),
                DB::raw('COUNT(DISTINCT pelanggaran.santri_id) as jumlah_santri_dicatat')
            )
            ->whereNotNull('pelanggaran.petugas_id')
            ->when($tanggalMulai && $tanggalSelesai, fn($q) => $q->whereBetween('pelanggaran.tanggal', [$tanggalMulai, $tanggalSelesai]))
            ->when($bulan && $tahun, fn($q) => $q->whereMonth('pelanggaran.tanggal', $bulan)->whereYear('pelanggaran.tanggal', $tahun))
            ->when($tahun && !$bulan, fn($q) => $q->whereYear('pelanggaran.tanggal', $tahun))
            ->when($daerahId, fn($q) => $q->whereHas('asrama', fn($q2) => $q2->where('daerah_id', $daerahId)))
            ->when($asramaId, fn($q) => $q->where('pelanggaran.asrama_id', $asramaId))
            ->when($sumberPencatatan, fn($q) => $q->where('pelanggaran.sumber_pencatatan', $sumberPencatatan))
            ->groupBy('petugas.id', 'petugas.jabatan', 'petugas.tugas', 'petugas.nama')
            ->orderByDesc('jumlah_pelanggaran')
            ->get();

        // Detail Pelanggaran
        $detail = Pelanggaran::with([
            'asrama.daerah',
            'santri',
            'daftarPelanggaran',
            'petugas',
        ])
            ->when($tanggalMulai && $tanggalSelesai, fn($q) => $q->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai]))
            ->when($bulan && $tahun, fn($q) => $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun))
            ->when($tahun && !$bulan, fn($q) => $q->whereYear('tanggal', $tahun))
            ->when($daerahId, fn($q) => $q->whereHas('asrama', fn($q2) => $q2->where('daerah_id', $daerahId)))
            ->when($asramaId, fn($q) => $q->where('asrama_id', $asramaId))
            ->when($iksass, fn($q) => $q->whereHas('santri', fn($q2) => $q2->where('iksass', $iksass)))
            ->when($sumberPencatatan, fn($q) => $q->where('sumber_pencatatan', $sumberPencatatan))
            ->orderByDesc('tanggal')
            ->limit(5000)
            ->get()
            ->map(fn($p) => [
                'tanggal' => optional($p->tanggal)->format('d/m/Y'),
                'santri_nama' => $p->santri?->nama ?? 'Tanpa Nama',
                'santri_nis' => $p->santri?->nis ?? '-',
                'santri_iksass' => $p->santri?->iksass ?? '-',
                'asrama_label' => $p->asrama?->daerah?->kode
                    ? substr($p->asrama->daerah->kode, 0, 1) . '.' . $p->asrama->nomor
                    : ($p->asrama?->nomor ?? '-'),
                'daerah' => $p->asrama?->daerah?->nama_daerah ?? '-',
                'jenis_pelanggaran' => $p->daftarPelanggaran?->nama_pelanggaran ?? '-',
                'jumlah' => $p->jumlah ?? 1,
                'sumber' => $p->sumber_pencatatan === 'petugas' ? 'Petugas' : 'Ketua Kamar',
                'petugas' => $p->petugas?->nama ?? ($p->petugas_id ? 'Petugas #' . $p->petugas_id : '-'),
                'keterangan' => $p->keterangan ?? '',
            ]);

        // Per IKSASS
        $perIksass = DB::table('pelanggaran')
            ->join('santri', 'pelanggaran.santri_id', '=', 'santri.id')
            ->select(
                'santri.iksass',
                DB::raw('count(*) as jumlah_pelanggaran'),
                DB::raw('COUNT(DISTINCT pelanggaran.santri_id) as jumlah_santri')
            )
            ->whereNotNull('pelanggaran.santri_id')
            ->whereNotNull('santri.iksass')
            ->when($tanggalMulai && $tanggalSelesai, fn($q) => $q->whereBetween('pelanggaran.tanggal', [$tanggalMulai, $tanggalSelesai]))
            ->when($bulan && $tahun, fn($q) => $q->whereMonth('pelanggaran.tanggal', $bulan)->whereYear('pelanggaran.tanggal', $tahun))
            ->when($tahun && !$bulan, fn($q) => $q->whereYear('pelanggaran.tanggal', $tahun))
            ->when($daerahId, fn($q) => $q->whereHas('asrama', fn($q2) => $q2->where('daerah_id', $daerahId)))
            ->when($asramaId, fn($q) => $q->where('pelanggaran.asrama_id', $asramaId))
            ->when($sumberPencatatan, fn($q) => $q->where('pelanggaran.sumber_pencatatan', $sumberPencatatan))
            ->groupBy('santri.iksass')
            ->orderByDesc('jumlah_pelanggaran')
            ->get();

        return [
            'ringkasan' => [
                'total_pelanggaran' => $totalPelanggaran,
                'total_santri' => $totalSantri,
                'sumber_petugas' => $sumberPetugas,
                'sumber_ketua_kamar' => $sumberKetuaKamar,
            ],
            'per_daerah' => $perDaerah,
            'per_asrama' => $perAsrama,
            'per_jenis_pelanggaran' => $perJenis,
            'per_sumber' => $perSumber,
            'per_petugas' => $perPetugas,
            'per_iksass' => $perIksass,
            'detail' => $detail,
        ];
    }

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

        $jumlahPelanggaran = (clone $query)
            ->selectRaw("COALESCE(SUM(CASE WHEN santri_id IS NULL THEN jumlah ELSE 1 END), 0) as total")
            ->value('total');
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

        $jumlahPelanggaran = (clone $query)
            ->selectRaw("COALESCE(SUM(CASE WHEN santri_id IS NULL THEN jumlah ELSE 1 END), 0) as total")
            ->value('total');
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
            'total_pelanggaran' => (clone $query)
                ->selectRaw("COALESCE(SUM(CASE WHEN santri_id IS NULL THEN jumlah ELSE 1 END), 0) as total")
                ->value('total'),
            'sumber_petugas' => (clone $query)->where('sumber_pencatatan', 'petugas')
                ->selectRaw("COALESCE(SUM(CASE WHEN santri_id IS NULL THEN jumlah ELSE 1 END), 0) as total")
                ->value('total'),
            'sumber_ketua_kamar' => (clone $query)->where('sumber_pencatatan', 'ketua_kamar')
                ->selectRaw("COALESCE(SUM(CASE WHEN santri_id IS NULL THEN jumlah ELSE 1 END), 0) as total")
                ->value('total'),
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
                DB::raw("SUM(CASE WHEN pelanggaran.santri_id IS NULL THEN pelanggaran.jumlah ELSE 1 END) as jumlah_pelanggaran"),
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

    public function getTahunTersedia(): array
    {
        return Pelanggaran::selectRaw('YEAR(tanggal) as tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun')
            ->toArray();
    }

    public function getBulanTersedia(int $tahun): array
    {
        return Pelanggaran::selectRaw('MONTH(tanggal) as bulan')
            ->whereYear('tanggal', $tahun)
            ->distinct()
            ->orderBy('bulan')
            ->pluck('bulan')
            ->toArray();
    }
}
