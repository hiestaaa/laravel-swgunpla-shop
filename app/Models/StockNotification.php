<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'email',
        'notified_at',
        'status',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
    ];

    // Một thông báo thuộc về 1 Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Một thông báo thuộc về 1 User (có thể null nếu là guest)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope: chỉ lấy thông báo đang chờ
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Scope: lọc theo product_id
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    // Đánh dấu đã thông báo
    public function markNotified(): void
    {
        $this->update([
            'status' => 'notified',
            'notified_at' => now(),
        ]);
    }
}
