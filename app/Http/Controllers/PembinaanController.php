<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePembinaanRequest;
use App\Models\Asrama;
use App\Models\AuditLog;
use App\Models\Daerah;
use App\Models\Pelanggaran;
use App\Models\Pembinaan;
use App\Models\Santri;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PembinaanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_pembinaan', ['only' => ['index']]);
        $this->middleware('permission:create_pembinaan', ['only' => ['store']]);
        $this->middleware('permission:edit_pembinaan', ['only' => ['update']]);
        $this->middleware('permission:edit_pembinaan', ['only' => ['setorSanksi']]);
        $this->middleware('permission:edit_pembinaan', ['only' => ['tambahSanksi']]);
        $this->middleware('role:super_admin|pembina', ['only' => ['pemutihan']]);
    }

    public function index(Request $request): Response
    {
        $query = Pembinaan::with(['santri.asrama.daerah', 'asrama.daerah', 'setoran']);

        // Search by santri name or asrama name (for anonymous)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('santri', function ($sq) use ($search) {
                    $sq->where('nama', 'like', "%{$search}%")
                        ->orWhere('nama_panggilan', 'like', "%{$search}%");
                })->orWhereHas('asrama', function ($aq) use ($search) {
                    $aq->whereHas('daerah', function ($dq) use ($search) {
                        $dq->where('kode', 'like', "%{$search}%");
                    })->orWhere('nomor', 'like', "%{$search}%");
                });
            });
        }

        // Filter by Daerah
        if ($request->filled('daerah_id')) {
            $daerahId = $request->daerah_id;
            $query->where(function ($q) use ($daerahId) {
                $q->whereHas('santri.asrama', function ($sq) use ($daerahId) {
                    $sq->where('daerah_id', $daerahId);
                })->orWhereHas('asrama', function ($aq) use ($daerahId) {
                    $aq->where('daerah_id', $daerahId);
                });
            });
        }

        // Filter by Asrama
        if ($request->filled('asrama_id')) {
            $asramaId = $request->asrama_id;
            $query->where(function ($q) use ($asramaId) {
                $q->whereHas('santri', function ($sq) use ($asramaId) {
                    $sq->where('asrama_id', $asramaId);
                })->orWhere('asrama_id', $asramaId);
            });
        }

        $sortColumn = $request->input('sort_column', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['created_at', 'sanksi', 'sisa_sanksi'];
        if (in_array($sortColumn, $allowedSorts)) {
            $query->orderBy($sortColumn, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $pembinaan = $query->paginate((int) $request->input('per_page', 15));
        $santri = Santri::with('asrama.daerah')->get();
        $daerah = Daerah::all();
        $asrama = Asrama::with('daerah')->get();

        return Inertia::render('pembinaan/index', [
            'pembinaan' => $pembinaan,
            'santri' => $santri,
            'daerah' => $daerah,
            'asrama' => $asrama,
            'filters' => $request->only(['search', 'daerah_id', 'asrama_id', 'per_page', 'sort_column', 'sort_direction']),
        ]);
    }

    public function update(StorePembinaanRequest $request, Pembinaan $pembinaan): RedirectResponse
    {
        $data = $request->validated();

        // If sanksi is updated, recalculate sisa_sanksi
        if (isset($data['sanksi'])) {
            $shalawatTertulis = $data['shalawat_tertulis'] ?? $pembinaan->shalawat_tertulis;
            $data['sisa_sanksi'] = max(0, (int) $data['sanksi'] - (int) $shalawatTertulis);
        }

        $pembinaan->update($data);

        return redirect()->back()->with('success', 'Pembinaan berhasil diubah.');
    }

    /**
     * Setor sanksi: reduce sisa_sanksi (dynamic), keep sanksi (fixed record)
     */
    public function setorSanksi(Request $request, Pembinaan $pembinaan): RedirectResponse
    {
        $validated = $request->validate([
            'pelanggaran_ids' => 'required|array|min:1',
            'pelanggaran_ids.*' => 'integer',
            'jumlah_setoran' => 'required|integer|min:1',
            'tanggal_setor' => 'nullable|date',
        ]);

        $jumlahSetoran = (int) $validated['jumlah_setoran'];
        $tanggalSetor = $validated['tanggal_setor'] ?? now()->format('Y-m-d');
        $pelanggaranIds = array_values(array_unique($validated['pelanggaran_ids']));

        // Determine which pelanggaran belong to this pembinaan
        // Preserve the user's selection order so the "last selected"
        // pelanggaran absorbs the remainder of the setor.
        $poolPelanggaran = $this->getPembinaanPelanggaran($pembinaan)
            ->whereIn('id', $pelanggaranIds)
            ->keyBy('id');

        if ($poolPelanggaran->count() !== count($pelanggaranIds)) {
            return redirect()->back()->with('error', 'Beberapa pelanggaran terpilih tidak valid atau sudah diselesaikan.');
        }

        $pelanggarans = collect($pelanggaranIds)
            ->map(fn ($id) => $poolPelanggaran->get((int) $id))
            ->filter()
            ->values();

        $count = $pelanggarans->count();

        // Max = sum of remaining sisa of the selected pelanggaran.
        // Min = max - sisa of the last selected pelanggaran (i.e. at least
        // settle all but the last one fully). When every selected pelanggaran
        // is fully unsettled (100 each), this becomes count*100 and (count-1)*100.
        $maxSetoran = $pelanggarans->sum('sisa_sanksi');
        $minSetoran = $maxSetoran - (int) $pelanggarans->last()->sisa_sanksi;

        if ($jumlahSetoran > $maxSetoran) {
            return redirect()->back()->with('error', "Jumlah setoran maksimal {$maxSetoran} sesuai jumlah pelanggaran yang dipilih.");
        }

        if ($jumlahSetoran < $minSetoran) {
            return redirect()->back()->with('error', "Jumlah setoran minimal {$minSetoran} sesuai jumlah pelanggaran yang dipilih.");
        }

        // Distribute: settle every selected pelanggaran except the last one fully,
        // the last selected pelanggaran absorbs the remainder.
        foreach ($pelanggarans as $index => $pelanggaran) {
            if ($index === $count - 1) {
                $pelanggaran->sisa_sanksi = $maxSetoran - $jumlahSetoran;
                $pelanggaran->save();
            } else {
                $pelanggaran->sisa_sanksi = 0;
                $pelanggaran->save();
            }
        }

        $pembinaan->setoran()->create([
            'jumlah' => $jumlahSetoran,
            'tanggal_setor' => $tanggalSetor,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
        ]);

        $pembinaan->shalawat_tertulis += $jumlahSetoran;
        $pembinaan->sisa_sanksi = max(0, $pembinaan->sisa_sanksi - $jumlahSetoran);
        $pembinaan->save();

        return redirect()->back()->with('success', "Sanksi {$jumlahSetoran} berhasil disetor. Sisa sanksi: {$pembinaan->sisa_sanksi}.");
    }

    /**
     * Tambah sanksi kembali: mengoreksi setoran yang salah.
     * Menambah sisa_sanksi dan mengurangi shalawat_tertulis,
     * dibatasi tidak boleh melebihi total sanksi yang sudah disetor.
     */
    public function tambahSanksi(Request $request, Pembinaan $pembinaan): RedirectResponse
    {
        $validated = $request->validate([
            'jumlah_tambah' => 'required|integer|min:1',
            'tanggal_koreksi' => 'nullable|date',
        ]);

        $jumlahTambah = (int) $validated['jumlah_tambah'];
        $tanggalKoreksi = $validated['tanggal_koreksi'] ?? now()->format('Y-m-d');

        // Cannot add back more than the total that has been setor
        if ($jumlahTambah > $pembinaan->shalawat_tertulis) {
            return redirect()->back()->with('error', 'Jumlah tambah sanksi melebihi total sanksi yang sudah disetor.');
        }

        $pembinaan->setoran()->create([
            'jumlah' => -$jumlahTambah,
            'tanggal_setor' => $tanggalKoreksi,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'keterangan' => 'Koreksi tambah sanksi',
        ]);

        // Restore the amount back to individual pelanggaran so the sum of
        // per-pelanggaran sisa stays consistent with pembinaan.sisa_sanksi.
        if ($pembinaan->santri_id) {
            $query = Pelanggaran::where('santri_id', $pembinaan->santri_id);
        } else {
            $query = Pelanggaran::whereNull('santri_id')->where('asrama_id', $pembinaan->asrama_id);
        }
        $pelanggarans = $query->get();
        $remaining = $jumlahTambah;
        // Reverse of the setor distribution: restore the amount back into the
        // pelanggaran (fully-settled ones first) so the sum of per-pelanggaran
        // sisa stays consistent with pembinaan.sisa_sanksi. No fixed
        // per-pelanggaran cap is applied because pemutihan may have scaled
        // sisa_sanksi beyond jumlah * 100; over-restoration is already bounded
        // by the pembinaan-level shalawat_tertulis check above.
        $pelanggarans = $pelanggarans->sortBy(fn ($pl) => $pl->sisa_sanksi);
        foreach ($pelanggarans as $pelanggaran) {
            if ($remaining <= 0) {
                break;
            }
            $pelanggaran->sisa_sanksi = $pelanggaran->sisa_sanksi + $remaining;
            $pelanggaran->save();
            $remaining = 0;
        }

        $pembinaan->shalawat_tertulis = max(0, $pembinaan->shalawat_tertulis - $jumlahTambah);
        $pembinaan->sisa_sanksi += $jumlahTambah;
        $pembinaan->save();

        return redirect()->back()->with('success', "Sanksi {$jumlahTambah} berhasil ditambahkan kembali. Sisa sanksi: {$pembinaan->sisa_sanksi}.");
    }

    /**
     * Pemutihan: multiply sisa_sanksi by a multiplier
     */
    public function pemutihan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'multiplier' => 'required|integer|min:1',
        ]);

        $multiplier = (int) $validated['multiplier'];

        // Multiply all sisa_sanksi records
        Pembinaan::where('sisa_sanksi', '>', 0)->chunkById(100, function ($pembinaans) use ($multiplier) {
            foreach ($pembinaans as $pembinaan) {
                $pembinaan->sisa_sanksi = $pembinaan->sisa_sanksi * $multiplier;
                $pembinaan->save();

                $query = Pelanggaran::where('sisa_sanksi', '>', 0);
                if ($pembinaan->santri_id) {
                    $query->where('santri_id', $pembinaan->santri_id);
                } else {
                    $query->whereNull('santri_id')->where('asrama_id', $pembinaan->asrama_id);
                }

                $query->get()->each(function ($pelanggaran) use ($multiplier) {
                    $pelanggaran->sisa_sanksi = $pelanggaran->sisa_sanksi * $multiplier;
                    $pelanggaran->save();
                });
            }
        });

        return redirect()->back()->with('success', "Pemutihan berhasil! Semua sisa sanksi dikalikan {$multiplier}.");
    }

    /**
     * Return the list of pelanggaran for a given pembinaan (only unsettled ones),
     * used by the setor sanksi modal.
     */
    public function getPelanggaran(Pembinaan $pembinaan): JsonResponse
    {
        $pelanggarans = $this->getPembinaanPelanggaran($pembinaan);

        return response()->json($pelanggarans->map(fn ($p) => [
            'id' => $p->id,
            'nama_pelanggaran' => $p->daftarPelanggaran?->nama_pelanggaran ?? '-',
            'tanggal' => $p->tanggal?->format('d/m/Y'),
            'sisa_sanksi' => (int) $p->sisa_sanksi,
            'sanksi' => (int) $p->jumlah * 100,
        ]));
    }

    /**
     * Query pelanggaran belonging to a pembinaan.
     * For named santri: match santri_id. For anonymous: match asrama_id with null santri_id.
     * Only returns pelanggaran that still have remaining sanction (sisa_sanksi > 0).
     */
    private function getPembinaanPelanggaran(Pembinaan $pembinaan): Collection
    {
        $query = Pelanggaran::with('daftarPelanggaran')
            ->where('sisa_sanksi', '>', 0);

        if ($pembinaan->santri_id) {
            $query->where('santri_id', $pembinaan->santri_id);
        } else {
            $query->whereNull('santri_id')
                ->where('asrama_id', $pembinaan->asrama_id);
        }

        return $query->orderBy('tanggal')->get();
    }

    public function cetak(Request $request): \Illuminate\Http\Response|RedirectResponse
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $filterInfo = [];
        $hasFilter = $request->filled('bulan') || $request->filled('tanggal_awal') || $request->filled('tanggal_akhir') || $request->filled('daerah_id');

        if ($hasFilter) {
            $pelanggaranQuery = Pelanggaran::with('daftarPelanggaran')
                ->where('sisa_sanksi', '>', 0)
                ->orderBy('tanggal');

            if ($request->filled('bulan')) {
                $bulan = $request->bulan;
                $pelanggaranQuery->whereYear('tanggal', (int) substr($bulan, 0, 4))
                    ->whereMonth('tanggal', (int) substr($bulan, 5, 2));
                $filterInfo['bulan'] = $bulan;
            }

            if ($request->filled('tanggal_awal')) {
                $pelanggaranQuery->whereDate('tanggal', '>=', $request->tanggal_awal);
                $filterInfo['tanggal_awal'] = $request->tanggal_awal;
            }

            if ($request->filled('tanggal_akhir')) {
                $pelanggaranQuery->whereDate('tanggal', '<=', $request->tanggal_akhir);
                $filterInfo['tanggal_akhir'] = $request->tanggal_akhir;
            }

            if ($request->filled('daerah_id')) {
                $daerahId = (int) $request->daerah_id;
                $pelanggaranQuery->where(function ($q) use ($daerahId) {
                    $q->whereHas('asrama', fn ($aq) => $aq->where('daerah_id', $daerahId));
                });
                $filterInfo['daerah_id'] = $daerahId;
            }

            $allPelanggaran = $pelanggaranQuery->get();
            $pelanggaranBySantri = $allPelanggaran->whereNotNull('santri_id')->groupBy('santri_id');
            $pelanggaranByAsrama = $allPelanggaran->whereNull('santri_id')->groupBy('asrama_id');
            $santriIds = $pelanggaranBySantri->keys()->toArray();

            $pembinaans = Pembinaan::with(['santri.asrama.daerah', 'asrama.daerah', 'setoran'])
                ->where('sisa_sanksi', '>', 0)
                ->where(function ($q) use ($santriIds) {
                    $q->whereNull('santri_id')
                        ->orWhereIn('santri_id', $santriIds);
                })
                ->where(function ($q) {
                    $q->whereNull('santri_id')
                        ->orWhereHas('santri', fn ($sq) => $sq->whereIn('status', ['aktif', 'tidak aktif']));
                });

            if ($request->filled('daerah_id')) {
                $daerahId = (int) $request->daerah_id;
                $pembinaans->where(function ($q) use ($daerahId) {
                    $q->whereHas('asrama', fn ($aq) => $aq->where('daerah_id', $daerahId))
                        ->orWhereHas('santri.asrama', fn ($sq) => $sq->where('daerah_id', $daerahId));
                });
            }

            $pembinaans = $pembinaans->get();
        } else {
            $pembinaans = Pembinaan::with(['santri.asrama.daerah', 'asrama.daerah', 'setoran'])
                ->where('sisa_sanksi', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('santri_id')
                        ->orWhereHas('santri', fn ($sq) => $sq->whereIn('status', ['aktif', 'tidak aktif']));
                })
                ->get();

            $santriIds = $pembinaans->whereNotNull('santri_id')->pluck('santri_id')->unique()->values();

            $allPelanggaran = Pelanggaran::with('daftarPelanggaran')
                ->where('sisa_sanksi', '>', 0)
                ->orderBy('tanggal')
                ->get();

            $pelanggaranBySantri = $allPelanggaran->whereNotNull('santri_id')
                ->whereIn('santri_id', $santriIds)
                ->groupBy('santri_id');
            $pelanggaranByAsrama = $allPelanggaran->whereNull('santri_id')->groupBy('asrama_id');
        }

        $pembinaans = $pembinaans->sortBy(function ($p) {
            $nama = $p->santri?->nama ?? 'zzz';
            $asramaNomor = $p->santri?->asrama?->nomor ?? $p->asrama?->nomor ?? 0;
            $daerahId = $p->santri?->asrama?->daerah_id ?? $p->asrama?->daerah_id ?? 0;

            return [$daerahId, (int) $asramaNomor, $nama];
        });

        $groups = [];
        foreach ($pembinaans as $p) {
            $daerahId = $p->santri?->asrama?->daerah_id ?? $p->asrama?->daerah_id;
            $daerah = $p->santri?->asrama?->daerah ?? $p->asrama?->daerah;
            if (! $daerah) {
                continue;
            }

            $santriKey = $p->santri_id ? 'santri_'.$p->santri_id : 'anon_'.$p->asrama_id;

            if (! isset($groups[$daerahId])) {
                $groups[$daerahId] = [
                    'daerah' => $daerah,
                    'santri' => [],
                ];
            }

            if (! isset($groups[$daerahId]['santri'][$santriKey])) {
                $pelanggarans = collect();
                if ($p->santri_id && isset($pelanggaranBySantri[$p->santri_id])) {
                    $pelanggarans = $pelanggaranBySantri[$p->santri_id];
                } elseif (! $p->santri_id && $p->asrama_id && isset($pelanggaranByAsrama[$p->asrama_id])) {
                    $pelanggarans = $pelanggaranByAsrama[$p->asrama_id];
                }

                $groups[$daerahId]['santri'][$santriKey] = [
                    'santri' => $p->santri,
                    'asrama' => $p->santri?->asrama ?? $p->asrama,
                    'pelanggarans' => $pelanggarans,
                    'total_sanksi' => $p->sisa_sanksi,
                    'sanksi_disetor' => $p->shalawat_tertulis,
                    'tanggal_setor' => $p->setoran->sortByDesc('tanggal_setor')->first()?->tanggal_setor,
                ];
            }
        }

        if (empty($groups)) {
            return redirect()->back()->with('error', 'Tidak ada data pembinaan dengan sisa sanksi.');
        }

        $pdf = Pdf::loadView('pdf.pembinaan-cetak', [
            'groups' => $groups,
            'tanggal_cetak' => now()->format('d/m/Y H:i'),
            'user' => auth()->user()->name,
            'filterInfo' => $filterInfo,
        ]);

        $pdf->setPaper('F4', 'landscape');

        try {
            $content = $pdf->output();
        } catch (\Exception $e) {
            Log::error('Gagal mencetak laporan pembinaan: '.$e->getMessage());

            return redirect()->back()->with('error', 'Gagal mencetak laporan pembinaan. Data terlalu besar.');
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'aktivitas' => 'mencetak laporan pembinaan',
            'model_type' => Pembinaan::class,
            'model_id' => null,
            'data' => [
                'jumlah_pembinaan' => $pembinaans->count(),
                'jumlah_daerah' => count($groups),
            ],
        ]);

        return response($content, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="pembinaan-ubudiyah-'.now()->format('Y-m-d').'.pdf"');
    }
}
