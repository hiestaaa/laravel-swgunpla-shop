@extends('layouts.admin')

@section('title', 'Quản lý Voucher')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Voucher</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Quản lý Mã giảm giá (Voucher)</h1>
    <a href="{{ route('admin.vouchers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Thêm mới
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-light border-bottom">
        <form action="{{ route('admin.vouchers.index') }}" method="GET" class="d-flex">
            <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Tìm mã voucher..." value="{{ request('search') }}">
            <button type="submit" class="btn btn-primary btn-sm d-flex align-items-center">
                <i class="bi bi-search me-1"></i> Tìm
            </button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light text-uppercase small">
                    <tr>
                        <th style="width: 20%;">Mã (Code)</th>
                        <th style="width: 15%;">Loại</th>
                        <th style="width: 15%;">Giá trị</th>
                        <th style="width: 15%;">Số lượng còn lại</th>
                        <th style="width: 20%;">Ngày hết hạn</th>
                        <th style="width: 15%;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $voucher)
                    <tr>
                        <td><strong>{{ $voucher->code }}</strong></td>
                        <td>{{ $voucher->type == 'fixed' ? 'Giảm cố định' : 'Giảm %' }}</td>
                        <td>
                            @if($voucher->type == 'fixed')
                                {{ number_format($voucher->value, 0, ',', '.') }} VNĐ
                            @else
                                {{ $voucher->value }}%
                            @endif
                        </td>
                        <td>{{ $voucher->quantity }}</td>
                        <td>{{ $voucher->expires_at ? $voucher->expires_at->format('d/m/Y') : 'Không hết hạn' }}</td>
                        <td>
                            <a href="{{ route('admin.vouchers.edit', $voucher) }}" class="btn btn-outline-warning btn-sm py-0 px-1" title="Sửa">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="{{ route('admin.vouchers.destroy', $voucher) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa voucher này?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-1" title="Xóa">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                     @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">Chưa có voucher nào.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
     @if ($vouchers->hasPages())
    <div class="card-footer bg-light border-top">
        {{ $vouchers->links() }}
    </div>
    @endif
</div>
@endsection