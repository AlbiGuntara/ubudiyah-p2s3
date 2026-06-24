<?php
namespace Database\Factories;

use App\Models\Santri;
use Illuminate\Database\Eloquent\Factories\Factory;

class SantriFactory extends Factory
{
    protected $model = Santri::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'nis' => (string) fake()->unique()->numberBetween(1000, 9999),
            'iksass' => (string) fake()->numberBetween(2019, 2025),
            'asrama_id' => \App\Models\Asrama::factory(),
        ];
    }
}
