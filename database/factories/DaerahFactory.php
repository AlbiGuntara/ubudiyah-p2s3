<?php
namespace Database\Factories;

use App\Models\Daerah;
use Illuminate\Database\Eloquent\Factories\Factory;

class DaerahFactory extends Factory
{
    protected $model = Daerah::class;

    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->randomLetter(),
            'nama_daerah' => 'Daerah ' . fake()->word(),
        ];
    }
}
