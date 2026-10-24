<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Exception;

class ChuyenGiaController extends Controller
{
    // =========================================================================
    // I. API HỆ THỐNG QUẢN TRỊ (ADMIN MANAGEMENT)
    // =========================================================================

    public function getDataChuyenGia(): JsonResponse
    {
        try {
            $data = DB::table('chuyen_gias')
                ->select('id', 'ho_ten', 'email', 'so_dien_thoai', 'ma_so_tuner', 'kinh_nghiem', 'trang_thai', 'id_hang_phu_tung', 'created_at')
                ->orderBy('id', 'desc')
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getDataChuyenGia: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Không thể tải danh sách chuyên gia.'], 500);
        }
    }

    public function storeChuyenGia(Request $request): JsonResponse
    {
        $request->validate([
            'ho_ten'            => 'required|string|max:255',
            'email'             => 'required|email|unique:chuyen_gias,email',
            'so_dien_thoai'     => 'required|string|min:10|max:20',
            'password'          => 'required|string|min:6',
            'ma_so_tuner'       => 'required|string|unique:chuyen_gias,ma_so_tuner',
            'kinh_nghiem'       => 'nullable|string',
            'id_hang_phu_tung'  => 'required|integer'
        ]);

        try {
            DB::table('chuyen_gias')->insert([
                'ho_ten'            => $request->ho_ten,
                'email'             => $request->email,
                'so_dien_thoai'     => $request->so_dien_thoai,
                'password'          => Hash::make($request->password),
                'ma_so_tuner'       => strtoupper($request->ma_so_tuner),
                'kinh_nghiem'       => $request->kinh_nghiem,
                'id_hang_phu_tung'  => $request->id_hang_phu_tung,
                'trang_thai'        => 1,
                'created_at'        => now(),
                'updated_at'        => now()
            ]);
            return response()->json(['status' => true, 'message' => 'Thêm chuyên gia tuning thành công.'], 201);
        } catch (Exception $e) {
            Log::error('Lỗi storeChuyenGia: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi hệ thống, không thể tạo chuyên gia.'], 500);
        }
    }

    public function updateChuyenGia(Request $request): JsonResponse
    {
        $request->validate([
            'id'                => 'required|integer|exists:chuyen_gias,id',
            'ho_ten'            => 'required|string|max:255',
            'email'             => 'required|email|unique:chuyen_gias,email,' . $request->id,
            'so_dien_thoai'     => 'required|string|min:10|max:20',
            'ma_so_tuner'       => 'required|string|unique:chuyen_gias,ma_so_tuner,' . $request->id,
            'id_hang_phu_tung'  => 'required|integer',
            'password'          => 'nullable|string|min:6'
        ]);

        try {
            $updateData = [
                'ho_ten'            => $request->ho_ten,
                'email'             => $request->email,
                'so_dien_thoai'     => $request->so_dien_thoai,
                'ma_so_tuner'       => strtoupper($request->ma_so_tuner),
                'kinh_nghiem'       => $request->kinh_nghiem,
                'id_hang_phu_tung'  => $request->id_hang_phu_tung,
                'updated_at'        => now()
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            DB::table('chuyen_gias')->where('id', $request->id)->update($updateData);
            return response()->json(['status' => true, 'message' => 'Cập nhật thông tin chuyên gia thành công.'], 200);
        } catch (Exception $e) {
            Log::error('Lỗi updateChuyenGia: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Không thể cập nhật chuyên gia.'], 500);
        }
    }

    public function destroyChuyenGia(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer|exists:chuyen_gias,id']);
        DB::beginTransaction();
        try {
            DB::table('personal_access_tokens')->where('tokenable_id', $request->id)->where('tokenable_type', 'App\Models\ChuyenGia')->delete();
            DB::table('chuyen_gias')->where('id', $request->id)->delete();
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Xóa thông tin chuyên gia thành công.'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lỗi destroyChuyenGia: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi xung đột dữ liệu vận hành, không thể xóa chuyên gia.'], 500);
        }
    }

    public function searchChuyenGia(Request $request): JsonResponse
    {
        try {
            $key = $request->input('value');
            $data = DB::table('chuyen_gias')
                ->where('ho_ten', 'LIKE', "%{$key}%")
                ->orWhere('ma_so_tuner', 'LIKE', "%{$key}%")
                ->orWhere('email', 'LIKE', "%{$key}%")
                ->orderBy('id', 'desc')
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi tìm kiếm chuyên gia.'], 500);
        }
    }

    public function changeStatusChuyenGia(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer|exists:chuyen_gias,id']);
        try {
            $expert = DB::table('chuyen_gias')->where('id', $request->id)->first();
            $newStatus = ($expert->trang_thai == 1) ? 0 : 1;

            DB::table('chuyen_gias')->where('id', $request->id)->update([
                'trang_thai' => $newStatus,
                'updated_at' => now()
            ]);

            if ($newStatus === 0) {
                DB::table('personal_access_tokens')->where('tokenable_id', $request->id)->where('tokenable_type', 'like', '%ChuyenGia%')->delete();
            }

            return response()->json(['status' => true, 'trang_thai_moi' => $newStatus, 'message' => 'Thay đổi trạng thái chuyên gia thành công.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // II. API PHÂN HỆ CHO CHUYÊN GIA / TUNER ĐĂNG NHẬP & PHAN VÙNG
    // =========================================================================

    public function loginChuyenGia(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        try {
            $user = DB::table('chuyen_gias')->where('email', $request->email)->first();
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['status' => false, 'message' => 'Tài khoản hoặc mật khẩu không chính xác.'], 401);
            }
            if ($user->trang_thai != 1) {
                return response()->json(['status' => false, 'message' => 'Tài khoản chuyên gia đang bị khóa.'], 403);
            }

            $token = Str::random(60);
            DB::table('personal_access_tokens')->insert([
                'tokenable_type' => 'App\Models\ChuyenGia',
                'tokenable_id'   => $user->id,
                'name'           => 'ChuyenGia_AuthToken',
                'token'          => hash('sha256', $token),
                'abilities'      => json_encode(['*']),
                'created_at'     => now(),
                'updated_at'     => now()
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Chuyên gia đăng nhập thành công.',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => ['ho_ten' => $user->ho_ten, 'ma_so_tuner' => $user->ma_so_tuner]
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi kết nối phân hệ chuyên gia.'], 500);
        }
    }

    public function registerChuyenGia(Request $request): JsonResponse
    {
        $request->validate([
            'ho_ten'            => 'required|string|max:255',
            'email'             => 'required|email|unique:chuyen_gias,email',
            'so_dien_thoai'     => 'required|string|min:10|max:15',
            'password'          => 'required|string|min:6|confirmed',
            'ma_so_tuner'       => 'required|string|unique:chuyen_gias,ma_so_tuner',
            'id_hang_phu_tung'  => 'required|integer'
        ]);

        try {
            $maKichHoat = Str::upper(Str::random(6));
            DB::table('chuyen_gias')->insert([
                'ho_ten'            => $request->ho_ten,
                'email'             => $request->email,
                'so_dien_thoai'     => $request->so_dien_thoai,
                'password'          => Hash::make($request->password),
                'ma_so_tuner'       => strtoupper($request->ma_so_tuner),
                'id_hang_phu_tung'  => $request->id_hang_phu_tung,
                'trang_thai'        => 0, // Đợi duyệt kích hoạt
                'ma_kich_hoat'      => $maKichHoat,
                'created_at'        => now(),
                'updated_at'        => now()
            ]);
            return response()->json(['status' => true, 'message' => 'Đăng ký tài khoản chuyên gia thành công, vui lòng chờ Quản trị viên kích hoạt.'], 201);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Đăng ký thất bại.'], 500);
        }
    }

    public function kichHoatChuyenGia(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email|exists:chuyen_gias,email', 'ma_kich_hoat' => 'required|string']);
        try {
            $user = DB::table('chuyen_gias')->where('email', $request->email)->first();
            if ($user->ma_kich_hoat !== strtoupper($request->ma_kich_hoat)) {
                return response()->json(['status' => false, 'message' => 'Mã kích hoạt không hợp lệ.'], 400);
            }
            DB::table('chuyen_gias')->where('id', $user->id)->update(['trang_thai' => 1, 'ma_kich_hoat' => null, 'updated_at' => now()]);
            return response()->json(['status' => true, 'message' => 'Kích hoạt tài khoản Tuner thành công.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi xử lý kích hoạt.'], 500);
        }
    }

    public function quenMatKhauChuyenGia(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email|exists:chuyen_gias,email']);
        try {
            $tokenToken = Str::random(40);
            DB::table('password_resets')->updateOrInsert(['email' => $request->email], ['token' => $tokenToken, 'created_at' => now()]);
            // Thực tế sẽ dispatch Mail::send tại đây
            return response()->json(['status' => true, 'message' => 'Yêu cầu đặt lại mật khẩu thành công. Vui lòng kiểm tra hòm thư.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi kết nối mail service.'], 500);
        }
    }

    public function doiMatKhauChuyenGia(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email|exists:chuyen_gias,email', 'token' => 'required|string', 'password' => 'required|string|min:6|confirmed']);
        try {
            $check = DB::table('password_resets')->where('email', $request->email)->where('token', $request->token)->first();
            if (!$check) {
                return response()->json(['status' => false, 'message' => 'Token không chính xác hoặc đã hết hạn.'], 400);
            }
            DB::table('chuyen_gias')->where('email', $request->email)->update(['password' => Hash::make($request->password), 'updated_at' => now()]);
            DB::table('password_resets')->where('email', $request->email)->delete();
            return response()->json(['status' => true, 'message' => 'Thay đổi mật khẩu thành công.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi đổi mật khẩu.'], 500);
        }
    }

    public function logoutChuyenGia(Request $request): JsonResponse
    {
        try {
            $hashedToken = hash('sha256', $request->bearerToken());
            DB::table('personal_access_tokens')->where('token', $hashedToken)->delete();
            return response()->json(['status' => true, 'message' => 'Đăng xuất tài khoản chuyên gia thành công.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi đăng xuất phiên làm việc.'], 500);
        }
    }

    public function checkTokenChuyenGia(): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Token Chuyên gia hợp lệ.'], 200);
    }

    public function getDataKhachHang(Request $request): JsonResponse
    {
        try {
            $chuyenGiaId = $request->attributes->get('id_chuyen_gia') ?? $request->user()->id;
            // Lấy danh sách khách hàng có lịch hẹn trực tiếp với chuyên gia này
            $data = DB::table('khach_hangs')
                ->join('phieu_dat_lichs', 'khach_hangs.id', '=', 'phieu_dat_lichs.id_khach_hang')
                ->select('khach_hangs.id', 'khach_hangs.ho_ten', 'khach_hangs.email', 'khach_hangs.so_dien_thoai', 'phieu_dat_lichs.ma_dat_lich')
                ->where('phieu_dat_lichs.id_chuyen_gia', $chuyenGiaId)
                ->distinct()
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi kết xuất dữ liệu khách hàng thuộc quyền cố vấn.'], 500);
        }
    }

    public function getLichSuDoXe(Request $request): JsonResponse
    {
        $request->validate(['id_khach_hang' => 'required|integer']);
        try {
            $expertId = $request->attributes->get('id_chuyen_gia') ?? $request->user()->id;
            $hasAssignment = DB::table('phieu_dat_lichs')
                ->where('id_khach_hang', $request->id_khach_hang)
                ->where('id_chuyen_gia', $expertId)
                ->exists();
            if (!$hasAssignment) {
                return response()->json(['status' => false, 'message' => 'Bạn không được phân công phụ trách khách hàng này.'], 403);
            }

            $data = DB::table('ho_so_xes')
                ->where('id_khach_hang', $request->id_khach_hang)
                ->orderBy('id', 'desc')
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Không thể tải lịch sử độ xe.'], 500);
        }
    }

    public function getDataHoSoXeCuaChuyenGia(Request $request): JsonResponse
    {
        try {
            $expertId = $request->attributes->get('id_chuyen_gia') ?? $request->user()->id;
            $customerIds = DB::table('phieu_dat_lichs')
                ->where('id_chuyen_gia', $expertId)
                ->whereNotNull('id_khach_hang')
                ->select('id_khach_hang');
            $data = DB::table('ho_so_xes')
                ->whereIn('id_khach_hang', $customerIds)
                ->orderByDesc('id')
                ->get();

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Không thể tải hồ sơ xe của chuyên gia.', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Không thể tải hồ sơ xe.'], 500);
        }
    }

    public function createHoSoXe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_khach_hang' => 'required|integer|exists:khach_hangs,id',
            'bien_so' => 'required|string|max:50',
            'ten_xe' => 'required|string|max:255',
            'so_khung' => 'nullable|string|max:100',
            'thong_so_tuning' => 'nullable|string|max:5000',
        ]);
        $expertId = $request->attributes->get('id_chuyen_gia') ?? $request->user()->id;

        $hasAssignment = DB::table('phieu_dat_lichs')
            ->where('id_khach_hang', $validated['id_khach_hang'])
            ->where('id_chuyen_gia', $expertId)
            ->exists();
        if (!$hasAssignment) {
            return response()->json(['status' => false, 'message' => 'Bạn không được phân công phụ trách khách hàng này.'], 403);
        }

        try {
            DB::table('ho_so_xes')->insert([
                'id_khach_hang' => $validated['id_khach_hang'],
                'bien_so' => strtoupper($validated['bien_so']),
                'ten_xe' => $validated['ten_xe'],
                'so_khung' => strtoupper($validated['so_khung'] ?? ''),
                'thong_so_tuning' => $validated['thong_so_tuning'] ?? null,
                'trang_thai' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['status' => true, 'message' => 'Đã lập hồ sơ xe và lưu thông số tuning.'], 201);
        } catch (Exception $e) {
            Log::error('Không thể tạo hồ sơ xe từ tài khoản chuyên gia.', [
                'expert_id' => $expertId,
                'customer_id' => $validated['id_khach_hang'],
                'error' => $e->getMessage(),
            ]);
            return response()->json(['status' => false, 'message' => 'Không thể tạo hồ sơ xe.'], 500);
        }
    }

    public function updateHoSoXe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:ho_so_xes,id',
            'bien_so' => 'required|string|max:50',
            'ten_xe' => 'required|string|max:255',
            'so_khung' => 'nullable|string|max:100',
            'thong_so_tuning' => 'nullable|string|max:5000',
        ]);
        $expertId = $request->attributes->get('id_chuyen_gia') ?? $request->user()->id;

        $record = DB::table('ho_so_xes')->where('id', $validated['id'])->first();
        $hasAssignment = $record && DB::table('phieu_dat_lichs')
            ->where('id_khach_hang', $record->id_khach_hang)
            ->where('id_chuyen_gia', $expertId)
            ->exists();
        if (!$hasAssignment) {
            return response()->json(['status' => false, 'message' => 'Bạn không được phân công phụ trách hồ sơ xe này.'], 403);
        }

        try {
            DB::table('ho_so_xes')->where('id', $validated['id'])->update([
                'bien_so' => strtoupper($validated['bien_so']),
                'ten_xe' => $validated['ten_xe'],
                'so_khung' => strtoupper($validated['so_khung'] ?? ''),
                'thong_so_tuning' => $validated['thong_so_tuning'] ?? null,
                'updated_at' => now(),
            ]);

            return response()->json(['status' => true, 'message' => 'Đã cập nhật hồ sơ và thông số tuning.'], 200);
        } catch (Exception $e) {
            Log::error('Không thể cập nhật hồ sơ xe từ tài khoản chuyên gia.', [
                'expert_id' => $expertId,
                'vehicle_record_id' => $validated['id'],
                'error' => $e->getMessage(),
            ]);
            return response()->json(['status' => false, 'message' => 'Không thể cập nhật hồ sơ xe.'], 500);
        }
    }

    public function timKiemKhachHang(Request $request): JsonResponse
    {
        try {
            $key = $request->input('value');
            $data = DB::table('khach_hangs')
                ->where('ho_ten', 'LIKE', "%{$key}%")
                ->orWhere('so_dien_thoai', 'LIKE', "%{$key}%")
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Tìm kiếm thất bại.'], 500);
        }
    }

    public function getProfile(Request $request): JsonResponse
    {
        try {
            $chuyenGiaId = $request->attributes->get('id_chuyen_gia') ?? $request->user()->id;
            $profile = DB::table('chuyen_gias')->where('id', $chuyenGiaId)->first();
            unset($profile->password, $profile->ma_kich_hoat);
            return response()->json(['status' => true, 'data' => $profile], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi đọc hồ sơ Chuyên gia.'], 500);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $chuyenGiaId = $request->attributes->get('id_chuyen_gia') ?? $request->user()->id;
        $request->validate(['ho_ten' => 'required|string', 'so_dien_thoai' => 'required|string']);
        try {
            DB::table('chuyen_gias')->where('id', $chuyenGiaId)->update([
                'ho_ten'        => $request->ho_ten,
                'so_dien_thoai' => $request->so_dien_thoai,
                'kinh_nghiem'   => $request->kinh_nghiem,
                'updated_at'    => now()
            ]);
            return response()->json(['status' => true, 'message' => 'Cập nhật Profile chuyên gia thành công.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi đồng bộ.'], 500);
        }
    }

    public function updateProfileAvatar(Request $request): JsonResponse
    {
        $request->validate(['avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048']);
        try {
            $chuyenGiaId = $request->attributes->get('id_chuyen_gia') ?? $request->user()->id;
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $filename = 'tuner_' . $chuyenGiaId . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/tuners'), $filename);
                $avatarUrl = '/uploads/tuners/' . $filename;

                DB::table('chuyen_gias')->where('id', $chuyenGiaId)->update(['avatar' => $avatarUrl, 'updated_at' => now()]);
                return response()->json(['status' => true, 'message' => 'Cập nhật ảnh đại diện thành công.', 'avatar_url' => $avatarUrl], 200);
            }
            return response()->json(['status' => false, 'message' => 'Tệp không hợp lệ.'], 400);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi xử lý tệp ảnh.'], 500);
        }
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate(['old_password' => 'required|string', 'password' => 'required|string|min:6|confirmed']);
        try {
            $chuyenGiaId = $request->attributes->get('id_chuyen_gia') ?? $request->user()->id;
            $user = DB::table('chuyen_gias')->where('id', $chuyenGiaId)->first();
            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json(['status' => false, 'message' => 'Mật khẩu cũ không chính xác.'], 400);
            }
            DB::table('chuyen_gias')->where('id', $chuyenGiaId)->update(['password' => Hash::make($request->password), 'updated_at' => now()]);
            return response()->json(['status' => true, 'message' => 'Đổi mật khẩu thành công.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi nghiệp vụ bảo mật.'], 500);
        }
    }

    public function getDataLichHenDoXe(Request $request): JsonResponse
    {
        try {
            $chuyenGiaId = $request->attributes->get('id_chuyen_gia') ?? $request->user()->id;
            $data = DB::table('phieu_dat_lichs')
                ->leftJoin('khu_vuc_workshops', 'khu_vuc_workshops.id', '=', 'phieu_dat_lichs.id_khu_vuc_workshop')
                ->where('phieu_dat_lichs.id_chuyen_gia', $chuyenGiaId)
                ->select('phieu_dat_lichs.*', 'khu_vuc_workshops.ten_khu_vuc')
                ->orderBy('phieu_dat_lichs.ngay_dat', 'desc')
                ->orderBy('phieu_dat_lichs.gio_dat', 'desc')
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Không thể tải danh sách lịch đặt độ xe.'], 500);
        }
    }

    // Chuyên gia xử lý lịch của chính mình: xác nhận (1), hoàn thành (2), từ chối/hủy (3).
    public function capNhatTrangThaiLichHen(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'trang_thai' => 'required|integer|in:1,2,3',
            'ghi_chu_chuyen_gia' => 'nullable|string|max:1000',
        ]);
        $chuyenGiaId = $request->attributes->get('id_chuyen_gia') ?? $request->user()->id;

        $lich = DB::table('phieu_dat_lichs')->where('id', $validated['id'])->where('id_chuyen_gia', $chuyenGiaId)->first();
        if (!$lich) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy lịch hẹn được phân công cho bạn.'], 404);
        }

        $allowed = [0 => [1, 3], 1 => [2, 3]];
        if (!in_array((int) $validated['trang_thai'], $allowed[(int) $lich->trang_thai] ?? [], true)) {
            return response()->json(['status' => false, 'message' => 'Không thể chuyển lịch hẹn sang trạng thái này.'], 422);
        }

        DB::table('phieu_dat_lichs')->where('id', $lich->id)->update([
            'trang_thai' => $validated['trang_thai'],
            'giai_doan' => match ((int) $validated['trang_thai']) {
                2 => 'da_ban_giao',
                3 => 'cho_nhan_xe',
                default => $lich->giai_doan ?? 'cho_nhan_xe',
            },
            'ghi_chu_chuyen_gia' => $validated['ghi_chu_chuyen_gia'] ?? $lich->ghi_chu_chuyen_gia ?? null,
            'updated_at' => now(),
        ]);

        $subjects = [1 => 'Chuyên gia đã xác nhận lịch hẹn của bạn', 2 => 'Lịch hẹn của bạn đã hoàn thành', 3 => 'Lịch hẹn của bạn đã bị hủy'];
        try {
            if ($lich->email) {
                Mail::to($lich->email)->send(new \App\Mail\LichHenMail(DB::table('phieu_dat_lichs')->where('id', $lich->id)->first(), $subjects[(int) $validated['trang_thai']]));
            }
        } catch (\Throwable $e) {
            Log::warning('Không gửi được email cập nhật lịch hẹn.', ['error' => $e->getMessage()]);
        }

        return response()->json(['status' => true, 'message' => 'Đã cập nhật lịch hẹn.'], 200);
    }

    public function dangKyCaLamViec(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ngay_lam_viec' => 'required|date|after_or_equal:today',
            'ca_lam_viec' => 'required|string|in:Ca sáng (08:00 - 12:00),Ca chiều (13:00 - 17:00)',
        ]);
        $chuyenGiaId = $request->attributes->get('id_chuyen_gia') ?? $request->user()->id;

        DB::table('lich_lam_viecs')->updateOrInsert(
            ['id_chuyen_gia' => $chuyenGiaId, 'ngay_lam_viec' => $validated['ngay_lam_viec'], 'ca_lam_viec' => $validated['ca_lam_viec']],
            ['updated_at' => now(), 'created_at' => now()]
        );

        return response()->json(['status' => true, 'message' => 'Đã đăng ký ca làm việc.'], 201);
    }

    public function xoaCaLamViec(Request $request): JsonResponse
    {
        $validated = $request->validate(['id' => 'required|integer']);
        $chuyenGiaId = $request->attributes->get('id_chuyen_gia') ?? $request->user()->id;

        $ca = DB::table('lich_lam_viecs')->where('id', $validated['id'])->where('id_chuyen_gia', $chuyenGiaId)->first();
        if (!$ca) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy ca làm việc.'], 404);
        }
        $hasBooking = DB::table('phieu_dat_lichs')
            ->where('id_chuyen_gia', $chuyenGiaId)
            ->whereDate('ngay_dat', $ca->ngay_lam_viec)
            ->whereIn('trang_thai', [0, 1])
            ->exists();
        if ($hasBooking) {
            return response()->json(['status' => false, 'message' => 'Ngày này đã có lịch hẹn khách hàng, không thể xóa ca.'], 422);
        }

        DB::table('lich_lam_viecs')->where('id', $ca->id)->delete();
        return response()->json(['status' => true, 'message' => 'Đã xóa ca làm việc.'], 200);
    }

    public function getDataKhuVucWorkshop(): JsonResponse
    {
        try {
            $data = DB::table('khu_vuc_workshops')->where('trang_thai', 1)->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDataHangPhuTung(): JsonResponse
    {
        try {
            $data = DB::table('hang_phu_tungs')->where('trang_thai', 1)->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDataLichLamViecCaNhan(Request $request): JsonResponse
    {
        try {
            $chuyenGiaId = $request->attributes->get('id_chuyen_gia') ?? $request->user()->id;
            $data = DB::table('lich_lam_viecs')->where('id_chuyen_gia', $chuyenGiaId)
                ->whereDate('ngay_lam_viec', '>=', now()->toDateString())
                ->orderBy('ngay_lam_viec')->orderBy('ca_lam_viec')->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
