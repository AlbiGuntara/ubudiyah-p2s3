<?php
namespace Database\Seeders;

use App\Models\Petugas;
use App\Models\User;
use Illuminate\Database\Seeder;

class PetugasSeeder extends Seeder
{
    public function run(): void
    {
        $santriList = \App\Models\Santri::limit(5)->pluck('id')->toArray();

        $petugas = [
            ['santri_id' => $santriList[0] ?? null, 'asrama_id' => 1, 'jabatan' => 'Koordinator', 'tugas' => 'Koordinator Umum'],
            ['santri_id' => $santriList[1] ?? null, 'asrama_id' => 2, 'jabatan' => 'Anggota', 'tugas' => 'Pengawas Sholat'],
            ['santri_id' => $santriList[2] ?? null, 'asrama_id' => 3, 'jabatan' => 'Anggota', 'tugas' => 'Pengawas Mengaji'],
            ['santri_id' => null, 'asrama_id' => 1, 'jabatan' => 'Pembina Utama', 'tugas' => 'Pembina Seluruh Daerah'],
            ['santri_id' => null, 'asrama_id' => 4, 'jabatan' => 'Anggota', 'tugas' => 'Pengawas Kegiatan'],
        ];

        foreach ($petugas as $p) {
            Petugas::create($p);
        }
    }
}
