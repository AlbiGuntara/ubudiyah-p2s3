<?php
namespace Database\Factories;

use App\Models\Petugas;
use Illuminate\Database\Eloquent\Factories\Factory;

class PetugasFactory extends Factory
{
    protected $model = Petugas::class;

    public function definition(): array
    {
        return [
            'santri_id' => null,
            'asrama_id' => \App\Models\Asrama::factory(),
            'jabatan' => fake()->randomElement(['Koordinator', 'Anggota', 'Pembina']),
            'tugas' => fake()->sentence(3),
        ];
    }
}
