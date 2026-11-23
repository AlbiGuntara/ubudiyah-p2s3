<?php

namespace Database\Seeders;

use App\Models\Asrama;
use App\Models\Pelanggaran;
use App\Models\User;
use App\Models\Santri;
use Illuminate\Database\Seeder;

class PelanggaranSeeder extends Seeder
{
    public function run(): void
    {
        $santriIds = Santri::pluck('id')->toArray();
        $asramaIds = Asrama::pluck('id')->toArray();
        $userIds = User::pluck('id')->toArray();

        // Individual violations from petugas
        for ($i = 0; $i < 50; $i++) {
            Pelanggaran::create([
                'santri_id' => $santriIds[array_rand($santriIds)],
                'asrama_id' => $asramaIds[array_rand($asramaIds)],
                'daftar_pelanggaran_id' => rand(1, 10),
                'user_id' => $userIds ? $userIds[array_rand($userIds)] : null,
                'jumlah' => 1,
                'sisa_sanksi' => 100,
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
                'user_id' => $userIds ? $userIds[array_rand($userIds)] : null,
                'jumlah' => 1,
                'sisa_sanksi' => 100,
                'sumber_pencatatan' => 'ketua_kamar',
                'tanggal' => now()->subDays(rand(0, 30)),
            ]);
        }

        // Mass violations
        for ($i = 0; $i < 10; $i++) {
            $jumlah = rand(2, 10);
            Pelanggaran::create([
                'santri_id' => null,
                'asrama_id' => $asramaIds[array_rand($asramaIds)],
                'daftar_pelanggaran_id' => rand(1, 10),
                'user_id' => $userIds ? $userIds[array_rand($userIds)] : null,
                'jumlah' => $jumlah,
                'sisa_sanksi' => $jumlah * 100,
                'sumber_pencatatan' => 'petugas',
                'tanggal' => now()->subDays(rand(0, 45)),
            ]);
        }
    }
}
