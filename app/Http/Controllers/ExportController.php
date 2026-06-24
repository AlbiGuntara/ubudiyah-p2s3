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
    ) {}

    public function excelBulanan(Request $request)
    {
        $bulan = $request->bulan ?? now()->month;
        $tahun = $request->tahun ?? now()->year;
        $data = $this->laporanService->laporanBulanan($bulan, $tahun, $request->daerah_id, $request->asrama_id, $request->iksass);

        return Excel::download(
            new LaporanExport($data, 'Laporan Bulanan', "Bulan {$bulan} Tahun {$tahun}"),
            'laporan-bulanan.xlsx'
        );
    }

    public function excelTahunan(Request $request)
    {
        $tahun = $request->tahun ?? now()->year;
        $data = $this->laporanService->laporanTahunan($tahun, $request->daerah_id, $request->asrama_id, $request->iksass);

        return Excel::download(
            new LaporanExport($data, 'Laporan Tahunan', "Tahun {$tahun}"),
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
}
