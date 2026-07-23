<?php
namespace App\Http\Controllers;

use App\Exports\LaporanExport;
use App\Http\Services\LaporanService;
use App\Models\AuditLog;
use App\Models\Pelanggaran;
use App\Models\Pembinaan;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function __construct(
        protected LaporanService $laporanService
    ) {
        $this->middleware('permission:export_laporan');
    }

    public function excelSection(Request $request, string $section)
    {
        $valid = ['per_daerah', 'per_asrama', 'per_jenis_pelanggaran', 'per_iksass'];
        if (!in_array($section, $valid)) {
            abort(404);
        }

        $data = $this->laporanService->laporanKomprehensif(
            $request->tanggal_mulai,
            $request->tanggal_selesai,
            $request->bulan ? (int) $request->bulan : null,
            $request->tahun ? (int) $request->tahun : null,
            $request->daerah_id,
            $request->asrama_id,
            $request->iksass,
            $request->sumber_pencatatan
        );

        $periode = $this->formatPeriode(
            $request->tanggal_mulai,
            $request->tanggal_selesai,
            $request->bulan,
            $request->tahun
        );

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'aktivitas' => 'mengexport laporan ' . str_replace('_', ' ', $section) . ' (Excel)',
            'model_type' => null,
            'model_id' => null,
            'data' => ['section' => $section, 'periode' => $periode],
        ]);

        return Excel::download(
            new \App\Exports\LaporanSingleExport($data, $periode, $section),
            "laporan-{$section}.xlsx"
        );
    }

    public function excelKomprehensif(Request $request)
    {
        $data = $this->laporanService->laporanKomprehensif(
            $request->tanggal_mulai,
            $request->tanggal_selesai,
            $request->bulan ? (int) $request->bulan : null,
            $request->tahun ? (int) $request->tahun : null,
            $request->daerah_id,
            $request->asrama_id,
            $request->iksass,
            $request->sumber_pencatatan
        );

        $periode = $this->formatPeriode(
            $request->tanggal_mulai,
            $request->tanggal_selesai,
            $request->bulan,
            $request->tahun
        );

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'aktivitas' => 'mengexport laporan komprehensif (Excel)',
            'model_type' => null,
            'model_id' => null,
            'data' => ['periode' => $periode],
        ]);

        return Excel::download(
            new LaporanExport($data, $periode),
            'laporan-ubudiyah-p2s3.xlsx'
        );
    }

    public function excelBulanan(Request $request)
    {
        $bulan = $request->bulan ?? now()->month;
        $tahun = $request->tahun ?? now()->year;
        $data = $this->laporanService->laporanBulanan($bulan, $tahun, $request->daerah_id, $request->asrama_id, $request->iksass);

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'aktivitas' => 'mengexport laporan bulanan (Excel)',
            'model_type' => null,
            'model_id' => null,
            'data' => ['bulan' => $bulan, 'tahun' => $tahun],
        ]);

        return Excel::download(
            new \App\Exports\LaporanLegacyExport($data, 'Laporan Bulanan', "Bulan {$bulan} Tahun {$tahun}"),
            'laporan-bulanan.xlsx'
        );
    }

    public function excelTahunan(Request $request)
    {
        $tahun = $request->tahun ?? now()->year;
        $data = $this->laporanService->laporanTahunan($tahun, $request->daerah_id, $request->asrama_id, $request->iksass);

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'aktivitas' => 'mengexport laporan tahunan (Excel)',
            'model_type' => null,
            'model_id' => null,
            'data' => ['tahun' => $tahun],
        ]);

        return Excel::download(
            new \App\Exports\LaporanLegacyExport($data, 'Laporan Tahunan', "Tahun {$tahun}"),
            'laporan-tahunan.xlsx'
        );
    }

    public function pdfBulanan(Request $request)
    {
        $bulan = $request->bulan ?? now()->month;
        $tahun = $request->tahun ?? now()->year;
        $data = $this->laporanService->laporanBulanan($bulan, $tahun, $request->daerah_id, $request->asrama_id, $request->iksass);

        $pdf = Pdf::loadView('exports.laporan', [
            'data' => $data,
            'judul' => 'Laporan Bulanan',
            'periode' => "Bulan {$bulan} Tahun {$tahun}",
            'tanggal_cetak' => now()->format('d/m/Y H:i'),
            'user' => auth()->user()->name,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'aktivitas' => 'mengexport laporan bulanan (PDF)',
            'model_type' => null,
            'model_id' => null,
            'data' => ['bulan' => $bulan, 'tahun' => $tahun],
        ]);

        return $pdf->download('laporan-bulanan.pdf');
    }

    public function pdfTahunan(Request $request)
    {
        $tahun = $request->tahun ?? now()->year;
        $data = $this->laporanService->laporanTahunan($tahun, $request->daerah_id, $request->asrama_id, $request->iksass);

        $pdf = Pdf::loadView('exports.laporan', [
            'data' => $data,
            'judul' => 'Laporan Tahunan',
            'periode' => "Tahun {$tahun}",
            'tanggal_cetak' => now()->format('d/m/Y H:i'),
            'user' => auth()->user()->name,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'aktivitas' => 'mengexport laporan tahunan (PDF)',
            'model_type' => null,
            'model_id' => null,
            'data' => ['tahun' => $tahun],
        ]);

        return $pdf->download('laporan-tahunan.pdf');
    }

    public function pdfPelanggaranFull()
    {
        $pembinaans = Pembinaan::with(['santri.asrama.daerah', 'asrama.daerah'])
            ->where('sisa_sanksi', '>', 0)
            ->get();

        $santriIds = $pembinaans->whereNotNull('santri_id')->pluck('santri_id')->unique()->values();
        $anonAsramaIds = $pembinaans->whereNull('santri_id')->pluck('asrama_id')->unique()->values();

        $pelanggaranBySantri = Pelanggaran::with('daftarPelanggaran')
            ->whereIn('santri_id', $santriIds)
            ->orderBy('tanggal')
            ->get()
            ->groupBy('santri_id');

        $anonPelanggaranByAsrama = Pelanggaran::with('daftarPelanggaran')
            ->whereNull('santri_id')
            ->whereIn('asrama_id', $anonAsramaIds)
            ->get()
            ->groupBy('asrama_id');

        $anonCounts = [];
        foreach ($anonPelanggaranByAsrama as $asramaId => $pelanggarans) {
            $anonCounts[$asramaId] = $pelanggarans->sum('jumlah');
        }

        $daerahGroups = [];
        foreach ($pembinaans as $p) {
            $isAnon = is_null($p->santri_id);
            $santriKey = $isAnon ? 'anon_' . $p->asrama_id : 'santri_' . $p->santri_id;
            $daerahId = $p->santri?->asrama?->daerah_id ?? $p->asrama?->daerah_id;
            $daerah = $p->santri?->asrama?->daerah ?? $p->asrama?->daerah;
            if (!$daerah) continue;

            $asrama = $p->santri?->asrama ?? $p->asrama;
            $asramaSort = $asrama?->nomor ?? 0;

            if (!isset($daerahGroups[$daerahId])) {
                $daerahGroups[$daerahId] = [
                    'daerah' => $daerah,
                    'santri' => [],
                ];
            }

            if (!isset($daerahGroups[$daerahId]['santri'][$santriKey])) {
                $asramaLabel = '-';
                if ($asrama) {
                    $kode = $asrama->daerah?->kode ?? '';
                    $asramaLabel = $kode ? substr($kode, 0, 1) . '.' . $asrama->nomor : (string) $asrama->nomor;
                }

                if ($isAnon) {
                    $total = $anonCounts[$p->asrama_id] ?? 0;
                    $anonPels = collect();
                    if (isset($anonPelanggaranByAsrama[$p->asrama_id])) {
                        $anonPels = $anonPelanggaranByAsrama[$p->asrama_id];
                    }
                    $daerahGroups[$daerahId]['santri'][$santriKey] = [
                        'is_anonymous' => true,
                        'santri_nama' => $total . ' Tanpa Nama',
                        'santri_nis' => '-',
                        'asrama_label' => $asramaLabel,
                        'asrama_sort' => $asramaSort,
                        'pelanggarans' => $anonPels,
                    ];
                } else {
                    $pelanggarans = collect();
                    if ($p->santri_id && isset($pelanggaranBySantri[$p->santri_id])) {
                        $pelanggarans = $pelanggaranBySantri[$p->santri_id];
                    }

                    $daerahGroups[$daerahId]['santri'][$santriKey] = [
                        'is_anonymous' => false,
                        'santri_nama' => $p->santri?->nama ?? 'Tanpa Nama',
                        'santri_nis' => $p->santri?->nis ?? '-',
                        'asrama_label' => $asramaLabel,
                        'asrama_sort' => $asramaSort,
                        'pelanggarans' => $pelanggarans,
                        'anon_summary' => null,
                    ];
                }
            }
        }

        foreach ($daerahGroups as &$group) {
            $santriArray = $group['santri'];
            uasort($santriArray, fn($a, $b) => $a['asrama_sort'] <=> $b['asrama_sort']);
            $group['santri'] = $santriArray;
        }
        unset($group);

        $pdf = Pdf::loadView('exports.pelanggaran-full', [
            'daerahGroups' => $daerahGroups,
            'tanggal_cetak' => now()->format('d/m/Y H:i'),
            'user' => auth()->user()->name,
        ]);

        $pdf->setPaper([0, 0, 609.45, 935.43], 'portrait');

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'aktivitas' => 'mengexport laporan pelanggaran full (PDF)',
            'model_type' => null,
            'model_id' => null,
            'data' => ['jumlah_daerah' => count($daerahGroups)],
        ]);

        return $pdf->download('export-pelanggaran-full-' . now()->format('Y-m-d') . '.pdf');
    }

    protected function formatPeriode(?string $tanggalMulai, ?string $tanggalSelesai, $bulan, $tahun): string
    {
        if ($tanggalMulai && $tanggalSelesai) {
            $d1 = \Carbon\Carbon::parse($tanggalMulai)->format('d/m/Y');
            $d2 = \Carbon\Carbon::parse($tanggalSelesai)->format('d/m/Y');
            return "{$d1} - {$d2}";
        }
        if ($bulan && $tahun) {
            $namaBulan = \Carbon\Carbon::create()->month($bulan)->format('F');
            return "{$namaBulan} {$tahun}";
        }
        if ($tahun) {
            return "Tahun {$tahun}";
        }
        return 'Semua Waktu';
    }
}
