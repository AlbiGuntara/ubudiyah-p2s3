<?php
namespace Database\Seeders;

use App\Models\DaftarPelanggaran;
use Illuminate\Database\Seeder;

class DaftarPelanggaranSeeder extends Seeder
{
    public function run(): void
    {
        $pelanggaran = [
            ['nama_pelanggaran' => 'Tidak Jamaah Sholat Wajib'],
            ['nama_pelanggaran' => 'Terlambat Jamaah Sholat Wajib'],
            ['nama_pelanggaran' => 'Tidak Mengikuti Kegiatan Ubudiyah'],
            ['nama_pelanggaran' => 'Tidak Sholat Sunnah Rawatib'],
            ['nama_pelanggaran' => 'Meninggalkan Sholat Malam'],
            ['nama_pelanggaran' => 'Tidak Puasa Sunnah'],
            ['nama_pelanggaran' => 'Membaca Al-Quran Tidak Tartil'],
            ['nama_pelanggaran' => 'Tidak Mengikuti Kajian'],
            ['nama_pelanggaran' => 'Berbicara Saat Khutbah'],
            ['nama_pelanggaran' => 'Meninggalkan Dzikir Pagi/Petang'],
        ];

        foreach ($pelanggaran as $p) {
            DaftarPelanggaran::create($p);
        }
    }
}
