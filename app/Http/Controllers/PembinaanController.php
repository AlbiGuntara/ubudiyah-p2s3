<?php
namespace App\Http\Controllers;

use App\Models\Pembinaan;
use App\Models\Santri;
use App\Http\Requests\StorePembinaanRequest;
use App\Traits\Auditable;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class PembinaanController extends Controller
{
    use Auditable;

    public function index(): Response
    {
        $pembinaan = Pembinaan::with('santri.asrama.daerah')->latest()->paginate(15);
        $santri = Santri::with('asrama.daerah')->get();

        return Inertia::render('pembinaan/index', [
            'pembinaan' => $pembinaan,
            'santri' => $santri,
        ]);
    }

    public function store(StorePembinaanRequest $request): RedirectResponse
    {
        Pembinaan::create($request->validated());
        return redirect()->route('pembinaan.index')->with('success', 'Pembinaan berhasil ditambahkan.');
    }

    public function update(StorePembinaanRequest $request, Pembinaan $pembinaan): RedirectResponse
    {
        $pembinaan->update($request->validated());
        return redirect()->route('pembinaan.index')->with('success', 'Pembinaan berhasil diubah.');
    }

    public function destroy(Pembinaan $pembinaan): RedirectResponse
    {
        $pembinaan->delete();
        return redirect()->route('pembinaan.index')->with('success', 'Pembinaan berhasil dihapus.');
    }
}
