<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderDetailService
{
    private const PAYMENT_LABELS = [
        'the' => 'Thẻ / VNPay',
        'vnpay' => 'VNPay',
        'cod' => 'Thanh toán khi nhận hàng (COD)',
        'tien_mat_khi_nhan_hang' => 'Tiền mặt khi nhận hàng',
        'chuyen_khoan' => 'Chuyển khoản',
    ];

    private const STATUS_LABELS = [
        'cho_xac_nhan' => 'Chờ xác nhận',
        'da_xac_nhan' => 'Đã xác nhận',
        'dang_giao' => 'Đang xử lý / giao hàng',
        'hoan_thanh' => 'Hoàn thành',
        'yeu_cau_huy' => 'Yêu cầu hủy',
        'da_huy_hoan_kho' => 'Đã hủy',
    ];

    public function build(int $orderId, ?int $userId = null): ?array
    {
        $query = DB::table('don_hangs')->where('id', $orderId);
        if ($userId !== null) {
            $query->where('users_id', $userId);
        }
        $order = $query->first();
        if (!$order) {
            return null;
        }

        $buyer = DB::table('users')->where('id', $order->users_id)->first();
        $staff = $order->xac_nhan_boi ? DB::table('admins')->where('id', $order->xac_nhan_boi)->first() : null;
        $payment = DB::table('vnpay_payments')->where('don_hang_id', $order->id)->orderByDesc('id')->first();
        $invoice = DB::table('hoa_dons')->where('don_hang_id', $order->id)->first();
        $voucher = DB::table('lich_su_su_dung_phieus as h')
            ->leftJoin('phieu_giam_gias as v', 'v.id', '=', 'h.phieu_id')
            ->where('h.don_hang_id', $order->id)
            ->where('h.trang_thai', 'da_su_dung')
            ->select('v.code', 'h.so_tien_giam')
            ->first();
        $appointment = DB::table('phieu_dat_lichs as l')
            ->leftJoin('chuyen_gias as c', 'c.id', '=', 'l.id_chuyen_gia')
            ->where('l.id_don_hang', $order->id)
            ->select('l.ma_dat_lich', 'l.ngay_dat', 'l.gio_dat', 'l.trang_thai', 'c.ho_ten as chuyen_gia')
            ->first();

        $items = DB::table('chi_tiet_don_hangs as d')
            ->leftJoin('san_phams as p', 'd.san_pham_id', '=', 'p.id')
            ->leftJoin('phu_tungs as a', 'd.phu_tung_id', '=', 'a.id')
            ->leftJoin('chi_tiet_san_pham_sales as s', 's.id', '=', 'd.chi_tiet_sale_id')
            ->where('d.don_hang_id', $order->id)
            ->select('d.so_luong', 'd.don_gia', 'd.thanh_tien', 's.gia_goc', DB::raw('COALESCE(p.ten, a.ten) as ten'))
            ->get();

        $saleDiscount = 0.0;
        $lines = [];
        foreach ($items as $item) {
            $original = $item->gia_goc !== null && (float) $item->gia_goc > (float) $item->don_gia
                ? (float) $item->gia_goc
                : (float) $item->don_gia;
            $saleDiscount += ($original - (float) $item->don_gia) * (int) $item->so_luong;
            $lines[] = [
                'ten' => $item->ten ?? 'Sản phẩm',
                'so_luong' => (int) $item->so_luong,
                'gia_goc' => $original,
                'don_gia' => (float) $item->don_gia,
                'thanh_tien' => (float) $item->thanh_tien,
            ];
        }

        $pickup = $order->phuong_thuc_nhan === 'nhan_tai_showroom';
        $paid = $order->trang_thai_thanh_toan === 'da_thanh_toan';
        $paidAt = $order->thanh_toan_luc ?: ($payment->paid_at ?? null);

        return [
            'id' => (int) $order->id,
            'ma_don_hang' => 'DH-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            'ma_hoa_don' => $invoice->ma_hoa_don ?? null,
            'trang_thai' => $order->trang_thai,
            'trang_thai_text' => self::STATUS_LABELS[$order->trang_thai] ?? $order->trang_thai,
            'ly_do_huy' => $order->ly_do_huy,
            'ben_ban' => config('shop.seller'),
            'ben_mua' => [
                'ho_ten' => $order->ten_nguoi_nhan ?: ($buyer->ho_ten ?? ''),
                'email' => $order->email_nguoi_nhan ?: ($buyer->email ?? ''),
                'so_dien_thoai' => $order->so_dien_thoai_nguoi_nhan ?: ($buyer->so_dien_thoai ?? ''),
                'dia_chi' => $order->dia_chi_nguoi_nhan ?: ($buyer->dia_chi ?? ''),
            ],
            'thoi_gian' => [
                'dat_hang' => $this->time($order->ngay_tao ?: $order->created_at),
                'xac_nhan' => $this->time($order->xac_nhan_luc),
                'thanh_toan' => $paid ? $this->time($paidAt) : null,
                'giao_hang' => $this->time($order->giao_hang_luc),
                'hoan_thanh' => $this->time($order->hoan_thanh_luc),
                'xuat_hoa_don' => $this->time($invoice->ngay_xuat ?? null),
            ],
            'nhan_vien_xac_nhan' => $staff ? ['ho_ten' => $staff->ho_ten, 'email' => $staff->email] : null,
            'thanh_toan' => [
                'phuong_thuc' => $order->phuong_thuc_tt,
                'phuong_thuc_text' => self::PAYMENT_LABELS[$order->phuong_thuc_tt] ?? $order->phuong_thuc_tt,
                'trang_thai' => $paid ? 'Đã thanh toán' : 'Chưa thanh toán',
                'da_thanh_toan' => $paid,
                'ma_giao_dich' => $payment->transaction_no ?? null,
                'ngan_hang' => $payment->bank_code ?? null,
            ],
            'nhan_hang' => [
                'hinh_thuc' => $order->phuong_thuc_nhan,
                'hinh_thuc_text' => $pickup ? 'Nhận tại showroom (lắp đặt tại showroom)' : 'Giao hàng tận nơi',
                'dia_chi' => $pickup ? null : $order->dia_chi_nguoi_nhan,
                'lich_lap_dat' => $appointment ? [
                    'ma_dat_lich' => $appointment->ma_dat_lich,
                    'ngay' => $appointment->ngay_dat,
                    'gio' => $appointment->gio_dat,
                    'chuyen_gia' => $appointment->chuyen_gia,
                    'trang_thai' => $appointment->trang_thai,
                ] : null,
                'ghi_chu' => $order->ghi_chu_don_hang,
            ],
            'san_pham' => $lines,
            'tong_ket' => [
                'tam_tinh' => (float) $order->tong_tien,
                'chiet_khau_sale' => $saleDiscount,
                'ma_giam_gia' => $voucher->code ?? null,
                'giam_gia' => (float) $order->giam_gia,
                'phi_van_chuyen' => (float) $order->phi_van_chuyen,
                'thanh_tien' => (float) $order->thanh_tien,
            ],
        ];
    }

    private function time($value): ?string
    {
        return $value ? Carbon::parse($value, config('app.timezone'))->setTimezone('Asia/Ho_Chi_Minh')->format('H:i:s d/m/Y') : null;
    }
}
