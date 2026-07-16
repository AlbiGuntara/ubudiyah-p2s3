<?php
namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Santri;
use App\Models\Daerah;
use App\Models\Asrama;
use App\Http\Requests\StoreSantriRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Imports\SantriImport;
use Maatwebsite\Excel\Facades\Excel;

class SantriController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_santri', ['only' => ['index', 'show']]);
        $this->middleware('permission:create_santri', ['only' => ['store']]);
        $this->middleware('permission:edit_santri', ['only' => ['update']]);
        $this->middleware('permission:delete_santri', ['only' => ['destroy', 'bulkDelete']]);
        $this->middleware('permission:import_santri', ['only' => ['import']]);
    }

    public function index(Request $request): Response
    {
        $query = Santri::with('asrama.daerah');

        if ($request->filled('daerah_id')) {
            $query->whereHas('asrama', fn($q) => $q->where('daerah_id', $request->daerah_id));
        }

        if ($request->filled('asrama_id')) {
            $query->where('asrama_id', $request->asrama_id);
        }

        if ($request->filled('iksass')) {
            $query->where('iksass', 'like', "%{$request->iksass}%");
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        $sortColumn = $request->input('sort_column', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['nis', 'nama', 'iksass', 'asrama_id', 'created_at'];
        if (in_array($sortColumn, $allowedSorts)) {
            $query->orderBy($sortColumn, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $santri = $query->paginate((int) $request->input("per_page", 15));
        $daerah = Daerah::all();
        $asrama = Asrama::with('daerah')->get();

        return Inertia::render('santri/index', [
            'santri' => $santri,
            'daerah' => $daerah,
            'asrama' => $asrama,
            'filters' => $request->only(['daerah_id', 'asrama_id', 'iksass', 'search']),
        ]);
    }

    public function store(StoreSantriRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('foto-santri', 'public');
        }

        Santri::create($data);
        return redirect()->route('santri.index')->with('success', 'Santri berhasil ditambahkan.');
    }

    public function show(Santri $santri): Response
    {
        $santri->load(['asrama.daerah', 'pelanggaran.daftarPelanggaran', 'pembinaan']);

        return Inertia::render('santri/show', [
            'santri' => $santri,
            'statistik' => [
                'total_pelanggaran' => $santri->total_pelanggaran,
                'total_shalawat' => $santri->total_shalawat,
            ],
        ]);
    }

    public function update(StoreSantriRequest $request, Santri $santri): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            if ($santri->foto) {
                Storage::disk('public')->delete($santri->foto);
            }
            $data['foto'] = $request->file('foto')->store('foto-santri', 'public');
        }

        $santri->update($data);
        return redirect()->route('santri.index')->with('success', 'Santri berhasil diubah.');
    }

    public function destroy(Santri $santri): RedirectResponse
    {
        $santri->delete();
        return redirect()->route('santri.index')->with('success', 'Santri berhasil dihapus.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        Santri::whereIn('id', $ids)->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'aktivitas' => 'menghapus banyak Santri',
            'model_type' => Santri::class,
            'data' => ['ids' => $ids, 'count' => count($ids)],
        ]);

        return redirect()->route('santri.index')->with('success', 'Santri berhasil dihapus.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);

        $import = new SantriImport;
        Excel::import($import, $request->file('file'));

        $imported = $import->getImportedCount();
        $errors = $import->getErrors();

        if (!empty($errors)) {
            $message = implode('<br>', array_slice($errors, 0, 10));
            if (count($errors) > 10) {
                $message .= '<br>... dan ' . (count($errors) - 10) . ' error lainnya';
            }
            if ($imported > 0) {
                $message = "Berhasil import {$imported} data.<br>" . $message;
            }
            return redirect()->route('santri.index')->with('warning', $message);
        }

        return redirect()->route('santri.index')->with('success', "Berhasil import {$imported} data santri.");
    }
}
