<?php
namespace Database\Seeders;

use App\Models\Pembinaan;
use Illuminate\Database\Seeder;

class PembinaanSeeder extends Seeder
{
    public function run(): void
    {
        $santriIds = range(1, 30);
        $panggilan = ['I', 'II', 'III'];

        for ($i = 0; $i < 15; $i++) {
            Pembinaan::create([
                'santri_id' => $santriIds[array_rand($santriIds)],
                'panggilan' => $panggilan[array_rand($panggilan)],
                'tanggal_panggilan' => now()->subDays(rand(0, 60)),
                'sanksi' => 'Membaca shalawat ' . rand(100, 500) . ' kali',
            ]);
        }
    }
}
