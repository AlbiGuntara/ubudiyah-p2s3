<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePelanggaranRequest;
use App\Models\Asrama;
use App\Models\AuditLog;
use App\Models\Daerah;
use App\Models\DaftarPelanggaran;
use App\Models\Pelanggaran;
use App\Models\Pembinaan;
use App\Models\Santri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PelanggaranController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_pelanggaran', ['only' => ['index']]);
        $this->middleware('permission:create_pelanggaran', ['only' => ['store', 'storeMassal']]);
        $this->middleware('permission:edit_pelanggaran', ['only' => ['update', 'bulkUpdate']]);
        $this->middleware('permission:delete_pelanggaran', ['only' => ['destroy', 'bulkDelete']]);
    }

    public function index(Request $request): Response
    {
        $query = Pelanggaran::with(['santri', 'asrama.daerah', 'daftarPelanggaran', 'petugas']);

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_selesai);
        }

        if ($request->filled('daerah_id')) {
            $query->whereHas('asrama', fn ($q) => $q->where('daerah_id', $request->daerah_id));
        }

        if ($request->filled('asrama_id')) {
            $query->where('asrama_id', $request->asrama_id);
        }

        if ($request->filled('iksass')) {
            $query->whereHas('santri', fn ($q) => $q->where('iksass', 'like', "%{$request->iksass}%"));
        }

        if ($request->filled('petugas_id')) {
            $query->where('petugas_id', $request->petugas_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('santri', fn ($sq) => $sq->where('nama', 'like', "%{$search}%")
                    ->orWhere('nama_panggilan', 'like', "%{$search}%")
                    ->orWhere('iksass', 'like', "%{$search}%"))
                    ->orWhereHas('asrama', fn ($aq) => $aq->where('nomor', 'like', "%{$search}%")
                        ->orWhereHas('daerah', fn ($dq) => $dq->where('kode', 'like', "%{$search}%")))
                    ->orWhereHas('daftarPelanggaran', fn ($dpq) => $dpq->where('nama_pelanggaran', 'like', "%{$search}%"));
            });
        }

        $sortColumn = $request->input('sort_column', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['tanggal', 'created_at', 'sumber_pencatatan', 'asrama_id', 'jumlah', 'santri', 'asrama', 'pelanggaran'];
        if (in_array($sortColumn, $allowedSorts)) {
            $dir = $sortDirection === 'asc' ? 'asc' : 'desc';
            match ($sortColumn) {
                'santri' => $query->orderBy(
                    Santri::select('nama')->whereColumn('santri.id', 'pelanggaran.santri_id'),
                    $dir
                ),
                'asrama' => $query->orderBy(
                    Asrama::select('nomor')->whereColumn('asrama.id', 'pelanggaran.asrama_id'),
                    $dir
                ),
                'pelanggaran' => $query->orderBy(
                    DaftarPelanggaran::select('nama_pelanggaran')->whereColumn('daftar_pelanggaran.id', 'pelanggaran.daftar_pelanggaran_id'),
                    $dir
                ),
                default => $query->orderBy($sortColumn, $dir),
            };
        } else {
            $query->latest();
        }

        $pelanggaran = $query->paginate((int) $request->input('per_page', 15));
        $santri = Santri::all();
        $asrama = Asrama::with('daerah')->get();
        $daerah = Daerah::all();
        $daftarPelanggaran = DaftarPelanggaran::all();

        return Inertia::render('pelanggaran/index', [
            'pelanggaran' => $pelanggaran,
            'santri' => $santri,
            'asrama' => $asrama,
            'daerah' => $daerah,
            'daftarPelanggaran' => $daftarPelanggaran,
            'filters' => $request->only(['search', 'tanggal', 'tanggal_mulai', 'tanggal_selesai', 'daerah_id', 'asrama_id', 'iksass', 'petugas_id', 'per_page', 'sort_column', 'sort_direction']),
            'voice' => [
                'enabled' => (bool) config('voice.enabled'),
                'max_duration' => (int) config('voice.recording.max_duration'),
            ],
        ]);
    }

    public function store(StorePelanggaranRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $santriPelanggaran = $data['santri_pelanggaran'] ?? [];

        $petugas = auth()->user()?->petugas;
        $petugasId = $petugas?->id ?? $data['petugas_id'] ?? null;

        // Create individual records for each selected santri with their own violation type and date
        if (! empty($santriPelanggaran)) {
            foreach ($santriPelanggaran as $item) {
                Pelanggaran::create([
                    'santri_id' => $item['santri_id'],
                    'asrama_id' => $item['asrama_id'] ?? $data['asrama_id'],
                    'daftar_pelanggaran_id' => $item['daftar_pelanggaran_id'],
                    'petugas_id' => $petugasId,
                    'jumlah' => 1,
                    'sisa_sanksi' => 100,
                    'sumber_pencatatan' => $item['sumber_pencatatan'] ?? $data['sumber_pencatatan'],
                    'tanggal' => $item['tanggal'],
                    'keterangan' => $item['keterangan'] ?? $data['keterangan'],
                ]);
                $this->syncPembinaan((int) $item['santri_id']);
            }
        }

        // Create records for anonymous santri (each entry has jumlah + daftar_pelanggaran_id)
        $anonymousEntries = $data['anonymous_entries'] ?? [];
        if (! empty($anonymousEntries)) {
            // Satu pengiriman bisa memuat beberapa asrama, sehingga pembinaan
            // disinkronkan untuk setiap asrama yang benar-benar tersentuh.
            $asramaTersentuh = [];

            foreach ($anonymousEntries as $entry) {
                $jumlah = (int) ($entry['jumlah'] ?? 0);
                if ($jumlah > 0) {
                    $asramaId = $entry['asrama_id'] ?? $data['asrama_id'];
                    $asramaTersentuh[(int) $asramaId] = true;

                    Pelanggaran::create([
                        'santri_id' => null,
                        'asrama_id' => $asramaId,
                        'daftar_pelanggaran_id' => $entry['daftar_pelanggaran_id'],
                        'petugas_id' => $petugasId,
                        'jumlah' => $jumlah,
                        'sisa_sanksi' => $jumlah * 100,
                        'sumber_pencatatan' => $entry['sumber_pencatatan'] ?? $data['sumber_pencatatan'],
                        'tanggal' => $entry['tanggal'],
                        'keterangan' => $entry['keterangan'] ?? $data['keterangan'] ?? 'Tanpa nama',
                    ]);
                }
            }

            foreach (array_keys($asramaTersentuh) as $asramaId) {
                $this->syncAnonymousPembinaan($asramaId);
            }
        }

        // Fallback for single santri (edit case / backward compat)
        if (empty($santriPelanggaran) && empty($anonymousEntries) && ! empty($data['santri_id'])) {
            $record = $data;
            $record['petugas_id'] = $petugasId;
            $record['sisa_sanksi'] = ($record['jumlah'] ?? 1) * 100;
            Pelanggaran::create($record);
            $this->syncPembinaan((int) $data['santri_id']);
        }

        return redirect()->back()->with('success', 'Pelanggaran berhasil dicatat.');
    }

    public function update(StorePelanggaranRequest $request, Pelanggaran $pelanggaran): RedirectResponse
    {
        $this->authorize('update', $pelanggaran);
        $data = $request->validated();
        $data['petugas_id'] = $data['petugas_id'] ?? $pelanggaran->petugas_id;
        $pelanggaran->update($data);

        return redirect()->back()->with('success', 'Pelanggaran berhasil diubah.');
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:pelanggaran,id',
            'sumber_pencatatan' => 'required|in:petugas,ketua_kamar',
            'keterangan' => 'nullable|string',
        ]);

        $ids = $validated['ids'];

        Pelanggaran::whereIn('id', $ids)->update([
            'sumber_pencatatan' => $validated['sumber_pencatatan'],
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'aktivitas' => 'mengubah banyak Pelanggaran',
            'model_type' => Pelanggaran::class,
            'data' => ['ids' => $ids, 'count' => count($ids)],
        ]);

        return redirect()->back()->with('success', 'Pelanggaran berhasil diubah.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        $pelanggarans = Pelanggaran::whereIn('id', $ids)->get(['id', 'santri_id', 'asrama_id']);
        $santriIds = $pelanggarans->whereNotNull('santri_id')->pluck('santri_id')->unique()->values()->toArray();
        $asramaIds = $pelanggarans->whereNull('santri_id')->pluck('asrama_id')->unique()->values()->toArray();

        Pelanggaran::whereIn('id', $ids)->delete();

        foreach ($santriIds as $santriId) {
            $this->syncPembinaan($santriId);
        }
        foreach ($asramaIds as $asramaId) {
            $this->syncAnonymousPembinaan($asramaId);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'aktivitas' => 'menghapus banyak Pelanggaran',
            'model_type' => Pelanggaran::class,
            'data' => ['ids' => $ids, 'count' => count($ids)],
        ]);

        return redirect()->back()->with('success', 'Pelanggaran berhasil dihapus.');
    }

    public function destroy(Pelanggaran $pelanggaran): RedirectResponse
    {
        $this->authorize('delete', $pelanggaran);

        $santriId = $pelanggaran->santri_id;
        $asramaId = $pelanggaran->asrama_id;

        $pelanggaran->delete();

        if ($santriId) {
            $this->syncPembinaan($santriId);
        } else {
            $this->syncAnonymousPembinaan($asramaId);
        }

        return redirect()->back()->with('success', 'Pelanggaran berhasil dihapus.');
    }

    private function syncPembinaan(int $santriId): void
    {
        $totalPelanggaran = Pelanggaran::where('santri_id', $santriId)->count();

        if ($totalPelanggaran === 0) {
            Pembinaan::where('santri_id', $santriId)->delete();

            return;
        }

        $sanksi = $totalPelanggaran * 100;

        $pembinaan = Pembinaan::firstOrNew(['santri_id' => $santriId]);
        $pembinaan->sanksi = $sanksi;
        if (! $pembinaan->exists || $pembinaan->sisa_sanksi === 0) {
            $pembinaan->sisa_sanksi = max(0, $sanksi - $pembinaan->shalawat_tertulis);
        }
        if ($pembinaan->exists && $sanksi > $pembinaan->getOriginal('sanksi')) {
            $pembinaan->sisa_sanksi += ($sanksi - $pembinaan->getOriginal('sanksi'));
        }
        if ($pembinaan->exists && $sanksi < $pembinaan->getOriginal('sanksi')) {
            $pembinaan->sisa_sanksi = max(0, $pembinaan->sisa_sanksi - ($pembinaan->getOriginal('sanksi') - $sanksi));
        }
        $pembinaan->save();
    }

    /**
     * Sync anonymous (tanpa nama) pembinaan grouped by asrama
     */
    private function syncAnonymousPembinaan(int $asramaId): void
    {
        $totalPelanggaran = Pelanggaran::whereNull('santri_id')
            ->where('asrama_id', $asramaId)
            ->sum('jumlah');

        if ($totalPelanggaran === 0) {
            Pembinaan::whereNull('santri_id')->where('asrama_id', $asramaId)->delete();

            return;
        }

        $sanksi = $totalPelanggaran * 100;

        $pembinaan = Pembinaan::firstOrNew([
            'santri_id' => null,
            'asrama_id' => $asramaId,
        ]);
        $pembinaan->sanksi = $sanksi;
        if (! $pembinaan->exists || $pembinaan->sisa_sanksi === 0) {
            $pembinaan->sisa_sanksi = max(0, $sanksi - ($pembinaan->shalawat_tertulis ?? 0));
        }
        if ($pembinaan->exists && $sanksi > $pembinaan->getOriginal('sanksi')) {
            $pembinaan->sisa_sanksi += ($sanksi - $pembinaan->getOriginal('sanksi'));
        }
        if ($pembinaan->exists && $sanksi < $pembinaan->getOriginal('sanksi')) {
            $pembinaan->sisa_sanksi = max(0, $pembinaan->sisa_sanksi - ($pembinaan->getOriginal('sanksi') - $sanksi));
        }
        $pembinaan->save();
    }
}
