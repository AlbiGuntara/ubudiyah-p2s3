<?php

namespace App\Http\Services;

use App\Models\Asrama;
use App\Models\Daerah;
use App\Models\Santri;

/**
 * Menyusun data rekap Santri per daerah untuk keperluan validasi data oleh
 * masing-masing asrama.
 *
 * Hasilnya dikelompokkan dua tingkat: daerah -> asrama (kamar) -> Santri.
 * Pembacaan memakai chunkById supaya pemakaian memori tetap terkendali
 * tidak langsung meledak ketika jumlah Santri bertambah banyak.
 *
 * @phpstan-type Baris array{nama: string, nis: string, iksass: string, asrama: string, status: string, jabatan: string}
 * @phpstan-type KelompokAsrama array{label: string, nomor: string, santri: list<Baris>}
 * @phpstan-type KelompokDaerah array{kode: string, nama_daerah: string, total: int, asrama: list<KelompokAsrama>}
 */
class SantriExportService
{
    /**
     * Jumlah baris per sekali ambil dari database.
     */
    private const UKURAN_CHUNK = 500;

    /**
     * Label status yang tampil di lembar export.
     *
     * @var array<string, string>
     */
    private const LABEL_STATUS = [
        'aktif' => 'Aktif',
        'tidak aktif' => 'Tidak Aktif',
        'berhenti' => 'Berhenti',
    ];

    /**
     * Guard sebelum merender PDF: daerah wajib ada dan punya minimal satu
     * Santri. Tanpa ini, permintaan tanpa daerah akan mencoba merender
     * seluruh data sekaligus dan berakhir dengan galat 500.
     */
    public function daerahMemilikiSantri(mixed $daerahId): bool
    {
        if (! is_numeric($daerahId)) {
            return false;
        }

        return Daerah::query()
            ->whereKey((int) $daerahId)
            ->whereHas('asrama.santri')
            ->exists();
    }

    /**
     * Hanya satu daerah per pemanggilan. Render PDF untuk seluruh daerah
     * sekaligus membuat dokumen terlalu besar dan berakhir dengan galat 500,
     * jadi pemanggil wajib memilih satu daerah.
     *
     * Seluruh status dimuat karena lembar ini dipakai untuk memvalidasi siapa
     * saja yang benar-benar masih tinggal di asrama tersebut.
     *
     * @return list<KelompokDaerah>
     */
    public function laporanValidasiSantri(int $daerahId): array
    {
        /** @var array<int, array{kode: string, nama_daerah: string, total: int, asrama: array<int, KelompokAsrama>}> $daerahGroups */
        $daerahGroups = [];

        Santri::query()
            ->with('asrama.daerah')
            ->whereHas('asrama', fn ($q) => $q->where('daerah_id', $daerahId))
            ->chunkById(self::UKURAN_CHUNK, function ($santris) use (&$daerahGroups): void {
                foreach ($santris as $santri) {
                    $asrama = $santri->asrama;
                    $daerah = $asrama?->daerah;

                    // Santri tanpa asrama/daerah yang valid tidak punya tujuan
                    // cetak, jadi dilewati.
                    if (! $asrama instanceof Asrama || ! $daerah instanceof Daerah) {
                        continue;
                    }

                    $daerahId = $daerah->id;
                    $asramaId = $asrama->id;
                    $label = $this->asramaLabel($asrama, $daerah);

                    if (! isset($daerahGroups[$daerahId])) {
                        $daerahGroups[$daerahId] = [
                            'kode' => (string) $daerah->kode,
                            'nama_daerah' => (string) $daerah->nama_daerah,
                            'total' => 0,
                            'asrama' => [],
                        ];
                    }

                    if (! isset($daerahGroups[$daerahId]['asrama'][$asramaId])) {
                        $daerahGroups[$daerahId]['asrama'][$asramaId] = [
                            'label' => $label,
                            'nomor' => (string) $asrama->nomor,
                            'santri' => [],
                        ];
                    }

                    $daerahGroups[$daerahId]['asrama'][$asramaId]['santri'][] = [
                        'nama' => (string) $santri->nama,
                        'nis' => (string) ($santri->nis ?? '-'),
                        'iksass' => (string) ($santri->iksass ?? '-'),
                        'asrama' => $label,
                        'status' => self::LABEL_STATUS[(string) $santri->status] ?? (string) $santri->status,
                        // Dibiarkan kosong supaya diisi tulis tangan oleh kamar.
                        'jabatan' => '',
                    ];

                    $daerahGroups[$daerahId]['total']++;
                }
            });

        ksort($daerahGroups);

        $hasil = [];
        foreach ($daerahGroups as $group) {
            uasort(
                $group['asrama'],
                fn (array $a, array $b): int => strnatcasecmp($a['nomor'], $b['nomor'])
            );
            $group['asrama'] = array_values($group['asrama']);
            $hasil[] = $group;
        }

        return $hasil;
    }

    /**
     * Label asrama cukup memakai huruf awalan kode daerah, contoh kode
     * "W/OS" nomor "07" menjadi "W.07".
     */
    private function asramaLabel(Asrama $asrama, Daerah $daerah): string
    {
        $huruf = mb_substr(trim((string) $daerah->kode), 0, 1);
        $nomor = trim((string) $asrama->nomor);

        if ($huruf === '') {
            return $nomor;
        }

        return $huruf.'.'.$nomor;
    }
}
