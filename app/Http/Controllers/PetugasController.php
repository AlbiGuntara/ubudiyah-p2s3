<?php
namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Petugas;
use App\Models\Daerah;
use App\Models\Asrama;
use App\Http\Requests\StorePetugasRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class PetugasController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_petugas', ['only' => ['index']]);
        $this->middleware('permission:create_petugas', ['only' => ['store']]);
        $this->middleware('permission:edit_petugas', ['only' => ['update']]);
        $this->middleware('permission:delete_petugas', ['only' => ['destroy', 'bulkDelete']]);
    }

    public function index(Request $request): Response
    {
        $query = Petugas::with(['daerah', 'asrama.daerah']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('nama', 'like', "%{$search}%");
        }

        $sortColumn = $request->input('sort_column', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['jabatan', 'created_at', 'nama'];
        if (in_array($sortColumn, $allowedSorts)) {
            $query->orderBy($sortColumn, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $petugas = $query->paginate((int) $request->input('per_page', 10));
        $daerah = Daerah::all();
        $asrama = Asrama::with('daerah')->get();

        return Inertia::render('petugas/index', [
            'petugas' => $petugas,
            'daerah' => $daerah,
            'asrama' => $asrama,
        ]);
    }

    public function store(StorePetugasRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('petugas', 'public');
        }

        Petugas::create($data);

        return redirect()->back()->with('success', 'Petugas berhasil ditambahkan.');
    }

    public function update(StorePetugasRequest $request, Petugas $petugas): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            // Delete old foto
            if ($petugas->foto) {
                Storage::disk('public')->delete($petugas->foto);
            }
            $data['foto'] = $request->file('foto')->store('petugas', 'public');
        } else {
            // Keep existing foto if no new file uploaded
            unset($data['foto']);
        }

        $petugas->update($data);

        return redirect()->back()->with('success', 'Petugas berhasil diubah.');
    }

    public function destroy(Petugas $petugas): RedirectResponse
    {
        if ($petugas->foto) {
            Storage::disk('public')->delete($petugas->foto);
        }
        $petugas->delete();

        return redirect()->back()->with('success', 'Petugas berhasil dihapus.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $petugas = Petugas::whereIn('id', $ids)->get();
        foreach ($petugas as $p) {
            if ($p->foto) {
                Storage::disk('public')->delete($p->foto);
            }
        }
        Petugas::whereIn('id', $ids)->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'aktivitas' => 'menghapus banyak Petugas',
            'model_type' => Petugas::class,
            'data' => ['ids' => $ids, 'count' => count($ids)],
        ]);

        return redirect()->back()->with('success', 'Petugas berhasil dihapus.');
    }
}
