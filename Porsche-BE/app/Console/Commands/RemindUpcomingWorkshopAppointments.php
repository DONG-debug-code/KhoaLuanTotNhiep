<?php

namespace App\Console\Commands;

use App\Mail\LichHenMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RemindUpcomingWorkshopAppointments extends Command
{
    protected $signature = 'workshop:remind-upcoming-appointments';

    protected $description = 'Send one reminder for each confirmed workshop appointment within 24 hours.';

    public function handle(): int
    {
        $now = Carbon::now();
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
                    $scheduledAt = Carbon::parse($appointment->ngay_dat.' '.$appointment->gio_dat);
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

        $this->info("Đã xếp lịch gửi {$reminded} email nhắc hẹn.");

        return self::SUCCESS;
    }
}
