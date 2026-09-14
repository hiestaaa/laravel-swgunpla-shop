<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Voucher>
 */
class VoucherFactory extends Factory
{
    public function definition(): array
    {
        $code = fake()->unique()->lexify('??????');
        $type = fake()->randomElement(['fixed', 'percent']);

        return [
            'code' => strtoupper($code),
            'type' => $type,
            'value' => $type === 'percent'
                ? fake()->numberBetween(5, 50)
                : fake()->numberBetween(50000, 500000),
            'quantity' => fake()->numberBetween(10, 1000),
            'expires_at' => fake()->optional(0.3)->dateTimeBetween('+1 week', '+1 month'),
        ];
    }

    public function percent(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'percent']);
    }

    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'fixed']);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => ['expires_at' => fake()->dateTimeBetween('-1 week', '-1 day')]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => ['quantity' => 0]);
    }
}
