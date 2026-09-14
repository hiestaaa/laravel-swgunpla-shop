<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'event_type',
        'from_status',
        'to_status',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Một sự kiện thuộc về 1 Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Người tạo sự kiện
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scope: lọc theo order_id
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    // Scope: lọc theo event_type
    public function scopeByType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    // Scope: lấy sự kiện gần nhất
    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at')->take(50);
    }
}
