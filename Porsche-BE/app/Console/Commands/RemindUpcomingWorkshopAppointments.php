<?php

namespace App\Console\Commands;

use App\Mail\LaiThuMail;
use App\Mail\LichHenMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class RemindUpcomingWorkshopAppointments extends Command
{
    protected $signature = 'workshop:remind-upcoming-appointments';

    protected $description = 'Send one reminder for each confirmed workshop appointment within 24 hours.';

    private const TZ = 'Asia/Ho_Chi_Minh';

    public function handle(): int
    {
        $now = Carbon::now(self::TZ);
        $remindUntil = $now->copy()->addHours(24);
        $reminded = 0;

        DB::table('phieu_dat_lichs')
            ->where('trang_thai', 1)
            ->whereNull('reminder_sent_at')
            ->whereDate('ngay_dat', '>=', $now->toDateString())
            ->whereDate('ngay_dat', '<=', $remindUntil->toDateString())
            ->orderBy('id')
            ->chunkById(100, function ($appointments) use ($now, $remindUntil, &$reminded) {
                foreach ($appointments as $appointment) {
                    $scheduledAt = Carbon::parse($appointment->ngay_dat.' '.$appointment->gio_dat, self::TZ);
                    if ($scheduledAt->lt($now) || $scheduledAt->gt($remindUntil)) {
                        continue;
                    }

                    try {
                        Mail::to($appointment->email)->queue(
                            new LichHenMail($appointment, 'Nhắc lịch Workshop Porsche trong 24 giờ tới.')
                        );
                        DB::table('phieu_dat_lichs')
                            ->where('id', $appointment->id)
                            ->whereNull('reminder_sent_at')
                            ->update(['reminder_sent_at' => now(), 'updated_at' => now()]);
                        $reminded++;
                    } catch (\Throwable $e) {
                        Log::error('Không thể gửi email nhắc lịch Workshop.', [
                            'appointment_id' => $appointment->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $reminded += $this->remindTestDrives($now, $remindUntil);

        $this->info("Đã xếp lịch gửi {$reminded} email nhắc hẹn.");

        return self::SUCCESS;
    }

    private function remindTestDrives(Carbon $now, Carbon $remindUntil): int
    {
        if (! Schema::hasTable('dang_ky_lai_thus')) {
            return 0;
        }

        $reminded = 0;
        DB::table('dang_ky_lai_thus')
            ->where('trang_thai', 1)
            ->whereNull('reminder_sent_at')
            ->whereDate('ngay_hen', '>=', $now->toDateString())
            ->whereDate('ngay_hen', '<=', $remindUntil->toDateString())
            ->orderBy('id')
            ->chunkById(100, function ($registrations) use ($now, $remindUntil, &$reminded) {
                foreach ($registrations as $registration) {
                    $scheduledAt = Carbon::parse($registration->ngay_hen.' '.$registration->gio_hen, self::TZ);
                    if ($scheduledAt->lt($now) || $scheduledAt->gt($remindUntil)) {
                        continue;
                    }

                    try {
                        Mail::to($registration->email)->queue(
                            new LaiThuMail($registration, 'Nhắc lịch lái thử Porsche trong 24 giờ tới', 'Lịch lái thử của bạn sẽ diễn ra trong vòng 24 giờ tới. Thông tin chi tiết như sau:')
                        );
                        DB::table('dang_ky_lai_thus')
                            ->where('id', $registration->id)
                            ->update(['reminder_sent_at' => now(), 'updated_at' => now()]);
                        $reminded++;
                    } catch (\Throwable $e) {
                        Log::error('Không thể gửi email nhắc lịch lái thử.', [
                            'registration_id' => $registration->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $reminded;
    }
}
