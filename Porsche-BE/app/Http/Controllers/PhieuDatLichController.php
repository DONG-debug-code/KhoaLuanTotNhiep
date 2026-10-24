<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Mail\LichHenMail;
use Carbon\Carbon;
use Exception;

class PhieuDatLichController extends Controller
{
    // =========================================================================
    // I. NHÓM API CHO HỆ THỐNG QUẢN TRỊ (ADMIN PANEL)
    // =========================================================================

    public function getLichHenData(): JsonResponse
    {
        try {
            $data = DB::table('phieu_dat_lichs')
                ->leftJoin('chuyen_gias', 'chuyen_gias.id', '=', 'phieu_dat_lichs.id_chuyen_gia')
                ->leftJoin('khu_vuc_workshops', 'khu_vuc_workshops.id', '=', 'phieu_dat_lichs.id_khu_vuc_workshop')
                ->select('phieu_dat_lichs.*', 'chuyen_gias.ho_ten as ten_chuyen_gia', 'khu_vuc_workshops.ten_khu_vuc')
                ->orderBy('phieu_dat_lichs.id', 'desc')
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
        }
    }

    public function timKiemLichHen(Request $request): JsonResponse
    {
        $request->validate(['tu_khoa' => 'required|string']);
        try {
            $key = $request->input('tu_khoa');
            $data = DB::table('phieu_dat_lichs')
                ->where('ma_dat_lich', 'LIKE', "%{$key}%")
                ->orWhere('ten_khach_hang', 'LIKE', "%{$key}%")
                ->orWhere('bien_so_xe', 'LIKE', "%{$key}%")
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Tìm kiếm thất bại.'], 500);
        }
    }

    public function createLichHen(Request $request): JsonResponse
    {
        $request->validate([
            'ten_khach_hang' => 'required|string|max:255',
            'email'          => 'required|email',
            'dien_thoai'     => 'required|string|max:15',
            'bien_so_xe'     => 'required|string',
            'ngay_dat'       => 'required|date|after_or_equal:today',
            'gio_dat'        => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $maVoucher = 'PORSCHE-' . strtoupper(uniqid());

            $id = DB::table('phieu_dat_lichs')->insertGetId([
                'ma_dat_lich'    => $maVoucher,
                'ten_khach_hang' => $request->ten_khach_hang,
                'email'          => $request->email,
                'dien_thoai'     => $request->dien_thoai,
                'bien_so_xe'     => $request->bien_so_xe,
                'ngay_dat'       => $request->ngay_dat,
                'gio_dat'        => $request->gio_dat,
                'ghi_chu'        => $request->ghi_chu ?? '',
                'trang_thai'     => 0, // 0: Chờ xác nhận/Chờ thanh toán
                'created_at'     => Carbon::now(),
                'updated_at'     => Carbon::now(),
            ]);

            $lichHen = DB::table('phieu_dat_lichs')->where('id', $id)->first();

            // Dispatch Mail gửi đi thực tế qua Queue để tối ưu hiệu năng
            Mail::to($lichHen->email)->queue(new LichHenMail($lichHen, 'Xác nhận đặt lịch hẹn thành công!'));

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Tạo lịch hẹn thành công.', 'data' => $lichHen], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Lỗi tạo lịch hẹn: ' . $e->getMessage()], 500);
        }
    }

    public function xacNhanLichHen(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer']);
        try {
            $lichHen = DB::table('phieu_dat_lichs')->where('id', $request->id)->first();
            if (!$lichHen) {
                return response()->json(['status' => false, 'message' => 'Lịch hẹn không tồn tại.'], 404);
            }

            DB::table('phieu_dat_lichs')->where('id', $request->id)->update([
                'trang_thai' => 1, // 1: Đang thực hiện / Đã tiếp nhận vào xưởng
                'updated_at' => Carbon::now()
            ]);

            return response()->json(['status' => true, 'message' => 'Đã tiếp nhận xe vào Bay dịch vụ thành công.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi xác nhận lịch.'], 500);
        }
    }

    public function phanCongLichHen(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:phieu_dat_lichs,id',
            'id_chuyen_gia' => 'required|integer|exists:chuyen_gias,id',
            'id_khu_vuc_workshop' => 'required|integer|exists:khu_vuc_workshops,id',
        ]);

        try {
            DB::table('phieu_dat_lichs')->where('id', $validated['id'])->update([
                'id_chuyen_gia' => $validated['id_chuyen_gia'],
                'id_khu_vuc_workshop' => $validated['id_khu_vuc_workshop'],
                'updated_at' => Carbon::now(),
            ]);

            return response()->json(['status' => true, 'message' => 'Phân công lịch hẹn thành công.'], 200);
        } catch (Exception $e) {
            Log::error('Lỗi phân công lịch hẹn: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Không thể phân công lịch hẹn.'], 500);
        }
    }

    public function hoanThanhLichHen(Request $request): JsonResponse
    {
        $request->validate([
            'id'           => 'required|integer',
            'tong_tien'    => 'required|numeric|min:0',
            'chi_tiet_kt'  => 'required|array' // Danh sách phụ tùng thay thế/nội dung sửa chữa chi tiết
        ]);

        DB::beginTransaction();
        try {
            $lichHen = DB::table('phieu_dat_lichs')->where('id', $request->id)->first();
            if (!$lichHen || $lichHen->trang_thai != 1) {
                return response()->json(['status' => false, 'message' => 'Lịch hẹn không hợp lệ hoặc chưa được tiếp nhận xử lý.'], 400);
            }

            // 1. Cập nhật trạng thái lịch hẹn thành Đã Hoàn Thành (2)
            DB::table('phieu_dat_lichs')->where('id', $request->id)->update([
                'trang_thai' => 2,
                'updated_at' => Carbon::now()
            ]);

            // 2. Tạo tự động hóa đơn tài chính đi kèm cho xe nghiệm thu
            $maHoaDon = 'HD-' . time();
            $hoaDonId = DB::table('hoa_dons')->insertGetId([
                'id_phieu_dat_lich' => $lichHen->id,
                'ma_hoa_don'        => $maHoaDon,
                'tong_tien'         => $request->tong_tien,
                'trang_thai'        => 1, // 1: Đã thanh toán (Nghiệm thu xuất xưởng)
                'created_at'        => Carbon::now(),
                'updated_at'        => Carbon::now()
            ]);

            // 3. Insert dữ liệu thô vào bảng chi tiết hóa đơn dịch vụ kỹ thuật
            foreach ($request->chi_tiet_kt as $item) {
                DB::table('hoa_don_chi_tiets')->insert([
                    'id_hoa_don'   => $hoaDonId,
                    'ten_vat_tu'   => $item['ten_vat_tu'], // Ví dụ: Bugi Bosch, Lọc nhớt Porsche OEM...
                    'so_luong'     => $item['so_luong'],
                    'don_gia'      => $item['don_gia'],
                    'thanh_tien'   => $item['so_luong'] * $item['don_gia'],
                    'created_at'   => Carbon::now(),
                ]);
            }

            Mail::to($lichHen->email)->queue(new LichHenMail($lichHen, 'Xe của bạn đã bảo dưỡng xong - Hóa đơn điện tử'));

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Nghiệm thu xuất xưởng thành công. Hóa đơn đã được chốt.'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Lỗi kết toán xuất xưởng: ' . $e->getMessage()], 500);
        }
    }

    public function huyLichHen(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer']);
        try {
            $lichHen = DB::table('phieu_dat_lichs')->where('id', $request->id)->first();
            if (!$lichHen) {
                return response()->json(['status' => false, 'message' => 'Lịch hẹn không tồn tại.'], 404);
            }

            DB::table('phieu_dat_lichs')->where('id', $request->id)->update([
                'trang_thai' => 3, // 3: Đã hủy bỏ hoàn toàn
                'updated_at' => Carbon::now()
            ]);

            Mail::to($lichHen->email)->queue(new LichHenMail($lichHen, 'Thông báo hủy lịch hẹn dịch vụ'));

            return response()->json(['status' => true, 'message' => 'Hủy lịch hẹn thành công.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi hệ thống khi hủy lịch.'], 500);
        }
    }

    public function changeStatusLichHen(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'trang_thai' => 'required|integer|in:0,1,2,3'
        ]);
        try {
            DB::table('phieu_dat_lichs')->where('id', $request->id)->update([
                'trang_thai' => $request->trang_thai,
                'updated_at' => Carbon::now()
            ]);
            return response()->json(['status' => true, 'message' => 'Cập nhật trạng thái nhanh thành công.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Không thể đảo trạng thái.'], 500);
        }
    }


    // =========================================================================
    // II. NHÓM API PORTAL KHÁCH HÀNG (CLIENT INTERFACE PRODUCTION)
    // =========================================================================

    public function getWorkshopBookingOptions(): JsonResponse
    {
        try {
            return response()->json([
                'status' => true,
                'data' => [
                    'chuyen_gias' => DB::table('chuyen_gias')
                        ->where('trang_thai', 1)
                        ->when(Schema::hasColumn('chuyen_gias', 'tinh_trang'), fn ($query) => $query->where('tinh_trang', '!=', 'tam-nghi'))
                        ->select('id', 'ho_ten', 'ma_so_tuner')
                        ->orderBy('ho_ten')
                        ->get(),
                    'khu_vuc_workshops' => DB::table('khu_vuc_workshops')
                        ->where('trang_thai', 1)
                        ->select('id', 'ten_khu_vuc')
                        ->orderBy('ten_khu_vuc')
                        ->get(),
                    'delivery_max_subtotal' => (int) config('services.checkout.delivery_max_subtotal'),
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Không thể tải lựa chọn đặt lịch Workshop.', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Không thể tải lịch chuyên gia và khu vực Workshop.'], 500);
        }
    }

    public function datLichKhamBenhNhan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bien_so_xe' => 'required|string|max:50',
            'ten_xe' => 'required|string|max:255',
            'ngay_dat' => 'required|date|after_or_equal:today',
            'gio_dat' => 'required|date_format:H:i',
            'ghi_chu' => 'nullable|string|max:1000',
            'loai_lich' => 'nullable|string|in:tu-van,do-xe',
            'id_chuyen_gia' => 'required|integer|exists:chuyen_gias,id',
            'id_khu_vuc_workshop' => 'nullable|integer|exists:khu_vuc_workshops,id',
        ]);

        $user = $request->user();
        $customerId = $request->attributes->get('id_khach_hang') ?? $user->id;
        if (Carbon::parse($validated['ngay_dat'] . ' ' . $validated['gio_dat'])->isPast()) {
            return response()->json(['status' => false, 'message' => 'Khung giờ đặt lịch phải ở thời điểm trong tương lai.'], 422);
        }

        try {
            $result = DB::transaction(function () use ($validated, $user, $customerId) {
                $specialist = DB::table('chuyen_gias')
                    ->where('id', $validated['id_chuyen_gia'])
                    ->where('trang_thai', 1)
                    ->lockForUpdate()
                    ->first();
                $workshop = empty($validated['id_khu_vuc_workshop']) ? null : DB::table('khu_vuc_workshops')
                    ->where('id', $validated['id_khu_vuc_workshop'])
                    ->where('trang_thai', 1)
                    ->lockForUpdate()
                    ->first();
                if (!$specialist || (!empty($validated['id_khu_vuc_workshop']) && !$workshop)) {
                    return ['error' => 'Chuyên gia hoặc Bay Workshop không còn hoạt động.'];
                }
                if (($specialist->tinh_trang ?? null) === 'tam-nghi') {
                    return ['error' => 'Chuyên gia đang tạm nghỉ, vui lòng chọn chuyên gia khác.'];
                }
                if (!$this->expertWorksAt($specialist->id, $validated['ngay_dat'], $validated['gio_dat'])) {
                    return ['error' => 'Chuyên gia không có ca làm việc ở khung giờ này.'];
                }

                $slotQuery = DB::table('phieu_dat_lichs')
                    ->whereDate('ngay_dat', $validated['ngay_dat'])
                    ->where('gio_dat', $validated['gio_dat'])
                    ->whereIn('trang_thai', [0, 1]);
                if ((clone $slotQuery)->where('id_chuyen_gia', $specialist->id)->exists()) {
                    return ['error' => 'Chuyên gia đã kín lịch ở khung giờ này.'];
                }
                if ($workshop && (clone $slotQuery)->where('id_khu_vuc_workshop', $workshop->id)->exists()) {
                    return ['error' => 'Bay Workshop đã kín lịch ở khung giờ này.'];
                }

                $vehicleDescription = trim($validated['ten_xe'] . ' · ' . strtoupper($validated['bien_so_xe']));
                $appointmentId = DB::table('phieu_dat_lichs')->insertGetId([
                    'ma_dat_lich' => 'PORSCHE-' . strtoupper(Str::random(8)),
                    'id_khach_hang' => $customerId,
                    'id_chuyen_gia' => $specialist->id,
                    'id_khu_vuc_workshop' => $workshop?->id,
                    'loai_lich' => $validated['loai_lich'] ?? 'tu-van',
                    'ten_khach_hang' => $user->ho_ten ?? $user->name ?? '',
                    'email' => $user->email,
                    'dien_thoai' => $user->so_dien_thoai ?? '',
                    'bien_so_xe' => strtoupper($validated['bien_so_xe']),
                    'thong_tin_xe' => $vehicleDescription,
                    'ngay_dat' => $validated['ngay_dat'],
                    'gio_dat' => $validated['gio_dat'],
                    'ghi_chu' => $validated['ghi_chu'] ?? '',
                    'trang_thai' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return ['appointment' => DB::table('phieu_dat_lichs')->where('id', $appointmentId)->first()];
            });

            if (isset($result['error'])) {
                return response()->json(['status' => false, 'message' => $result['error']], 422);
            }

            try {
                if (!empty($result['appointment']->email)) {
                    Mail::to($result['appointment']->email)->send(new LichHenMail($result['appointment'], 'Xác nhận đặt lịch với chuyên gia Porsche Workshop'));
                }
            } catch (\Throwable $mailError) {
                Log::warning('Không gửi được email đặt lịch chuyên gia.', ['error' => $mailError->getMessage()]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Đã ghi nhận yêu cầu đặt lịch với chuyên gia.',
                'data' => $result['appointment'],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Không thể đặt lịch Workshop cho khách hàng.', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['status' => false, 'message' => 'Không thể tạo lịch hẹn. Vui lòng thử lại sau.'], 500);
        }
    }

    // Ca làm có dạng "Ca sáng (08:00 - 12:00)"; chuyên gia chưa khai báo lịch ngày đó thì không nhận được lịch.
    private function expertWorksAt(int $expertId, string $date, string $time): bool
    {
        $shifts = DB::table('lich_lam_viecs')
            ->where('id_chuyen_gia', $expertId)
            ->whereDate('ngay_lam_viec', $date)
            ->pluck('ca_lam_viec');

        foreach ($shifts as $shift) {
            if (!preg_match('/(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})/', $shift, $m)) {
                return true;
            }
            $start = sprintf('%02d:%02d', $m[1], $m[2]);
            $end = sprintf('%02d:%02d', $m[3], $m[4]);
            if ($time >= $start && $time < $end) {
                return true;
            }
        }

        return false;
    }

    public function getKhungGioChuyenGia(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate(['ngay' => 'required|date|after_or_equal:today']);
        $booked = DB::table('phieu_dat_lichs')
            ->where('id_chuyen_gia', $id)
            ->whereDate('ngay_dat', $validated['ngay'])
            ->whereIn('trang_thai', [0, 1])
            ->pluck('gio_dat')
            ->map(fn ($time) => substr($time, 0, 5))
            ->all();

        $slots = [];
        foreach (['08:00', '09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'] as $time) {
            $past = Carbon::parse($validated['ngay'] . ' ' . $time)->isPast();
            $slots[] = [
                'gio' => $time,
                'con_trong' => !$past && !in_array($time, $booked, true) && $this->expertWorksAt($id, $validated['ngay'], $time),
            ];
        }

        return response()->json(['status' => true, 'data' => $slots], 200);
    }

    public function huyLichKhamBenhNhan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $customerId = $request->attributes->get('id_khach_hang') ?? $request->user()->id;

        $appointment = DB::table('phieu_dat_lichs')
            ->where('id', $validated['id'])
            ->where('id_khach_hang', $customerId)
            ->first();

        if (!$appointment) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy lịch hẹn của tài khoản này.'], 404);
        }
        if (in_array((int) $appointment->trang_thai, [2, 3], true)) {
            return response()->json(['status' => false, 'message' => 'Lịch đã hoàn thành hoặc đã hủy, không thể thay đổi.'], 422);
        }
        if ((int) $appointment->trang_thai === 1) {
            return response()->json(['status' => false, 'message' => 'Lịch đã được tiếp nhận vào Workshop, vui lòng liên hệ showroom để được hỗ trợ.'], 422);
        }

        DB::table('phieu_dat_lichs')->where('id', $appointment->id)->update([
            'trang_thai' => 3,
            'updated_at' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Đã hủy lịch hẹn.'], 200);
    }

    public function datLichKhamXe(Request $request): JsonResponse
    {
        $request->validate([
            'ten_khach_hang' => 'required|string|max:255',
            'email'          => 'required|email|max:255',
            'dien_thoai'     => 'required|string|max:15',
            'bien_so_xe'     => 'required|string|max:50',
            'ngay_dat'       => 'required|date|after:today',
            'gio_dat'        => 'required|string',
            'ghi_chu'        => 'nullable|string|max:1000'
        ]);

        DB::beginTransaction();
        try {
            // Tự động kiểm tra khách hàng đã tồn tại trong hệ thống chưa (qua Số điện thoại)
            $khachHang = DB::table('khach_hangs')
                ->where('so_dien_thoai', $request->dien_thoai)
                ->first();

            if (!$khachHang) {
                $idKhachHang = DB::table('khach_hangs')->insertGetId([
                    'ho_ten'         => $request->ten_khach_hang,
                    'email'          => $request->email,
                    'so_dien_thoai'  => $request->dien_thoai,
                    'created_at'     => now(),
                    'updated_at'     => now()
                ]);
            } else {
                $idKhachHang = $khachHang->id;

                DB::table('khach_hangs')->where('id', $idKhachHang)->update([
                    'email'      => $request->email,
                    'ho_ten'     => $request->ten_khach_hang,
                    'updated_at' => now()
                ]);
            }

            // Tạo mã đặc quyền đặt lịch độc bản
            $maVoucher = 'PORSCHE-' . strtoupper(Str::random(8));

            $idPhieu = DB::table('phieu_dat_lichs')->insertGetId([
                'ma_dat_lich'    => $maVoucher,
                'id_khach_hang'  => $idKhachHang,
                'ten_khach_hang' => $request->ten_khach_hang,
                'email'          => $request->email,
                'dien_thoai'     => $request->dien_thoai,
                'bien_so_xe'     => $request->bien_so_xe,
                'ngay_dat'       => $request->ngay_dat,
                'gio_dat'        => $request->gio_dat,
                'ghi_chu'        => $request->ghi_chu ?? '',
                'trang_thai'     => 0,
                'created_at'     => now(),
                'updated_at'     => now()
            ]);

            $lichHen = DB::table('phieu_dat_lichs')->where('id', $idPhieu)->first();

            // Kích hoạt gửi Email Luxury xác nhận đến khách hàng ngay lập tức
            Mail::to($lichHen->email)->queue(new LichHenMail($lichHen, 'Xác Nhận Đăng Ký Đặc Quyền Bảo Dưỡng Porsche'));

            DB::commit();
            return response()->json([
                'status'  => true,
                'message' => 'Hệ thống đã ghi nhận lịch hẹn đặc quyền của Quý khách.',
                'data'    => [
                    'ma_dat_lich' => $lichHen->ma_dat_lich,
                    'ngay_dat'    => $lichHen->ngay_dat,
                    'gio_dat'     => $lichHen->gio_dat
                ]
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lỗi thực tế tại datLichKhamXe: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Hệ thống đang bận, Quý khách vui lòng thử lại sau ít phút.'
            ], 500);
        }
    }

    public function huyLichKhamXe(Request $request): JsonResponse
    {
        $request->validate([
            'ma_dat_lich'   => 'required|string',
            'so_dien_thoai' => 'required|string'
        ]);

        try {
            $lichHen = DB::table('phieu_dat_lichs')
                ->where('ma_dat_lich', strtoupper($request->ma_dat_lich))
                ->where('dien_thoai', $request->so_dien_thoai)
                ->first();

            if (!$lichHen) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Thông tin xác thực lịch hẹn không chính xác. Vui lòng kiểm tra lại.'
                ], 422);
            }

            if ($lichHen->trang_thai == 1 || $lichHen->trang_thai == 2) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Xe của Quý khách đã được tiếp nhận vào khu vực kỹ thuật, không thể hủy lịch vào lúc này.'
                ], 400);
            }

            if ($lichHen->trang_thai == 3) {
                return response()->json([
                    'status'  => true,
                    'message' => 'Lịch hẹn này đã được ghi nhận hủy bỏ trên hệ thống từ trước.'
                ], 200);
            }

            DB::table('phieu_dat_lichs')
                ->where('id', $lichHen->id)
                ->update([
                    'trang_thai' => 3,
                    'updated_at' => now()
                ]);

            Mail::to($lichHen->email)->queue(new LichHenMail($lichHen, 'Thông Báo Hủy Lịch Hẹn Đặc Quyền Đã Hoàn Tất'));

            return response()->json([
                'status'  => true,
                'message' => 'Hủy lịch hẹn đặc quyền thành công. Porsche Workshop rất tiếc vì lỡ hẹn cùng Quý khách lần này.'
            ], 200);
        } catch (Exception $e) {
            Log::error('Lỗi thực tế tại huyLichKhamXe: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Không thể thực hiện tác vụ hủy lịch, vui lòng liên hệ Hotline CSKH.'
            ], 500);
        }
    }

    public function kiemTraMaDatLich(Request $request): JsonResponse
    {
        $request->validate(['ma_dat_lich' => 'required|string']);
        try {
            $data = DB::table('phieu_dat_lichs')
                ->where('ma_dat_lich', $request->ma_dat_lich)
                ->first();

            if (!$data) {
                return response()->json(['status' => false, 'message' => 'Mã đặt lịch không tồn tại trên hệ thống Porsche.'], 404);
            }

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi tra cứu dữ liệu.'], 500);
        }
    }

    public function hoaDonChiTiet(Request $request): JsonResponse
    {
        $request->validate(['id_phieu_dat_lich' => 'required|integer']);
        try {
            $hoaDon = DB::table('hoa_dons')
                ->where('id_phieu_dat_lich', $request->id_phieu_dat_lich)
                ->first();

            if (!$hoaDon) {
                return response()->json(['status' => false, 'message' => 'Lịch hẹn này chưa được kết toán xuất hóa đơn.'], 404);
            }

            $chiTiet = DB::table('hoa_don_chi_tiets')
                ->where('id_hoa_don', $hoaDon->id)
                ->get();

            return response()->json([
                'status'   => true,
                'hoa_don'  => $hoaDon,
                'chi_tiet' => $chiTiet
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi kết xuất chi tiết hóa đơn.'], 500);
        }
    }

    public function getKySuTheoHangPhuTung(Request $request): JsonResponse
    {
        $request->validate(['id_hang' => 'required|integer']);
        try {
            $data = DB::table('chuyen_gias')
                ->where('id_hang_phu_tung', $request->id_hang)
                ->where('trang_thai', 1)
                ->select('id', 'ho_ten', 'ma_so_tuner', 'kinh_nghiem')
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi khi lấy danh sách kỹ sư.'], 500);
        }
    }
}
