<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePelanggaranRequest;
use App\Models\Asrama;
use App\Models\Daerah;
use App\Models\DaftarPelanggaran;
use App\Models\Pelanggaran;
use App\Models\Pembinaan;
use App\Models\Santri;
use App\Traits\Auditable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PelanggaranController extends Controller
{
    use Auditable;

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

        if ($request->filled('sumber')) {
            $query->where('sumber_pencatatan', $request->sumber);
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
            'filters' => $request->only(['tanggal', 'tanggal_mulai', 'tanggal_selesai', 'daerah_id', 'asrama_id', 'iksass', 'petugas_id', 'sumber']),
        ]);
    }

    public function store(StorePelanggaranRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $santriIds = $data['santri_ids'] ?? [];

        $petugas = auth()->user()?->petugas;
        $petugasId = $petugas?->id ?? $data['petugas_id'] ?? null;

        // Create individual records for each selected santri
        if (! empty($santriIds)) {
            foreach ($santriIds as $santriId) {
                $pelanggaran = Pelanggaran::create([
                    'santri_id' => $santriId,
                    'asrama_id' => $data['asrama_id'],
                    'daftar_pelanggaran_id' => $data['daftar_pelanggaran_id'],
                    'petugas_id' => $petugasId,
                    'jumlah' => 1,
                    'sumber_pencatatan' => $data['sumber_pencatatan'],
                    'tanggal' => $data['tanggal'],
                    'keterangan' => $data['keterangan'],
                ]);
                $this->syncPembinaan($santriId);
            }
        }

        // Create mass record for anonymous santri
        $tanpaNama = (int) ($data['tanpa_nama'] ?? 0);
        if ($tanpaNama > 0) {
            Pelanggaran::create([
                'santri_id' => null,
                'asrama_id' => $data['asrama_id'],
                'daftar_pelanggaran_id' => $data['daftar_pelanggaran_id'],
                'petugas_id' => $petugasId,
                'jumlah' => $tanpaNama,
                'sumber_pencatatan' => $data['sumber_pencatatan'],
                'tanggal' => $data['tanggal'],
                'keterangan' => $data['keterangan'] ?? 'Tanpa nama',
            ]);
            $this->syncAnonymousPembinaan((int) $data['asrama_id']);
        }

        // Fallback for single santri (edit case / backward compat)
        if (empty($santriIds) && $tanpaNama === 0 && ! empty($data['santri_id'])) {
            $data['petugas_id'] = $petugasId;
            Pelanggaran::create($data);
            $this->syncPembinaan($data['santri_id']);
        }

        return redirect()->route('pelanggaran.index')->with('success', 'Pelanggaran berhasil dicatat.');
    }

    public function update(StorePelanggaranRequest $request, Pelanggaran $pelanggaran): RedirectResponse
    {
        $this->authorize('update', $pelanggaran);
        $data = $request->validated();
        $data['petugas_id'] = $data['petugas_id'] ?? $pelanggaran->petugas_id;
        $pelanggaran->update($data);

        return redirect()->route('pelanggaran.index')->with('success', 'Pelanggaran berhasil diubah.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        Pelanggaran::whereIn('id', $ids)->delete();

        return redirect()->route('pelanggaran.index')->with('success', 'Pelanggaran berhasil dihapus.');
    }

    public function destroy(Pelanggaran $pelanggaran): RedirectResponse
    {
        $this->authorize('delete', $pelanggaran);
        $pelanggaran->delete();

        return redirect()->route('pelanggaran.index')->with('success', 'Pelanggaran berhasil dihapus.');
    }

    private function syncPembinaan(int $santriId): void
    {
        $totalPelanggaran = Pelanggaran::where('santri_id', $santriId)->count();
        $sanksi = $totalPelanggaran * 100;

        $pembinaan = Pembinaan::firstOrNew(['santri_id' => $santriId]);
        $pembinaan->sanksi = $sanksi;
        // Only set sisa_sanksi if it's a new record or zero (first time)
        if (! $pembinaan->exists || $pembinaan->sisa_sanksi === 0) {
            $pembinaan->sisa_sanksi = max(0, $sanksi - $pembinaan->shalawat_tertulis);
        }
        // If sanksi increased, add the difference to sisa_sanksi
        if ($pembinaan->exists && $sanksi > $pembinaan->getOriginal('sanksi')) {
            $pembinaan->sisa_sanksi += ($sanksi - $pembinaan->getOriginal('sanksi'));
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
        $sanksi = $totalPelanggaran * 100;

        $pembinaan = Pembinaan::firstOrNew([
            'santri_id' => null,
            'asrama_id' => $asramaId,
        ]);
        $pembinaan->sanksi = $sanksi;
        // Only set sisa_sanksi if it's a new record or zero
        if (! $pembinaan->exists || $pembinaan->sisa_sanksi === 0) {
            $pembinaan->sisa_sanksi = max(0, $sanksi - ($pembinaan->shalawat_tertulis ?? 0));
        }
        // If sanksi increased, add the difference to sisa_sanksi
        if ($pembinaan->exists && $sanksi > $pembinaan->getOriginal('sanksi')) {
            $pembinaan->sisa_sanksi += ($sanksi - $pembinaan->getOriginal('sanksi'));
        }
        $pembinaan->save();
    }
}
