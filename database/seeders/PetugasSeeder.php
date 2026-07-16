<?php
namespace Database\Seeders;

use App\Models\Petugas;
use Illuminate\Database\Seeder;

class PetugasSeeder extends Seeder
{
    public function run(): void
    {
        $petugas = [
            ['nama' => 'Ahmad Fauzi', 'daerah_id' => 1, 'asrama_id' => 1, 'jabatan' => 'Koordinator', 'tugas' => 'Koordinator Umum'],
            ['nama' => 'Budi Santoso', 'daerah_id' => 1, 'asrama_id' => 2, 'jabatan' => 'Anggota', 'tugas' => 'Pengawas Sholat'],
            ['nama' => 'Choirul Anam', 'daerah_id' => 2, 'asrama_id' => 3, 'jabatan' => 'Anggota', 'tugas' => 'Pengawas Mengaji'],
            ['nama' => 'Doni Prasetyo', 'daerah_id' => 1, 'asrama_id' => 1, 'jabatan' => 'Pembina Utama', 'tugas' => 'Pembina Seluruh Daerah'],
            ['nama' => 'Eko Wahyudi', 'daerah_id' => 2, 'asrama_id' => 4, 'jabatan' => 'Anggota', 'tugas' => 'Pengawas Kegiatan'],
        ];

        foreach ($petugas as $p) {
            Petugas::create($p);
        }
    }
}
