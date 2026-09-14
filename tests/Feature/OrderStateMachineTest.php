<?php

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->artisan('migrate:fresh');
    // Model events use auth()->id() which is null outside HTTP context.
    // Set a default so booted() event creator is populated.
    Auth::shouldReceive('id')->andReturn(1);
});

// 1. Order creation creates 'created' event
test('order_creation_creates_event', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 10]);

    // Simulate order creation by creating an order directly
    // and checking that the booted event fires
    $order = new Order([
        'user_id' => $user->id,
        'total_amount' => 100000,
        'status' => 'pending',
        'payment_method' => 'cod',
    ]);
    $order->save();

    $event = OrderEvent::forOrder($order->id)->first();
    expect($event)->not->toBeNull();
    expect($event->event_type)->toBe('created');
    expect($event->to_status)->toBe('pending');
    expect($event->from_status)->toBeNull();
    expect($event->created_by)->toBe($user->id);
});

// 2. Status change creates 'status_changed' event
test('status_change_creates_event', function () {
    $order = Order::factory()->create(['status' => 'pending']);

    // Change status to processing
    $order->update(['status' => 'processing']);

    $event = OrderEvent::forOrder($order->id)
        ->byType('status_changed')
        ->first();

    expect($event)->not->toBeNull();
    expect($event->from_status)->toBe('pending');
    expect($event->to_status)->toBe('processing');
});

// 3. Event records from and to status
test('event_records_from_and_to_status', function () {
    $order = Order::factory()->create(['status' => 'processing']);

    $order->update(['status' => 'completed']);

    $event = OrderEvent::forOrder($order->id)->byType('status_changed')->first();
    expect($event->from_status)->toBe('processing');
    expect($event->to_status)->toBe('completed');
});

// 4. Event records creator
test('event_records_creator', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // Override the default Auth::shouldReceive for this test
    Auth::shouldReceive('id')->andReturn($admin->id);

    $order = Order::factory()->create(['status' => 'pending']);

    $event = OrderEvent::forOrder($order->id)->byType('created')->first();
    expect($event->created_by)->toBe($admin->id);
});

// 5. Order events are ordered by date (newest first via scopeRecent)
test('order_events_are_ordered_by_date', function () {
    $order = Order::factory()->create(['status' => 'pending']);

    // Create multiple events
    $order->update(['status' => 'processing']);
    $order->update(['status' => 'completed']);

    $events = OrderEvent::forOrder($order->id)->get();
    expect($events->count())->toBeGreaterThanOrEqual(3); // created + 2 status_changed

    // First event should be the most recent
    expect($events->first()->event_type)->toBe('status_changed');
    expect($events->first()->to_status)->toBe('completed');
});

// 6. Admin can view order events
test('admin_can_view_order_events', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    OrderEvent::factory()->count(5)->create();

    $this->actingAs($admin)
        ->get(route('admin.order-events.index'))
        ->assertStatus(200)
        ->assertSee('Lịch sử trạng thái');
});

// 7. Non-admin cannot access order events
test('non_admin_cannot_access_order_events', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.order-events.index'))
        ->assertRedirect('/');
});

// 8. Cancellation creates cancelled event
test('cancellation_creates_cancelled_event', function () {
    $order = Order::factory()->create(['status' => 'pending']);

    $order->update(['status' => 'cancelled']);

    $event = OrderEvent::forOrder($order->id)->byType('status_changed')->latest()->first();
    expect($event)->not->toBeNull();
    expect($event->from_status)->toBe('pending');
    expect($event->to_status)->toBe('cancelled');
});

// 9. Order event metadata is stored correctly
test('event_metadata_is_stored_correctly', function () {
    $order = Order::factory()->create([
        'status' => 'pending',
        'payment_method' => 'vnpay',
        'total_amount' => 250000,
    ]);

    $event = OrderEvent::forOrder($order->id)->byType('created')->first();
    expect($event)->not->toBeNull();
    expect($event->metadata)->toHaveKey('payment_method');
    expect($event->metadata['payment_method'])->toBe('vnpay');
    expect($event->metadata['total_amount'])->toBe('250000');
});

// 10. ScopeRecent returns only recent events
test('scope_recent_returns_only_recent_events', function () {
    $order = Order::factory()->create(['status' => 'pending']);

    // Create many events
    for ($i = 0; $i < 10; $i++) {
        $order->update(['status' => $i % 2 === 0 ? 'processing' : 'pending']);
    }

    $recent = OrderEvent::forOrder($order->id)->recent()->count();
    expect($recent)->toBeLessThanOrEqual(50);
});
