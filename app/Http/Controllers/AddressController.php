<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $addresses = $request->user()->addresses()->latest()->get();
        return view('addresses.index', compact('addresses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('addresses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address_line' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'ward' => 'required|string|max:100',
        ]);

        $validated['user_id'] = $request->user()->id;

        // Xử lý địa chỉ mặc định
        if ($request->has('is_default')) {
            // Xóa địa chỉ mặc định cũ
            $request->user()->addresses()->update(['is_default' => false]);
            $validated['is_default'] = true;
        }

        Address::create($validated);

        return redirect()->route('addresses.index')->with('success', 'Thêm địa chỉ mới thành công.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Address $address)
    {
        Gate::authorize('view', $address);
        return view('addresses.show', compact('address'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Address $address)
    {
        Gate::authorize('update', $address); 
        return view('addresses.edit', compact('address'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Address $address)
    {
        Gate::authorize('update', $address);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address_line' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'ward' => 'required|string|max:100',
        ]);

        if ($request->has('is_default')) {
            $request->user()->addresses()->update(['is_default' => false]);
            $validated['is_default'] = true;
        } else {
            $validated['is_default'] = false;
        }

        $address->update($validated);
        
        return redirect()->route('addresses.index')->with('success', 'Cập nhật địa chỉ thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Address $address)
    {
        Gate::authorize('delete', $address);
        
        $address->delete();
        return back()->with('success', 'Xóa địa chỉ thành công.');
    }
}
