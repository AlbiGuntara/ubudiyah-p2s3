<?php
namespace App\Http\Controllers;

use App\Models\Pelanggaran;
use App\Models\Santri;
use App\Models\Asrama;
use App\Models\DaftarPelanggaran;
use App\Http\Requests\StorePelanggaranRequest;
use App\Traits\Auditable;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PelanggaranController extends Controller
{
    use Auditable;

    public function index(Request $request): Response
    {
        $query = Pelanggaran::with(['santri', 'asrama.daerah', 'daftarPelanggaran', 'petugas']);

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal', $request->bulan);
        }

        if ($request->filled('tahun')) {
            $query->whereYear('tanggal', $request->tahun);
        }

        if ($request->filled('daerah_id')) {
            $query->whereHas('asrama', fn($q) => $q->where('daerah_id', $request->daerah_id));
        }

        if ($request->filled('asrama_id')) {
            $query->where('asrama_id', $request->asrama_id);
        }

        if ($request->filled('iksass')) {
            $query->whereHas('santri', fn($q) => $q->where('iksass', $request->iksass));
        }

        if ($request->filled('petugas_id')) {
            $query->where('petugas_id', $request->petugas_id);
        }

        if ($request->filled('sumber')) {
            $query->where('sumber_pencatatan', $request->sumber);
        }

        $pelanggaran = $query->latest()->paginate(15);
        $santri = Santri::all();
        $asrama = Asrama::with('daerah')->get();
        $daftarPelanggaran = DaftarPelanggaran::all();

        return Inertia::render('pelanggaran/index', [
            'pelanggaran' => $pelanggaran,
            'santri' => $santri,
            'asrama' => $asrama,
            'daftarPelanggaran' => $daftarPelanggaran,
            'filters' => $request->only(['tanggal', 'bulan', 'tahun', 'daerah_id', 'asrama_id', 'iksass', 'petugas_id', 'sumber']),
        ]);
    }

    public function store(StorePelanggaranRequest $request): RedirectResponse
    {
        Pelanggaran::create($request->validated());
        return redirect()->route('pelanggaran.index')->with('success', 'Pelanggaran berhasil dicatat.');
    }

    public function storeMassal(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asrama_id' => 'required|exists:asrama,id',
            'daftar_pelanggaran_id' => 'required|exists:daftar_pelanggaran,id',
            'petugas_id' => 'required|exists:petugas,id',
            'jumlah' => 'required|integer|min:2',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
        ]);

        Pelanggaran::create([
            'santri_id' => null,
            'asrama_id' => $validated['asrama_id'],
            'daftar_pelanggaran_id' => $validated['daftar_pelanggaran_id'],
            'petugas_id' => $validated['petugas_id'],
            'jumlah' => $validated['jumlah'],
            'sumber_pencatatan' => 'petugas',
            'tanggal' => $validated['tanggal'],
            'keterangan' => $validated['keterangan'] ?? 'Pelanggaran massal',
        ]);

        return redirect()->route('pelanggaran.index')->with('success', 'Pelanggaran massal berhasil dicatat.');
    }

    public function update(StorePelanggaranRequest $request, Pelanggaran $pelanggaran): RedirectResponse
    {
        $this->authorize('update', $pelanggaran);
        $pelanggaran->update($request->validated());
        return redirect()->route('pelanggaran.index')->with('success', 'Pelanggaran berhasil diubah.');
    }

    public function destroy(Pelanggaran $pelanggaran): RedirectResponse
    {
        $this->authorize('delete', $pelanggaran);
        $pelanggaran->delete();
        return redirect()->route('pelanggaran.index')->with('success', 'Pelanggaran berhasil dihapus.');
    }
}
