<?php
namespace App\Http\Controllers;

use App\Models\Petugas;
use App\Models\Santri;
use App\Models\Asrama;
use App\Http\Requests\StorePetugasRequest;
use App\Traits\Auditable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class PetugasController extends Controller
{
    use Auditable;

    public function index(Request $request): Response
    {
        $query = Petugas::with(['santri.asrama.daerah']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('santri', function ($sq) use ($search) {
                $sq->where('nama', 'like', "%{$search}%");
            });
        }

        $sortColumn = $request->input('sort_column', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['jabatan', 'created_at', 'nama_petugas'];
        if (in_array($sortColumn, $allowedSorts)) {
            if ($sortColumn === 'nama_petugas') {
                $query->orderBy(
                    Santri::select('nama')->whereColumn('santri.id', 'petugas.santri_id'),
                    $sortDirection === 'asc' ? 'asc' : 'desc'
                );
            } else {
                $query->orderBy($sortColumn, $sortDirection === 'asc' ? 'asc' : 'desc');
            }
        } else {
            $query->latest();
        }

        $petugas = $query->paginate((int) $request->input('per_page', 10));
        $santri = Santri::with('asrama.daerah')->get();
        $asrama = Asrama::with('daerah')->get();

        return Inertia::render('petugas/index', [
            'petugas' => $petugas,
            'santri' => $santri,
            'asrama' => $asrama,
        ]);
    }

    public function store(StorePetugasRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('petugas', 'public');
        }

        Petugas::create($data);

        return redirect()->route('petugas.index')->with('success', 'Petugas berhasil ditambahkan.');
    }

    public function update(StorePetugasRequest $request, Petugas $petugas): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            // Hapus foto lama jika ada
            if ($petugas->foto) {
                Storage::disk('public')->delete($petugas->foto);
            }
            $data['foto'] = $request->file('foto')->store('petugas', 'public');
        } else {
            // Jangan overwrite foto jika tidak ada file baru
            unset($data['foto']);
        }

        $petugas->update($data);

        return redirect()->route('petugas.index')->with('success', 'Petugas berhasil diubah.');
    }

    public function destroy(Petugas $petugas): RedirectResponse
    {
        if ($petugas->foto) {
            Storage::disk('public')->delete($petugas->foto);
        }

        $petugas->delete();

        return redirect()->route('petugas.index')->with('success', 'Petugas berhasil dihapus.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        Petugas::whereIn('id', $ids)->delete();
        return redirect()->route('petugas.index')->with('success', 'Petugas berhasil dihapus.');
    }
}
