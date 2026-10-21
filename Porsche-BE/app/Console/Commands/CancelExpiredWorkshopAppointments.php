<?php

namespace App\Console\Commands;

use App\Mail\LichHenMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CancelExpiredWorkshopAppointments extends Command
{
    protected $signature = 'workshop:cancel-expired-appointments';

    protected $description = 'Cancel unpaid workshop appointments after the reservation hold expires.';

    public function handle(): int
    {
        $cancelled = 0;
        $expiredBefore = now()->subMinutes(30);

        DB::table('phieu_dat_lichs')
            ->where('trang_thai', 0)
            ->where('created_at', '<=', $expiredBefore)
            ->orderBy('id')
            ->chunkById(100, function ($appointments) use (&$cancelled) {
                foreach ($appointments as $appointment) {
                    $didCancel = DB::transaction(function () use ($appointment) {
                        $lockedAppointment = DB::table('phieu_dat_lichs')
                            ->where('id', $appointment->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $lockedAppointment || (int) $lockedAppointment->trang_thai !== 0) {
                            return false;
                        }

                        DB::table('phieu_dat_lichs')
                            ->where('id', $lockedAppointment->id)
                            ->update(['trang_thai' => 3, 'updated_at' => now()]);

                        return true;
                    });

                    if (! $didCancel) {
                        continue;
                    }
                    $cancelled++;

                    try {
                        Mail::to($appointment->email)->queue(
                            new LichHenMail($appointment, 'Lịch hẹn Workshop đã tự động hủy do hết thời gian giữ chỗ.')
                        );
                    } catch (\Throwable $e) {
                        Log::error('Không thể gửi email thông báo tự hủy lịch Workshop.', [
                            'appointment_id' => $appointment->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Đã tự động hủy {$cancelled} lịch Workshop quá hạn.");

        return self::SUCCESS;
    }
}
