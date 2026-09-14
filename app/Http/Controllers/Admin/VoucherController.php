<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Voucher::query();

        // Xử lý tìm kiếm theo mã voucher
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('code', 'LIKE', "%{$search}%");
        }

        $vouchers = $query->latest()->paginate(15);
        return view('admin.vouchers.index', compact('vouchers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.vouchers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation dữ liệu
        $validated = $request->validate([
            'code' => 'required|string|unique:vouchers|max:50',
            'type' => 'required|in:fixed,percent',
            'value' => [
                'required',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('type') === 'percent' && ($value < 1 || $value > 100)) {
                        $fail('Voucher phần trăm phải có giá trị từ 1 đến 100.');
                    }
                },
            ],
            'quantity' => 'required|integer|min:0',
            'expires_at' => 'nullable|date',
        ]);

        Voucher::create($validated);

        return redirect()->route('admin.vouchers.index')->with('success', 'Tạo voucher thành công.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Voucher $voucher)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Voucher $voucher)
    {
        return view('admin.vouchers.edit', compact('voucher'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Voucher $voucher)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code,' . $voucher->id,
            'type' => 'required|in:fixed,percent',
            'value' => [
                'required',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('type') === 'percent' && ($value < 1 || $value > 100)) {
                        $fail('Voucher phần trăm phải có giá trị từ 1 đến 100.');
                    }
                },
            ],
            'quantity' => 'required|integer|min:0',
            'expires_at' => 'nullable|date',
        ]);

        $voucher->update($validated);

        return redirect()->route('admin.vouchers.index')->with('success', 'Cập nhật voucher thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Voucher $voucher)
    {
        $voucher->delete();
        return back()->with('success', 'Xóa voucher thành công.');
    }
}
