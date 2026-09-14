<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderEvent>
 */
class OrderEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'event_type' => fake()->randomElement(['created', 'status_changed', 'payment_received', 'shipped', 'delivered', 'refunded']),
            'from_status' => fake()->optional()->randomElement(['pending', 'processing', 'completed', 'cancelled']),
            'to_status' => fake()->optional()->randomElement(['pending', 'processing', 'completed', 'cancelled']),
            'metadata' => [],
            'created_by' => User::factory(),
        ];
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => ['order_id' => $order->id]);
    }

    public function type(string $eventType): static
    {
        return $this->state(fn (array $attributes) => ['event_type' => $eventType]);
    }

    public function statusChange(string $from, string $to): static
    {
        return $this->state(fn (array $attributes) => [
            'from_status' => $from,
            'to_status' => $to,
        ]);
    }
}
