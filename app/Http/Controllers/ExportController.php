<?php
namespace App\Http\Controllers;

use App\Exports\LaporanExport;
use App\Http\Services\LaporanService;
use App\Models\AuditLog;
use App\Models\Pelanggaran;
use App\Models\Pembinaan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function __construct(
        protected LaporanService $laporanService
    ) {
        $this->middleware('permission:export_laporan');
    }

    public function excelSection(Request $request, string $section)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $valid = ['per_daerah', 'per_asrama', 'per_jenis_pelanggaran', 'per_iksass', 'per_nama'];
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

        try {
            $content = Excel::raw(
                new \App\Exports\LaporanSingleExport($data, $periode, $section),
                ExcelType::XLSX
            );
        } catch (\Exception $e) {
            Log::error('Gagal export Excel section: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengexport laporan. Data terlalu besar, coba dengan filter yang lebih spesifik.');
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'aktivitas' => 'mengexport laporan ' . str_replace('_', ' ', $section) . ' (Excel)',
            'model_type' => null,
            'model_id' => null,
            'data' => ['section' => $section, 'periode' => $periode],
        ]);

        return response($content, 200)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="laporan-' . $section . '.xlsx"');
    }

    public function excelKomprehensif(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

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

        try {
            $content = Excel::raw(
                new LaporanExport($data, $periode),
                ExcelType::XLSX
            );
        } catch (\Exception $e) {
            Log::error('Gagal export Excel komprehensif: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengexport laporan. Data terlalu besar, coba dengan filter yang lebih spesifik.');
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'aktivitas' => 'mengexport laporan komprehensif (Excel)',
            'model_type' => null,
            'model_id' => null,
            'data' => ['periode' => $periode],
        ]);

        return response($content, 200)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="laporan-ubudiyah-p2s3.xlsx"');
    }

    public function excelBulanan(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $bulan = $request->bulan ?? now()->month;
        $tahun = $request->tahun ?? now()->year;
        $data = $this->laporanService->laporanBulanan($bulan, $tahun, $request->daerah_id, $request->asrama_id, $request->iksass);

        try {
            $content = Excel::raw(
                new \App\Exports\LaporanLegacyExport($data, 'Laporan Bulanan', "Bulan {$bulan} Tahun {$tahun}"),
                ExcelType::XLSX
            );
        } catch (\Exception $e) {
            Log::error('Gagal export Excel bulanan: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengexport laporan bulanan. Data terlalu besar.');
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'aktivitas' => 'mengexport laporan bulanan (Excel)',
            'model_type' => null,
            'model_id' => null,
            'data' => ['bulan' => $bulan, 'tahun' => $tahun],
        ]);

        return response($content, 200)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="laporan-bulanan.xlsx"');
    }

    public function excelTahunan(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $tahun = $request->tahun ?? now()->year;
        $data = $this->laporanService->laporanTahunan($tahun, $request->daerah_id, $request->asrama_id, $request->iksass);

        try {
            $content = Excel::raw(
                new \App\Exports\LaporanLegacyExport($data, 'Laporan Tahunan', "Tahun {$tahun}"),
                ExcelType::XLSX
            );
        } catch (\Exception $e) {
            Log::error('Gagal export Excel tahunan: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengexport laporan tahunan. Data terlalu besar.');
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'aktivitas' => 'mengexport laporan tahunan (Excel)',
            'model_type' => null,
            'model_id' => null,
            'data' => ['tahun' => $tahun],
        ]);

        return response($content, 200)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="laporan-tahunan.xlsx"');
    }

    public function pdfBulanan(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

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

        try {
            $content = $pdf->output();
        } catch (\Exception $e) {
            Log::error('Gagal export PDF bulanan: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengexport laporan bulanan. Data terlalu besar.');
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'aktivitas' => 'mengexport laporan bulanan (PDF)',
            'model_type' => null,
            'model_id' => null,
            'data' => ['bulan' => $bulan, 'tahun' => $tahun],
        ]);

        return response($content, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="laporan-bulanan.pdf"');
    }

    public function pdfTahunan(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $tahun = $request->tahun ?? now()->year;
        $data = $this->laporanService->laporanTahunan($tahun, $request->daerah_id, $request->asrama_id, $request->iksass);

        $pdf = Pdf::loadView('exports.laporan', [
            'data' => $data,
            'judul' => 'Laporan Tahunan',
            'periode' => "Tahun {$tahun}",
            'tanggal_cetak' => now()->format('d/m/Y H:i'),
            'user' => auth()->user()->name,
        ]);

        try {
            $content = $pdf->output();
        } catch (\Exception $e) {
            Log::error('Gagal export PDF tahunan: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengexport laporan tahunan. Data terlalu besar.');
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'aktivitas' => 'mengexport laporan tahunan (PDF)',
            'model_type' => null,
            'model_id' => null,
            'data' => ['tahun' => $tahun],
        ]);

        return response($content, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="laporan-tahunan.pdf"');
    }

    public function pdfPelanggaranFull(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $daerahId = $request->input('daerah_id');
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        $pembinaans = Pembinaan::with(['santri.asrama.daerah', 'asrama.daerah'])
            ->where(function ($q) {
                $q->whereNull('santri_id')
                    ->orWhereHas('santri', fn ($sq) => $sq->whereIn('status', ['aktif', 'tidak aktif']));
            })
            ->when($daerahId, function ($q) use ($daerahId) {
                $q->where(function ($sub) use ($daerahId) {
                    $sub->whereHas('asrama', fn ($sq) => $sq->where('daerah_id', $daerahId))
                        ->orWhereHas('santri', fn ($sq) => $sq->whereHas('asrama', fn ($sq2) => $sq2->where('daerah_id', $daerahId)));
                });
            })
            ->get();

        $santriIds = $pembinaans->whereNotNull('santri_id')->pluck('santri_id')->unique()->values();
        $anonAsramaIds = $pembinaans->whereNull('santri_id')->pluck('asrama_id')->unique()->values();

        $pelanggaranBySantri = Pelanggaran::with('daftarPelanggaran')
            ->whereIn('santri_id', $santriIds)
            ->when($tanggalMulai, fn ($q) => $q->whereDate('tanggal', '>=', $tanggalMulai))
            ->when($tanggalSelesai, fn ($q) => $q->whereDate('tanggal', '<=', $tanggalSelesai))
            ->orderBy('tanggal')
            ->get()
            ->groupBy('santri_id');

        $anonPelanggaranByAsrama = Pelanggaran::with('daftarPelanggaran')
            ->whereNull('santri_id')
            ->whereIn('asrama_id', $anonAsramaIds)
            ->when($tanggalMulai, fn ($q) => $q->whereDate('tanggal', '>=', $tanggalMulai))
            ->when($tanggalSelesai, fn ($q) => $q->whereDate('tanggal', '<=', $tanggalSelesai))
            ->get()
            ->groupBy('asrama_id');

        $anonCounts = [];
        foreach ($anonPelanggaranByAsrama as $asramaId => $pelanggarans) {
            $anonCounts[$asramaId] = $pelanggarans->sum('jumlah');
        }

        if ($tanggalMulai || $tanggalSelesai) {
            $pembinaans = $pembinaans->filter(function ($p) use ($pelanggaranBySantri, $anonPelanggaranByAsrama) {
                if (!is_null($p->santri_id)) {
                    return isset($pelanggaranBySantri[$p->santri_id]) && $pelanggaranBySantri[$p->santri_id]->count() > 0;
                }
                return isset($anonPelanggaranByAsrama[$p->asrama_id]) && $anonPelanggaranByAsrama[$p->asrama_id]->count() > 0;
            });
        }

        $daerahGroups = [];
        $warnaStatus = function (Pembinaan $p): string {
            if ((int) $p->sisa_sanksi === 0) {
                return 'selesai';
            }
            if ((int) $p->shalawat_tertulis > 0) {
                return 'sebagian';
            }
            return 'belum';
        };

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
                        'warna_status' => $warnaStatus($p),
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
                        'warna_status' => $warnaStatus($p),
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

        try {
            $content = $pdf->output();
        } catch (\Exception $e) {
            Log::error('Gagal export PDF pelanggaran full: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengexport laporan pelanggaran. Data terlalu besar.');
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'aktivitas' => 'mengexport laporan pelanggaran full (PDF)',
            'model_type' => null,
            'model_id' => null,
            'data' => ['jumlah_daerah' => count($daerahGroups)],
        ]);

        return response($content, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="export-pelanggaran-full-' . now()->format('Y-m-d') . '.pdf"');
    }

    protected function formatPeriode(?string $tanggalMulai, ?string $tanggalSelesai, $bulan, $tahun): string
    {
        if ($tanggalMulai && $tanggalSelesai) {
            $d1 = \Carbon\Carbon::parse($tanggalMulai)->format('d/m/Y');
            $d2 = \Carbon\Carbon::parse($tanggalSelesai)->format('d/m/Y');
            return "{$d1} - {$d2}";
        }
        if ($bulan && $tahun) {
            $namaBulan = \Carbon\Carbon::create()->month((int) $bulan)->format('F');
            return "{$namaBulan} {$tahun}";
        }
        if ($tahun) {
            return "Tahun {$tahun}";
        }
        return 'Semua Waktu';
    }
}
