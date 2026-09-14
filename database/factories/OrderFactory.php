<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'address_id' => Address::factory(),
            'total_amount' => fake()->numberBetween(500000, 10000000),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'cancelled']),
            'payment_method' => fake()->randomElement(['cod', 'vnpay']),
            'voucher_id' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'pending']);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'completed']);
    }

    public function withVoucher(): static
    {
        return $this->state(fn (array $attributes) => [
            'voucher_id' => Voucher::factory(),
        ]);
    }
}
