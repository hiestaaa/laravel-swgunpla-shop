@extends('layouts.app')

@section('title', 'Chi tiết địa chỉ')

@section('header')
<div class="d-flex justify-content-between align-items-center">
    <h2 class="h4 mb-0 fw-bold">Chi tiết địa chỉ</h2>
    <a href="{{ route('addresses.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Quay lại
    </a>
</div>
@endsection

@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="row mb-3">
                <div class="col-sm-3 fw-bold">Họ và tên</div>
                <div class="col-sm-9">{{ $address->full_name }}</div>
            </div>
            <hr>
            <div class="row mb-3">
                <div class="col-sm-3 fw-bold">Số điện thoại</div>
                <div class="col-sm-9">{{ $address->phone }}</div>
            </div>
            <hr>
            <div class="row mb-3">
                <div class="col-sm-3 fw-bold">Địa chỉ</div>
                <div class="col-sm-9">{{ $address->address_line }}, {{ $address->ward }}, {{ $address->district }}, {{ $address->city }}</div>
            </div>
            <hr>
            <div class="row mb-3">
                <div class="col-sm-3 fw-bold">Địa chỉ mặc định</div>
                <div class="col-sm-9">
                    @if($address->is_default)
                        <span class="badge bg-primary">Mặc định</span>
                    @else
                        <span class="badge bg-secondary">Không</span>
                    @endif
                </div>
            </div>
            <hr>
            <div class="d-flex gap-2 mt-4">
                <a href="{{ route('addresses.edit', $address) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i> Sửa
                </a>
                <form action="{{ route('addresses.destroy', $address) }}" method="POST" onsubmit="return confirm('Xóa địa chỉ này?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash3 me-1"></i> Xóa
                    </button>
                </form>
                <a href="{{ route('addresses.index') }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </div>
    </div>
@endsection
