<?php
namespace Database\Seeders;

use App\Models\Pelanggaran;
use Illuminate\Database\Seeder;

class PelanggaranSeeder extends Seeder
{
    public function run(): void
    {
        $santriIds = \App\Models\Santri::pluck('id')->toArray();
        $asramaIds = \App\Models\Asrama::pluck('id')->toArray();
        $petugasIds = \App\Models\Petugas::pluck('id')->toArray();

        // Individual violations from petugas
        for ($i = 0; $i < 50; $i++) {
            Pelanggaran::create([
                'santri_id' => $santriIds[array_rand($santriIds)],
                'asrama_id' => $asramaIds[array_rand($asramaIds)],
                'daftar_pelanggaran_id' => rand(1, 10),
                'petugas_id' => $petugasIds[array_rand($petugasIds)],
                'jumlah' => 1,
                'sumber_pencatatan' => 'petugas',
                'tanggal' => now()->subDays(rand(0, 60)),
            ]);
        }

        // Individual violations from ketua_kamar
        for ($i = 0; $i < 20; $i++) {
            Pelanggaran::create([
                'santri_id' => $santriIds[array_rand($santriIds)],
                'asrama_id' => $asramaIds[array_rand($asramaIds)],
                'daftar_pelanggaran_id' => rand(1, 10),
                'petugas_id' => $petugasIds[array_rand($petugasIds)],
                'jumlah' => 1,
                'sumber_pencatatan' => 'ketua_kamar',
                'tanggal' => now()->subDays(rand(0, 30)),
            ]);
        }

        // Mass violations
        for ($i = 0; $i < 10; $i++) {
            Pelanggaran::create([
                'santri_id' => null,
                'asrama_id' => $asramaIds[array_rand($asramaIds)],
                'daftar_pelanggaran_id' => rand(1, 10),
                'petugas_id' => $petugasIds[array_rand($petugasIds)],
                'jumlah' => rand(2, 10),
                'sumber_pencatatan' => 'petugas',
                'tanggal' => now()->subDays(rand(0, 45)),
            ]);
        }
    }
}
