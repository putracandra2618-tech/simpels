<?php

namespace Database\Factories;

use App\Models\Laptop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Laptop>
 */
class LaptopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->randomElement([
                'Lenovo ThinkPad T14',
                'Dell Latitude 3420',
                'Asus Vivobook 14',
                'HP ProBook 450 G8',
                'Acer Aspire 5',
            ]),
            'merek' => fake()->randomElement(['Lenovo', 'Dell', 'Asus', 'HP', 'Acer']),
            'spesifikasi' => fake()->sentence(6),
            'qr_token' => Str::uuid()->toString(),
            'status' => 'tersedia',
        ];
    }
}
