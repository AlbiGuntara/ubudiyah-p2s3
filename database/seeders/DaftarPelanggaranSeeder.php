<?php
namespace Database\Seeders;

use App\Models\DaftarPelanggaran;
use Illuminate\Database\Seeder;

class DaftarPelanggaranSeeder extends Seeder
{
    public function run(): void
    {
        $pelanggaran = [
            ['nama_pelanggaran' => 'Tidak Jamaah Sholat Wajib', 'poin' => 1],
            ['nama_pelanggaran' => 'Terlambat Jamaah Sholat Wajib', 'poin' => 1],
            ['nama_pelanggaran' => 'Tidak Mengikuti Kegiatan Ubudiyah', 'poin' => 1],
            ['nama_pelanggaran' => 'Tidak Sholat Sunnah Rawatib', 'poin' => 1],
            ['nama_pelanggaran' => 'Meninggalkan Sholat Malam', 'poin' => 2],
            ['nama_pelanggaran' => 'Tidak Puasa Sunnah', 'poin' => 1],
            ['nama_pelanggaran' => 'Membaca Al-Quran Tidak Tartil', 'poin' => 1],
            ['nama_pelanggaran' => 'Tidak Mengikuti Kajian', 'poin' => 1],
            ['nama_pelanggaran' => 'Berbicara Saat Khutbah', 'poin' => 2],
            ['nama_pelanggaran' => 'Meninggalkan Dzikir Pagi/Petang', 'poin' => 1],
        ];

        foreach ($pelanggaran as $p) {
            DaftarPelanggaran::create($p);
        }
    }
}
