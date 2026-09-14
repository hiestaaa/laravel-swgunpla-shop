<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryReservation>
 */
class InventoryReservationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'session_id' => fake()->uuid(),
            'user_id' => null,
            'quantity' => fake()->numberBetween(1, 5),
            'expires_at' => fake()->dateTimeBetween('+5 minutes', '+15 minutes'),
            'status' => 'pending',
        ];
    }

    public function forSession(string $sessionId): static
    {
        return $this->state(fn (array $attributes) => ['session_id' => $sessionId]);
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => ['user_id' => $userId]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('-1 hour', '-5 minutes'),
        ]);
    }

    public function committed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'committed']);
    }

    public function released(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'released']);
    }
}
