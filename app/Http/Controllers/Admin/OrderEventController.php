<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderEvent;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderEventController extends Controller
{
    /**
     * Danh sách sự kiện đơn hàng (audit trail).
     */
    public function index(Request $request)
    {
        $query = OrderEvent::with('order', 'creator');

        // Lọc theo order_id nếu có
        if ($request->filled('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        // Lọc theo event_type nếu có
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        $events = $query->latest()->paginate(30);

        // Lấy danh sách event_type cho filter dropdown
        $eventTypes = OrderEvent::select('event_type')
            ->distinct()
            ->orderBy('event_type')
            ->pluck('event_type');

        return view('admin.order-events.index', compact('events', 'eventTypes'));
    }

    /**
     * Chi tiết một sự kiện đơn hàng.
     */
    public function show(OrderEvent $orderEvent)
    {
        $orderEvent->load('order.user', 'creator');

        return view('admin.order-events.show', compact('orderEvent'));
    }
}
