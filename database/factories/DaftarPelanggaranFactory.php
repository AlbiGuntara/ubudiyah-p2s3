<?php
namespace Database\Factories;

use App\Models\DaftarPelanggaran;
use Illuminate\Database\Eloquent\Factories\Factory;

class DaftarPelanggaranFactory extends Factory
{
    protected $model = DaftarPelanggaran::class;

    public function definition(): array
    {
        return [
            'nama_pelanggaran' => fake()->sentence(3),
            'poin' => 1,
        ];
    }
}
