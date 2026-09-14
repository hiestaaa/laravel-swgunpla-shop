<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Lấy đơn hàng của tài khoản đang đăng nhập
        // Tự động lọc theo user_id
        $orders = $request->user()->orders() 
            ->latest()
            ->paginate(10);

        return view('user.orders.index', compact('orders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Order $order)
    {
        // Xác thực user có quyền xem đơn hàng
        // dùng Policy để xác thực
        Gate::authorize('view', $order);

        $order->load('address', 'items.product');

        // Lấy danh sách ID các sản phẩm đã được user đánh giá
        $reviewedProductIds = Review::where('user_id', $order->user_id)
                                    ->pluck('product_id') // Chỉ lấy cột product_id
                                    ->toArray(); // Chuyển thành mảng

        return view('user.orders.show', compact('order', 'reviewedProductIds'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Hủy đơn hàng của user (chỉ cho phép đơn đang chờ xử lý)
     */
    public function cancel(Request $request, Order $order)
    {
        // Kiểm tra quyền sở hữu
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Bạn không có quyền hủy đơn hàng này.');
        }

        // Chỉ cho phép hủy đơn hàng đang chờ xử lý
        if ($order->status !== 'pending') {
            return back()->with('error', 'Chỉ có thể hủy đơn hàng đang chờ xử lý.');
        }

        $order->update(['status' => 'cancelled']);

        return back()->with('success', 'Đơn hàng đã được hủy thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
}
