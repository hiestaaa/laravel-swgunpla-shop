<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class VNPayController extends Controller
{
    /**
     * TẠO ĐƠN HÀNG VÀ CHUYỂN HƯỚNG ĐẾN VNPAY
     */
    public function createPayment(Request $request)
    {
        // 1. TẠO ĐƠN HÀNG (PENDING)
        $cartItems = session()->get('cart', []);
        if (empty($cartItems)) return redirect()->back()->with('error', 'Giỏ hàng rỗng');

        $request->validate([
            'address_id' => ['required', Rule::exists('addresses', 'id')->where('user_id', $request->user()->id)]
        ]);

        $total = 0;
        foreach ($cartItems as $item) $total += $item['price'] * $item['quantity'];

        // Áp dụng voucher nếu có
        $voucherSession = session()->get('voucher');
        $discount = 0;
        $voucherId = null;

        if ($voucherSession) {
            $voucher = Voucher::find($voucherSession['id']);
            if ($voucher && $voucher->quantity > 0 && (!$voucher->expires_at || $voucher->expires_at >= Carbon::now())) {
                if ($voucherSession['type'] == 'fixed') {
                    $discount = $voucherSession['value'];
                } elseif ($voucherSession['type'] == 'percent') {
                    $discount = ($total * $voucherSession['value']) / 100;
                }
                if ($discount > $total) $discount = $total;
                $voucherId = $voucher->id;
            }
        }
        $finalTotal = $total - $discount;

        // Tạo Order trong DB
        DB::beginTransaction();

        try {
            $order = Order::create([
                'user_id' => $request->user()->id,
                'address_id' => $request->address_id,
                'total_amount' => $finalTotal,
                'status' => 'pending', // QUAN TRỌNG: Trạng thái là PENDING
                'payment_method' => 'vnpay',
                'voucher_id' => $voucherId,
            ]);

            // Lưu chi tiết đơn hàng
            foreach ($cartItems as $id => $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            // Trừ số lượng voucher
            if ($voucherId) {
                Voucher::find($voucherId)->decrement('quantity');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Đã xảy ra lỗi khi tạo đơn hàng: ' . $e->getMessage());
        }

        // 2. CẤU HÌNH VNPAY URL (PHẦN QUAN TRỌNG ĐỂ CHUYỂN HƯỚNG)
        $vnp_Url = env('VNP_URL');
        $vnp_Returnurl = route('vnpay.return');
        $vnp_TmnCode = env('VNP_TMN_CODE');
        $vnp_HashSecret = env('VNP_HASH_SECRET');

        $vnp_TxnRef = $order->id; 
        $vnp_OrderInfo = "Thanh toan don hang #" . $order->id;
        $vnp_OrderType = "billpayment";
        $vnp_Amount = $finalTotal * 100; // VNPay yêu cầu nhân 100
        $vnp_Locale = "vn";
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        $vnp_CreateDate = date('YmdHis');

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => $vnp_CreateDate,
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
        );

        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }

        // Xóa giỏ hàng trước khi đi
        session()->forget('cart');
        session()->forget('voucher');

        // QUAN TRỌNG: CHUYỂN HƯỚNG NGƯỜI DÙNG ĐẾN VNPAY
        return redirect($vnp_Url);
    }

    /**
     * XỬ LÝ KẾT QUẢ TRẢ VỀ (RETURN URL)
     */
    public function vnpayReturn(Request $request)
    {
        $vnp_HashSecret = env('VNP_HASH_SECRET');
        $inputData = array();
        
        // Lấy tất cả tham số vnp_ trả về
        foreach ($request->all() as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }
        
        $vnp_SecureHash = $inputData['vnp_SecureHash'];
        unset($inputData['vnp_SecureHash']);
        
        ksort($inputData);
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        
        // Kiểm tra chữ ký
        if ($secureHash == $vnp_SecureHash) {
            // Chữ ký hợp lệ
            $orderId = $inputData['vnp_TxnRef'];
            $order = Order::find($orderId);

            // Kiểm tra quyền sở hữu đơn hàng
            if ($order && $request->user() && $order->user_id !== $request->user()->id) {
                return redirect()->route('cart.index')->with('error', 'Bạn không có quyền truy cập đơn hàng này.');
            }

            if ($request->vnp_ResponseCode == '00') {
                // Giao dịch thành công
                if ($order && $order->status == 'pending') {
                    $order->update(['status' => 'processing']); // Đã thanh toán
                }
                return redirect()->route('checkout.success')->with('success', 'Thanh toán VNPay thành công!');
            } else {
                // Giao dịch thất bại / Hủy
                if ($order && $order->status == 'pending') {
                    $order->update(['status' => 'cancelled']);
                }
                return redirect()->route('cart.index')->with('error', 'Giao dịch VNPay thất bại hoặc đã bị hủy.');
            }
        } else {
            return redirect()->route('cart.index')->with('error', 'Chữ ký không hợp lệ (Sai Checksum).');
        }
    }
}
