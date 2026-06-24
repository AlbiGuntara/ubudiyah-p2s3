<?php
namespace Database\Factories;

use App\Models\Pelanggaran;
use Illuminate\Database\Eloquent\Factories\Factory;

class PelanggaranFactory extends Factory
{
    protected $model = Pelanggaran::class;

    public function definition(): array
    {
        return [
            'santri_id' => \App\Models\Santri::factory(),
            'asrama_id' => \App\Models\Asrama::factory(),
            'daftar_pelanggaran_id' => \App\Models\DaftarPelanggaran::factory(),
            'petugas_id' => \App\Models\Petugas::factory(),
            'jumlah' => 1,
            'sumber_pencatatan' => 'petugas',
            'tanggal' => fake()->date(),
        ];
    }
}
