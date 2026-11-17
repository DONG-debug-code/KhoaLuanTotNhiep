<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chat)
    {
    }

    // ------------------------------------------------------------------ Khách hàng

    /** Cuộc trò chuyện đang mở của khách (nếu có) kèm số tin chưa đọc, dùng cho huy hiệu thông báo. */
    public function khachHienTai(Request $request): JsonResponse
    {
        $conversation = DB::table('cuoc_tro_chuyens')
            ->where('user_id', $request->user()->id)
            ->where('trang_thai', '!=', 'dong')
            ->orderByDesc('last_message_at')
            ->first();

        return response()->json(['status' => true, 'data' => $conversation ? $this->present($conversation, ChatService::SENDER_CUSTOMER) : null]);
    }

    /** Khách muốn nói chuyện với nhân viên: dùng lại cuộc đang mở hoặc tạo mới (có thể gắn với một lịch hẹn). */
    public function khachKetNoi(Request $request): JsonResponse
    {
        $data = $request->validate(['id_phieu_dat_lich' => 'nullable|integer']);
        $userId = (int) $request->user()->id;

        $appointmentId = null;
        if (!empty($data['id_phieu_dat_lich'])) {
            $appointmentId = DB::table('phieu_dat_lichs')
                ->where('id', $data['id_phieu_dat_lich'])
                ->where('id_khach_hang', $userId)
                ->value('id');
            if (!$appointmentId) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy lịch hẹn của bạn.'], 404);
            }
        }

        $conversation = $this->chat->openForCustomer($userId, $appointmentId ? (int) $appointmentId : null);

        return response()->json(['status' => true, 'data' => $this->present($conversation, ChatService::SENDER_CUSTOMER)]);
    }

    public function khachTinNhan(Request $request, int $id): JsonResponse
    {
        $conversation = $this->customerConversation($request, $id);
        if (!$conversation) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy cuộc trò chuyện.'], 404);
        }

        return $this->messagesResponse($request, $conversation, ChatService::SENDER_CUSTOMER);
    }

    public function khachGui(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['noi_dung' => 'required|string|max:2000']);
        $conversation = $this->customerConversation($request, $id);
        if (!$conversation) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy cuộc trò chuyện.'], 404);
        }
        if ($conversation->trang_thai === 'dong') {
            return response()->json(['status' => false, 'message' => 'Cuộc trò chuyện đã kết thúc. Vui lòng bắt đầu cuộc mới.'], 422);
        }

        $messageId = $this->chat->post($conversation, ChatService::SENDER_CUSTOMER, (int) $request->user()->id, trim($data['noi_dung']));

        return response()->json(['status' => true, 'data' => ['id' => $messageId]], 201);
    }

    public function khachDong(Request $request, int $id): JsonResponse
    {
        $conversation = $this->customerConversation($request, $id);
        if (!$conversation) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy cuộc trò chuyện.'], 404);
        }

        DB::table('cuoc_tro_chuyens')->where('id', $id)->update(['trang_thai' => 'dong', 'updated_at' => now()]);

        return response()->json(['status' => true, 'message' => 'Đã kết thúc cuộc trò chuyện.']);
    }

    // ------------------------------------------------------------------ Nhân viên

    /** Hàng đợi chat: cuộc đang chờ/đang trao đổi và các cuộc vừa kết thúc gần đây. */
    public function nhanVienDanhSach(): JsonResponse
    {
        $rows = DB::table('cuoc_tro_chuyens as c')
            ->join('users as u', 'u.id', '=', 'c.user_id')
            ->leftJoin('admins as a', 'a.id', '=', 'c.nhan_vien_id')
            ->leftJoin('phieu_dat_lichs as p', 'p.id', '=', 'c.id_phieu_dat_lich')
            ->where(function ($query) {
                $query->where('c.trang_thai', '!=', 'dong')->orWhere('c.last_message_at', '>=', now()->subDays(7));
            })
            ->orderByRaw("CASE c.trang_thai WHEN 'cho' THEN 0 WHEN 'dang' THEN 1 ELSE 2 END")
            ->orderByDesc('c.last_message_at')
            ->limit(100)
            ->get(['c.id', 'c.user_id', 'c.trang_thai', 'c.last_message_at', 'c.id_phieu_dat_lich', 'u.ho_ten as ten_khach_hang', 'u.so_dien_thoai', 'a.ho_ten as ten_nhan_vien', 'p.ma_dat_lich']);

        $rows->each(function ($row) {
            $row->so_chua_doc = $this->chat->unreadFor($row->id, ChatService::SENDER_STAFF);
            $row->tin_cuoi = DB::table('tin_nhans')->where('cuoc_tro_chuyen_id', $row->id)->orderByDesc('id')->value('noi_dung');
        });

        return response()->json(['status' => true, 'data' => $rows]);
    }

    public function nhanVienTinNhan(Request $request, int $id): JsonResponse
    {
        $conversation = DB::table('cuoc_tro_chuyens')->where('id', $id)->first();
        if (!$conversation) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy cuộc trò chuyện.'], 404);
        }

        return $this->messagesResponse($request, $conversation, ChatService::SENDER_STAFF);
    }

    public function nhanVienGui(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['noi_dung' => 'required|string|max:2000']);
        $staff = $request->user();
        $conversation = DB::table('cuoc_tro_chuyens')->where('id', $id)->first();
        if (!$conversation || !($staff instanceof Admin)) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy cuộc trò chuyện.'], 404);
        }
        if ($conversation->trang_thai === 'dong') {
            return response()->json(['status' => false, 'message' => 'Cuộc trò chuyện đã kết thúc.'], 422);
        }

        $messageId = $this->chat->post($conversation, ChatService::SENDER_STAFF, (int) $staff->id, trim($data['noi_dung']));

        return response()->json(['status' => true, 'data' => ['id' => $messageId]], 201);
    }

    public function nhanVienNhan(Request $request, int $id): JsonResponse
    {
        $updated = DB::table('cuoc_tro_chuyens')->where('id', $id)->where('trang_thai', '!=', 'dong')
            ->update(['nhan_vien_id' => $request->user()->id, 'trang_thai' => 'dang', 'updated_at' => now()]);

        return $updated
            ? response()->json(['status' => true, 'message' => 'Đã nhận cuộc trò chuyện.'])
            : response()->json(['status' => false, 'message' => 'Không tìm thấy cuộc trò chuyện đang mở.'], 404);
    }

    public function nhanVienDong(int $id): JsonResponse
    {
        $updated = DB::table('cuoc_tro_chuyens')->where('id', $id)->update(['trang_thai' => 'dong', 'updated_at' => now()]);

        return $updated
            ? response()->json(['status' => true, 'message' => 'Đã kết thúc cuộc trò chuyện.'])
            : response()->json(['status' => false, 'message' => 'Không tìm thấy cuộc trò chuyện.'], 404);
    }

    // ------------------------------------------------------------------ Dùng chung

    private function customerConversation(Request $request, int $id): ?object
    {
        return DB::table('cuoc_tro_chuyens')->where('id', $id)->where('user_id', $request->user()->id)->first();
    }

    private function messagesResponse(Request $request, object $conversation, string $viewer): JsonResponse
    {
        $messages = $this->chat->messages($conversation, (int) $request->query('sau_id', 0), $viewer);
        $fresh = DB::table('cuoc_tro_chuyens')->where('id', $conversation->id)->first();

        return response()->json([
            'status' => true,
            'data' => [
                'cuoc_tro_chuyen' => $this->present($fresh, $viewer),
                'tin_nhan' => $messages,
            ],
        ]);
    }

    private function present(object $conversation, string $viewer): array
    {
        $staffName = $conversation->nhan_vien_id
            ? DB::table('admins')->where('id', $conversation->nhan_vien_id)->value('ho_ten')
            : null;

        return [
            'id' => $conversation->id,
            'trang_thai' => $conversation->trang_thai,
            'id_phieu_dat_lich' => $conversation->id_phieu_dat_lich,
            'ten_nhan_vien' => $staffName,
            'so_chua_doc' => $this->chat->unreadFor($conversation->id, $viewer),
        ];
    }
}
