<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'category_id',
        'brand_id'
    ];

    // Nhiều Product thuộc về 1 Category
    public function category() {
        return $this->belongsTo(Category::class);
    }

    // Nhiều Product thuộc về 1 Brand
    public function brand() {
        return $this->belongsTo(Brand::class);
    }

    // 1 Product có nhiều ảnh
    public function images() {
        return $this->hasMany(ProductImage::class);
    }

    // 1 Product có nhiều đánh giá
    public function reviews() {
        return $this->hasMany(Review::class);
    }

    // 1 Product có nhiều thông báo tồn kho
    public function stockNotifications() {
        return $this->hasMany(StockNotification::class);
    }

    // Nhiều Product có trong nhiều Order
    public function orders() {
        return $this->belongsToMany(Order::class, 'order_items')->withPivot('quantity', 'price');
    }

    // Kiểm tra sản phẩm còn hàng
    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    // === ACCESSOR ĐỂ LẤY ẢNH ĐẦU TIÊN ===
    /**
     * Lấy URL đầy đủ của hình ảnh đầu tiên.
     *
     * @return string
     */
    public function getFirstImageUrlAttribute(): string
    {
        // Lấy ảnh đầu tiên, nếu không có thì dùng ảnh placeholder
        $image = $this->images->first();

        if ($image) {
            // Trả về URL đầy đủ (ví dụ: /storage/products/abc.jpg)
            return Storage::url($image->image_path);
        }

        // Link ảnh placeholder
        return 'https://via.placeholder.com/300x200.png?text=SWGunpla';
    }
}
