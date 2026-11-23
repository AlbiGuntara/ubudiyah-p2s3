<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIksassRequest;
use App\Models\AuditLog;
use App\Models\Iksass;
use App\Models\Santri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class IksassController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_iksass', ['only' => ['index']]);
        $this->middleware('permission:create_iksass', ['only' => ['store']]);
        $this->middleware('permission:edit_iksass', ['only' => ['update']]);
        $this->middleware('permission:delete_iksass', ['only' => ['destroy', 'bulkDelete']]);
    }

    public function index(Request $request): Response
    {
        $query = Iksass::withCount([
            'santri as santri_count' => fn ($q) => $q->where('status', 'aktif'),
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                    ->orWhere('nama_iksass', 'like', "%{$search}%");
            });
        }

        $sortColumn = $request->input('sort_column', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['kode', 'nama_iksass', 'santri_count'];
        if (in_array($sortColumn, $allowedSorts)) {
            $query->orderBy($sortColumn, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $iksass = $query->paginate((int) $request->input('per_page', 10));

        return Inertia::render('iksass/index', [
            'iksass' => $iksass,
        ]);
    }

    public function store(StoreIksassRequest $request): RedirectResponse
    {
        Iksass::create($request->validated());

        return redirect()->back()->with('success', 'IKSASS berhasil ditambahkan.');
    }

    public function update(StoreIksassRequest $request, Iksass $iksass): RedirectResponse
    {
        $iksass->update($request->validated());

        return redirect()->back()->with('success', 'IKSASS berhasil diubah.');
    }

    public function destroy(Iksass $iksass): RedirectResponse
    {
        Santri::where('iksass_id', $iksass->id)->update(['iksass_id' => null]);
        $iksass->delete();

        return redirect()->back()->with('success', 'IKSASS berhasil dihapus.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = (array) $request->input('ids', []);

        Santri::whereIn('iksass_id', $ids)->update(['iksass_id' => null]);
        Iksass::whereIn('id', $ids)->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'aktivitas' => 'menghapus banyak IKSASS',
            'model_type' => Iksass::class,
            'data' => ['ids' => $ids, 'count' => count($ids)],
        ]);

        return redirect()->back()->with('success', 'IKSASS berhasil dihapus.');
    }
}
