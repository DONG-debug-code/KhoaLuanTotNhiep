<?php

namespace App\Console\Commands;

use App\Mail\LichHenMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class CancelExpiredWorkshopAppointments extends Command
{
    private const OVERDUE_GRACE_HOURS = 2;
    private const TZ = 'Asia/Ho_Chi_Minh';

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

        $overdue = $this->cancelOverdueAppointments();

        $this->info("Đã tự động hủy {$cancelled} lịch giữ chỗ hết hạn và {$overdue} lịch quá giờ hẹn.");

        return self::SUCCESS;
    }

    /**
     * Hủy lịch đã qua giờ hẹn mà xe chưa được tiếp nhận vào xưởng (chưa xác nhận hoặc vẫn ở giai đoạn chờ nhận xe).
     */
    private function cancelOverdueAppointments(): int
    {
        $deadline = Carbon::now(self::TZ)->subHours(self::OVERDUE_GRACE_HOURS);
        $hasStage = Schema::hasColumn('phieu_dat_lichs', 'giai_doan');
        $cancelled = 0;

        $query = DB::table('phieu_dat_lichs')
            ->whereIn('trang_thai', [0, 1])
            ->whereDate('ngay_dat', '<=', $deadline->toDateString());

        if ($hasStage) {
            $query->where(function ($q) {
                $q->where('trang_thai', 0)
                    ->orWhereNull('giai_doan')
                    ->orWhere('giai_doan', 'cho_nhan_xe');
            });
        }

        $query->orderBy('id')->chunkById(100, function ($appointments) use (&$cancelled, $deadline) {
            foreach ($appointments as $appointment) {
                if (Carbon::parse($appointment->ngay_dat . ' ' . $appointment->gio_dat, self::TZ)->gt($deadline)) {
                    continue;
                }

                $didCancel = DB::table('phieu_dat_lichs')
                    ->where('id', $appointment->id)
                    ->whereIn('trang_thai', [0, 1])
                    ->update(['trang_thai' => 3, 'updated_at' => now()]) > 0;

                if (! $didCancel) {
                    continue;
                }
                $cancelled++;

                try {
                    Mail::to($appointment->email)->queue(
                        new LichHenMail($appointment, 'Lịch hẹn Workshop đã tự động hủy do quá giờ hẹn.')
                    );
                } catch (\Throwable $e) {
                    Log::error('Không thể gửi email thông báo hủy lịch quá hạn.', [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        return $cancelled;
    }
}
