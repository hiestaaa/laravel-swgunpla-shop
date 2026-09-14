@extends('layouts.guest')

@section('title', $product->name . ' - SWGunpla')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-lg-6 mb-4 mb-lg-0">
            @if($product->images->isNotEmpty())
                <img src="{{ Storage::url($product->images->first()->image_path) }}" class="img-fluid rounded mb-3 shadow-sm product-main-image" alt="{{ $product->name }}" id="mainProductImage">

                <div class="d-flex flex-wrap product-thumbnails">
                    @foreach($product->images as $image)
                    <img src="{{ Storage::url($image->image_path) }}"
                         width="80" height="80"
                         class="img-thumbnail me-2 mb-2 object-fit-cover"
                         alt="thumbnail"
                         style="cursor: pointer;"
                         onclick="document.getElementById('mainProductImage').src = this.src">
                    @endforeach
                </div>
            @else
                <img src="{{ $product->first_image_url }}" class="img-fluid rounded mb-3 shadow-sm" alt="{{ $product->name }}" id="mainProductImage">
            @endif
        </div>
        <div class="col-lg-6 ps-lg-4">
            <h1>{{ $product->name }}</h1>
            <p class="fs-4 text-danger fw-bold mb-3">{{ number_format($product->price, 0, ',', '.') }} VNĐ</p>
            <p class="mb-2">
                <strong>Thương hiệu:</strong>
                <a href="{{ route('products.index', ['brand' => $product->brand->slug]) }}" class="text-decoration-none">{{ $product->brand->name }}</a> <br>
                <strong>Danh mục:</strong>
                <a href="{{ route('products.index', ['category' => $product->category->slug]) }}" class="text-decoration-none">{{ $product->category->name }}</a> <br>
                <strong>Tình trạng:</strong>
                @if($product->stock > 0)
                    <span class="badge bg-success">Còn hàng ({{ $product->stock }})</span>
                @else
                    <span class="badge bg-danger">Hết hàng</span>
                @endif
            </p>

            <hr>

            @if($product->stock > 0)
            <form action="{{ route('cart.add') }}" method="POST" class="d-flex align-items-center mb-4">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <div class="input-group me-3" style="max-width: 150px;">
                    <button class="btn btn-outline-secondary" type="button" onclick="this.nextElementSibling.stepDown()">-</button>
                    <input type="number" class="form-control text-center" name="quantity" id="quantity" value="1" min="1" max="{{ $product->stock }}" aria-label="Quantity">
                    <button class="btn btn-outline-secondary" type="button" onclick="this.previousElementSibling.stepUp()">+</button>
                </div>
                <button type="submit" class="btn btn-danger btn-lg">
                    <i class="bi bi-cart-plus me-1"></i> Thêm vào giỏ
                </button>
            </form>
            @else
            <div class="alert alert-warning">
                <i class="bi bi-bell me-1"></i> Sản phẩm tạm hết hàng.
                @auth
                    @php
                        $hasNotification = \App\Models\StockNotification::where('product_id', $product->id)
                            ->where('user_id', auth()->id())
                            ->where('status', 'pending')
                            ->exists();
                    @endphp
                    @if($hasNotification)
                        <span class="badge bg-success ms-2">Đã đăng ký nhận thông báo</span>
                    @else
                        <form action="{{ route('stock-notifications.subscribe', $product) }}" method="POST" class="d-inline mt-2">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm">
                                <i class="bi bi-bell me-1"></i> Thông báo khi có hàng
                            </button>
                        </form>
                    @endif
                @else
                    <p class="mb-0 mt-2 small"><a href="{{ route('login') }}">Đăng nhập</a> để nhận thông báo khi có hàng.</p>
                @endauth
            </div>
            @endif

            <hr>

            <h4>Mô tả</h4>
            <div class="product-description-wrapper collapsed" id="description-wrapper">
                <div class="product-description">
                    {!! nl2br(e($product->description ?? 'Chưa có mô tả cho sản phẩm này.')) !!}
                </div>
                <div class="description-fade-out"></div>
            </div>

            <a role="button" class="btn-link text-decoration-none fw-bold p-0" 
            id="description-toggle-btn">
                Đọc thêm <i class="bi bi-chevron-down small"></i>
            </a>
        </div>
    </div>

    <hr class="my-5">
    <div class="row">
        <div class="col-12">
            <h3>Đánh giá ({{ $product->reviews->count() }})</h3>

            @forelse($product->reviews as $review)
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div>
                                <strong class="me-2">{{ $review->user->name ?? 'Người dùng ẩn' }}</strong>
                                <span class="text-warning">
                                    @for ($i = 0; $i < 5; $i++)
                                        <i class="bi {{ $i < $review->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                                    @endfor
                                </span>
                            </div>
                            <small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                        </div>
                        <p class="card-text mb-0">{{ $review->comment }}</p>
                    </div>
                </div>
            @empty
                <div class="alert alert-light">Sản phẩm này chưa có đánh giá nào.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('styles') {{-- Thêm CSS riêng cho trang này --}}
<style>
    .product-main-image { max-height: 500px; width: 100%; object-fit: contain; }
    .product-thumbnails img { cursor: pointer; transition: opacity 0.3s ease; }
    .product-thumbnails img:hover { opacity: 0.7; }
    .product-description { line-height: 1.7; }
</style>
@endpush

@push('scripts')
<script>
    // Lắng nghe sự kiện click trên nút "Đọc thêm"
    var toggleBtn = document.getElementById('description-toggle-btn');
    var wrapper = document.getElementById('description-wrapper');

    if (toggleBtn && wrapper) {
        toggleBtn.addEventListener('click', function () {
            // Kiểm tra xem có class "collapsed" hay không
            if (wrapper.classList.contains('collapsed')) {
                // Nếu đang thu gọn -> Mở rộng ra
                wrapper.classList.remove('collapsed');
                toggleBtn.innerHTML = 'Thu gọn <i class="bi bi-chevron-up small"></i>';
            } else {
                // Nếu đang mở rộng -> Thu gọn lại
                wrapper.classList.add('collapsed');
                toggleBtn.innerHTML = 'Đọc thêm <i class="bi bi-chevron-down small"></i>';
            }
        });
    }
</script>
@endpush