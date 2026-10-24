<?php

namespace App\Console\Commands;

use App\Http\Controllers\DonHangController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireUnpaidCardOrders extends Command
{
    protected $signature = 'payments:expire-unpaid-card-orders';

    protected $description = 'Cancel card orders whose VNPay payment window expired and restore their stock.';

    public function handle(): int
    {
        $minutes = max(5, (int) config('services.vnpay.expire_minutes')) + 5;
        $orderIds = DB::table('vnpay_payments')
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->pluck('don_hang_id');

        foreach ($orderIds as $orderId) {
            app(DonHangController::class)->cancelUnpaidCardOrder((int) $orderId, 'expired');
        }

        $this->info('Đã hủy ' . $orderIds->count() . ' đơn thanh toán thẻ quá hạn.');

        return self::SUCCESS;
    }
}
