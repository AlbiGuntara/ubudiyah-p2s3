<?php

namespace Database\Seeders;

use App\Models\Iksass;
use Illuminate\Database\Seeder;

class IksassSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['kode' => 'SIT', 'nama_iksass' => 'Situbondo'],
            ['kode' => 'BON', 'nama_iksass' => 'Bondowoso'],
            ['kode' => 'JEM', 'nama_iksass' => 'Jember'],
            ['kode' => 'BAN', 'nama_iksass' => 'Banyuwangi'],
            ['kode' => 'PRO', 'nama_iksass' => 'Probolinggo'],
            ['kode' => 'LUM', 'nama_iksass' => 'Lumajang'],
        ];

        foreach ($data as $item) {
            Iksass::firstOrCreate(['kode' => $item['kode']], $item);
        }
    }
}
