<?php
namespace App\Http\Controllers;

use App\Models\DaftarPelanggaran;
use App\Http\Requests\StoreDaftarPelanggaranRequest;
use App\Traits\Auditable;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class DaftarPelanggaranController extends Controller
{
    use Auditable;

    public function index(): Response
    {
        $daftarPelanggaran = DaftarPelanggaran::withCount('pelanggaran')
            ->latest()
            ->paginate(10);

        return Inertia::render('daftar-pelanggaran/index', [
            'daftarPelanggaran' => $daftarPelanggaran,
        ]);
    }

    public function store(StoreDaftarPelanggaranRequest $request): RedirectResponse
    {
        DaftarPelanggaran::create($request->validated());
        return redirect()->route('daftar-pelanggaran.index')->with('success', 'Jenis pelanggaran berhasil ditambahkan.');
    }

    public function update(StoreDaftarPelanggaranRequest $request, DaftarPelanggaran $daftarPelanggaran): RedirectResponse
    {
        $daftarPelanggaran->update($request->validated());
        return redirect()->route('daftar-pelanggaran.index')->with('success', 'Jenis pelanggaran berhasil diubah.');
    }

    public function destroy(DaftarPelanggaran $daftarPelanggaran): RedirectResponse
    {
        $daftarPelanggaran->delete();
        return redirect()->route('daftar-pelanggaran.index')->with('success', 'Jenis pelanggaran berhasil dihapus.');
    }
}
