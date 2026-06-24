<?php
namespace App\Http\Controllers;

use App\Models\Asrama;
use App\Models\Daerah;
use App\Http\Requests\StoreAsramaRequest;
use App\Traits\Auditable;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AsramaController extends Controller
{
    use Auditable;

    public function index(Request $request): Response
    {
        $query = Asrama::with('daerah')->withCount('santri');

        if ($request->filled('daerah_id')) {
            $query->where('daerah_id', $request->daerah_id);
        }

        $asrama = $query->latest()->paginate(10);
        $daerah = Daerah::all();

        return Inertia::render('asrama/index', [
            'asrama' => $asrama,
            'daerah' => $daerah,
            'filters' => $request->only(['daerah_id']),
        ]);
    }

    public function store(StoreAsramaRequest $request): RedirectResponse
    {
        Asrama::create($request->validated());
        return redirect()->route('asrama.index')->with('success', 'Asrama berhasil ditambahkan.');
    }

    public function update(StoreAsramaRequest $request, Asrama $asrama): RedirectResponse
    {
        $asrama->update($request->validated());
        return redirect()->route('asrama.index')->with('success', 'Asrama berhasil diubah.');
    }

    public function destroy(Asrama $asrama): RedirectResponse
    {
        $asrama->delete();
        return redirect()->route('asrama.index')->with('success', 'Asrama berhasil dihapus.');
    }
}
