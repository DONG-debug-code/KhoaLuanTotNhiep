<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Exception;

class AdminController extends Controller
{
    public function loginAdmin(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        try {
            $admin = DB::table('admins')->where('email', $request->email)->first();
            if (!$admin || !Hash::check($request->password, $admin->password)) {
                return response()->json(['status' => false, 'message' => 'Thông tin đăng nhập Admin sai.'], 401);
            }

            $token = Str::random(60);
            DB::table('personal_access_tokens')->insert([
                'tokenable_type' => 'App\Models\Admin',
                'tokenable_id'   => $admin->id,
                'name'           => 'Admin_Token',
                'token'          => hash('sha256', $token),
                'abilities'      => json_encode(['*']),
                'created_at'     => now(),
                'updated_at'     => now()
            ]);

            return response()->json(['status' => true, 'access_token' => $token, 'message' => 'Hệ thống Admin kết nối thành công.'], 200);
        } catch (Exception $e) {
            Log::error('Lỗi loginAdmin: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi máy chủ cục bộ.'], 500);
        }
    }

    public function xuatExcelKhachHang(Request $request): JsonResponse
    {
        try {
            // Nghiệp vụ thực tế xuất danh sách thô sang định dạng JSON mảng để Client xuất file excel nhanh bằng thư viện JS
            $data = DB::table('khach_hangs')->select('id', 'ho_ten', 'email', 'so_dien_thoai', 'created_at')->get();
            return response()->json(['status' => true, 'data' => $data, 'message' => 'Kết xuất luồng dữ liệu excel thành công.'], 200);
        } catch (Exception $e) {
            Log::error('Lỗi xuatExcelKhachHang: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi kết xuất Excel.'], 500);
        }
    }

    public function logoutAdmin(Request $request): JsonResponse
    {
        try {
            $hashedToken = hash('sha256', $request->bearerToken());
            DB::table('personal_access_tokens')->where('token', $hashedToken)->delete();
            return response()->json(['status' => true, 'message' => 'Admin hủy token phiên làm việc thành công.'], 200);
        } catch (Exception $e) {
            Log::error('Lỗi logoutAdmin: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Đăng xuất thất bại.'], 500);
        }
    }

    public function checkTokenAdmin(): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Token Admin hợp lệ.'], 200);
    }

    public function getProfile(Request $request): JsonResponse
    {
        try {
            $adminId = $request->user()->id;
            $profile = DB::table('admins')->where('id', $adminId)->select('id', 'ho_ten', 'email', 'avatar')->first();
            return response()->json(['status' => true, 'data' => $profile], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getProfile Admin: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Không đọc được hồ sơ.'], 500);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate(['ho_ten' => 'required|string']);
        try {
            $adminId = $request->user()->id;
            DB::table('admins')->where('id', $adminId)->update(['ho_ten' => $request->ho_ten, 'updated_at' => now()]);
            return response()->json(['status' => true, 'message' => 'Đồng bộ hồ sơ quản trị viên thành công.'], 200);
        } catch (Exception $e) {
            Log::error('Lỗi updateProfile Admin: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Đồng bộ lỗi.'], 500);
        }
    }

    public function updateProfileAvatar(Request $request): JsonResponse
    {
        $request->validate(['avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048']);
        try {
            $adminId = $request->user()->id;
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $filename = 'admin_' . $adminId . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/admins'), $filename);
                $avatarUrl = '/uploads/admins/' . $filename;

                DB::table('admins')->where('id', $adminId)->update(['avatar' => $avatarUrl, 'updated_at' => now()]);
                return response()->json(['status' => true, 'avatar_url' => $avatarUrl, 'message' => 'Cập nhật Avatar thành công.'], 200);
            }
            return response()->json(['status' => false, 'message' => 'Tệp không hợp lệ.'], 400);
        } catch (Exception $e) {
            Log::error('Lỗi updateProfileAvatar Admin: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Xử lý file ảnh thất bại.'], 500);
        }
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate(['old_password' => 'required|string', 'password' => 'required|string|min:6|confirmed']);
        try {
            $adminId = $request->user()->id;
            $admin = DB::table('admins')->where('id', $adminId)->first();
            if (!Hash::check($request->old_password, $admin->password)) {
                return response()->json(['status' => false, 'message' => 'Mật khẩu cũ không đúng.'], 400);
            }
            DB::table('admins')->where('id', $adminId)->update(['password' => Hash::make($request->password), 'updated_at' => now()]);
            return response()->json(['status' => true, 'message' => 'Thay đổi mật khẩu quản trị cấp cao thành công.'], 200);
        } catch (Exception $e) {
            Log::error('Lỗi changePassword Admin: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi bảo mật.'], 500);
        }
    }
}
