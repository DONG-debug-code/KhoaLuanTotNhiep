<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Chat giữa khách hàng và nhân viên (client hỏi lại máy chủ định kỳ, không dùng websocket).
 */
class ChatService
{
    public const SENDER_CUSTOMER = 'khach_hang';
    public const SENDER_STAFF = 'nhan_vien';
    public const SENDER_SYSTEM = 'he_thong';

    /** Cuộc trò chuyện đang mở của khách (ưu tiên cuộc gắn với lịch hẹn nếu có), chưa có thì tạo mới. */
    public function openForCustomer(int $userId, ?int $appointmentId = null, ?int $staffId = null): object
    {
        $query = DB::table('cuoc_tro_chuyens')->where('user_id', $userId)->where('trang_thai', '!=', 'dong');
        $conversation = null;
        if ($appointmentId) {
            $conversation = (clone $query)->where('id_phieu_dat_lich', $appointmentId)->orderByDesc('id')->first();
        }
        $conversation ??= $query->orderByDesc('last_message_at')->orderByDesc('id')->first();

        if ($conversation) {
            if ($appointmentId && !$conversation->id_phieu_dat_lich) {
                DB::table('cuoc_tro_chuyens')->where('id', $conversation->id)->update(['id_phieu_dat_lich' => $appointmentId, 'updated_at' => now()]);
                $conversation->id_phieu_dat_lich = $appointmentId;
            }

            return $conversation;
        }

        $id = DB::table('cuoc_tro_chuyens')->insertGetId([
            'user_id' => $userId,
            'nhan_vien_id' => $staffId,
            'id_phieu_dat_lich' => $appointmentId,
            'trang_thai' => $staffId ? 'dang' : 'cho',
            'last_message_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('cuoc_tro_chuyens')->where('id', $id)->first();
    }

    public function post(object $conversation, string $senderType, ?int $senderId, string $text): int
    {
        $id = DB::table('tin_nhans')->insertGetId([
            'cuoc_tro_chuyen_id' => $conversation->id,
            'nguoi_gui_loai' => $senderType,
            'nguoi_gui_id' => $senderId,
            'noi_dung' => $text,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $update = ['last_message_at' => now(), 'updated_at' => now()];
        if ($senderType === self::SENDER_STAFF && $conversation->trang_thai === 'cho') {
            $update += ['trang_thai' => 'dang', 'nhan_vien_id' => $senderId];
        }
        DB::table('cuoc_tro_chuyens')->where('id', $conversation->id)->update($update);

        return $id;
    }

    /**
     * Tin nhắn mới hơn $afterId; đánh dấu đã đọc các tin của phía còn lại.
     *
     * @param  string  $viewer  khach_hang | nhan_vien
     */
    public function messages(object $conversation, int $afterId, string $viewer)
    {
        $otherSides = $viewer === self::SENDER_CUSTOMER
            ? [self::SENDER_STAFF, self::SENDER_SYSTEM]
            : [self::SENDER_CUSTOMER];

        DB::table('tin_nhans')
            ->where('cuoc_tro_chuyen_id', $conversation->id)
            ->whereIn('nguoi_gui_loai', $otherSides)
            ->whereNull('da_doc_luc')
            ->update(['da_doc_luc' => now()]);

        return DB::table('tin_nhans as t')
            ->leftJoin('admins as a', fn ($join) => $join->on('a.id', '=', 't.nguoi_gui_id')->where('t.nguoi_gui_loai', '=', self::SENDER_STAFF))
            ->leftJoin('users as u', fn ($join) => $join->on('u.id', '=', 't.nguoi_gui_id')->where('t.nguoi_gui_loai', '=', self::SENDER_CUSTOMER))
            ->where('t.cuoc_tro_chuyen_id', $conversation->id)
            ->where('t.id', '>', $afterId)
            ->orderBy('t.id')
            ->limit(200)
            ->get(['t.id', 't.nguoi_gui_loai', 't.noi_dung', 't.created_at', DB::raw('COALESCE(a.ho_ten, u.ho_ten) as nguoi_gui_ten')]);
    }

    public function unreadFor(int $conversationId, string $viewer): int
    {
        $otherSides = $viewer === self::SENDER_CUSTOMER
            ? [self::SENDER_STAFF, self::SENDER_SYSTEM]
            : [self::SENDER_CUSTOMER];

        return DB::table('tin_nhans')
            ->where('cuoc_tro_chuyen_id', $conversationId)
            ->whereIn('nguoi_gui_loai', $otherSides)
            ->whereNull('da_doc_luc')
            ->count();
    }
}
