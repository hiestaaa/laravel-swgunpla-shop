<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class HomeController extends Controller
{
    /**
     * Hiển thị trang chủ
     */
    public function index()
    {
        // Lấy 8 sản phẩm mới nhất
        $newArrivals = Product::with('category', 'brand')
                              ->latest() // Sắp xếp theo ngày tạo
                              ->take(8)
                              ->get();

        // Lấy 8 sản phẩm nổi bật (được đánh giá cao - chỉ đếm review đã duyệt)
        $featuredProducts = Product::with('category', 'brand')
                                   ->withCount(['reviews' => function ($query) {
                                       $query->where('status', 'approved');
                                   }])
                                   ->orderBy('reviews_count', 'desc') // Sắp xếp
                                   ->take(8)
                                   ->get();

        return view('home', compact('newArrivals', 'featuredProducts'));
    }
}
