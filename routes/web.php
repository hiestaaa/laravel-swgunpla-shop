<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\VoucherController as AdminVoucherController;
use App\Http\Controllers\Admin\DashboardController;

use App\Http\Controllers\User\OrderController as UserOrderController;

use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\VNPayController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // === ĐỊA CHỈ ===
    Route::resource('addresses', AddressController::class);

    // === HÓA ĐƠN ===
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'placeOrder'])->name('checkout.place');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');

    // === LỊCH SỬ ĐƠN HÀNG ===
    // Trang danh sách
    Route::get('/my-orders', [UserOrderController::class, 'index'])->name('user.orders.index');
    // Trang chi tiết
    Route::get('/my-orders/{order}', [UserOrderController::class, 'show'])->name('user.orders.show');
    // Hủy đơn hàng
    Route::post('/my-orders/{order}/cancel', [UserOrderController::class, 'cancel'])->name('user.orders.cancel');

    // === ĐÁNH GIÁ SẢN PHẨM ===
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');

    // 1. Gửi yêu cầu thanh toán VNPay
    Route::post('/checkout/vnpay', [VNPayController::class, 'createPayment'])->name('checkout.vnpay');
    // 2. Xử lý kết quả trả về từ VNPay
    Route::get('/checkout/vnpay/return', [VNPayController::class, 'vnpayReturn'])->name('vnpay.return');
});

// === USER ===
// Route xem sản phẩm (Public)
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

// === GIỎ HÀNG ===
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');

// === VOUCHER ===
Route::post('/cart/apply-voucher', [CartController::class, 'applyVoucher'])->name('cart.apply_voucher');
Route::get('/cart/remove-voucher', [CartController::class, 'removeVoucher'])->name('cart.remove_voucher');

// === ADMINISTRATOR ===
// Nhóm các route quản trị, yêu cầu đăng nhập và là admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // CRUD Product
    Route::resource('products', AdminProductController::class);
    // CRUD Categories
    Route::resource('categories', AdminCategoryController::class);
    // CRUD Brand
    Route::resource('brands', AdminBrandController::class);

    // === XÓA ẢNH SẢN PHẨM ===
    Route::delete('/product-image/{productImage}', [AdminProductController::class, 'destroyImage'])->name('products.image.destroy');
    
    // === QUẢN LÝ ĐƠN HÀNG ===
    // Trang danh sách
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    // Trang chi tiết
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    // Cập nhật trạng thái
    Route::put('/orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');

    // === QUẢN LÝ ĐÁNH GIÁ ===
    Route::resource('reviews', AdminReviewController::class)->only(['index', 'destroy']);
    
    Route::patch('/reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');

    // === QUẢN LÝ VOUCHER ===
    Route::resource('vouchers', AdminVoucherController::class);
});

require __DIR__.'/auth.php';
