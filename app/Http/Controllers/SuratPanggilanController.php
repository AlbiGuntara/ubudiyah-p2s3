<?php

namespace App\Http\Controllers;

use App\Models\Asrama;
use App\Models\AuditLog;
use App\Models\Pelanggaran;
use App\Models\SuratPanggilan;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SuratPanggilanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:cetak_surat_panggilan');
    }

    public function cetak(Request $request): Response|RedirectResponse
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $query = Pelanggaran::with(['santri', 'daftarPelanggaran', 'asrama.daerah'])
            ->belumTercetak();

        if ($request->filled('daerah_id')) {
            $query->whereHas('asrama', fn($q) => $q->where('daerah_id', $request->daerah_id));
        }

        $unprintedPelanggaran = $query->get()->groupBy('asrama_id');

        if ($unprintedPelanggaran->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada pelanggaran baru yang belum dicetak.');
        }

        $asramas = Asrama::with('daerah')
            ->whereIn('id', $unprintedPelanggaran->keys())
            ->get()
            ->keyBy('id');

        $printedAt = now();
        $letters = [];
        $pendingInserts = [];

        foreach ($unprintedPelanggaran as $asramaId => $pelanggaranList) {
            $asrama = $asramas->get($asramaId);
            if (!$asrama) continue;

            $count = SuratPanggilan::where('asrama_id', $asramaId)->count() + 1;
            $kodeSurat = sprintf(
                'SP/U/%s/%03d/%s',
                $asrama->nomor,
                $count,
                now()->format('m/Y')
            );

            $letters[] = [
                'surat' => (object) ['tanggal_cetak' => $printedAt],
                'asrama' => $asrama,
                'pelanggaran' => $pelanggaranList,
            ];

            $pendingInserts[] = [
                'asrama_id' => $asramaId,
                'kode_surat' => $kodeSurat,
                'pelanggaran_ids' => $pelanggaranList->pluck('id')->toArray(),
            ];
        }

        $pdf = Pdf::loadView('pdf.surat-panggilan', [
            'letters' => $letters,
            'tanggal_cetak' => $printedAt->format('d/m/Y H:i'),
            'user' => auth()->user()->name,
        ]);

        $pdf->setPaper('A4', 'portrait');

        try {
            $content = $pdf->output();
        } catch (\Exception $e) {
            Log::error('Gagal mencetak surat panggilan: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mencetak surat panggilan. Data terlalu besar, coba cetak per daerah.');
        }

        DB::beginTransaction();
        try {
            foreach ($pendingInserts as $data) {
                $surat = SuratPanggilan::create([
                    'asrama_id' => $data['asrama_id'],
                    'kode_surat' => $data['kode_surat'],
                    'tanggal_cetak' => now(),
                    'printed_at' => $printedAt,
                    'dicetak_oleh' => auth()->id(),
                ]);
                $surat->pelanggaran()->attach($data['pelanggaran_ids']);
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'aktivitas' => 'mencetak surat panggilan',
                'model_type' => SuratPanggilan::class,
                'model_id' => null,
                'data' => [
                    'jumlah_surat' => count($pendingInserts),
                    'jumlah_pelanggaran' => $unprintedPelanggaran->flatten()->count(),
                    'printed_at' => $printedAt->toDateTimeString(),
                ],
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan riwayat cetak: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menyimpan riwayat cetak. Silakan coba lagi.');
        }

        return response($content, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="surat-panggilan-ubudiyah-' . now()->format('Y-m-d') . '.pdf"');
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
                'tanggal_cetak' => $s->tanggal_cetak->format('d/m/Y H:i'),
                'jumlah_pelanggaran' => $s->pelanggaran()->count(),
                'pencetak' => $s->pencetak?->name,
            ]);

        return response()->json($suratPanggilan);
    }

    public function riwayatGlobal(): JsonResponse
    {
        $suratPanggilan = SuratPanggilan::with(['asrama.daerah', 'pencetak'])
            ->latest('printed_at')
            ->get()
            ->groupBy(fn ($s) => $s->printed_at?->format('Y-m-d H:i:s')
                ?? $s->tanggal_cetak?->format('Y-m-d H:i:s')
                ?? $s->created_at->format('Y-m-d H:i:s'))
            ->map(fn ($items) => [
                'printed_at' => $items->first()->printed_at?->format('Y-m-d H:i:s')
                    ?? $items->first()->tanggal_cetak?->format('Y-m-d H:i:s')
                    ?? $items->first()->created_at->format('Y-m-d H:i:s'),
                'tanggal_display' => $items->first()->printed_at?->format('d/m/Y H:i')
                    ?? $items->first()->tanggal_cetak?->format('d/m/Y H:i')
                    ?? $items->first()->created_at->format('d/m/Y H:i'),
                'pencetak' => $items->first()->pencetak?->name,
                'jumlah_surat' => $items->count(),
                'jumlah_pelanggaran' => $items->sum(fn ($s) => $s->pelanggaran()->count()),
                'asramas' => $items->map(fn ($s) =>
                    $s->asrama
                        ? ($s->asrama->daerah?->kode
                            ? substr($s->asrama->daerah->kode, 0, 1) . '.' . $s->asrama->nomor
                            : (string) $s->asrama->nomor)
                        : '-'
                )->values(),
            ])
            ->values();

        return response()->json($suratPanggilan);
    }

    public function destroySession(Request $request): JsonResponse
    {
        $request->validate(['printed_at' => 'required|string']);

        $records = SuratPanggilan::where('printed_at', $request->printed_at)->get();

        foreach ($records as $surat) {
            $surat->pelanggaran()->detach();
            $surat->delete();
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'aktivitas' => 'menghapus sesi cetak surat panggilan',
            'model_type' => SuratPanggilan::class,
            'model_id' => null,
            'data' => [
                'printed_at' => $request->printed_at,
                'jumlah_surat' => $records->count(),
            ],
        ]);

        return response()->json(['success' => true, 'deleted' => $records->count()]);
    }

    public function cetakUlang(SuratPanggilan $suratPanggilan): Response|RedirectResponse
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

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

        try {
            $content = $pdf->output();
        } catch (\Exception $e) {
            Log::error('Gagal mencetak ulang surat panggilan: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mencetak ulang surat panggilan. Data terlalu besar.');
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'aktivitas' => 'mencetak ulang surat panggilan',
            'model_type' => SuratPanggilan::class,
            'model_id' => $suratPanggilan->id,
            'data' => [
                'kode_surat' => $suratPanggilan->kode_surat,
                'asrama_id' => $suratPanggilan->asrama_id,
            ],
        ]);

        $filename = 'surat-panggilan-ubudiyah-' . $suratPanggilan->kode_surat . '.pdf';
        $filename = str_replace('/', '-', $filename);

        return response($content, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
