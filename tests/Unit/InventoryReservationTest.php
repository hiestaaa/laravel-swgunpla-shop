<?php

use App\Models\InventoryReservation;
use App\Models\Product;

beforeEach(function () {
    $this->artisan('migrate:fresh');
});

// 1. Reservation is expired when past due
test('reservation_is_expired_when_past_due', function () {
    $reservation = InventoryReservation::factory()->expired()->create();

    expect($reservation->isExpired())->toBeTrue();
});

// 2. Reservation is not expired when future
test('reservation_is_not_expired_when_future', function () {
    $reservation = InventoryReservation::factory()->create([
        'expires_at' => now()->addHours(2),
    ]);

    expect($reservation->isExpired())->toBeFalse();
});

// 3. Commit changes status
test('commit_changes_status', function () {
    $reservation = InventoryReservation::factory()->create(['status' => 'pending']);

    $reservation->commit();

    $reservation->refresh();
    expect($reservation->status)->toBe('committed');
});

// 4. Release changes status
test('release_changes_status', function () {
    $reservation = InventoryReservation::factory()->create(['status' => 'pending']);

    $reservation->release();

    $reservation->refresh();
    expect($reservation->status)->toBe('released');
});

// 5. Scope active filters only pending and unexpired
test('scope_active_filters_only_pending_unexpired', function () {
    $product = Product::factory()->create();

    // Active reservation
    InventoryReservation::factory()->forProduct($product->id)->create([
        'status' => 'pending',
        'expires_at' => now()->addMinutes(10),
    ]);

    // Expired reservation
    InventoryReservation::factory()->forProduct($product->id)->create([
        'status' => 'pending',
        'expires_at' => now()->subMinutes(10),
    ]);

    // Released reservation
    InventoryReservation::factory()->forProduct($product->id)->create([
        'status' => 'released',
        'expires_at' => now()->addMinutes(10),
    ]);

    $active = InventoryReservation::active()->count();
    expect($active)->toBe(1);
});

// 6. Scope for product filters correctly
test('scope_for_product_filters_correctly', function () {
    $productA = Product::factory()->create();
    $productB = Product::factory()->create();

    InventoryReservation::factory()->forProduct($productA->id)->count(3)->create();
    InventoryReservation::factory()->forProduct($productB->id)->count(2)->create();

    $countA = InventoryReservation::forProduct($productA->id)->count();
    $countB = InventoryReservation::forProduct($productB->id)->count();

    expect($countA)->toBe(3);
    expect($countB)->toBe(2);
});

// 7. Scope for session filters correctly
test('scope_for_session_filters_correctly', function () {
    $sessionA = 'session-aaa';
    $sessionB = 'session-bbb';

    InventoryReservation::factory()->forSession($sessionA)->count(3)->create();
    InventoryReservation::factory()->forSession($sessionB)->count(2)->create();

    $countA = InventoryReservation::forSession($sessionA)->count();
    $countB = InventoryReservation::forSession($sessionB)->count();

    expect($countA)->toBe(3);
    expect($countB)->toBe(2);
});
