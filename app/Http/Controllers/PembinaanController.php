<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePembinaanRequest;
use App\Models\Asrama;
use App\Models\Daerah;
use App\Models\Pembinaan;
use App\Models\Santri;
use App\Traits\Auditable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PembinaanController extends Controller
{
    use Auditable;

    public function index(Request $request): Response
    {
        $query = Pembinaan::with(['santri.asrama.daerah', 'asrama.daerah']);

        // Search by santri name or asrama name (for anonymous)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('santri', function ($sq) use ($search) {
                    $sq->where('nama', 'like', "%{$search}%");
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
            'filters' => $request->only(['search', 'daerah_id', 'asrama_id']),
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

        return redirect()->route('pembinaan.index')->with('success', 'Pembinaan berhasil diubah.');
    }

    public function destroy(Pembinaan $pembinaan): RedirectResponse
    {
        $pembinaan->delete();

        return redirect()->route('pembinaan.index')->with('success', 'Pembinaan berhasil dihapus.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        Pembinaan::whereIn('id', $ids)->delete();

        return redirect()->route('pembinaan.index')->with('success', 'Pembinaan berhasil dihapus.');
    }

    /**
     * Setor sanksi: reduce sisa_sanksi (dynamic), keep sanksi (fixed record)
     */
    public function setorSanksi(Request $request, Pembinaan $pembinaan): RedirectResponse
    {
        $validated = $request->validate([
            'jumlah_setoran' => 'required|integer|min:1',
        ]);

        $jumlahSetoran = (int) $validated['jumlah_setoran'];

        // Cannot setor more than remaining sisa_sanksi
        if ($jumlahSetoran > $pembinaan->sisa_sanksi) {
            return redirect()->route('pembinaan.index')->with('error', 'Jumlah setoran melebihi sisa sanksi.');
        }

        $pembinaan->shalawat_tertulis += $jumlahSetoran;
        $pembinaan->sisa_sanksi = max(0, $pembinaan->sisa_sanksi - $jumlahSetoran);
        $pembinaan->save();

        return redirect()->route('pembinaan.index')->with('success', "Sanksi {$jumlahSetoran} berhasil disetor. Sisa sanksi: {$pembinaan->sisa_sanksi}.");
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
            }
        });

        return redirect()->route('pembinaan.index')->with('success', "Pemutihan berhasil! Semua sisa sanksi dikalikan {$multiplier}.");
    }
}
