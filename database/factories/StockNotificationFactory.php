<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockNotification>
 */
class StockNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'email' => null,
            'notified_at' => null,
            'status' => 'pending',
        ];
    }

    public function forGuest(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'email' => $email,
        ]);
    }

    public function notified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'notified',
            'notified_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'cancelled']);
    }
}
