<?php

namespace App\Http\Controllers;

use App\Mail\LaiThuMail;
use App\Models\Admin;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LaiThuController extends Controller
{
    private const TZ = 'Asia/Ho_Chi_Minh';
    private const GIO_MO_CUA = '08:00';
    private const GIO_DONG_CUA = '20:00';
    private const MANAGE_ROLES = ['super_admin', 'sales_manager', 'sales_executive', 'customer_relation'];

    /**
     * Khách (có hoặc chưa đăng nhập) gửi form đăng ký lái thử.
     * Hệ thống gửi ngay email xác nhận thời gian và địa điểm; khách trả lời email để trao đổi với nhân viên.
     */
    public function dangKy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ho_ten' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'so_dien_thoai' => ['required', 'string', 'regex:/^[0-9\s\-\+\(\)]{9,20}$/'],
            'dong_xe' => 'nullable|string|max:100',
            'san_pham_id' => 'nullable|integer|exists:san_phams,id',
            'ngay_hen' => 'required|date_format:Y-m-d',
            'gio_hen' => 'required|date_format:H:i',
            'ghi_chu' => 'nullable|string|max:1000',
        ]);

        $now = Carbon::now(self::TZ);
        $scheduledAt = Carbon::parse($data['ngay_hen'] . ' ' . $data['gio_hen'], self::TZ);
        if ($scheduledAt->lt($now->copy()->addHour())) {
            throw ValidationException::withMessages(['ngay_hen' => 'Vui lòng chọn thời gian lái thử sau thời điểm hiện tại ít nhất 1 giờ.']);
        }
        if ($scheduledAt->gt($now->copy()->addDays(60))) {
            throw ValidationException::withMessages(['ngay_hen' => 'Chỉ nhận đăng ký lái thử trong vòng 60 ngày tới.']);
        }
        if ($data['gio_hen'] < self::GIO_MO_CUA || $data['gio_hen'] > self::GIO_DONG_CUA) {
            throw ValidationException::withMessages(['gio_hen' => 'Showroom nhận lái thử từ ' . self::GIO_MO_CUA . ' đến ' . self::GIO_DONG_CUA . '.']);
        }

        if (empty($data['dong_xe']) && !empty($data['san_pham_id'])) {
            $data['dong_xe'] = DB::table('san_phams')->where('id', $data['san_pham_id'])->value('ten')
                ?? DB::table('san_phams')->where('id', $data['san_pham_id'])->value('ten_san_pham');
        }

        $user = $request->user('sanctum');

        try {
            $id = DB::table('dang_ky_lai_thus')->insertGetId([
                'ma_lai_thu' => 'LT-' . strtoupper(Str::random(8)),
                'user_id' => $user instanceof User ? $user->id : null,
                'san_pham_id' => $data['san_pham_id'] ?? null,
                'dong_xe' => $data['dong_xe'] ?? null,
                'ho_ten' => $data['ho_ten'],
                'email' => $data['email'],
                'so_dien_thoai' => $data['so_dien_thoai'],
                'ngay_hen' => $data['ngay_hen'],
                'gio_hen' => $data['gio_hen'],
                'dia_diem' => config('shop.showroom_dia_chi'),
                'ghi_chu' => $data['ghi_chu'] ?? null,
                'trang_thai' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Lỗi đăng ký lái thử: ' . $e->getMessage());

            return response()->json(['status' => false, 'message' => 'Không thể ghi nhận đăng ký lái thử. Vui lòng thử lại sau.'], 500);
        }

        $laiThu = DB::table('dang_ky_lai_thus')->where('id', $id)->first();
        $emailSent = $this->sendMail($laiThu, 'Xác nhận lịch lái thử Porsche');

        return response()->json([
            'status' => true,
            'message' => $emailSent
                ? 'Đã ghi nhận đăng ký. Thông tin thời gian và địa điểm lái thử đã được gửi tới email của bạn.'
                : 'Đã ghi nhận đăng ký. Nhân viên sẽ liên hệ với bạn qua hotline ' . config('shop.hotline') . '.',
            'data' => ['ma_lai_thu' => $laiThu->ma_lai_thu, 'email_da_gui' => $emailSent],
        ], 201);
    }

    /** Lịch lái thử của khách đang đăng nhập (đăng ký khi đã đăng nhập hoặc trùng email tài khoản). */
    public function cuaToi(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = DB::table('dang_ky_lai_thus')
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('email', $user->email))
            ->orderByDesc('id')
            ->get();

        return response()->json(['status' => true, 'data' => $data]);
    }

    public function adminData(Request $request): JsonResponse
    {
        $query = DB::table('dang_ky_lai_thus')->orderByDesc('id');
        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', (int) $request->input('trang_thai'));
        }
        if ($request->filled('tu_khoa')) {
            $key = '%' . $request->input('tu_khoa') . '%';
            $query->where(fn ($q) => $q->where('ma_lai_thu', 'like', $key)
                ->orWhere('ho_ten', 'like', $key)
                ->orWhere('email', 'like', $key)
                ->orWhere('so_dien_thoai', 'like', $key));
        }

        return response()->json(['status' => true, 'data' => $query->get()]);
    }

    /** Nhân viên xác nhận, đổi giờ/địa điểm, hoàn tất hoặc hủy; khách luôn được báo qua email. */
    public function adminCapNhat(Request $request): JsonResponse
    {
        $staff = $request->user();
        if (!($staff instanceof Admin) || !collect(self::MANAGE_ROLES)->contains(fn ($role) => $staff->hasRole($role))) {
            return response()->json(['status' => false, 'message' => 'Bạn không có quyền xử lý đăng ký lái thử.'], 403);
        }

        $data = $request->validate([
            'id' => 'required|integer|exists:dang_ky_lai_thus,id',
            'trang_thai' => 'nullable|integer|in:0,1,2,3',
            'ngay_hen' => 'nullable|date_format:Y-m-d',
            'gio_hen' => 'nullable|date_format:H:i',
            'dia_diem' => 'nullable|string|max:255',
            'ghi_chu_nhan_vien' => 'nullable|string|max:1000',
        ]);

        $current = DB::table('dang_ky_lai_thus')->where('id', $data['id'])->first();
        $update = collect($data)->except('id')->filter(fn ($value) => $value !== null)->all();
        if ($update === []) {
            return response()->json(['status' => false, 'message' => 'Không có thông tin nào để cập nhật.'], 422);
        }
        DB::table('dang_ky_lai_thus')->where('id', $current->id)->update($update + ['updated_at' => now()]);

        $laiThu = DB::table('dang_ky_lai_thus')->where('id', $current->id)->first();
        $scheduleChanged = $laiThu->ngay_hen !== $current->ngay_hen
            || $laiThu->gio_hen !== $current->gio_hen
            || $laiThu->dia_diem !== $current->dia_diem;

        if ((int) $laiThu->trang_thai === 3 && (int) $current->trang_thai !== 3) {
            $this->sendMail($laiThu, 'Lịch lái thử đã được hủy', 'Lịch lái thử của bạn đã được hủy. Bạn có thể đăng ký lại hoặc gọi hotline ' . config('shop.hotline') . ' để được hỗ trợ.');
        } elseif ($scheduleChanged || ((int) $laiThu->trang_thai === 1 && (int) $current->trang_thai !== 1)) {
            $note = $laiThu->ghi_chu_nhan_vien ? ' Ghi chú từ nhân viên: ' . $laiThu->ghi_chu_nhan_vien : '';
            $this->sendMail($laiThu, 'Cập nhật lịch lái thử Porsche', 'Lịch lái thử của bạn đã được cập nhật theo thông tin dưới đây.' . $note);
        }

        return response()->json(['status' => true, 'message' => 'Đã cập nhật đăng ký lái thử.', 'data' => $laiThu]);
    }

    private function sendMail(object $laiThu, string $subject, string $message = ''): bool
    {
        try {
            Mail::to($laiThu->email)->send(new LaiThuMail($laiThu, $subject, $message));

            return true;
        } catch (Throwable $e) {
            Log::error('Không thể gửi email lái thử: ' . $e->getMessage(), ['lai_thu_id' => $laiThu->id]);

            return false;
        }
    }
}
