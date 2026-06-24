<?php
namespace Database\Seeders;

use App\Models\Asrama;
use Illuminate\Database\Seeder;

class AsramaSeeder extends Seeder
{
    public function run(): void
    {
        $asrama = [];
        $nomor = 1;
        for ($daerahId = 1; $daerahId <= 5; $daerahId++) {
            for ($i = 1; $i <= 3; $i++) {
                $asrama[] = [
                    'daerah_id' => $daerahId,
                    'nomor' => $nomor++,
                ];
            }
        }

        foreach ($asrama as $a) {
            Asrama::create($a);
        }
    }
}
