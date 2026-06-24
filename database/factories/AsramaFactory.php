<?php
namespace Database\Factories;

use App\Models\Asrama;
use Illuminate\Database\Eloquent\Factories\Factory;

class AsramaFactory extends Factory
{
    protected $model = Asrama::class;

    public function definition(): array
    {
        return [
            'daerah_id' => \App\Models\Daerah::factory(),
            'nomor' => (string) fake()->unique()->numberBetween(1, 100),
        ];
    }
}
