<?php

namespace App\Console\Commands;

use App\Services\VoucherService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GrantLoyaltyVouchers extends Command
{
    protected $signature = 'vouchers:grant-loyalty';

    protected $description = 'Cấp voucher ưu đãi cho khách có tổng chi tiêu từ 1 tỷ đồng nhưng chưa nhận voucher.';

    public function handle(VoucherService $vouchers): int
    {
        $granted = 0;

        $customerIds = DB::table('don_hangs')
            ->where('trang_thai_thanh_toan', 'da_thanh_toan')
            ->distinct()
            ->pluck('users_id')
            ->merge(
                DB::table('hoa_dons')
                    ->join('phieu_dat_lichs', 'phieu_dat_lichs.id', '=', 'hoa_dons.id_phieu_dat_lich')
                    ->whereNotNull('phieu_dat_lichs.id_khach_hang')
                    ->distinct()
                    ->pluck('phieu_dat_lichs.id_khach_hang')
            )
            ->unique()
            ->filter();

        foreach ($customerIds as $userId) {
            $granted += $vouchers->grantLoyaltyIfEligible((int) $userId)->count();
        }

        $this->info("Đã cấp {$granted} voucher ưu đãi chi tiêu.");

        return self::SUCCESS;
    }
}
