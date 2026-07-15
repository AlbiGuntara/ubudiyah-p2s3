<?php
namespace App\Http\Controllers;

use App\Http\Services\LaporanService;
use App\Models\Daerah;
use App\Models\Asrama;
use App\Models\DaftarPelanggaran;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function __construct(
        protected LaporanService $laporanService
    ) {
        $this->middleware('permission:view_laporan');
    }

    public function index(Request $request): Response
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

        return Inertia::render('laporan/index', [
            'data' => $data,
            'daerah' => Daerah::all(),
            'asrama' => Asrama::with('daerah')->get(),
            'jenisPelanggaran' => DaftarPelanggaran::all(),
            'tahunTersedia' => $this->laporanService->getTahunTersedia(),
            'filters' => $request->only(['tanggal_mulai', 'tanggal_selesai', 'bulan', 'tahun', 'daerah_id', 'asrama_id', 'iksass', 'sumber_pencatatan']),
        ]);
    }

    public function bulanan(Request $request): Response
    {
        $bulan = $request->bulan ?? now()->month;
        $tahun = $request->tahun ?? now()->year;

        $data = $this->laporanService->laporanBulanan(
            $bulan, $tahun,
            $request->daerah_id,
            $request->asrama_id,
            $request->iksass
        );

        return Inertia::render('laporan/bulanan', [
            'data' => $data,
            'daerah' => Daerah::all(),
            'asrama' => Asrama::with('daerah')->get(),
            'filters' => $request->only(['bulan', 'tahun', 'daerah_id', 'asrama_id', 'iksass']),
        ]);
    }

    public function tahunan(Request $request): Response
    {
        $tahun = $request->tahun ?? now()->year;

        $data = $this->laporanService->laporanTahunan(
            $tahun,
            $request->daerah_id,
            $request->asrama_id,
            $request->iksass
        );

        return Inertia::render('laporan/tahunan', [
            'data' => $data,
            'daerah' => Daerah::all(),
            'asrama' => Asrama::with('daerah')->get(),
            'filters' => $request->only(['tahun', 'daerah_id', 'asrama_id', 'iksass']),
        ]);
    }

    public function total(Request $request): Response
    {
        $data = $this->laporanService->laporanTotalPelanggaran(
            $request->daerah_id,
            $request->asrama_id
        );

        return Inertia::render('laporan/total', [
            'data' => $data,
            'daerah' => Daerah::all(),
            'asrama' => Asrama::with('daerah')->get(),
            'filters' => $request->only(['daerah_id', 'asrama_id']),
        ]);
    }

    public function perDaerah(): Response
    {
        $data = $this->laporanService->laporanPerDaerah();

        return Inertia::render('laporan/per-daerah', [
            'data' => $data,
        ]);
    }

    public function perAsrama(Request $request): Response
    {
        $data = $this->laporanService->laporanPerAsrama($request->asrama_id);
        $asrama = Asrama::with('daerah')->get();

        return Inertia::render('laporan/per-asrama', [
            'data' => $data,
            'asrama' => $asrama,
            'filters' => $request->only(['asrama_id']),
        ]);
    }
}
