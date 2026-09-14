@extends('layouts.app')

@section('title', 'Chi tiết Đơn hàng #' . $order->id)

@section('header')
<div class="d-flex justify-content-between align-items-center">
    <h2 class="h4 mb-0 fw-bold">
         Chi tiết Đơn hàng #{{ $order->id }}
    </h2>
     <div class="d-flex gap-2">
        <a href="{{ route('user.orders.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Quay lại
         </a>
        @if($order->status == 'pending')
        <form action="{{ route('user.orders.cancel', $order) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn hủy đơn hàng này?');">
            @csrf
            @method('POST')
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-x-circle me-1"></i> Hủy đơn hàng
            </button>
        </form>
        @endif
     </div>
</div>
@endsection

@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="row mb-4">
                <div class="col-md-6 mb-3 mb-md-0">
                    <h5 class="mb-3">Thông tin đơn hàng</h5>
                    <p class="mb-1"><strong>ID Đơn hàng:</strong> #{{ $order->id }}</p>
                    <p class="mb-1"><strong>Ngày đặt:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                    <p class="mb-1"><strong>Phương thức TT:</strong> {{ strtoupper($order->payment_method) }}</p>
                    <p class="mb-1"><strong>Trạng thái:</strong>
                        @if($order->status == 'pending')
                            <span class="badge bg-warning text-dark">Chờ xử lý</span>
                        @elseif($order->status == 'processing')
                            <span class="badge bg-info text-dark">Đang xử lý</span>
                        @elseif($order->status == 'completed')
                            <span class="badge bg-success">Hoàn thành</span>
                        @elseif($order->status == 'cancelled')
                            <span class="badge bg-danger">Đã hủy</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-6">
                     <h5 class="mb-3">Địa chỉ giao hàng</h5>
                     @if($order->address)
                        <p class="mb-1">
                            <strong>{{ $order->address->full_name }}</strong> ({{ $order->address->phone }})<br>
                            {{ $order->address->address_line }}, {{ $order->address->ward }}, {{ $order->address->district }}, {{ $order->address->city }}
                        </p>
                    @else
                        <p class="text-danger">Không có thông tin địa chỉ.</p>
                    @endif
                </div>
            </div>

            <hr>

            <h5 class="mt-4 mb-3">Các sản phẩm đã đặt</h5>
             <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Sản phẩm</th>
                            <th class="text-end">Đơn giá</th>
                            <th class="text-center">Số lượng</th>
                            <th class="text-end">Thành tiền</th>
                            <th>Đánh giá</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td>{{ $item->product->name ?? 'Sản phẩm đã bị xóa' }}</td>
                            <td class="text-end">{{ number_format($item->price, 0, ',', '.') }} VNĐ</td>
                            <td class="text-center">x {{ $item->quantity }}</td>
                            <td class="text-end fw-bold">{{ number_format($item->price * $item->quantity, 0, ',', '.') }} VNĐ</td>
                            <td>
                                @if(in_array($item->product_id, $reviewedProductIds))
                                    <span class="badge bg-success">Đã đánh giá</span>
                                @elseif($order->status == 'completed')
                                    <button class="btn btn-warning btn-sm py-0 px-2"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#reviewForm-{{ $item->id }}"
                                            aria-expanded="false">
                                        Viết ĐG
                                    </button>
                                @else
                                    <span class="badge bg-light text-dark">Chờ đơn hoàn thành</span>
                                @endif
                            </td>
                        </tr>
                        @if($order->status == 'completed' && !in_array($item->product_id, $reviewedProductIds))
                        <tr class="collapse" id="reviewForm-{{ $item->id }}">
                            <td colspan="5">
                                <form action="{{ route('reviews.store') }}" method="POST" class="card card-body bg-light border-0">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $item->product_id }}">
                                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                                    <p class="mb-1 small">Đánh giá cho <strong>{{ $item->product->name }}</strong></p>
                                    <div class="mb-2">
                                        <label class="form-label small">Xếp hạng (1-5 sao)*</label>
                                        <select name="rating" class="form-select form-select-sm" required style="width: 200px;">
                                            <option value="5">5 sao (Rất Tốt)</option>
                                            <option value="4">4 sao (Tốt)</option>
                                            <option value="3" selected>3 sao (Bình thường)</option>
                                            <option value="2">2 sao (Tệ)</option>
                                            <option value="1">1 sao (Rất Tệ)</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">Bình luận</label>
                                        <textarea name="comment" class="form-control form-control-sm" rows="3"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm" style="width: 150px;">Gửi đánh giá</button>
                                </form>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end border-0 fw-bold fs-5">TỔNG CỘNG:</td>
                            <td class="text-end fs-5 fw-bold text-danger border-0">
                                {{ number_format($order->total_amount, 0, ',', '.') }} VNĐ
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection