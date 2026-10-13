<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class LogController extends Controller
{
    /**
     * LẤY DANH SÁCH NHẬT KÝ HOẠT ĐỘNG KHO VÀ HỆ THỐNG
     * URL: GET /api/admin/logs/kho
     */
    public function nhatKyKho(Request $request): JsonResponse
    {
        try {
            $query = DB::table('nhat_ky_hoat_dong_admin_models')
                ->join('users', 'nhat_ky_hoat_dong_admin_models.user_id', '=', 'users.id')
                ->select(
                    'nhat_ky_hoat_dong_admin_models.id',
                    'nhat_ky_hoat_dong_admin_models.hanh_dong',
                    'nhat_ky_hoat_dong_admin_models.chi_tiet',
                    'nhat_ky_hoat_dong_admin_models.created_at',
                    'users.name as ten_nguoi_thuc_hien'
                );

            // BỘ LỌC TÌM KIẾM THEO TỪ KHÓA (Ví dụ: "Remap", "Xóa", "Mâm")
            if ($request->has('tu_khoa') && !empty($request->input('tu_khoa'))) {
                $tuKhoa = $request->input('tu_khoa');
                $query->where(function ($q) use ($tuKhoa) {
                    $q->where('nhat_ky_hoat_dong_admin_models.hanh_dong', 'like', '%' . $tuKhoa . '%')
                        ->orWhere('nhat_ky_hoat_dong_admin_models.chi_tiet', 'like', '%' . $tuKhoa . '%');
                });
            }

            if ($request->has('user_id') && is_numeric($request->input('user_id'))) {
                $query->where('nhat_ky_hoat_dong_admin_models.user_id', $request->input('user_id'));
            }

            // Phân trang 20 dòng một trang cho dữ liệu bảng biểu admin mượt mà
            $logs = $query->orderBy('nhat_ky_hoat_dong_admin_models.created_at', 'desc')
                ->paginate(20);

            return response()->json(['status' => 'success', 'data' => $logs], 200);
        } catch (\Throwable $e) {
            Log::error('Lỗi khi truy xuất nhật ký: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Không thể tải nhật ký hệ thống.'], 500);
        }
    }
}
