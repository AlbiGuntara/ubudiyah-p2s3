<?php
namespace App\Http\Controllers;

use App\Exports\LaporanExport;
use App\Http\Services\LaporanService;
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

        return Excel::download(
            new \App\Exports\LaporanLegacyExport($data, 'Laporan Bulanan', "Bulan {$bulan} Tahun {$tahun}"),
            'laporan-bulanan.xlsx'
        );
    }

    public function excelTahunan(Request $request)
    {
        $tahun = $request->tahun ?? now()->year;
        $data = $this->laporanService->laporanTahunan($tahun, $request->daerah_id, $request->asrama_id, $request->iksass);

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

        return $pdf->download('laporan-tahunan.pdf');
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
