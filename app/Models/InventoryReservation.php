<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'session_id',
        'user_id',
        'quantity',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    // Một reservation thuộc về 1 Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Một reservation thuộc về 1 User (có thể null nếu là guest)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope: chỉ lấy reservation đang active (pending và chưa hết hạn)
    public function scopeActive($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    // Scope: lọc theo product_id
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    // Scope: lọc theo session_id
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    // Kiểm tra reservation đã hết hạn chưa
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    // Đánh dấu đã commit (đã chuyển thành đơn hàng)
    public function commit(): void
    {
        $this->update(['status' => 'committed']);
    }

    // Đánh dấu đã release (trả hàng về kho)
    public function release(): void
    {
        $this->update(['status' => 'released']);
    }
}
