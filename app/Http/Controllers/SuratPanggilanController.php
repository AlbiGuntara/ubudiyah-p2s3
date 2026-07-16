<?php

namespace App\Http\Controllers;

use App\Models\Asrama;
use App\Models\Pelanggaran;
use App\Models\SuratPanggilan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SuratPanggilanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:cetak_surat_panggilan');
    }

    public function cetak(Request $request): Response|RedirectResponse
    {
        $unprintedPelanggaran = Pelanggaran::with(['santri', 'daftarPelanggaran', 'asrama.daerah'])
            ->belumTercetak()
            ->get()
            ->groupBy('asrama_id');

        if ($unprintedPelanggaran->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada pelanggaran baru yang belum dicetak.');
        }

        $asramas = Asrama::with('daerah')
            ->whereIn('id', $unprintedPelanggaran->keys())
            ->get()
            ->keyBy('id');

        $letters = [];

        foreach ($unprintedPelanggaran as $asramaId => $pelanggaranList) {
            $asrama = $asramas->get($asramaId);

            if (!$asrama) {
                continue;
            }

            $count = SuratPanggilan::where('asrama_id', $asramaId)->count() + 1;
            $kodeSurat = sprintf(
                'SP/U/%s/%03d/%s',
                $asrama->nomor,
                $count,
                now()->format('m/Y')
            );

            $surat = SuratPanggilan::create([
                'asrama_id' => $asramaId,
                'kode_surat' => $kodeSurat,
                'tanggal_cetak' => now(),
                'dicetak_oleh' => auth()->id(),
            ]);

            $pelanggaranIds = $pelanggaranList->pluck('id')->toArray();
            $surat->pelanggaran()->attach($pelanggaranIds);

            $letters[] = [
                'surat' => $surat->load('pencetak'),
                'asrama' => $asrama,
                'pelanggaran' => $pelanggaranList,
            ];
        }

        $pdf = Pdf::loadView('pdf.surat-panggilan', [
            'letters' => $letters,
            'tanggal_cetak' => now()->format('d/m/Y H:i'),
            'user' => auth()->user()->name,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('surat-panggilan-ubudiyah-' . now()->format('Y-m-d') . '.pdf');
    }

    public function riwayat(Request $request): JsonResponse
    {
        $suratPanggilan = SuratPanggilan::with(['asrama.daerah', 'pencetak'])
            ->where('asrama_id', $request->asrama_id)
            ->latest()
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'kode_surat' => $s->kode_surat,
                'tanggal_cetak' => $s->tanggal_cetak->format('d/m/Y'),
                'jumlah_pelanggaran' => $s->pelanggaran()->count(),
                'pencetak' => $s->pencetak?->name,
            ]);

        return response()->json($suratPanggilan);
    }

    public function cetakUlang(SuratPanggilan $suratPanggilan): Response
    {
        $suratPanggilan->load([
            'asrama.daerah',
            'pelanggaran.santri',
            'pelanggaran.daftarPelanggaran',
            'pencetak',
        ]);

        $letters = [
            [
                'surat' => $suratPanggilan,
                'asrama' => $suratPanggilan->asrama,
                'pelanggaran' => $suratPanggilan->pelanggaran,
            ],
        ];

        $pdf = Pdf::loadView('pdf.surat-panggilan', [
            'letters' => $letters,
            'tanggal_cetak' => now()->format('d/m/Y H:i'),
            'user' => auth()->user()->name,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = 'surat-panggilan-ubudiyah-' . $suratPanggilan->kode_surat . '.pdf';
        $filename = str_replace('/', '-', $filename);

        return $pdf->download($filename);
    }
}
