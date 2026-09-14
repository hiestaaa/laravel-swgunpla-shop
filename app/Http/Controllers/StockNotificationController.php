<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockNotificationController extends Controller
{
    /**
     * Đăng ký nhận thông báo khi sản phẩm có hàng trở lại.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Chỉ cho phép đăng ký khi sản phẩm hết hàng
        if ($product->stock > 0) {
            return back()->with('error', 'Sản phẩm này vẫn còn hàng, không cần đăng ký thông báo.');
        }

        $userId = Auth::id();
        $email = Auth::user()->email ?? $request->input('email');

        // Kiểm tra đã đăng ký chưa
        $existing = StockNotification::where('product_id', $product->id)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when(!$userId, fn($q) => $q->where('email', $email))
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return back()->with('info', 'Bạn đã đăng ký nhận thông báo cho sản phẩm này rồi.');
        }

        StockNotification::create([
            'product_id' => $product->id,
            'user_id' => $userId,
            'email' => $email,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Đăng ký nhận thông báo thành công. Chúng tôi sẽ thông báo khi sản phẩm có hàng trở lại.');
    }

    /**
     * Hủy đăng ký thông báo.
     */
    public function unsubscribe(Request $request, StockNotification $notification)
    {
        // Xác thực quyền sở hữu
        if ($notification->user_id !== Auth::id()) {
            abort(403, 'Bạn không có quyền hủy đăng ký này.');
        }

        $notification->update(['status' => 'cancelled']);

        return back()->with('success', 'Đã hủy đăng ký thông báo.');
    }
}
