<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 
        'address_id', 
        'total_amount', 
        'status', 
        'payment_method', 
        'voucher_id'
    ];

    // Nhiều Order thuộc về 1 User 
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    // Nhiều Order được giao đến 1 Address
    public function address() {
        return $this->belongsTo(Address::class);
    }

    // Nhiều Order có nhiều Product
    public function products() {
        return $this->belongsToMany(Product::class, 'order_items')->withPivot('quantity', 'price');
    }

    // 1 Order có nhiều OrderItem
    public function items() {
        return $this->hasMany(OrderItem::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    protected static function booted()
    {
        // Lắng nghe sự kiện "updated" (khi đơn hàng được cập nhật)
        static::updated(function ($order) {

            // TRƯỜNG HỢP 1: HỦY ĐƠN HÀNG -> HOÀN KHO
            // Nếu trạng thái đổi thành 'cancelled' VÀ trạng thái cũ KHÔNG phải là 'cancelled'
            if ($order->status === 'cancelled' && $order->getOriginal('status') !== 'cancelled') {
                // Eager-load items để tránh N+1 query trong event
                $order->loadMissing('items.product');

                // Duyệt qua các sản phẩm trong đơn
                foreach ($order->items as $item) {
                    // Cộng lại số lượng vào kho
                    if ($item->product) {
                        $item->product->increment('stock', $item->quantity);
                    }
                }

                // (Tùy chọn) Nếu có Voucher, hoàn lại số lượng voucher
                if ($order->voucher) {
                    $order->voucher->increment('quantity');
                }
            }

            // TRƯỜNG HỢP 2: KHÔI PHỤC ĐƠN HÀNG (Admin lỡ tay hủy rồi bật lại) -> TRỪ KHO LẠI
            // Nếu trạng thái cũ là 'cancelled' VÀ trạng thái mới KHÁC 'cancelled'
            if ($order->getOriginal('status') === 'cancelled' && $order->status !== 'cancelled') {
                // Eager-load items để tránh N+1 query trong event
                $order->loadMissing('items.product');

                foreach ($order->items as $item) {
                    if ($item->product) {
                        // Kiểm tra tồn kho trước khi trừ để tránh âm
                        if ($item->product->stock >= $item->quantity) {
                            $item->product->decrement('stock', $item->quantity);
                        } else {
                            \Log::warning("Đơn hàng #{$order->id} được khôi phục nhưng sản phẩm #{$item->product->id} không đủ tồn kho để trừ.");
                        }
                    }
                }

                if ($order->voucher) {
                    $order->voucher->decrement('quantity');
                }
            }
        });
    }
}
