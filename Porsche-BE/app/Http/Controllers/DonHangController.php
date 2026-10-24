<?php

namespace App\Http\Controllers;

use App\Models\donHangModel;
use App\Services\VnpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DonHangController extends Controller
{
    public function datMuaXe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'san_pham_id' => 'required|integer|exists:san_phams,id',
            'phuong_thuc_tt' => 'required|string|max:50',
            'dia_chi_giao_hang_id' => 'nullable|integer',
            'so_luong' => 'nullable|integer|min:1',
            'ma_voucher' => 'nullable|string|max:100',
        ]);

        try {
            $result = DB::transaction(function () use ($request, $data) {
                $userId = (int) $request->user()->id;
                $quantity = (int) ($data['so_luong'] ?? 1);
                $product = DB::table('san_phams')
                    ->where('id', $data['san_pham_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$product || (int) $product->so_luong < $quantity) {
                    return ['error' => 'Số lượng sản phẩm trong kho không đủ.', 'code' => 422];
                }

                $sale = DB::table('chuong_trinh_sales as campaigns')
                    ->join('chi_tiet_san_pham_sales as details', 'campaigns.id', '=', 'details.chuong_trinh_sale_id')
                    ->where('details.san_pham_id', $product->id)
                    ->where('campaigns.trang_thai', true)
                    ->where('campaigns.ngay_bat_dau', '<=', now())
                    ->where('campaigns.ngay_ket_thuc', '>=', now())
                    ->whereRaw('(details.so_luong_sale - details.da_ban) >= ?', [$quantity])
                    ->select('details.id', 'details.gia_khuyen_mai', 'details.gioi_han_moi_khach')
                    ->lockForUpdate()
                    ->first();

                if ($sale) {
                    $priorSaleQuantity = DB::table('chi_tiet_don_hangs as details')
                        ->join('don_hangs as orders', 'orders.id', '=', 'details.don_hang_id')
                        ->where('details.chi_tiet_sale_id', $sale->id)
                        ->where('orders.users_id', $userId)
                        ->whereNotIn('orders.trang_thai', [
                            donHangModel::TRANG_THAI_DA_HUY_HOAN_KHO,
                        ])
                        ->sum('details.so_luong');

                    if ($priorSaleQuantity + $quantity > $sale->gioi_han_moi_khach) {
                        $sale = null;
                    }
                }

                $unitPrice = (float) ($sale->gia_khuyen_mai ?? $product->gia);
                $subtotal = $unitPrice * $quantity;
                $voucher = null;
                $discount = 0;

                if (!empty($data['ma_voucher'])) {
                    [$voucher, $discount, $error] = $this->calculateVoucherDiscount(
                        $data['ma_voucher'],
                        $subtotal,
                        true,
                        $userId
                    );
                    if ($error !== null) {
                        return ['error' => $error, 'code' => 422];
                    }
                }

                $total = max(0, $subtotal - $discount);
                $orderId = DB::table('don_hangs')->insertGetId([
                    'users_id' => $userId,
                    'tong_tien' => $subtotal,
                    'giam_gia' => $discount,
                    'thanh_tien' => $total,
                    'trang_thai' => donHangModel::TRANG_THAI_CHO_XAC_NHAN,
                    'phuong_thuc_tt' => $data['phuong_thuc_tt'],
                    'dia_chi_giao_hang_id' => $data['dia_chi_giao_hang_id'] ?? null,
                    'ngay_tao' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('chi_tiet_don_hangs')->insert([
                    'don_hang_id' => $orderId,
                    'san_pham_id' => $product->id,
                    'chi_tiet_sale_id' => $sale->id ?? null,
                    'so_luong' => $quantity,
                    'don_gia' => $unitPrice,
                    'thanh_tien' => $total,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('san_phams')->where('id', $product->id)->update([
                    'so_luong' => DB::raw('so_luong - ' . $quantity),
                    'so_luong_kho' => DB::raw('so_luong_kho - ' . $quantity),
                    'updated_at' => now(),
                ]);

                if ($sale) {
                    DB::table('chi_tiet_san_pham_sales')->where('id', $sale->id)->increment('da_ban', $quantity);
                }

                if ($voucher) {
                    DB::table('lich_su_su_dung_phieus')->insert([
                        'user_id' => $userId,
                        'phieu_id' => $voucher->id,
                        'don_hang_id' => $orderId,
                        'so_tien_giam' => $discount,
                        'trang_thai' => 'da_su_dung',
                        'thoi_diem_su_dung' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('nhat_ky_khos')->insert([
                    'user_id' => $userId,
                    'san_pham_id' => $product->id,
                    'loai' => 'xuat',
                    'so_luong' => $quantity,
                    'ly_do' => 'Đặt hàng #' . $orderId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return ['don_hang_id' => $orderId, 'thanh_tien' => $total];
            });

            if (isset($result['error'])) {
                return response()->json(['status' => false, 'message' => $result['error']], $result['code']);
            }

            return response()->json([
                'status' => true,
                'message' => 'Đặt mua xe thành công. Đơn hàng đang chờ xác nhận.',
                'data' => $result,
            ], 201);
        } catch (Throwable $e) {
            Log::error('Không thể tạo đơn hàng.', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Không thể đặt mua sản phẩm.'], 500);
        }
    }

    public function datDonHang(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => 'required|array|min:1|max:50',
            'items.*.type' => 'required|string|in:goi_do,phu_tung',
            'items.*.id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'ten_nguoi_nhan' => 'required|string|max:255',
            'email_nguoi_nhan' => 'required|email|max:255',
            'so_dien_thoai_nguoi_nhan' => 'required|string|max:30',
            'phuong_thuc_nhan' => 'required|string|in:nhan_tai_showroom,giao_tan_noi',
            'dia_chi_nguoi_nhan' => 'required_if:phuong_thuc_nhan,giao_tan_noi|nullable|string|max:1000',
            'ghi_chu_don_hang' => 'nullable|string|max:1000',
            'phuong_thuc_tt' => 'required|string|in:the,tien_mat_khi_nhan_hang',
            'ma_voucher' => 'nullable|string|max:100',
        ]);

        if ($data['phuong_thuc_tt'] === 'the' && !app(VnpayService::class)->isConfigured()) {
            return response()->json([
                'status' => false,
                'message' => 'Thanh toán thẻ chưa được cấu hình đầy đủ. Vui lòng chọn tiền mặt hoặc liên hệ showroom.',
            ], 503);
        }

        try {
            $result = DB::transaction(function () use ($request, $data) {
                $userId = (int) $request->user()->id;
                $items = collect($data['items'])
                    ->groupBy(fn ($item) => $item['type'] . ':' . $item['id'])
                    ->map(fn ($group) => [
                        'type' => $group->first()['type'],
                        'id' => (int) $group->first()['id'],
                        'quantity' => $group->sum('quantity'),
                    ])
                    ->values();
                $orderItems = [];
                $subtotal = 0;

                foreach ($items as $item) {
                    $table = $item['type'] === 'phu_tung' ? 'phu_tungs' : 'san_phams';
                    $product = DB::table($table)
                        ->where('id', $item['id'])
                        ->lockForUpdate()
                        ->first();

                    if (!$product) {
                        return ['error' => 'Một sản phẩm trong giỏ hàng không còn tồn tại.', 'code' => 422];
                    }

                    if ($item['type'] === 'goi_do'
                        && in_array(strtolower((string) ($product->loai_san_pham ?? '')), ['xe', 'car', 'vehicle', 'xe_nguyen_chiec'], true)) {
                        return ['error' => 'Xe không thể đặt qua giỏ phụ kiện.', 'code' => 422];
                    }

                    $stock = (int) $product->so_luong;
                    if ($item['type'] === 'goi_do'
                        && Schema::hasColumn('san_phams', 'so_luong_kho')
                        && $product->so_luong_kho !== null) {
                        $stock = min($stock, (int) $product->so_luong_kho);
                    }

                    if ($stock < $item['quantity']) {
                        return [
                            'error' => 'Số lượng sản phẩm "' . $product->ten . '" trong kho không đủ.',
                            'code' => 422,
                        ];
                    }

                    $unitPrice = (float) $product->gia;
                    if ($unitPrice < 0) {
                        return ['error' => 'Giá sản phẩm không hợp lệ.', 'code' => 422];
                    }

                    $lineTotal = $unitPrice * $item['quantity'];
                    $subtotal += $lineTotal;
                    $orderItems[] = [
                        ...$item,
                        'product' => $product,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ];
                }

                $voucher = null;
                $discount = 0;
                if (!empty($data['ma_voucher'])) {
                    [$voucher, $discount, $error] = $this->calculateVoucherDiscount(
                        $data['ma_voucher'],
                        $subtotal,
                        true,
                        $userId
                    );
                    if ($error !== null) {
                        return ['error' => $error, 'code' => 422];
                    }
                }

                $shippingFee = $data['phuong_thuc_nhan'] === 'nhan_tai_showroom' ? 0 : null;
                $total = max(0, $subtotal - $discount) + ($shippingFee ?? 0);
                $orderId = DB::table('don_hangs')->insertGetId([
                    'users_id' => $userId,
                    'tong_tien' => $subtotal,
                    'giam_gia' => $discount,
                    'thanh_tien' => $total,
                    'trang_thai' => donHangModel::TRANG_THAI_CHO_XAC_NHAN,
                    'trang_thai_thanh_toan' => $data['phuong_thuc_tt'] === 'the'
                        ? 'cho_thanh_toan'
                        : 'thanh_toan_khi_nhan_hang',
                    'phuong_thuc_tt' => $data['phuong_thuc_tt'],
                    'phuong_thuc_nhan' => $data['phuong_thuc_nhan'],
                    'phi_van_chuyen' => $shippingFee,
                    'ten_nguoi_nhan' => $data['ten_nguoi_nhan'],
                    'email_nguoi_nhan' => $data['email_nguoi_nhan'],
                    'so_dien_thoai_nguoi_nhan' => $data['so_dien_thoai_nguoi_nhan'],
                    'dia_chi_nguoi_nhan' => $data['dia_chi_nguoi_nhan'] ?? null,
                    'ghi_chu_don_hang' => $data['ghi_chu_don_hang'] ?? null,
                    'ngay_tao' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($orderItems as $item) {
                    $isAccessory = $item['type'] === 'phu_tung';
                    DB::table('chi_tiet_don_hangs')->insert([
                        'don_hang_id' => $orderId,
                        'san_pham_id' => $isAccessory ? null : $item['id'],
                        'phu_tung_id' => $isAccessory ? $item['id'] : null,
                        'chi_tiet_sale_id' => null,
                        'so_luong' => $item['quantity'],
                        'don_gia' => $item['unit_price'],
                        'thanh_tien' => $item['line_total'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $stockTable = $isAccessory ? 'phu_tungs' : 'san_phams';
                    $stockUpdate = [
                        'so_luong' => DB::raw('so_luong - ' . $item['quantity']),
                        'updated_at' => now(),
                    ];
                    if (!$isAccessory && Schema::hasColumn('san_phams', 'so_luong_kho')) {
                        $stockUpdate['so_luong_kho'] = DB::raw('so_luong_kho - ' . $item['quantity']);
                    }
                    DB::table($stockTable)->where('id', $item['id'])->update($stockUpdate);

                    DB::table('nhat_ky_khos')->insert([
                        'user_id' => $userId,
                        'san_pham_id' => $isAccessory ? null : $item['id'],
                        'phu_tung_id' => $isAccessory ? $item['id'] : null,
                        'loai' => 'xuat',
                        'so_luong' => $item['quantity'],
                        'ly_do' => 'Đặt hàng #' . $orderId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if ($voucher) {
                    DB::table('lich_su_su_dung_phieus')->insert([
                        'user_id' => $userId,
                        'phieu_id' => $voucher->id,
                        'don_hang_id' => $orderId,
                        'so_tien_giam' => $discount,
                        'trang_thai' => 'da_su_dung',
                        'thoi_diem_su_dung' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return [
                    'don_hang_id' => $orderId,
                    'ma_don_hang' => 'DH-' . now()->format('ymd') . '-' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT),
                    'tong_tien' => $subtotal,
                    'giam_gia' => $discount,
                    'phi_van_chuyen' => $shippingFee,
                    'thanh_tien' => $total,
                    'trang_thai_thanh_toan' => $data['phuong_thuc_tt'] === 'the'
                        ? 'cho_thanh_toan'
                        : 'thanh_toan_khi_nhan_hang',
                ];
            });

            if (isset($result['error'])) {
                return response()->json(['status' => false, 'message' => $result['error']], $result['code']);
            }

            if ($data['phuong_thuc_tt'] === 'the') {
                $orderId = (int) $result['don_hang_id'];

                try {
                    $amount = (int) round((float) $result['thanh_tien']);
                    $txnRef = 'DH' . $orderId . 'T' . now()->timestamp;

                    DB::table('vnpay_payments')->insert([
                        'don_hang_id' => $orderId,
                        'txn_ref' => $txnRef,
                        'amount' => $amount,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $result['checkout_url'] = app(VnpayService::class)
                        ->buildPaymentUrl($orderId, $txnRef, $amount, (string) $request->ip());
                } catch (Throwable $e) {
                    Log::error('Không thể khởi tạo thanh toán VNPay.', [
                        'order_id' => $orderId,
                        'error' => $e->getMessage(),
                    ]);
                    $this->cancelUnpaidCardOrder($orderId, 'failed');

                    return response()->json([
                        'status' => false,
                        'message' => 'Không thể khởi tạo thanh toán thẻ. Đơn hàng đã được hủy, vui lòng thử lại.',
                    ], 502);
                }
            }

            return response()->json([
                'status' => true,
                'message' => $data['phuong_thuc_tt'] === 'the'
                    ? 'Đang chuyển bạn đến cổng thanh toán VNPay.'
                    : 'Đã ghi nhận đơn hàng COD. Bạn thanh toán tiền mặt khi nhận hàng.',
                'data' => $result,
            ], 201);
        } catch (Throwable $e) {
            Log::error('Không thể tạo đơn hàng từ giỏ hàng.', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['status' => false, 'message' => 'Không thể tạo đơn hàng. Vui lòng thử lại.'], 500);
        }
    }

    public function khachYeuCauHuy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'don_hang_id' => 'required|integer|exists:don_hangs,id',
            'ly_do_huy' => 'required|string|max:500',
        ]);

        try {
            $result = DB::transaction(function () use ($request, $data) {
                $order = DB::table('don_hangs')
                    ->where('id', $data['don_hang_id'])
                    ->where('users_id', $request->user()->id)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    return ['error' => 'Không tìm thấy đơn hàng.', 'code' => 404];
                }
                if ($order->trang_thai_thanh_toan === 'cho_thanh_toan') {
                    return ['error' => 'Đơn hàng đang chờ thanh toán thẻ. Vui lòng hoàn tất thanh toán hoặc chờ phiên thanh toán hết hạn.', 'code' => 409];
                }
                if (in_array($order->trang_thai, [
                    donHangModel::TRANG_THAI_HOAN_THANH,
                    donHangModel::TRANG_THAI_DA_HUY_HOAN_KHO,
                    donHangModel::TRANG_THAI_YEU_CAU_HUY,
                ], true)) {
                    return ['error' => 'Đơn hàng đã hoàn tất, đã hủy hoặc đang chờ duyệt hủy.', 'code' => 409];
                }

                $cancelImmediately = $order->trang_thai === donHangModel::TRANG_THAI_CHO_XAC_NHAN;
                DB::table('don_hangs')->where('id', $order->id)->update([
                    'trang_thai' => $cancelImmediately
                        ? donHangModel::TRANG_THAI_DA_HUY_HOAN_KHO
                        : donHangModel::TRANG_THAI_YEU_CAU_HUY,
                    'ly_do_huy' => $data['ly_do_huy'],
                    'updated_at' => now(),
                ]);

                if ($cancelImmediately) {
                    $this->restoreOrderStock($order->id);
                }

                return ['cancelled' => $cancelImmediately];
            });

            if (isset($result['error'])) {
                return response()->json(['status' => false, 'message' => $result['error']], $result['code']);
            }

            return response()->json([
                'status' => true,
                'message' => $result['cancelled']
                    ? 'Đơn hàng đã hủy và tồn kho đã được hoàn lại.'
                    : 'Yêu cầu hủy đã được gửi để phê duyệt.',
            ]);
        } catch (Throwable $e) {
            Log::error('Không thể xử lý yêu cầu hủy đơn hàng.', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Không thể xử lý yêu cầu hủy.'], 500);
        }
    }

    public function capNhatTrangThai(Request $request): JsonResponse
    {
        $data = $request->validate([
            'don_hang_id' => 'required|integer|exists:don_hangs,id',
            'trang_thai' => 'required|in:cho_xac_nhan,da_xac_nhan,dang_giao,hoan_thanh',
        ]);

        try {
            $result = DB::transaction(function () use ($data) {
                $order = DB::table('don_hangs')->where('id', $data['don_hang_id'])->lockForUpdate()->first();
                if (in_array($order->trang_thai, [
                    donHangModel::TRANG_THAI_HOAN_THANH,
                    donHangModel::TRANG_THAI_DA_HUY_HOAN_KHO,
                    donHangModel::TRANG_THAI_YEU_CAU_HUY,
                ], true)) {
                    if ($order->trang_thai === donHangModel::TRANG_THAI_HOAN_THANH
                        && $data['trang_thai'] === donHangModel::TRANG_THAI_HOAN_THANH
                        && $order->phuong_thuc_tt === 'tien_mat_khi_nhan_hang'
                        && $order->trang_thai_thanh_toan === 'da_thanh_toan'
                        && !$order->thanh_toan_email_sent_at) {
                        return ['cash_receipt_order_id' => (int) $order->id];
                    }

                    return ['error' => 'Đơn hàng đã kết thúc hoặc đang chờ duyệt hủy.', 'code' => 409];
                }

                $paymentStatus = $order->trang_thai_thanh_toan;
                if ($data['trang_thai'] === donHangModel::TRANG_THAI_HOAN_THANH) {
                    if ($order->phuong_thuc_tt === 'the' && $paymentStatus !== 'da_thanh_toan') {
                        return ['error' => 'Không thể hoàn tất đơn thẻ khi VNPay chưa xác nhận thanh toán.', 'code' => 409];
                    }
                    if ($order->phuong_thuc_tt === 'tien_mat_khi_nhan_hang') {
                        $paymentStatus = 'da_thanh_toan';
                    }
                }

                if ($data['trang_thai'] === donHangModel::TRANG_THAI_HOAN_THANH
                    && !DB::table('hoa_dons')->where('don_hang_id', $order->id)->exists()) {
                    DB::table('hoa_dons')->insert([
                        'ma_hoa_don' => 'HD-' . now()->format('YmdHis') . '-' . $order->id,
                        'don_hang_id' => $order->id,
                        'users_id' => $order->users_id,
                        'tong_tien' => $order->thanh_tien,
                        'trang_thai_tt' => $paymentStatus,
                        'ngay_xuat' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('don_hangs')->where('id', $order->id)->update([
                    'trang_thai' => $data['trang_thai'],
                    'trang_thai_thanh_toan' => $paymentStatus,
                    'updated_at' => now(),
                ]);

                return [
                    'ok' => true,
                    'cash_receipt_order_id' => $paymentStatus === 'da_thanh_toan'
                        && $order->phuong_thuc_tt === 'tien_mat_khi_nhan_hang'
                        ? (int) $order->id
                        : null,
                ];
            });

            if (isset($result['error'])) {
                return response()->json(['status' => false, 'message' => $result['error']], $result['code']);
            }

            if (!empty($result['cash_receipt_order_id'])) {
                $this->sendCashPaymentReceipt((int) $result['cash_receipt_order_id']);
            }

            return response()->json(['status' => true, 'message' => 'Cập nhật trạng thái đơn hàng thành công.']);
        } catch (Throwable $e) {
            Log::error('Không thể cập nhật trạng thái đơn hàng.', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Không thể cập nhật trạng thái đơn hàng.'], 500);
        }
    }

    public function duyetHuyVaHoanKho(Request $request): JsonResponse
    {
        $data = $request->validate([
            'don_hang_id' => 'required|integer|exists:don_hangs,id',
            'dong_y_huy' => 'required|boolean',
        ]);

        try {
            $result = DB::transaction(function () use ($data) {
                $order = DB::table('don_hangs')->where('id', $data['don_hang_id'])->lockForUpdate()->first();
                if ($order->trang_thai !== donHangModel::TRANG_THAI_YEU_CAU_HUY) {
                    return ['error' => 'Đơn hàng không ở trạng thái chờ duyệt hủy.', 'code' => 409];
                }

                if ($data['dong_y_huy']) {
                    DB::table('don_hangs')->where('id', $order->id)->update([
                        'trang_thai' => donHangModel::TRANG_THAI_DA_HUY_HOAN_KHO,
                        'updated_at' => now(),
                    ]);
                    $this->restoreOrderStock($order->id);
                } else {
                    DB::table('don_hangs')->where('id', $order->id)->update([
                        'trang_thai' => donHangModel::TRANG_THAI_DANG_GIAO,
                        'updated_at' => now(),
                    ]);
                }

                return ['approved' => (bool) $data['dong_y_huy']];
            });

            if (isset($result['error'])) {
                return response()->json(['status' => false, 'message' => $result['error']], $result['code']);
            }

            return response()->json([
                'status' => true,
                'message' => $result['approved']
                    ? 'Đã duyệt hủy đơn và hoàn tồn kho.'
                    : 'Đã từ chối yêu cầu hủy; đơn hàng tiếp tục được giao.',
            ]);
        } catch (Throwable $e) {
            Log::error('Không thể phê duyệt hủy đơn hàng.', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Không thể xử lý phê duyệt hủy.'], 500);
        }
    }

    public function donHangCuaToi(Request $request): JsonResponse
    {
        $imageIds = DB::table('anh_san_phams')
            ->select('san_pham_id', DB::raw('MIN(id) as image_id'))
            ->groupBy('san_pham_id');

        $orders = DB::table('don_hangs')
            ->where('users_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        foreach ($orders as $order) {
            $order->chi_tiet = DB::table('chi_tiet_don_hangs as details')
                ->leftJoin('san_phams as products', 'details.san_pham_id', '=', 'products.id')
                ->leftJoin('phu_tungs as accessories', 'details.phu_tung_id', '=', 'accessories.id')
                ->leftJoinSub($imageIds, 'product_images', 'product_images.san_pham_id', '=', 'products.id')
                ->leftJoin('anh_san_phams as images', 'images.id', '=', 'product_images.image_id')
                ->where('details.don_hang_id', $order->id)
                ->select(
                    'details.*',
                    DB::raw('COALESCE(products.ten, accessories.ten) as ten_san_pham'),
                    DB::raw('COALESCE(images.duong_dan, accessories.anh) as hinh_anh')
                )
                ->get();
        }

        return response()->json(['status' => true, 'data' => $orders]);
    }

    public function apMaGiamGia(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ma_voucher' => 'required|string|max:100',
            'tong_tien' => 'required|numeric|min:0',
        ]);

        [$voucher, $discount, $error] = $this->calculateVoucherDiscount(
            $data['ma_voucher'],
            (float) $data['tong_tien'],
            false,
            (int) $request->user()->id
        );
        if ($error !== null) {
            return response()->json(['status' => false, 'message' => $error], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Áp dụng mã giảm giá thành công.',
            'data' => [
                'ma_voucher' => $voucher->code,
                'so_tien_giam' => $discount,
                'tong_tien_ban_dau' => (float) $data['tong_tien'],
                'tong_tien_sau_giam' => max(0, (float) $data['tong_tien'] - $discount),
            ],
        ]);
    }

    private function calculateVoucherDiscount(string $code, float $subtotal, bool $lock = false, ?int $userId = null): array
    {
        $query = DB::table('phieu_giam_gias')
            ->where('code', strtoupper(trim($code)))
            ->where('trang_thai', true);
        if ($lock) {
            $query->lockForUpdate();
        }
        $voucher = $query->first();

        if (!$voucher) {
            return [null, 0, 'Mã giảm giá không tồn tại hoặc đã bị khóa.'];
        }

        if (!DB::table('user_vouchers')
            ->where('user_id', $userId)
            ->where('phieu_id', $voucher->id)
            ->exists()) {
            return [null, 0, 'Mã giảm giá này chưa được cấp cho tài khoản của bạn.'];
        }

        $now = now();
        if (($voucher->ngay_bat_dau && $now->lt($voucher->ngay_bat_dau))
            || ($voucher->ngay_ket_thuc && $now->gt($voucher->ngay_ket_thuc))) {
            return [null, 0, 'Mã giảm giá chưa có hiệu lực hoặc đã hết hạn.'];
        }

        if ($subtotal < (float) $voucher->gia_tri_don_hang_toi_thieu) {
            return [null, 0, 'Đơn hàng chưa đạt giá trị tối thiểu để sử dụng mã giảm giá.'];
        }

        $used = DB::table('lich_su_su_dung_phieus')
            ->where('phieu_id', $voucher->id)
            ->where('trang_thai', 'da_su_dung')
            ->count();
        if ((int) $voucher->so_luong <= $used) {
            return [null, 0, 'Mã giảm giá đã hết lượt sử dụng.'];
        }

        $discount = $voucher->loai === 'phan_tram'
            ? $subtotal * (float) $voucher->gia_tri / 100
            : (float) $voucher->gia_tri;

        if ($voucher->giam_toi_da !== null && (float) $voucher->giam_toi_da > 0) {
            $discount = min($discount, (float) $voucher->giam_toi_da);
        }

        return [$voucher, min($subtotal, max(0, $discount)), null];
    }

    private function restoreOrderStock(int $orderId): void
    {
        $details = DB::table('chi_tiet_don_hangs')->where('don_hang_id', $orderId)->get();

        foreach ($details as $detail) {
            if ($detail->phu_tung_id) {
                DB::table('phu_tungs')->where('id', $detail->phu_tung_id)->update([
                    'so_luong' => DB::raw('so_luong + ' . (int) $detail->so_luong),
                    'updated_at' => now(),
                ]);
            } else {
                $stockUpdate = [
                    'so_luong' => DB::raw('so_luong + ' . (int) $detail->so_luong),
                    'updated_at' => now(),
                ];
                if (Schema::hasColumn('san_phams', 'so_luong_kho')) {
                    $stockUpdate['so_luong_kho'] = DB::raw('so_luong_kho + ' . (int) $detail->so_luong);
                }
                DB::table('san_phams')->where('id', $detail->san_pham_id)->update($stockUpdate);
            }

            if ($detail->chi_tiet_sale_id) {
                DB::table('chi_tiet_san_pham_sales')
                    ->where('id', $detail->chi_tiet_sale_id)
                    ->where('da_ban', '>=', $detail->so_luong)
                    ->decrement('da_ban', $detail->so_luong);
            }

            DB::table('nhat_ky_khos')->insert([
                'user_id' => null,
                'san_pham_id' => $detail->san_pham_id,
                'phu_tung_id' => $detail->phu_tung_id,
                'loai' => 'nhap',
                'so_luong' => $detail->so_luong,
                'ly_do' => 'Hoàn kho đơn #' . $orderId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('lich_su_su_dung_phieus')
            ->where('don_hang_id', $orderId)
            ->where('trang_thai', 'da_su_dung')
            ->update(['trang_thai' => 'huy', 'updated_at' => now()]);
    }

    public function cancelUnpaidCardOrder(int $orderId, string $paymentStatus): void
    {
        DB::transaction(function () use ($orderId, $paymentStatus) {
            $order = DB::table('don_hangs')->where('id', $orderId)->lockForUpdate()->first();
            if (!$order || $order->trang_thai_thanh_toan !== 'cho_thanh_toan') {
                return;
            }

            DB::table('don_hangs')->where('id', $orderId)->update([
                'trang_thai' => donHangModel::TRANG_THAI_DA_HUY_HOAN_KHO,
                'trang_thai_thanh_toan' => 'thanh_toan_that_bai',
                'updated_at' => now(),
            ]);
            $this->restoreOrderStock($orderId);

            DB::table('vnpay_payments')
                ->where('don_hang_id', $orderId)
                ->where('status', 'pending')
                ->update(['status' => $paymentStatus, 'updated_at' => now()]);
        });
    }

    private function sendCashPaymentReceipt(int $orderId): void
    {
        $order = DB::table('don_hangs')->where('id', $orderId)->first();
        if (!$order || $order->thanh_toan_email_sent_at || $order->trang_thai_thanh_toan !== 'da_thanh_toan') {
            return;
        }

        Mail::to($order->email_nguoi_nhan)->send(new \App\Mail\PaymentReceiptMail(
            $order->ten_nguoi_nhan,
            (int) $order->id,
            (float) $order->thanh_tien,
            'COD-' . $order->id
        ));

        DB::table('don_hangs')->where('id', $orderId)->update([
            'thanh_toan_email_sent_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
