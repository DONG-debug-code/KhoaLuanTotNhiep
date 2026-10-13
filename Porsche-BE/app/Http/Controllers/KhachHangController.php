<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Exception;

class KhachHangController extends Controller
{
    // =========================================================================
    // I. API HỆ THỐNG QUẢN TRỊ (ADMIN) & CHUYÊN GIA ĐIỀU HÀNH KHÁCH HÀNG
    // =========================================================================

    public function getDataKhachHang(): JsonResponse
    {
        try {
            // Đã đổi khach_hangs -> users (chỉ lấy người dùng có vai_tro_id = 3 là khách hàng)
            $data = DB::table('users')
                ->where('vai_tro_id', 3)
                ->select('id', 'ho_ten', 'email', 'so_dien_thoai', 'dia_chi', 'trang_thai', 'created_at')
                ->orderBy('id', 'desc')
                ->get();

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getDataKhachHang: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Không thể tải danh sách khách hàng.'], 500);
        }
    }

    public function storeKhachHang(Request $request): JsonResponse
    {
        $request->validate([
            'ho_ten'        => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'so_dien_thoai' => 'required|string|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20',
            'password'      => 'required|string|min:6',
            'dia_chi'       => 'nullable|string|max:500'
        ]);

        try {
            DB::table('users')->insert([
                'ho_ten'        => $request->ho_ten,
                'email'         => $request->email,
                'so_dien_thoai' => $request->so_dien_thoai,
                'password'      => Hash::make($request->password),
                'dia_chi'       => $request->dia_chi,
                'vai_tro_id'     => 3, // Mặc định là vai trò Khách hàng
                'trang_thai'    => 1,
                'created_at'    => now(),
                'updated_at'    => now()
            ]);

            return response()->json(['status' => true, 'message' => 'Thêm tài khoản khách hàng thành công.'], 201);
        } catch (Exception $e) {
            Log::error('Lỗi storeKhachHang: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi hệ thống, không thể tạo khách hàng.'], 500);
        }
    }

    public function updateKhachHang(Request $request): JsonResponse
    {
        $request->validate([
            'id'            => 'required|integer|exists:users,id',
            'ho_ten'        => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $request->id,
            'so_dien_thoai' => 'required|string|min:10|max:20',
            'dia_chi'       => 'nullable|string|max:500',
            'password'      => 'nullable|string|min:6'
        ]);

        try {
            $updateData = [
                'ho_ten'        => $request->ho_ten,
                'email'         => $request->email,
                'so_dien_thoai' => $request->so_dien_thoai,
                'dia_chi'       => $request->dia_chi,
                'updated_at'    => now()
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            DB::table('users')->where('id', $request->id)->update($updateData);

            return response()->json(['status' => true, 'message' => 'Cập nhật thông tin khách hàng thành công.'], 200);
        } catch (Exception $e) {
            Log::error('Lỗi updateKhachHang: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Không thể cập nhật thông tin khách hàng.'], 500);
        }
    }

    public function destroyKhachHang(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer|exists:users,id']);

        DB::beginTransaction();
        try {
            DB::table('personal_access_tokens')
                ->where('tokenable_id', $request->id)
                ->where('tokenable_type', 'App\Models\User') // 👈 Đổi App\Models\User
                ->delete();

            DB::table('users')->where('id', $request->id)->delete();

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Xóa vĩnh viễn tài khoản khách hàng thành công.'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lỗi destroyKhachHang: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi xung đột dữ liệu lịch sử, không thể xóa khách hàng này.'], 500);
        }
    }

    public function changeStatusKhachHang(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer|exists:users,id']);
        try {
            $user = DB::table('users')->where('id', $request->id)->first();
            $newStatus = ($user->trang_thai == 1) ? 0 : 1;

            DB::table('users')->where('id', $request->id)->update([
                'trang_thai' => $newStatus,
                'updated_at' => now()
            ]);

            if ($newStatus === 0) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_id', $request->id)
                    ->where('tokenable_type', 'App\Models\User')
                    ->delete();
            }

            return response()->json([
                'status' => true,
                'trang_thai_moi' => $newStatus,
                'message' => 'Thay đổi trạng thái và xử lý phiên đăng nhập thành công.'
            ], 200);
        } catch (Exception $e) {
            Log::error('Lỗi changeStatusKhachHang: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function searchKhachHang(Request $request): JsonResponse
    {
        try {
            $key = $request->input('value');
            $data = DB::table('users')
                ->where('vai_tro_id', 3)
                ->where(function ($query) use ($key) {
                    $query->where('ho_ten', 'LIKE', "%{$key}%")
                        ->orWhere('email', 'LIKE', "%{$key}%")
                        ->orWhere('so_dien_thoai', 'LIKE', "%{$key}%");
                })
                ->orderBy('id', 'desc')
                ->get();

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi tìm kiếm nâng cao.'], 500);
        }
    }

    public function lichSuDoXeById($id): JsonResponse
    {
        try {
            $data = DB::table('ho_so_xes')
                ->join('phieu_dat_lichs', 'ho_so_xes.id_phieu_dat_lich', '=', 'phieu_dat_lichs.id')
                ->join('chuyen_gias', 'phieu_dat_lichs.id_chuyen_gia', '=', 'chuyen_gias.id')
                ->select('ho_so_xes.*', 'chuyen_gias.ho_ten as ten_chuyen_gia', 'phieu_dat_lichs.ma_dat_lich')
                ->where('ho_so_xes.id_khach_hang', $id)
                ->orderBy('ho_so_xes.id', 'desc')
                ->get();

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Không thể kết xuất lịch sử nâng cấp cấu hình xe.'], 500);
        }
    }


    // =========================================================================
    // II. API PHÂN HỆ PORTAL KHÁCH HÀNG & THỦ TỤC XÁC THỰC
    // =========================================================================

    public function loginKhachHang(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string'
        ]);

        try {
            $user = DB::table('users')->where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['status' => false, 'message' => 'Thông tin đăng nhập không chính xác.'], 401);
            }

            if ($user->trang_thai != 1) {
                return response()->json(['status' => false, 'message' => 'Tài khoản của bạn đã bị khóa hoặc chưa được kích hoạt.'], 403);
            }

            $token = Str::random(60);
            DB::table('personal_access_tokens')->insert([
                'tokenable_type' => 'App\Models\User', // 👈 Đã sửa thành App\Models\User
                'tokenable_id'   => $user->id,
                'name'           => 'KhachHang_AuthToken',
                'token'          => hash('sha256', $token),
                'abilities'      => json_encode(['*']),
                'created_at'     => now(),
                'updated_at'     => now()
            ]);

            return response()->json([
                'status'       => true,
                'message'      => 'Đăng nhập thành công.',
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => [
                    'id'     => $user->id,
                    'ho_ten' => $user->ho_ten,
                    'email'  => $user->email
                ]
            ], 200);
        } catch (Exception $e) {
            Log::error('Lỗi loginKhachHang: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi kết nối máy chủ xác thực.'], 500);
        }
    }

    public function registerKhachHang(Request $request): JsonResponse
    {
        $request->validate([
            'ho_ten'        => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'so_dien_thoai' => 'required|string|min:10|max:15',
            'password'      => 'required|string|min:6|confirmed'
        ]);

        DB::beginTransaction();
        try {
            $maKichHoat = Str::upper(Str::random(6));

            $khachHangId = DB::table('users')->insertGetId([
                'ho_ten'        => $request->ho_ten,
                'email'         => $request->email,
                'so_dien_thoai' => $request->so_dien_thoai,
                'password'      => Hash::make($request->password),
                'vai_tro_id'    => 3, // Khách hàng
                'trang_thai'    => 0, // Chờ kích hoạt
                'ma_kich_hoat'  => $maKichHoat,
                'created_at'    => now(),
                'updated_at'    => now()
            ]);

            Mail::send('emails.kich_hoat', ['ho_ten' => $request->ho_ten, 'ma' => $maKichHoat], function ($message) use ($request) {
                $message->to($request->email)->subject('🏁 Kích hoạt tài khoản Porsche Workshop Portal');
            });

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Đăng ký thành công. Vui lòng kiểm tra Email để nhận mã kích hoạt.'], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lỗi registerKhachHang: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Đăng ký thất bại. Xin thử lại sau.'], 500);
        }
    }

    public function kichHoatKhachHang(Request $request): JsonResponse
    {
        $request->validate([
            'email'        => 'required|email|exists:users,email',
            'ma_kich_hoat' => 'required|string|size:6'
        ]);

        try {
            $user = DB::table('users')->where('email', $request->email)->first();

            if ($user->ma_kich_hoat !== strtoupper($request->ma_kich_hoat)) {
                return response()->json(['status' => false, 'message' => 'Mã xác nhận không trùng khớp.'], 400);
            }

            DB::table('users')->where('id', $user->id)->update([
                'trang_thai'   => 1,
                'ma_kich_hoat' => null,
                'updated_at'   => now()
            ]);

            return response()->json(['status' => true, 'message' => 'Kích hoạt tài khoản thành công. Bạn hiện có thể đăng nhập.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Không thể kích hoạt tài khoản vào lúc này.'], 500);
        }
    }

    public function quenMatKhauKhachHang(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        try {
            $tokenToken = Str::random(40);

            DB::table('password_resets')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token'      => $tokenToken,
                    'created_at' => now()
                ]
            );

            Mail::send('emails.quen_mat_khau', ['token' => $tokenToken], function ($message) use ($request) {
                $message->to($request->email)->subject('🔑 Khôi phục mật khẩu Porsche Workshop');
            });

            return response()->json(['status' => true, 'message' => 'Hệ thống đã điều hướng link khôi phục mật khẩu vào Email của bạn.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Trục trặc dịch vụ Mail OTP.'], 500);
        }
    }

    public function doiMatKhauKhachHang(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'token'    => 'required|string',
            'password' => 'required|string|min:6|confirmed'
        ]);

        try {
            $resetRecord = DB::table('password_resets')
                ->where('email', $request->email)
                ->where('token', $request->token)
                ->first();

            if (!$resetRecord) {
                return response()->json(['status' => false, 'message' => 'Mã xác thực đổi mật khẩu đã hết hạn hoặc không đúng.'], 400);
            }

            DB::table('users')->where('email', $request->email)->update([
                'password'   => Hash::make($request->password),
                'updated_at' => now()
            ]);

            DB::table('password_resets')->where('email', $request->email)->delete();

            return response()->json(['status' => true, 'message' => 'Đổi mật khẩu tài khoản thành công.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Đổi mật khẩu thất bại.'], 500);
        }
    }

    public function logoutKhachHang(Request $request): JsonResponse
    {
        try {
            $hashedToken = hash('sha256', $request->bearerToken());
            DB::table('personal_access_tokens')->where('token', $hashedToken)->delete();

            return response()->json(['status' => true, 'message' => 'Đăng xuất phiên làm việc hiện tại thành công.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi kết thúc phiên hoạt động.'], 500);
        }
    }

    public function logoutAllKhachHang(Request $request): JsonResponse
    {
        try {
            $khachHangId = $request->attributes->get('id_khach_hang') ?? $request->user()->id;

            DB::table('personal_access_tokens')
                ->where('tokenable_id', $khachHangId)
                ->where('tokenable_type', 'App\Models\User')
                ->delete();

            return response()->json(['status' => true, 'message' => 'Đã thu hồi quyền truy cập và hủy phiên trên toàn bộ các thiết bị.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Xử lý lỗi hệ thống đăng xuất.'], 500);
        }
    }

    public function checkTokenKhachHang(Request $request): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => 'Phiên làm việc Porsche Portal hợp lệ.',
            'time'    => now()
        ], 200);
    }

    public function getProfile(Request $request): JsonResponse
    {
        try {
            $khachHangId = $request->attributes->get('id_khach_hang') ?? $request->user()->id;
            $profile = DB::table('users')
                ->select('id', 'ho_ten', 'email', 'so_dien_thoai', 'dia_chi', 'avatar', 'created_at')
                ->where('id', $khachHangId)
                ->first();

            return response()->json(['status' => true, 'data' => $profile], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Không tải được Profile.'], 500);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $khachHangId = $request->attributes->get('id_khach_hang') ?? $request->user()->id;

        $request->validate([
            'ho_ten'        => 'required|string|max:255',
            'so_dien_thoai' => 'required|string|min:10|max:15',
            'dia_chi'       => 'nullable|string|max:500'
        ]);

        try {
            DB::table('users')->where('id', $khachHangId)->update([
                'ho_ten'        => $request->ho_ten,
                'so_dien_thoai' => $request->so_dien_thoai,
                'dia_chi'       => $request->dia_chi,
                'updated_at'    => now()
            ]);

            return response()->json(['status' => true, 'message' => 'Cập nhật Profile cá nhân thành công.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi đồng bộ thông tin cá nhân.'], 500);
        }
    }

    public function updateProfileAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        try {
            $khachHangId = $request->attributes->get('id_khach_hang') ?? $request->user()->id;

            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $filename = 'avatar_porsche_' . $khachHangId . '_' . time() . '.' . $file->getClientOriginalExtension();

                $file->move(public_path('uploads/avatars'), $filename);
                $avatarUrl = '/uploads/avatars/' . $filename;

                $oldAvatar = DB::table('users')->where('id', $khachHangId)->value('avatar');
                if ($oldAvatar && file_exists(public_path($oldAvatar))) {
                    @unlink(public_path($oldAvatar));
                }

                DB::table('users')->where('id', $khachHangId)->update([
                    'avatar'     => $avatarUrl,
                    'updated_at' => now()
                ]);

                return response()->json(['status' => true, 'message' => 'Cập nhật ảnh đại diện thành công.', 'avatar_url' => $avatarUrl], 200);
            }

            return response()->json(['status' => false, 'message' => 'Không tìm thấy tệp tin được truyền lên.'], 400);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi xử lý lưu trữ tệp vật lý.'], 500);
        }
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'old_password' => 'required|string',
            'password'     => 'required|string|min:6|confirmed'
        ]);

        try {
            $khachHangId = $request->attributes->get('id_khach_hang') ?? $request->user()->id;
            $user = DB::table('users')->where('id', $khachHangId)->first();

            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json(['status' => false, 'message' => 'Mật khẩu cũ không chính xác.'], 400);
            }

            DB::table('users')->where('id', $khachHangId)->update([
                'password'   => Hash::make($request->password),
                'updated_at' => now()
            ]);

            return response()->json(['status' => true, 'message' => 'Thay đổi mật khẩu tài khoản thành công.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi thay đổi thông tin mật khẩu bảo mật.'], 500);
        }
    }

    public function getDataLichHenCuaToi(Request $request): JsonResponse
    {
        try {
            $khachHangId = $request->attributes->get('id_khach_hang') ?? $request->user()->id;

            $data = DB::table('phieu_dat_lichs')
                ->leftJoin('chuyen_gias', 'phieu_dat_lichs.id_chuyen_gia', '=', 'chuyen_gias.id')
                ->select(
                    'phieu_dat_lichs.id',
                    'phieu_dat_lichs.ma_dat_lich',
                    'phieu_dat_lichs.ngay_dat',
                    'phieu_dat_lichs.gio_dat',
                    'phieu_dat_lichs.thong_tin_xe',
                    'phieu_dat_lichs.trang_thai',
                    'chuyen_gias.ho_ten as ten_chuyen_gia'
                )
                ->where('phieu_dat_lichs.id_khach_hang', $khachHangId)
                ->orderBy('phieu_dat_lichs.id', 'desc')
                ->get();

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getDataLichHenCuaToi: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Không thể kết xuất dữ liệu lịch hẹn của bạn.'], 500);
        }
    }
}