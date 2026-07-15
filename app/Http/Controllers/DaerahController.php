<?php
namespace App\Http\Controllers;

use App\Models\Daerah;
use App\Http\Requests\StoreDaerahRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class DaerahController extends Controller
{

    public function index(Request $request): Response
    {
        $query = Daerah::withCount('asrama');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('nama_daerah', 'like', "%{$search}%");
            });
        }

        $sortColumn = $request->input('sort_column', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['kode', 'nama_daerah', 'asrama_count'];
        if (in_array($sortColumn, $allowedSorts)) {
            $query->orderBy($sortColumn, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $daerah = $query->paginate((int) $request->input("per_page", 10));

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

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        Daerah::whereIn('id', $ids)->delete();
        return redirect()->route('daerah.index')->with('success', 'Daerah berhasil dihapus.');
    }
}
