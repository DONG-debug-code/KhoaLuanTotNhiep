<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Voucher do hệ thống tự cấp: chào mừng khách mới và ưu đãi cho khách chi tiêu từ 1 tỷ.
 */
class VoucherService
{
    public const WELCOME_CODE = 'CHAOMUNG5';
    public const LOYALTY_CODE_UNDER_700 = 'VIP10-DUOI700TR';
    public const LOYALTY_CODE_UNDER_200 = 'VIP5-DUOI200TR';
    public const SPEND_THRESHOLD = 1_000_000_000;
    public const WELCOME_VALID_DAYS = 30;

    private const TEMPLATES = [
        self::WELCOME_CODE => ['nhom' => 'chao_mung', 'gia_tri' => 5, 'gia_san_pham_toi_da' => null],
        self::LOYALTY_CODE_UNDER_700 => ['nhom' => 'chi_tieu', 'gia_tri' => 10, 'gia_san_pham_toi_da' => 700_000_000],
        self::LOYALTY_CODE_UNDER_200 => ['nhom' => 'chi_tieu', 'gia_tri' => 5, 'gia_san_pham_toi_da' => 200_000_000],
    ];

    /** Voucher mẫu (tạo lần đầu khi cần); quản trị viên có thể khóa mã để ngừng cấp. */
    public function template(string $code): ?object
    {
        $voucher = DB::table('phieu_giam_gias')->where('code', $code)->first();
        if (!$voucher) {
            $definition = self::TEMPLATES[$code];
            $id = DB::table('phieu_giam_gias')->insertGetId([
                'code' => $code,
                'loai' => 'phan_tram',
                'gia_tri' => $definition['gia_tri'],
                'gia_tri_don_hang_toi_thieu' => 0,
                'giam_toi_da' => null,
                'so_luong' => 1_000_000,
                'ngay_bat_dau' => null,
                'ngay_ket_thuc' => null,
                'trang_thai' => true,
                'nhom' => $definition['nhom'],
                'gia_san_pham_toi_da' => $definition['gia_san_pham_toi_da'],
                'gioi_han_moi_khach' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $voucher = DB::table('phieu_giam_gias')->where('id', $id)->first();
        }

        return (int) $voucher->trang_thai === 1 ? $voucher : null;
    }

    /** Cấp voucher 5% mọi sản phẩm cho khách mới, hết hạn sau 30 ngày kể từ lúc gửi email đăng ký. */
    public function grantWelcome(int $userId, ?Carbon $sentAt = null): ?object
    {
        $voucher = $this->template(self::WELCOME_CODE);
        if (!$voucher) {
            return null;
        }

        $expiresAt = ($sentAt ?? now())->copy()->addDays(self::WELCOME_VALID_DAYS);
        if (!$this->hasVoucher($userId, (int) $voucher->id)) {
            DB::table('user_vouchers')->insertOrIgnore([
                'user_id' => $userId,
                'phieu_id' => $voucher->id,
                'het_han_luc' => $expiresAt,
                'nguon' => 'chao_mung',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $voucher->het_han_luc = $expiresAt;

        return $voucher;
    }

    private function hasVoucher(int $userId, int $voucherId): bool
    {
        return DB::table('user_vouchers')->where('user_id', $userId)->where('phieu_id', $voucherId)->exists();
    }

    /** Tổng chi tiêu: đơn hàng đã thanh toán + hóa đơn dịch vụ workshop đã thanh toán. */
    public function totalSpend(int $userId): float
    {
        $orders = (float) DB::table('don_hangs')
            ->where('users_id', $userId)
            ->where('trang_thai_thanh_toan', 'da_thanh_toan')
            ->where('trang_thai', '!=', 'da_huy_hoan_kho')
            ->sum('thanh_tien');

        $workshop = (float) DB::table('hoa_dons')
            ->join('phieu_dat_lichs', 'phieu_dat_lichs.id', '=', 'hoa_dons.id_phieu_dat_lich')
            ->where('phieu_dat_lichs.id_khach_hang', $userId)
            ->whereNull('hoa_dons.don_hang_id')
            ->where('hoa_dons.trang_thai', 1)
            ->sum('hoa_dons.tong_tien');

        return $orders + $workshop;
    }

    /**
     * Khách chi tiêu >= 1 tỷ được cấp voucher 10% (sản phẩm dưới 700tr) và 5% (sản phẩm dưới 200tr).
     *
     * @return Collection<int, object> voucher vừa được cấp mới
     */
    public function grantLoyaltyIfEligible(int $userId): Collection
    {
        $granted = collect();
        if ($this->totalSpend($userId) < self::SPEND_THRESHOLD) {
            return $granted;
        }

        foreach ([self::LOYALTY_CODE_UNDER_700, self::LOYALTY_CODE_UNDER_200] as $code) {
            $voucher = $this->template($code);
            if (!$voucher || $this->hasVoucher($userId, (int) $voucher->id)) {
                continue;
            }

            DB::table('user_vouchers')->insertOrIgnore([
                'user_id' => $userId,
                'phieu_id' => $voucher->id,
                'het_han_luc' => null,
                'nguon' => 'chi_tieu',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $granted->push($voucher);
        }

        if ($granted->isNotEmpty()) {
            $this->notifyLoyalty($userId, $granted);
        }

        return $granted;
    }

    /** Dùng ở các điểm hoàn tất thanh toán: lỗi cấp voucher không được làm hỏng nghiệp vụ chính. */
    public function grantLoyaltyQuietly(?int $userId): void
    {
        if (!$userId) {
            return;
        }

        try {
            $this->grantLoyaltyIfEligible($userId);
        } catch (Throwable $e) {
            Log::error('Không thể cấp voucher ưu đãi chi tiêu.', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }

    private function notifyLoyalty(int $userId, Collection $vouchers): void
    {
        $user = DB::table('users')->where('id', $userId)->first(['ho_ten', 'email']);
        if (!$user) {
            return;
        }

        try {
            Mail::send('emails.voucher_chi_tieu', [
                'ho_ten' => $user->ho_ten,
                'vouchers' => $vouchers,
                'nguong' => self::SPEND_THRESHOLD,
            ], function ($message) use ($user) {
                $message->to($user->email)->subject('🎁 Ưu đãi dành riêng cho khách hàng thân thiết Porsche');
            });
        } catch (Throwable $e) {
            Log::error('Không thể gửi email voucher ưu đãi chi tiêu.', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }
}
