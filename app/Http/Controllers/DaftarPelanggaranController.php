<?php
namespace App\Http\Controllers;

use App\Models\DaftarPelanggaran;
use App\Http\Requests\StoreDaftarPelanggaranRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class DaftarPelanggaranController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_daftar_pelanggaran', ['only' => ['index']]);
        $this->middleware('permission:create_daftar_pelanggaran', ['only' => ['store']]);
        $this->middleware('permission:edit_daftar_pelanggaran', ['only' => ['update']]);
        $this->middleware('permission:delete_daftar_pelanggaran', ['only' => ['destroy', 'bulkDelete']]);
    }

    public function index(Request $request): Response
    {
        $query = DaftarPelanggaran::withCount('pelanggaran');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('nama_pelanggaran', 'like', "%{$search}%");
        }

        $sortColumn = $request->input('sort_column', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['nama_pelanggaran', 'pelanggaran_count', 'created_at'];
        if (in_array($sortColumn, $allowedSorts)) {
            $query->orderBy($sortColumn, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $daftarPelanggaran = $query->paginate((int) $request->input("per_page", 10));

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

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        DaftarPelanggaran::whereIn('id', $ids)->delete();
        return redirect()->route('daftar-pelanggaran.index')->with('success', 'Jenis pelanggaran berhasil dihapus.');
    }
}
