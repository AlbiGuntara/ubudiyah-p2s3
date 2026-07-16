<?php
namespace Database\Seeders;

use App\Models\Pembinaan;
use Illuminate\Database\Seeder;

class PembinaanSeeder extends Seeder
{
    public function run(): void
    {
        $santriIds = range(1, 30);

        for ($i = 0; $i < 15; $i++) {
            $santriId = $santriIds[array_rand($santriIds)];
            $sanksi = rand(100, 500);

            Pembinaan::create([
                'santri_id' => $santriId,
                'sanksi' => $sanksi,
                'sisa_sanksi' => $sanksi,
                'shalawat_tertulis' => 0,
            ]);
        }
    }
}
