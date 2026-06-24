<?php
namespace Database\Seeders;

use App\Models\Daerah;
use Illuminate\Database\Seeder;

class DaerahSeeder extends Seeder
{
    public function run(): void
    {
        $daerah = [
            ['kode' => 'A', 'nama_daerah' => 'Daerah A'],
            ['kode' => 'B', 'nama_daerah' => 'Daerah B'],
            ['kode' => 'C', 'nama_daerah' => 'Daerah C'],
            ['kode' => 'D', 'nama_daerah' => 'Daerah D'],
            ['kode' => 'E', 'nama_daerah' => 'Daerah E'],
        ];

        foreach ($daerah as $d) {
            Daerah::create($d);
        }
    }
}
