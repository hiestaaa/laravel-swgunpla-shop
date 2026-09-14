<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockNotification;
use Illuminate\Http\Request;

class StockNotificationController extends Controller
{
    /**
     * Danh sách thông báo tồn kho.
     */
    public function index(Request $request)
    {
        $query = StockNotification::with('product', 'user');

        // Lọc theo product_id nếu có
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Lọc theo status nếu có
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $notifications = $query->latest()->paginate(20);

        // Lấy danh sách sản phẩm đang hết hàng cho dropdown filter
        $outOfStockProducts = Product::where('stock', '<=', 0)->orderBy('name')->get();

        return view('admin.stock-notifications.index', compact('notifications', 'outOfStockProducts'));
    }

    /**
     * Đánh dấu thông báo đã được gửi.
     */
    public function notify(StockNotification $notification)
    {
        $notification->markNotified();

        return back()->with('success', 'Đã đánh dấu thông báo là đã gửi.');
    }

    /**
     * Gửi thông báo hàng loạt cho một sản phẩm.
     */
    public function bulkNotify(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($product->stock <= 0) {
            return back()->with('error', 'Sản phẩm này vẫn chưa có hàng, không thể gửi thông báo.');
        }

        $notified = StockNotification::forProduct($product->id)
            ->pending()
            ->count();

        // Đánh dấu tất cả thông báo pending của sản phẩm là notified
        StockNotification::forProduct($product->id)
            ->pending()
            ->update([
                'status' => 'notified',
                'notified_at' => now(),
            ]);

        return back()->with('success', "Đã gửi thông báo cho {$notified} người dùng về sản phẩm {$product->name}.");
    }
}
