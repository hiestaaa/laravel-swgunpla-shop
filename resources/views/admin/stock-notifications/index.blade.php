@extends('layouts.admin')

@section('title', 'Thông báo tồn kho')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Thông báo tồn kho</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Thông báo khi có hàng</h1>
    <span class="badge bg-warning text-dark fs-6">
        {{ $notifications->where('status', 'pending')->count() }} đang chờ
    </span>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-light border-bottom">
        <form action="{{ route('admin.stock-notifications.index') }}" method="GET" class="row g-2">
            <div class="col-md-4">
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">-- Lọc theo sản phẩm --</option>
                    @foreach($outOfStockProducts as $p)
                        <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Trạng thái --</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                    <option value="notified" {{ request('status') == 'notified' ? 'selected' : '' }}>Đã thông báo</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search me-1"></i> Lọc
                </button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.stock-notifications.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="bi bi-x-circle me-1"></i> Xóa lọc
                </a>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light text-uppercase small">
                    <tr>
                        <th style="width: 25%;">Sản phẩm</th>
                        <th style="width: 20%;">Người dùng / Email</th>
                        <th style="width: 12%;">Trạng thái</th>
                        <th style="width: 18%;">Ngày tạo</th>
                        <th style="width: 15%;">Ngày thông báo</th>
                        <th style="width: 10%;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $notification)
                    <tr>
                        <td>
                            <strong>{{ $notification->product->name ?? 'Sản phẩm đã xóa' }}</strong>
                            @if($notification->product && $notification->product->stock > 0)
                                <span class="badge bg-success ms-1">Đã có hàng</span>
                            @endif
                        </td>
                        <td>
                            @if($notification->user)
                                <i class="bi bi-person me-1"></i>{{ $notification->user->name }}
                            @elseif($notification->email)
                                <i class="bi bi-envelope me-1"></i>{{ $notification->email }}
                            @else
                                <span class="text-muted">Không rõ</span>
                            @endif
                        </td>
                        <td>
                            @switch($notification->status)
                                @case('pending')
                                    <span class="badge bg-warning text-dark">Chờ xử lý</span>
                                    @break
                                @case('notified')
                                    <span class="badge bg-success">Đã thông báo</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-danger">Đã hủy</span>
                                    @break
                            @endswitch
                        </td>
                        <td>{{ $notification->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $notification->notified_at ? $notification->notified_at->format('d/m/Y H:i') : '-' }}</td>
                        <td>
                            @if($notification->status === 'pending')
                                <form action="{{ route('admin.stock-notifications.notify', $notification) }}" method="POST" class="d-inline" onsubmit="return confirm('Đánh dấu đã thông báo cho người dùng này?')">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success btn-sm py-0 px-1" title="Đánh dấu đã thông báo">
                                        <i class="bi bi-bell"></i>
                                    </button>
                                </form>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">Chưa có thông báo nào.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($notifications->hasPages())
    <div class="card-footer bg-light border-top">
        {{ $notifications->links() }}
    </div>
    @endif
</div>
@endsection
