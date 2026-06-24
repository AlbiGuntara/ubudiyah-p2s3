<?php
namespace App\Http\Controllers;

use App\Models\Santri;
use App\Models\Daerah;
use App\Models\Asrama;
use App\Http\Requests\StoreSantriRequest;
use App\Traits\Auditable;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Imports\SantriImport;
use Maatwebsite\Excel\Facades\Excel;

class SantriController extends Controller
{
    use Auditable;

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
            $query->where('iksass', $request->iksass);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        $santri = $query->latest()->paginate(15);
        $daerah = Daerah::all();
        $asrama = Asrama::with('daerah')->get();

        return Inertia::render('santri/index', [
            'santri' => $santri,
            'daerah' => $daerah,
            'asrama' => $asrama,
            'filters' => $request->only(['daerah_id', 'asrama_id', 'iksass', 'search']),
        ]);
    }

    public function store(StoreSantriRequest $request): RedirectResponse
    {
        Santri::create($request->validated());
        return redirect()->route('santri.index')->with('success', 'Santri berhasil ditambahkan.');
    }

    public function show(Santri $santri): Response
    {
        $santri->load(['asrama.daerah', 'pelanggaran.daftarPelanggaran', 'pembinaan']);

        return Inertia::render('santri/show', [
            'santri' => $santri,
            'statistik' => [
                'total_pelanggaran' => $santri->total_pelanggaran,
                'total_shalawat' => $santri->total_shalawat,
                'jumlah_panggilan' => $santri->jumlah_panggilan,
            ],
        ]);
    }

    public function update(StoreSantriRequest $request, Santri $santri): RedirectResponse
    {
        $santri->update($request->validated());
        return redirect()->route('santri.index')->with('success', 'Santri berhasil diubah.');
    }

    public function destroy(Santri $santri): RedirectResponse
    {
        $santri->delete();
        return redirect()->route('santri.index')->with('success', 'Santri berhasil dihapus.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);

        Excel::import(new SantriImport, $request->file('file'));

        return redirect()->route('santri.index')->with('success', 'Data santri berhasil diimport.');
    }
}
