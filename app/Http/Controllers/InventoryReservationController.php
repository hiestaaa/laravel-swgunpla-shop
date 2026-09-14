<?php

namespace App\Http\Controllers;

use App\Models\InventoryReservation;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class InventoryReservationController extends Controller
{
    /**
     * Đặt trước tồn kho cho sản phẩm (khi thêm vào giỏ hàng).
     * Reservation có hiệu lực trong 15 phút.
     */
    public function reserve(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Kiểm tra tồn kho có đủ không (bao gồm cả reservation đang active)
        $activeReservations = InventoryReservation::active()
            ->forProduct($product->id)
            ->sum('quantity');
        $availableStock = $product->stock - $activeReservations;

        if ($availableStock < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không đủ số lượng tồn kho!',
            ], 422);
        }

        $sessionId = Session::getId();
        $userId = auth()->id();
        $quantity = $request->quantity;

        // Tìm reservation đang active cho sản phẩm này trong session hiện tại
        $existing = InventoryReservation::active()
            ->forProduct($product->id)
            ->forSession($sessionId)
            ->first();

        if ($existing) {
            // Cập nhật reservation cũ: reset thời gian hết hạn
            $existing->update([
                'quantity' => $quantity,
                'expires_at' => now()->addMinutes(15),
            ]);
        } else {
            // Tạo reservation mới
            InventoryReservation::create([
                'product_id' => $product->id,
                'session_id' => $sessionId,
                'user_id' => $userId,
                'quantity' => $quantity,
                'expires_at' => now()->addMinutes(15),
                'status' => 'pending',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đặt trước tồn kho thành công.',
            'data' => [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'expires_at' => now()->addMinutes(15)->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Giải phóng reservation cho sản phẩm (khi xóa khỏi giỏ hàng).
     */
    public function release(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $sessionId = Session::getId();

        // Tìm và giải phóng reservation active
        $reservation = InventoryReservation::active()
            ->forProduct($request->product_id)
            ->forSession($sessionId)
            ->first();

        if ($reservation) {
            $reservation->release();
            return response()->json([
                'success' => true,
                'message' => 'Đã giải phóng đặt trước.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy đặt trước nào.',
        ], 404);
    }

    /**
     * Dọn dẹp các reservation đã hết hạn (đánh dấu là expired).
     */
    public function cleanup()
    {
        $expired = InventoryReservation::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $reservation) {
            $reservation->update(['status' => 'expired']);
        }

        return response()->json([
            'success' => true,
            'cleaned' => $expired->count(),
            'message' => "Đã dọn dẹp {$expired->count()} reservation hết hạn.",
        ]);
    }
}
