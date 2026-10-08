<?php

namespace Database\Factories;

use App\Models\Borrowing;
use App\Models\Laptop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Borrowing>
 */
class BorrowingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'laptop_id' => Laptop::factory(),
            'created_by' => User::factory(),
            'borrowed_at' => now(),
            'status' => 'aktif',
            'returned_at' => null,
            'condition' => null,
        ];
    }
}
