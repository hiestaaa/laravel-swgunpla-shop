<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Sử dụng eager loading 'user' để lấy tên người đặt
        $query = Order::with('user');

        // Chức năng tìm kiếm đơn giản theo ID hoặc tên user
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('id', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'LIKE', "%{$search}%");
                  });
        }
        
        // Sắp xếp đơn hàng mới nhất lên đầu và phân trang
        $orders = $query->latest()->paginate(15);

        return view('admin.orders.index', compact('orders'));
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
    public function show(Order $order)
    {
        // Tải đầy đủ thông tin: người dùng, địa chỉ, và các sản phẩm (items)
        $order->load('user', 'address', 'items.product');
        
        return view('admin.orders.show', compact('order'));
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
        // Validate dữ liệu đầu vào
        $validated = $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled',
        ]);

        $newStatus = $validated['status'];
        $currentStatus = $order->status;

        // Không cho phép chuyển trạng thái nếu đơn đã hoàn thành (trạng thái cuối)
        if ($currentStatus === 'completed' && $newStatus !== 'completed') {
            return back()->with('error', "Không thể chuyển trạng thái đơn hàng đã hoàn thành.");
        }

        // Ngăn chuyển từ pending/processing trực tiếp sang completed mà qua processing
        // Tuy nhiên cho phép chuyển linh hoạt: pending -> processing/cancelled, processing -> completed/cancelled
        $allowedTransitions = [
            'pending'    => ['processing', 'cancelled'],
            'processing' => ['completed', 'cancelled'],
            'cancelled'  => ['processing'],
        ];

        if (!in_array($newStatus, $allowedTransitions[$currentStatus] ?? [])) {
            return back()->with('error', "Không thể chuyển trạng thái từ '{$currentStatus}' sang '{$newStatus}'.");
        }

        $order->update($validated);

        return redirect()->route('admin.orders.show', $order)->with('success', 'Cập nhật trạng thái đơn hàng thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
}
