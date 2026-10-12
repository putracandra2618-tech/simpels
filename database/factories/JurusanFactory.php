<?php

namespace Database\Factories;

use App\Models\Jurusan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jurusan>
 */
class JurusanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->randomElement(['TKJ', 'RPL', 'TEI', 'TPSB', 'TB', 'TKR', 'TP']),
            'nama' => fake()->words(2, true),
        ];
    }
}
