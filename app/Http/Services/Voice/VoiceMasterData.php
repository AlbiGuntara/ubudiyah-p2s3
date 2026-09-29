<?php

namespace App\Http\Services\Voice;

use App\Models\Asrama;
use App\Models\Daerah;
use App\Models\DaftarPelanggaran;
use Illuminate\Support\Facades\Cache;

/**
 * Master data yang dipakai voice parser.
 *
 * Daerah, asrama, dan jenis pelanggaran sengaja dipaketkan sebagai daftar
 * tertutup. Rules parser memindainya dengan pencocokan token, dan LLM
 * fallback menerimanya sebagai daftar pilihan supaya model tidak mengarang
 * nilai yang tidak ada di database.
 */
class VoiceMasterData
{
    /**
     * @return list<array{id: int, kode: string, nama: string}>
     */
    public function daerah(): array
    {
        /** @var list<array{id: int, kode: string, nama: string}> $daerah */
        $daerah = Cache::remember('voice:master:daerah', 3600, fn (): array => Daerah::query()
            ->orderBy('id')
            ->get(['id', 'kode', 'nama_daerah'])
            ->map(fn (Daerah $d): array => [
                'id' => $d->id,
                'kode' => (string) $d->kode,
                'nama' => (string) $d->nama_daerah,
            ])
            ->all());

        return $daerah;
    }

    /**
     * Asrama beserta kode daerah induknya.
     *
     * @return list<array{id: int, nomor: string, daerah_id: int, daerah_kode: string, label: string}>
     */
    public function asrama(): array
    {
        /**
         * Asrama disatukan dengan daerahnya lewat query langsung, bukan lewat
         * relasi, karena relasi `daerah` pada model Asrama belum bertipe
         * sehingga tidak bisa dianalisis dan tidak ada jaminan daerahnya ada.
         * Inner join juga otomatis membuang asrama yatim, yang memang tidak
         * bisa dipilih di formulir.
         *
         * @var list<array{id: int, nomor: string, daerah_id: int, daerah_kode: string, label: string}> $asrama
         */
        $asrama = Cache::remember('voice:master:asrama', 3600, fn (): array => Asrama::query()
            ->join('daerah', 'daerah.id', '=', 'asrama.daerah_id')
            ->orderBy('asrama.id')
            ->get([
                'asrama.id',
                'asrama.nomor',
                'asrama.daerah_id',
                'daerah.kode as daerah_kode',
            ])
            ->map(function (Asrama $a): array {
                $kode = (string) $a->getAttribute('daerah_kode');
                $nomor = str_pad((string) $a->nomor, 2, '0', STR_PAD_LEFT);

                return [
                    'id' => (int) $a->id,
                    'nomor' => $nomor,
                    'daerah_id' => (int) $a->daerah_id,
                    'daerah_kode' => $kode,
                    'label' => $kode === '' ? 'Asrama '.$nomor : $kode.'.'.$nomor,
                ];
            })
            ->all());

        return $asrama;
    }

    /**
     * Jenis pelanggaran. Perhatikan master data produksi masih memuat
     * duplikat nama, misalnya "Tidak Khotmil Qur'an" dan
     * "Tidak Mahallul Qiyam" muncul dua kali, sehingga pemanggil wajib
     * siap menerima lebih dari satu id.
     *
     * @return list<array{id: int, nama: string}>
     */
    public function daftarPelanggaran(): array
    {
        /** @var list<array{id: int, nama: string}> $daftar */
        $daftar = Cache::remember('voice:master:daftar-pelanggaran', 3600, fn (): array => DaftarPelanggaran::query()
            ->orderBy('id')
            ->get(['id', 'nama_pelanggaran'])
            ->map(fn (DaftarPelanggaran $d): array => [
                'id' => $d->id,
                'nama' => (string) $d->nama_pelanggaran,
            ])
            ->all());

        return $daftar;
    }

    /**
     * Buang seluruh cache master voice. Dipanggil dari controller master data
     * setelah daerah, asrama, atau jenis pelanggaran berubah.
     */
    public function flush(): void
    {
        Cache::forget('voice:master:daerah');
        Cache::forget('voice:master:asrama');
        Cache::forget('voice:master:daftar-pelanggaran');
        Cache::forget((string) config('voice.cache.key'));
    }
}
