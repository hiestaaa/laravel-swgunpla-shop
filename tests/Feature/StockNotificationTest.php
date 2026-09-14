<?php

use App\Models\StockNotification;
use App\Models\User;
use App\Models\Product;

// RefreshDatabase is enabled globally in Pest.php for Feature tests

// 1. Auth user can subscribe to out-of-stock product
test('auth_user_can_subscribe_to_out_of_stock_product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 0]);

    $this->actingAs($user)
        ->post(route('stock-notifications.subscribe', $product))
        ->assertRedirect();

    $this->assertDatabaseHas('stock_notifications', [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'status' => 'pending',
    ]);
});

// 2. Guest can subscribe with email
test('guest_can_subscribe_with_email', function () {
    $product = Product::factory()->create(['stock' => 0]);

    $this->post(route('stock-notifications.subscribe', $product), [
        'email' => 'guest@example.com',
    ])->assertRedirect();

    $this->assertDatabaseHas('stock_notifications', [
        'product_id' => $product->id,
        'email' => 'guest@example.com',
        'user_id' => null,
    ]);
});

// 3. Cannot subscribe to in-stock product
test('cannot_subscribe_to_in_stock_product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 5]);

    $this->actingAs($user)
        ->post(route('stock-notifications.subscribe', $product))
        ->assertSessionHas('error');

    $this->assertDatabaseMissing('stock_notifications', [
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);
});

// 4. User can unsubscribe
test('user_can_unsubscribe_from_notification', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 0]);

    $notification = StockNotification::create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->delete(route('stock-notifications.unsubscribe', $notification))
        ->assertRedirect();

    $this->assertDatabaseHas('stock_notifications', [
        'id' => $notification->id,
        'status' => 'cancelled',
    ]);
});

// 5. Cannot duplicate subscribe
test('cannot_duplicate_subscribe', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 0]);

    // First subscription
    $this->actingAs($user)
        ->post(route('stock-notifications.subscribe', $product))
        ->assertRedirect();

    // Second subscription
    $this->actingAs($user)
        ->post(route('stock-notifications.subscribe', $product))
        ->assertSessionHas('info');

    $this->assertDatabaseCount('stock_notifications', 1);
});

// 6. Cannot unsubscribe another user's notification
test('cannot_unsubscribe_others_notification', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $product = Product::factory()->create(['stock' => 0]);

    $notification = StockNotification::create([
        'product_id' => $product->id,
        'user_id' => $otherUser->id,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->delete(route('stock-notifications.unsubscribe', $notification))
        ->assertStatus(403);
});

// 7. Admin can view notifications list
test('admin_can_view_notifications_list', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    StockNotification::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.stock-notifications.index'))
        ->assertStatus(200)
        ->assertSee('Thông báo khi có hàng');
});

// 8. Non-admin cannot access admin notifications
test('non_admin_cannot_access_admin_notifications', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.stock-notifications.index'))
        ->assertRedirect('/');
});

// 9. Admin can mark notification as notified
test('admin_can_mark_notification_notified', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $product = Product::factory()->create(['stock' => 0]);
    $notification = StockNotification::create([
        'product_id' => $product->id,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.stock-notifications.notify', $notification))
        ->assertRedirect();

    $notification->refresh();
    expect($notification->status)->toBe('notified');
    expect($notification->notified_at)->not->toBeNull();
});

// 10. Bulk notify for in-stock product
test('admin_can_bulk_notify_for_in_stock_product', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $product = Product::factory()->create(['stock' => 10]);
    $user = User::factory()->create();

    StockNotification::create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.stock-notifications.bulk-notify'), [
            'product_id' => $product->id,
        ])
        ->assertSessionHas('success');

    $this->assertDatabaseCount('stock_notifications', 1);
    $this->assertDatabaseHas('stock_notifications', [
        'product_id' => $product->id,
        'status' => 'notified',
    ]);
});
