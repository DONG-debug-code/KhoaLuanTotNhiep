<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class ThongKeController extends Controller
{
    public function getDashboardData(): JsonResponse
    {
        try {
            $tongDoanhThu = DB::table('hoa_dons')->sum('tong_tien');
            $tongKhachHang = DB::table('khach_hangs')->count();
            $tongChuyenGia = DB::table('chuyen_gias')->count();
            $lichHenMoi = DB::table('phieu_dat_lichs')->where('trang_thai', 0)->count();

            return response()->json([
                'status' => true,
                'data' => [
                    'tong_doanh_thu'  => $tongDoanhThu,
                    'tong_khach_hang' => $tongKhachHang,
                    'tong_chuyen_gia' => $tongChuyenGia,
                    'lich_hen_moi'    => $lichHenMoi
                ]
            ], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getDashboardData: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi kết xuất báo cáo thống kê.'], 500);
        }
    }
}
