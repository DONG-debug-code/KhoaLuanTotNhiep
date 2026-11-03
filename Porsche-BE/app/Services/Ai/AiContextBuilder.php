<?php

namespace App\Services\Ai;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Dựng ngữ cảnh (dữ liệu hệ thống + dữ liệu riêng của khách) đưa vào AI.
 * Dữ liệu khách chỉ được lấy theo đúng người dùng đang đăng nhập.
 */
class AiContextBuilder
{
    private const MAX_ITEMS = 60;

    private const ORDER_STATUS = [
        'cho_xac_nhan' => 'Chờ xác nhận',
        'da_xac_nhan' => 'Đã xác nhận',
        'dang_giao' => 'Đang giao',
        'hoan_thanh' => 'Hoàn thành',
        'huy' => 'Đã hủy',
    ];

    private const APPOINTMENT_STATUS = [
        0 => 'Chờ xác nhận',
        1 => 'Đã xác nhận / đang thực hiện',
        2 => 'Đã hoàn thành',
        3 => 'Đã hủy',
    ];

    public function catalog(): string
    {
        $lines = ["=== DỮ LIỆU KHO & DỊCH VỤ CỦA SHOWROOM (nguồn tin cậy duy nhất cho giá và tồn kho) ==="];

        $lines[] = "\n-- Xe & sản phẩm --";
        $lines = array_merge($lines, $this->safe(fn () => DB::table('san_phams')
            ->where('trang_thai', 1)
            ->orderByDesc('id')
            ->limit(self::MAX_ITEMS)
            ->get()
            ->map(function ($p) {
                $gia = (float) ($p->gia_khuyen_mai ?: $p->gia);
                $parts = [
                    "{$p->ten}",
                    $p->loai_san_pham ? "loại: {$p->loai_san_pham}" : null,
                    'giá: ' . $this->money($gia),
                    'tồn kho: ' . $this->stock((int) ($p->so_luong_kho ?: $p->so_luong)),
                    $p->dong_co ? "động cơ: {$p->dong_co}" : null,
                    $p->cong_suat ? "công suất: {$p->cong_suat}" : null,
                    $p->tang_toc_0_100 ? "0-100km/h: {$p->tang_toc_0_100}" : null,
                    $p->van_toc_toi_da ? "tốc độ tối đa: {$p->van_toc_toi_da}" : null,
                    $p->mo_ta ? 'mô tả: ' . Str::limit(strip_tags($p->mo_ta), 160) : null,
                ];
                return '- ' . implode(' | ', array_filter($parts));
            })->all(), 'Chưa có dữ liệu sản phẩm.'));

        $lines[] = "\n-- Phụ tùng & gói nâng cấp --";
        $lines = array_merge($lines, $this->safe(fn () => DB::table('phu_tungs')
            ->orderByDesc('id')
            ->limit(self::MAX_ITEMS)
            ->get()
            ->map(function ($p) {
                $parts = [
                    $p->ten,
                    $p->hang ? "hãng: {$p->hang}" : null,
                    $p->loai ? "hạng mục: {$p->loai}" : null,
                    'giá: ' . $this->money((float) $p->gia),
                    'tồn kho: ' . $this->stock((int) $p->so_luong),
                    $p->dong_xe ? 'dòng xe phù hợp: ' . $this->flatten($p->dong_xe) : null,
                    $p->bao_hanh ? "bảo hành: {$p->bao_hanh}" : null,
                    $p->bao_gom ? 'bao gồm: ' . Str::limit($this->flatten($p->bao_gom), 120) : null,
                    $p->mo_ta ? 'mô tả: ' . Str::limit(strip_tags($p->mo_ta), 160) : null,
                ];
                return '- ' . implode(' | ', array_filter($parts));
            })->all(), 'Chưa có dữ liệu phụ tùng.'));

        $goiDo = $this->safe(fn () => DB::table('goi_do_xes')
            ->leftJoin('mau_xes', 'goi_do_xes.mau_xe_id', '=', 'mau_xes.id')
            ->where('goi_do_xes.trang_thai', 1)
            ->select('goi_do_xes.ten_goi', 'goi_do_xes.mo_ta', 'goi_do_xes.tong_gia_goc', 'goi_do_xes.gia_goi_khuyen_mai', 'mau_xes.ten as ten_mau_xe')
            ->limit(30)
            ->get()
            ->map(fn ($g) => '- ' . implode(' | ', array_filter([
                $g->ten_goi,
                $g->ten_mau_xe ? "áp dụng: {$g->ten_mau_xe}" : null,
                'giá gốc: ' . $this->money((float) $g->tong_gia_goc),
                $g->gia_goi_khuyen_mai ? 'giá khuyến mãi: ' . $this->money((float) $g->gia_goi_khuyen_mai) : null,
                $g->mo_ta ? Str::limit(strip_tags($g->mo_ta), 140) : null,
            ])))->all(), null);
        if ($goiDo) {
            $lines[] = "\n-- Gói độ đang bán --";
            $lines = array_merge($lines, $goiDo);
        }

        $chuyenGia = $this->safe(fn () => DB::table('chuyen_gias')
            ->where('trang_thai', 1)
            ->limit(20)
            ->get()
            ->map(fn ($c) => '- ' . implode(' | ', array_filter([
                $c->ho_ten,
                $c->chuc_danh,
                $c->chuyen_mon ? 'chuyên môn: ' . $this->flatten($c->chuyen_mon) : null,
                $c->dong_xe ? 'dòng xe: ' . $this->flatten($c->dong_xe) : null,
                $c->so_nam_kinh_nghiem ? "{$c->so_nam_kinh_nghiem} năm kinh nghiệm" : null,
                $c->tinh_trang ? "tình trạng: {$c->tinh_trang}" : null,
            ])))->all(), null);
        if ($chuyenGia) {
            $lines[] = "\n-- Chuyên gia / tuner của showroom --";
            $lines = array_merge($lines, $chuyenGia);
        }

        return implode("\n", $lines);
    }

    public function customer(User $user): string
    {
        $lines = ["=== DỮ LIỆU RIÊNG CỦA KHÁCH HÀNG ĐANG CHAT (chỉ của người này, bảo mật) ==="];

        $lines[] = "\n-- Hồ sơ --";
        $lines[] = implode(' | ', array_filter([
            "Họ tên: {$user->ho_ten}",
            $user->so_dien_thoai ? "SĐT: {$user->so_dien_thoai}" : null,
            $user->dia_chi ? "Địa chỉ: {$user->dia_chi}" : null,
            'Điểm tích lũy: ' . (int) $user->diem_tich_luy,
        ]));

        $diaChi = $this->safe(fn () => DB::table('dia_chi_users')
            ->where('user_id', $user->id)
            ->orderByDesc('mac_dinh')
            ->limit(5)
            ->get()
            ->map(fn ($d) => '- ' . ($d->mac_dinh ? '[Mặc định] ' : '') . "{$d->dia_chi}, {$d->phuong_xa}, {$d->quan_huyen}, {$d->thanh_pho}")
            ->all(), null);
        if ($diaChi) {
            $lines[] = "\n-- Địa chỉ đã lưu --";
            $lines = array_merge($lines, $diaChi);
        }

        $lines[] = "\n-- Đơn hàng gần đây --";
        $orders = $this->safe(fn () => DB::table('don_hangs')
            ->where('users_id', $user->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get(), null);
        $owned = [];
        if (!$orders || $orders->isEmpty()) {
            $lines[] = 'Khách chưa có đơn hàng nào.';
        } else {
            $items = $this->safe(fn () => DB::table('chi_tiet_don_hangs as ct')
                ->leftJoin('san_phams as sp', 'ct.san_pham_id', '=', 'sp.id')
                ->leftJoin('phu_tungs as pt', 'ct.phu_tung_id', '=', 'pt.id')
                ->whereIn('ct.don_hang_id', $orders->pluck('id'))
                ->select('ct.don_hang_id', 'ct.so_luong', 'ct.don_gia', DB::raw('COALESCE(sp.ten, pt.ten) as ten'))
                ->get()
                ->groupBy('don_hang_id'), null) ?? collect();

            foreach ($orders as $o) {
                $chiTiet = collect($items->get($o->id, []))
                    ->map(fn ($i) => "{$i->ten} x{$i->so_luong}")
                    ->implode(', ');
                $status = self::ORDER_STATUS[$o->trang_thai] ?? $o->trang_thai;
                $lines[] = "- Đơn #{$o->id} ({$o->created_at}) | {$status} | thanh toán: " . ($o->trang_thai_thanh_toan ?? 'n/a')
                    . ' | tổng: ' . $this->money((float) $o->thanh_tien) . " | {$chiTiet}";

                if ($o->trang_thai === 'hoan_thanh') {
                    foreach ($items->get($o->id, []) as $i) {
                        $owned[$i->ten] = true;
                    }
                }
            }
        }

        $lines[] = "\n-- Xe trong garage của khách --";
        $lines = array_merge($lines, $this->safe(fn () => DB::table('xe_khach_hangs')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn ($x) => '- ' . implode(' | ', array_filter([
                $x->ten_xe,
                "biển số: {$x->bien_so_xe}",
                $x->dong_xe ? "dòng xe: {$x->dong_xe}" : null,
                $x->nam_san_xuat ? "năm SX: {$x->nam_san_xuat}" : null,
                $x->mau_sac ? "màu: {$x->mau_sac}" : null,
                $x->so_km !== null ? 'số km: ' . number_format((int) $x->so_km, 0, ',', '.') : null,
                $x->ghi_chu ? 'ghi chú: ' . Str::limit($x->ghi_chu, 120) : null,
            ])))->all(), 'Khách chưa thêm xe nào vào garage.'));

        $lines[] = "\n-- Sản phẩm khách đang sở hữu (từ đơn đã hoàn thành) --";
        $lines[] = $owned ? '- ' . implode("\n- ", array_keys($owned)) : 'Chưa có sản phẩm nào từ đơn hoàn thành.';

        $lines[] = "\n-- Lịch hẹn độ xe / bảo dưỡng --";
        $lines = array_merge($lines, $this->safe(fn () => DB::table('phieu_dat_lichs')
            ->leftJoin('chuyen_gias', 'phieu_dat_lichs.id_chuyen_gia', '=', 'chuyen_gias.id')
            ->where('phieu_dat_lichs.id_khach_hang', $user->id)
            ->orderByDesc('phieu_dat_lichs.id')
            ->limit(10)
            ->select('phieu_dat_lichs.*', 'chuyen_gias.ho_ten as ten_chuyen_gia')
            ->get()
            ->map(fn ($l) => '- ' . implode(' | ', array_filter([
                "{$l->ma_dat_lich}",
                "{$l->ngay_dat} {$l->gio_dat}",
                self::APPOINTMENT_STATUS[(int) $l->trang_thai] ?? 'Không rõ',
                $l->giai_doan ? "giai đoạn: {$l->giai_doan}" : null,
                $l->thong_tin_xe ? "xe: {$l->thong_tin_xe}" : null,
                $l->bien_so_xe ? "biển số: {$l->bien_so_xe}" : null,
                $l->tieu_de_cong_viec ? "công việc: {$l->tieu_de_cong_viec}" : null,
                $l->ten_chuyen_gia ? "chuyên gia: {$l->ten_chuyen_gia}" : null,
            ])))->all(), 'Khách chưa có lịch hẹn nào.'));

        $lines[] = "\n-- Lịch sử bảo dưỡng --";
        $lines = array_merge($lines, $this->safe(fn () => DB::table('lich_su_bao_duongs')
            ->leftJoin('san_phams', 'lich_su_bao_duongs.san_pham_id', '=', 'san_phams.id')
            ->where('lich_su_bao_duongs.user_id', $user->id)
            ->orderByDesc('lich_su_bao_duongs.ngay_bao_duong')
            ->limit(10)
            ->select('lich_su_bao_duongs.*', 'san_phams.ten as ten_xe')
            ->get()
            ->map(fn ($h) => "- {$h->ngay_bao_duong} | {$h->noi_dung}"
                . ($h->ten_xe ? " | xe: {$h->ten_xe}" : '')
                . ' | chi phí: ' . $this->money((float) $h->chi_phi))->all(), 'Chưa có lịch sử bảo dưỡng.'));

        $lines[] = "\n-- Voucher của khách --";
        $today = now()->toDateString();
        $lines = array_merge($lines, $this->safe(fn () => DB::table('user_vouchers')
            ->join('phieu_giam_gias as p', 'user_vouchers.phieu_id', '=', 'p.id')
            ->where('user_vouchers.user_id', $user->id)
            ->select('p.code', 'p.loai', 'p.gia_tri', 'p.gia_tri_don_hang_toi_thieu', 'p.giam_toi_da', 'p.ngay_ket_thuc', 'p.trang_thai')
            ->get()
            ->map(function ($v) use ($today) {
                $con = (int) $v->trang_thai === 1 && (!$v->ngay_ket_thuc || substr((string) $v->ngay_ket_thuc, 0, 10) >= $today);
                return "- {$v->code} | {$v->loai}: {$v->gia_tri}"
                    . ($v->gia_tri_don_hang_toi_thieu ? ' | đơn tối thiểu ' . $this->money((float) $v->gia_tri_don_hang_toi_thieu) : '')
                    . ($v->giam_toi_da ? ' | giảm tối đa ' . $this->money((float) $v->giam_toi_da) : '')
                    . ($v->ngay_ket_thuc ? " | hạn: {$v->ngay_ket_thuc}" : '')
                    . ' | ' . ($con ? 'còn hiệu lực' : 'hết hiệu lực / không áp dụng');
            })->all(), 'Khách chưa có voucher.'));

        return implode("\n", $lines);
    }

    /**
     * Chạy truy vấn, lỗi thì ghi log và bỏ qua để 1 nguồn dữ liệu hỏng không làm chết cả cuộc chat.
     */
    private function safe(callable $query, ?string $empty): array|\Illuminate\Support\Collection|null
    {
        try {
            $result = $query();
        } catch (\Throwable $e) {
            Log::warning('AiContextBuilder bỏ qua nguồn dữ liệu: ' . $e->getMessage());
            return $empty === null ? null : [$empty];
        }

        if (is_array($result) && $result === []) {
            return $empty === null ? null : [$empty];
        }

        return $result;
    }

    private function money(float $value): string
    {
        return number_format($value, 0, ',', '.') . ' VNĐ';
    }

    private function stock(int $qty): string
    {
        return $qty > 0 ? "còn hàng ({$qty})" : 'TẠM HẾT HÀNG';
    }

    private function flatten(mixed $value): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        return is_array($value)
            ? implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE), $value))
            : (string) $value;
    }
}
