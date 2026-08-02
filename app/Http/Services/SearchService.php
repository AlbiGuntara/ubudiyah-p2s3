<?php
namespace App\Http\Services;

use App\Models\Santri;
use App\Models\Asrama;
use App\Models\Daerah;

class SearchService
{
    public function search(string $query): array
    {
        $santri = Santri::where('nama', 'like', "%{$query}%")
            ->orWhere('nama_panggilan', 'like', "%{$query}%")
            ->orWhere('nis', 'like', "%{$query}%")
            ->with('asrama.daerah')
            ->limit(5)
            ->get()
            ->map(fn($s) => [
                'type' => 'Santri',
                'label' => $s->nama . ' (' . $s->nis . ')',
                'url' => route('santri.show', $s->id),
            ]);

        $asrama = Asrama::where('nomor', 'like', "%{$query}%")
            ->with('daerah')
            ->limit(5)
            ->get()
            ->map(fn($a) => [
                'type' => 'Asrama',
                'label' => 'Asrama ' . $a->daerah->kode . '.' . str_pad($a->nomor, 2, '0', STR_PAD_LEFT),
                'url' => route('asrama.show', $a->id),
            ]);

        $daerah = Daerah::where('nama_daerah', 'like', "%{$query}%")
            ->orWhere('kode', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(fn($d) => [
                'type' => 'Daerah',
                'label' => $d->nama_daerah . ' (' . $d->kode . ')',
                'url' => route('daerah.show', $d->id),
            ]);

        return array_merge($santri->toArray(), $asrama->toArray(), $daerah->toArray());
    }
}
