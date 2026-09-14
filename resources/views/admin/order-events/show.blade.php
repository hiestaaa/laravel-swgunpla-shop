@extends('layouts.admin')

@section('title', 'Chi tiết sự kiện #' . $orderEvent->id)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.orders.index') }}">Đơn hàng</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.order-events.index') }}">Lịch sử</a></li>
    <li class="breadcrumb-item active" aria-current="page">Chi tiết sự kiện</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Chi tiết sự kiện #{{ $orderEvent->id }}</h1>
    <a href="{{ route('admin.order-events.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Quay lại
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <div class="row mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
                <h5 class="mb-3">Thông tin sự kiện</h5>
                <p class="mb-1"><strong>ID:</strong> #{{ $orderEvent->id }}</p>
                <p class="mb-1"><strong>Loại sự kiện:</strong>
                    @php
                        $badgeClass = match($orderEvent->event_type) {
                            'created' => 'bg-primary',
                            'status_changed' => 'bg-warning text-dark',
                            'payment_received' => 'bg-success',
                            'shipped' => 'bg-info',
                            'delivered' => 'bg-success',
                            'refunded' => 'bg-danger',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ $orderEvent->event_type }}</span>
                </p>
                <p class="mb-1"><strong>Thời gian:</strong> {{ $orderEvent->created_at->format('d/m/Y H:i:s') }}</p>
            </div>
            <div class="col-md-6">
                <h5 class="mb-3">Người thực hiện</h5>
                <p class="mb-1">
                    @if($orderEvent->creator)
                        <strong>{{ $orderEvent->creator->name }}</strong> ({{ $orderEvent->creator->email }})
                    @else
                        <span class="text-muted">System / Automated</span>
                    @endif
                </p>
            </div>
        </div>

        <hr>

        <h5 class="mt-4 mb-3">Thay đổi trạng thái</h5>
        <div class="row">
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border">
                    <small class="text-muted d-block mb-1">Từ trạng thái</small>
                    <strong class="fs-5 text-capitalize">{{ $orderEvent->from_status ?? 'N/A' }}</strong>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border">
                    <small class="text-muted d-block mb-1">Đến trạng thái</small>
                    <strong class="fs-5 text-capitalize">{{ $orderEvent->to_status ?? 'N/A' }}</strong>
                </div>
            </div>
        </div>

        @if($orderEvent->metadata)
        <hr>
        <h5 class="mt-4 mb-3">Dữ liệu bổ sung (Metadata)</h5>
        <pre class="bg-dark text-light p-3 rounded small" style="max-height: 300px; overflow-y: auto;">{{ json_encode($orderEvent->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @endif

        @if($orderEvent->order)
        <hr>
        <a href="{{ route('admin.orders.show', $orderEvent->order) }}" class="btn btn-outline-primary">
            <i class="bi bi-box me-1"></i> Xem đơn hàng #{{ $orderEvent->order->id }}
        </a>
        @endif
    </div>
</div>
@endsection
