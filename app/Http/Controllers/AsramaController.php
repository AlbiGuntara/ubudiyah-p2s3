<?php
namespace App\Http\Controllers;

use App\Models\Asrama;
use App\Models\AuditLog;
use App\Models\Daerah;
use App\Http\Requests\StoreAsramaRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AsramaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_asrama', ['only' => ['index']]);
        $this->middleware('permission:create_asrama', ['only' => ['store']]);
        $this->middleware('permission:edit_asrama', ['only' => ['update']]);
        $this->middleware('permission:delete_asrama', ['only' => ['destroy', 'bulkDelete']]);
    }

    public function index(Request $request): Response
    {
        $query = Asrama::with('daerah')->withCount('santri');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('nomor', 'like', "%{$search}%");
        }

        if ($request->filled('daerah_id')) {
            $query->where('daerah_id', $request->daerah_id);
        }

        $sortColumn = $request->input('sort_column', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['nomor', 'daerah_id', 'santri_count', 'daerah'];
        if (in_array($sortColumn, $allowedSorts)) {
            if ($sortColumn === 'daerah') {
                $query->orderBy(
                    Daerah::select('nama_daerah')->whereColumn('daerah.id', 'asrama.daerah_id'),
                    $sortDirection === 'asc' ? 'asc' : 'desc'
                );
            } else {
                $query->orderBy($sortColumn, $sortDirection === 'asc' ? 'asc' : 'desc');
            }
        } else {
            $query->latest();
        }

        $asrama = $query->paginate((int) $request->input("per_page", 10));
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

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        Asrama::whereIn('id', $ids)->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'aktivitas' => 'menghapus banyak Asrama',
            'model_type' => Asrama::class,
            'data' => ['ids' => $ids, 'count' => count($ids)],
        ]);

        return redirect()->route('asrama.index')->with('success', 'Asrama berhasil dihapus.');
    }
}
