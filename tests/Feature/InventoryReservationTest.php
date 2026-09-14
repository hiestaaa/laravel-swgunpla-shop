<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Session;

beforeEach(function () {
    $this->artisan('migrate:fresh');
});

// 1. User can reserve product stock
test('user_can_reserve_product_stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs($user)
        ->postJson(route('reservations.create'), [
            'product_id' => $product->id,
            'quantity' => 3,
        ])
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('inventory_reservations', [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'quantity' => 3,
        'status' => 'pending',
    ]);
});

// 2. Guest can reserve with session
test('guest_can_reserve_product_stock', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $response = $this->postJson(route('reservations.create'), [
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('inventory_reservations', [
        'product_id' => $product->id,
        'quantity' => 2,
        'status' => 'pending',
    ]);
});

// 3. Cannot reserve more than available stock
test('cannot_reserve_more_than_available_stock', function () {
    $product = Product::factory()->create(['stock' => 5]);

    $this->postJson(route('reservations.create'), [
        'product_id' => $product->id,
        'quantity' => 10,
    ])
    ->assertStatus(422)
    ->assertJson(['success' => false]);

    $this->assertDatabaseMissing('inventory_reservations', [
        'product_id' => $product->id,
    ]);
});

// 4. Expired reservations are cleaned up
test('expired_reservations_are_cleaned_up', function () {
    $product = Product::factory()->create(['stock' => 10]);

    // Create an expired reservation directly
    $reservation = \App\Models\InventoryReservation::create([
        'product_id' => $product->id,
        'session_id' => 'test-session',
        'quantity' => 3,
        'expires_at' => now()->subMinutes(20),
        'status' => 'pending',
    ]);

    // Run cleanup
    $response = $this->postJson(route('reservations.cleanup'));
    $response->assertStatus(200);

    $reservation->refresh();
    expect($reservation->status)->toBe('expired');
});

// 5. Reservation is released when cart item removed
test('reservation_is_released_when_cart_item_removed', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $sessionId = Session::getId();

    // Create reservation
    \App\Models\InventoryReservation::create([
        'product_id' => $product->id,
        'session_id' => $sessionId,
        'quantity' => 2,
        'expires_at' => now()->addMinutes(15),
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('inventory_reservations', [
        'product_id' => $product->id,
        'status' => 'pending',
    ]);

    $this->postJson(route('reservations.release'), [
        'product_id' => $product->id,
    ])->assertStatus(200);

    $this->assertDatabaseHas('inventory_reservations', [
        'product_id' => $product->id,
        'status' => 'released',
    ]);
});

// 6. Cannot reserve already fully reserved stock
test('cannot_reserve_already_reserved_stock', function () {
    $product = Product::factory()->create(['stock' => 5]);

    // First reservation takes all stock
    \App\Models\InventoryReservation::create([
        'product_id' => $product->id,
        'session_id' => 'session-a',
        'quantity' => 5,
        'expires_at' => now()->addMinutes(15),
        'status' => 'pending',
    ]);

    // Second reservation should fail because available stock = 0
    $this->postJson(route('reservations.create'), [
        'product_id' => $product->id,
        'quantity' => 1,
    ])
    ->assertStatus(422)
    ->assertJson(['success' => false]);
});

// 7. Reservation extends expiry on update
test('reservation_extends_expiry_on_update', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $sessionId = Session::getId();

    $reservation = \App\Models\InventoryReservation::create([
        'product_id' => $product->id,
        'session_id' => $sessionId,
        'quantity' => 2,
        'expires_at' => now()->addMinutes(5),
        'status' => 'pending',
    ]);

    $oldExpiry = $reservation->expires_at;

    // Reserve again should extend expiry
    $this->postJson(route('reservations.create'), [
        'product_id' => $product->id,
        'quantity' => 2,
    ])->assertStatus(200);

    $reservation->refresh();
    expect($reservation->expires_at->gt($oldExpiry))->toBeTrue();
});

// 8. Reservation updates quantity on re-reserve
test('reservation_updates_quantity_on_re_reserve', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $sessionId = Session::getId();

    \App\Models\InventoryReservation::create([
        'product_id' => $product->id,
        'session_id' => $sessionId,
        'quantity' => 2,
        'expires_at' => now()->addMinutes(15),
        'status' => 'pending',
    ]);

    $this->postJson(route('reservations.create'), [
        'product_id' => $product->id,
        'quantity' => 5,
    ])->assertStatus(200);

    $this->assertDatabaseHas('inventory_reservations', [
        'product_id' => $product->id,
        'quantity' => 5,
    ]);
});
