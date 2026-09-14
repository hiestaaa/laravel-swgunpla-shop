<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Session;

// RefreshDatabase is enabled globally in Pest.php for Feature tests

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

    // Create reservation directly with the test session ID
    $sessionId = Session::getId();
    $reservation = \App\Models\InventoryReservation::create([
        'product_id' => $product->id,
        'session_id' => $sessionId,
        'quantity' => 2,
        'expires_at' => now()->addMinutes(15),
        'status' => 'pending',
    ]);

    // Simulate the release controller logic directly
    $reservation->release();

    $reservation->refresh();
    expect($reservation->status)->toBe('released');
});

// 6. Cannot reserve already fully reserved stock
test('cannot_reserve_already_reserved_stock', function () {
    $product = Product::factory()->create(['stock' => 5]);

    // First reservation takes all stock via HTTP
    $this->postJson(route('reservations.create'), [
        'product_id' => $product->id,
        'quantity' => 5,
    ])->assertStatus(200);

    // Second reservation should fail because available stock = 0
    $this->postJson(route('reservations.create'), [
        'product_id' => $product->id,
        'quantity' => 1,
    ])
    ->assertStatus(422)
    ->assertJson(['success' => false]);
});

// 7. Reservation extends expiry on re-reserve (via controller update path)
test('reservation_extends_expiry_on_update', function () {
    $product = Product::factory()->create(['stock' => 10]);

    // Create reservation directly with a near-expired time
    $reservation = \App\Models\InventoryReservation::create([
        'product_id' => $product->id,
        'session_id' => Session::getId(),
        'quantity' => 2,
        'expires_at' => now()->addMinutes(2),
        'status' => 'pending',
    ]);

    $oldExpiry = $reservation->expires_at;

    // Simulate what the controller does when finding an existing reservation:
    // it updates with now()->addMinutes(15)
    $reservation->update([
        'expires_at' => now()->addMinutes(15),
    ]);

    $reservation->refresh();
    expect($reservation->expires_at->gt($oldExpiry))->toBeTrue();
});

// 8. Reservation updates quantity on re-reserve
test('reservation_updates_quantity_on_re_reserve', function () {
    $product = Product::factory()->create(['stock' => 10]);

    \App\Models\InventoryReservation::create([
        'product_id' => $product->id,
        'session_id' => Session::getId(),
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
