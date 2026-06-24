<?php
namespace App\Http\Controllers;

use App\Models\Daerah;
use App\Http\Requests\StoreDaerahRequest;
use App\Traits\Auditable;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class DaerahController extends Controller
{
    use Auditable;

    public function index(): Response
    {
        $daerah = Daerah::withCount('asrama')
            ->latest()
            ->paginate(10);

        return Inertia::render('daerah/index', [
            'daerah' => $daerah,
        ]);
    }

    public function store(StoreDaerahRequest $request): RedirectResponse
    {
        $daerah = Daerah::create($request->validated());
        return redirect()->route('daerah.index')->with('success', 'Daerah berhasil ditambahkan.');
    }

    public function update(StoreDaerahRequest $request, Daerah $daerah): RedirectResponse
    {
        $daerah->update($request->validated());
        return redirect()->route('daerah.index')->with('success', 'Daerah berhasil diubah.');
    }

    public function destroy(Daerah $daerah): RedirectResponse
    {
        $daerah->delete();
        return redirect()->route('daerah.index')->with('success', 'Daerah berhasil dihapus.');
    }
}
