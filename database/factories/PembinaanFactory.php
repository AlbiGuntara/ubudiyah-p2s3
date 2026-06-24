<?php
namespace Database\Factories;

use App\Models\Pembinaan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PembinaanFactory extends Factory
{
    protected $model = Pembinaan::class;

    public function definition(): array
    {
        return [
            'santri_id' => \App\Models\Santri::factory(),
            'panggilan' => fake()->randomElement(['I', 'II', 'III']),
            'tanggal_panggilan' => fake()->date(),
            'sanksi' => fake()->sentence(),
        ];
    }
}
