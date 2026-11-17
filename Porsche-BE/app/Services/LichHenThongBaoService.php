<?php

namespace App\Services;

use App\Mail\LichHenMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Mỗi thay đổi lịch hẹn (xác nhận, đổi giờ, đổi chuyên gia, hủy) được ghi vào lịch sử của lịch,
 * nhắn vào chat với khách và gửi email thông báo.
 */
class LichHenThongBaoService
{
    public function __construct(private readonly ChatService $chat)
    {
    }

    public function notifyQuietly(object $appointment, string $type, string $message, ?array $data = null, ?object $staff = null, ?string $subject = null, bool $sendEmail = true): void
    {
        try {
            $this->notify($appointment, $type, $message, $data, $staff, $subject, $sendEmail);
        } catch (Throwable $e) {
            Log::error('Không thể ghi thông báo thay đổi lịch hẹn.', ['appointment_id' => $appointment->id, 'type' => $type, 'error' => $e->getMessage()]);
        }
    }

    public function notify(object $appointment, string $type, string $message, ?array $data = null, ?object $staff = null, ?string $subject = null, bool $sendEmail = true): void
    {
        $actor = $staff?->ho_ten ?? null;

        DB::table('lich_su_phieu_dat_lichs')->insert([
            'id_phieu_dat_lich' => $appointment->id,
            'loai' => $type,
            'noi_dung' => $message,
            'du_lieu' => $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : null,
            'nguoi_thuc_hien' => $actor,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!empty($appointment->id_khach_hang)) {
            try {
                $conversation = $this->chat->openForCustomer((int) $appointment->id_khach_hang, (int) $appointment->id, $staff?->id);
                $this->chat->post($conversation, ChatService::SENDER_SYSTEM, null, "[{$appointment->ma_dat_lich}] {$message}");
            } catch (Throwable $e) {
                Log::error('Không thể nhắn thông báo lịch hẹn vào chat.', ['appointment_id' => $appointment->id, 'error' => $e->getMessage()]);
            }
        }

        if ($sendEmail && !empty($appointment->email)) {
            try {
                $current = DB::table('phieu_dat_lichs')->where('id', $appointment->id)->first() ?? $appointment;
                Mail::to($appointment->email)->send(new LichHenMail($current, $subject ?? 'Cập nhật lịch hẹn Porsche Workshop', $message));
            } catch (Throwable $e) {
                Log::error('Không thể gửi email thay đổi lịch hẹn.', ['appointment_id' => $appointment->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
