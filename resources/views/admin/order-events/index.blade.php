@extends('layouts.admin')

@section('title', 'Lịch sử trạng thái đơn hàng')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.orders.index') }}">Đơn hàng</a></li>
    <li class="breadcrumb-item active" aria-current="page">Lịch sử trạng thái</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Lịch sử trạng thái đơn hàng</h1>
    <a href="{{ route('admin.order-events.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Tất cả sự kiện
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-light border-bottom">
        <form action="{{ route('admin.order-events.index') }}" method="GET" class="d-flex">
            <input type="text" name="order_id" class="form-control form-control-sm me-2" placeholder="Lọc theo Order ID..." value="{{ request('order_id') }}">
            <button type="submit" class="btn btn-primary btn-sm d-flex align-items-center">
                <i class="bi bi-search me-1"></i> Lọc
            </button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light text-uppercase small">
                    <tr>
                        <th style="width: 8%;">ID</th>
                        <th style="width: 12%;">Order #</th>
                        <th style="width: 15%;">Loại sự kiện</th>
                        <th style="width: 12%;">Từ trạng thái</th>
                        <th style="width: 12%;">Đến trạng thái</th>
                        <th style="width: 15%;">Người tạo</th>
                        <th style="width: 15%;">Thời gian</th>
                        <th style="width: 11%;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                    <tr>
                        <td>#{{ $event->id }}</td>
                        <td>
                            @if($event->order)
                                <a href="{{ route('admin.orders.show', $event->order) }}">#{{ $event->order->id }}</a>
                            @else
                                <span class="text-muted">Đã xóa</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $badgeClass = match($event->event_type) {
                                    'created' => 'bg-primary',
                                    'status_changed' => 'bg-warning text-dark',
                                    'payment_received' => 'bg-success',
                                    'shipped' => 'bg-info',
                                    'delivered' => 'bg-success',
                                    'refunded' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $event->event_type }}</span>
                        </td>
                        <td>{{ $event->from_status ? ucfirst($event->from_status) : '-' }}</td>
                        <td>{{ $event->to_status ? ucfirst($event->to_status) : '-' }}</td>
                        <td>{{ $event->creator->name ?? 'System' }}</td>
                        <td>{{ $event->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.order-events.show', $event) }}" class="btn btn-outline-primary btn-sm py-0 px-1" title="Chi tiết">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">Chưa có sự kiện nào.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($events->hasPages())
    <div class="card-footer bg-light border-top">
        {{ $events->links() }}
    </div>
    @endif
</div>
@endsection
