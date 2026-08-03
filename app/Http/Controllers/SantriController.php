<?php
namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Santri;
use App\Models\Pelanggaran;
use App\Models\Pembinaan;
use App\Models\PembinaanSetoran;
use App\Models\Daerah;
use App\Models\Asrama;
use App\Http\Requests\StoreSantriRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Imports\SantriImport;
use Maatwebsite\Excel\Facades\Excel;

class SantriController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_santri', ['only' => ['index', 'show']]);
        $this->middleware('permission:create_santri', ['only' => ['store']]);
        $this->middleware('permission:edit_santri', ['only' => ['update']]);
        $this->middleware('permission:delete_santri', ['only' => ['destroy', 'bulkDelete']]);
        $this->middleware('permission:import_santri', ['only' => ['import']]);
    }

    public function index(Request $request): Response
    {
        $query = Santri::with('asrama.daerah');

        if ($request->filled('daerah_id')) {
            $query->whereHas('asrama', fn($q) => $q->where('daerah_id', $request->daerah_id));
        }

        if ($request->filled('asrama_id')) {
            $query->where('asrama_id', $request->asrama_id);
        }

        if ($request->filled('iksass')) {
            $query->where('iksass', 'like', "%{$request->iksass}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nama_panggilan', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        $sortColumn = $request->input('sort_column', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['nis', 'nama', 'iksass', 'nama_panggilan', 'status', 'asrama_id', 'created_at'];
        if (in_array($sortColumn, $allowedSorts)) {
            $query->orderBy($sortColumn, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $santri = $query->paginate((int) $request->input("per_page", 15));
        $daerah = Daerah::all();
        $asrama = Asrama::with('daerah')->get();

        return Inertia::render('santri/index', [
            'santri' => $santri,
            'daerah' => $daerah,
            'asrama' => $asrama,
            'filters' => $request->only(['daerah_id', 'asrama_id', 'iksass', 'status', 'search', 'per_page', 'sort_column', 'sort_direction']),
        ]);
    }

    public function store(StoreSantriRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('foto-santri', 'public');
        }

        $mergeAction = $request->input('merge_action');
        $mergeTargetId = $request->input('merge_target_id');

        if ($mergeAction === 'keep_old' && $mergeTargetId) {
            $existing = Santri::findOrFail($mergeTargetId);
            return redirect()->back()->with('success', 'Data baru dibatalkan, data ' . $existing->nama . ' tetap digunakan.');
        }

        $santri = Santri::create($data);

        $mergedCount = $this->autoMerge($santri);

        $message = 'Santri berhasil ditambahkan.';
        if ($mergedCount > 0) {
            $message .= " {$mergedCount} data duplikat otomatis digabungkan.";
        }

        return redirect()->back()->with('success', $message);
    }

    public function show(Santri $santri): Response
    {
        $santri->load(['asrama.daerah', 'pelanggaran.daftarPelanggaran', 'pembinaan.setoran']);

        return Inertia::render('santri/show', [
            'santri' => $santri,
            'statistik' => [
                'total_pelanggaran' => $santri->total_pelanggaran,
                'total_shalawat' => $santri->total_shalawat,
            ],
        ]);
    }

    public function update(StoreSantriRequest $request, Santri $santri): RedirectResponse
    {
        $data = $request->validated();

        $mergeAction = $request->input('merge_action');
        $mergeTargetId = $request->input('merge_target_id');

        if ($mergeAction === 'keep_other' && $mergeTargetId) {
            $other = Santri::findOrFail($mergeTargetId);
            $this->mergeInto($santri, $other);
            $this->syncPembinaan($other);
            return redirect()->back()->with('success', 'Riwayat pelanggaran digabungkan ke ' . $other->nama . '.');
        }

        if ($request->hasFile('foto')) {
            if ($santri->foto) {
                Storage::disk('public')->delete($santri->foto);
            }
            $data['foto'] = $request->file('foto')->store('foto-santri', 'public');
        }

        $santri->update($data);

        $mergedCount = $this->autoMerge($santri);

        $message = 'Santri berhasil diubah.';
        if ($mergedCount > 0) {
            $message .= " {$mergedCount} data duplikat otomatis digabungkan.";
        }

        return redirect()->back()->with('success', $message);
    }

    public function destroy(Santri $santri): RedirectResponse
    {
        $santri->delete();
        return redirect()->back()->with('success', 'Santri berhasil dihapus.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        Santri::whereIn('id', $ids)->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'aktivitas' => 'menghapus banyak Santri',
            'model_type' => Santri::class,
            'data' => ['ids' => $ids, 'count' => count($ids)],
        ]);

        return redirect()->back()->with('success', 'Santri berhasil dihapus.');
    }

    public function cekNis(Request $request): \Illuminate\Http\JsonResponse
    {
        $nis = $request->input('nis');
        $excludeId = $request->input('exclude_id');

        if (empty($nis)) {
            return response()->json(['found' => false]);
        }

        $existing = Santri::where('nis', $nis)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->first(['id', 'nama', 'nis']);

        return response()->json([
            'found' => $existing !== null,
            'santri' => $existing,
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);

        $import = new SantriImport;
        Excel::import($import, $request->file('file'));

        $imported = $import->getImportedCount();
        $errors = $import->getErrors();

        if (!empty($errors)) {
            $message = implode('<br>', array_slice($errors, 0, 10));
            if (count($errors) > 10) {
                $message .= '<br>... dan ' . (count($errors) - 10) . ' error lainnya';
            }
            if ($imported > 0) {
                $message = "Berhasil import {$imported} data.<br>" . $message;
            }
            return redirect()->back()->with('warning', $message);
        }

        return redirect()->back()->with('success', "Berhasil import {$imported} data santri.");
    }

    private function autoMerge(Santri $santri): int
    {
        $mergedCount = 0;
        $processedIds = [];

        if (! empty($santri->nis)) {
            $duplicates = Santri::where('nis', $santri->nis)
                ->where('id', '!=', $santri->id)
                ->get();

            foreach ($duplicates as $duplicate) {
                $this->mergeInto($duplicate, $santri);
                $processedIds[] = $duplicate->id;
                $mergedCount++;
            }
        }

        if (! empty($santri->nama) && ! empty($santri->asrama_id)) {
            $query = Santri::where('nama', $santri->nama)
                ->where('asrama_id', $santri->asrama_id)
                ->where('id', '!=', $santri->id);

            if (! empty($santri->iksass)) {
                $query->where('iksass', $santri->iksass);
            }

            $duplicates = $query->get();

            foreach ($duplicates as $duplicate) {
                if (! in_array($duplicate->id, $processedIds) && ! $duplicate->trashed()) {
                    $this->mergeInto($duplicate, $santri);
                    $mergedCount++;
                }
            }
        }

        if ($mergedCount > 0) {
            $this->syncPembinaan($santri);
        }

        return $mergedCount;
    }

    private function mergeInto(Santri $duplicate, Santri $primary): void
    {
        Pelanggaran::where('santri_id', $duplicate->id)
            ->whereNotNull('santri_id')
            ->update([
                'santri_id' => $primary->id,
                'asrama_id' => $primary->asrama_id,
            ]);

        $dupPembinaan = Pembinaan::where('santri_id', $duplicate->id)
            ->whereNotNull('santri_id')
            ->first();

        if ($dupPembinaan) {
            $primaryPembinaan = Pembinaan::firstOrNew(['santri_id' => $primary->id]);
            $primaryPembinaan->shalawat_tertulis = ($primaryPembinaan->shalawat_tertulis ?? 0) + ($dupPembinaan->shalawat_tertulis ?? 0);
            $primaryPembinaan->sisa_sanksi = ($primaryPembinaan->sisa_sanksi ?? 0) + ($dupPembinaan->sisa_sanksi ?? 0);
            $primaryPembinaan->sanksi = Pelanggaran::where('santri_id', $primary->id)->count() * 100;
            $primaryPembinaan->save();

            PembinaanSetoran::where('pembinaan_id', $dupPembinaan->id)
                ->update(['pembinaan_id' => $primaryPembinaan->id]);

            $dupPembinaan->delete();
        }

        $duplicate->delete();
    }

    private function syncPembinaan(Santri $santri): void
    {
        $totalPelanggaran = Pelanggaran::where('santri_id', $santri->id)->count();

        if ($totalPelanggaran === 0) {
            Pembinaan::where('santri_id', $santri->id)->delete();
            return;
        }

        $sanksi = $totalPelanggaran * 100;

        $pembinaan = Pembinaan::firstOrNew(['santri_id' => $santri->id]);
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
