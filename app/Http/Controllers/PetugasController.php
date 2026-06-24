<?php
namespace App\Http\Controllers;

use App\Models\Petugas;
use App\Models\Santri;
use App\Models\Asrama;
use App\Http\Requests\StorePetugasRequest;
use App\Traits\Auditable;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class PetugasController extends Controller
{
    use Auditable;

    public function index(): Response
    {
        $petugas = Petugas::with(['santri', 'asrama.daerah'])->latest()->paginate(10);
        $santri = Santri::all();
        $asrama = Asrama::with('daerah')->get();

        return Inertia::render('petugas/index', [
            'petugas' => $petugas,
            'santri' => $santri,
            'asrama' => $asrama,
        ]);
    }

    public function store(StorePetugasRequest $request): RedirectResponse
    {
        Petugas::create($request->validated());
        return redirect()->route('petugas.index')->with('success', 'Petugas berhasil ditambahkan.');
    }

    public function update(StorePetugasRequest $request, Petugas $petugas): RedirectResponse
    {
        $petugas->update($request->validated());
        return redirect()->route('petugas.index')->with('success', 'Petugas berhasil diubah.');
    }

    public function destroy(Petugas $petugas): RedirectResponse
    {
        $petugas->delete();
        return redirect()->route('petugas.index')->with('success', 'Petugas berhasil dihapus.');
    }
}
