<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Query\Builder;
use Exception;

class ThongKeController extends Controller
{
    public function getLichHenTheoHangPhuTung(Request $request): JsonResponse
    {
        $filters = $this->validateAppointmentFilters($request);

        try {
            $data = $this->applyAppointmentFilters(
                DB::table('phieu_dat_lichs as p')
                    ->join('chuyen_gias as cg', 'p.id_chuyen_gia', '=', 'cg.id')
                    ->join('hang_phu_tungs as hpt', 'cg.id_hang_phu_tung', '=', 'hpt.id'),
                $filters
            )
                ->select('hpt.id as id_hang_phu_tung', 'hpt.ten_hang', DB::raw('COUNT(p.id) as tong_lich_hen'))
                ->groupBy('hpt.id', 'hpt.ten_hang')
                ->orderByDesc('tong_lich_hen')
                ->get();

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getLichHenTheoHangPhuTung: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi kết xuất lịch hẹn theo hãng phụ tùng.'], 500);
        }
    }

    public function getLichDoXeTheoChuyenGia(Request $request): JsonResponse
    {
        $filters = $this->validateAppointmentFilters($request);

        try {
            $hoaDonTheoLich = DB::table('hoa_dons')
                ->select('id_phieu_dat_lich', DB::raw('SUM(tong_tien) as doanh_thu'))
                ->groupBy('id_phieu_dat_lich');
            $data = $this->applyAppointmentFilters(
                DB::table('phieu_dat_lichs as p')
                    ->join('chuyen_gias as cg', 'p.id_chuyen_gia', '=', 'cg.id')
                    ->leftJoinSub($hoaDonTheoLich, 'hd', function ($join) {
                        $join->on('hd.id_phieu_dat_lich', '=', 'p.id');
                    }),
                $filters
            )
                ->select(
                    'cg.id as id_chuyen_gia',
                    'cg.ho_ten as ten_chuyen_gia',
                    DB::raw('COUNT(p.id) as tong_lich_do'),
                    DB::raw('SUM(CASE WHEN p.trang_thai = 2 THEN 1 ELSE 0 END) as lich_da_hoan_thanh'),
                    DB::raw('COALESCE(SUM(hd.doanh_thu), 0) as tong_doanh_thu')
                )
                ->groupBy('cg.id', 'cg.ho_ten')
                ->orderByDesc('tong_lich_do')
                ->get();

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getLichDoXeTheoChuyenGia: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi kết xuất báo cáo theo chuyên gia.'], 500);
        }
    }

    public function getXeTheoKhuVuc(Request $request): JsonResponse
    {
        $filters = $this->validateAppointmentFilters($request);

        try {
            $data = $this->applyAppointmentFilters(
                DB::table('phieu_dat_lichs as p')
                    ->join('khu_vuc_workshops as kv', 'p.id_khu_vuc_workshop', '=', 'kv.id'),
                $filters
            )
                ->select(
                    'kv.id as id_khu_vuc_workshop',
                    'kv.ten_khu_vuc',
                    DB::raw('COUNT(p.id) as tong_xe'),
                    DB::raw('SUM(CASE WHEN p.trang_thai = 1 THEN 1 ELSE 0 END) as xe_dang_phuc_vu')
                )
                ->groupBy('kv.id', 'kv.ten_khu_vuc')
                ->orderByDesc('tong_xe')
                ->get();

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getXeTheoKhuVuc: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi kết xuất số xe theo khu vực workshop.'], 500);
        }
    }

    public function getKhachHangTheoChuyenGia(Request $request): JsonResponse
    {
        $filters = $this->validateAppointmentFilters($request);

        try {
            $data = $this->applyAppointmentFilters(
                DB::table('phieu_dat_lichs as p')
                    ->join('chuyen_gias as cg', 'p.id_chuyen_gia', '=', 'cg.id'),
                $filters
            )
                ->select(
                    'cg.id as id_chuyen_gia',
                    'cg.ho_ten as ten_chuyen_gia',
                    DB::raw('COUNT(DISTINCT p.id_khach_hang) as tong_khach_hang'),
                    DB::raw('COUNT(p.id) as tong_lich_hen')
                )
                ->groupBy('cg.id', 'cg.ho_ten')
                ->orderByDesc('tong_khach_hang')
                ->get();

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getKhachHangTheoChuyenGia: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi kết xuất khách hàng theo chuyên gia.'], 500);
        }
    }

    public function getLichLamViecTheoKhuVuc(Request $request): JsonResponse
    {
        $filters = $this->validateAppointmentFilters($request);

        try {
            $data = $this->applyAppointmentFilters(
                DB::table('phieu_dat_lichs as p')
                    ->join('khu_vuc_workshops as kv', 'p.id_khu_vuc_workshop', '=', 'kv.id'),
                $filters
            )
                ->select(
                    'kv.id as id_khu_vuc_workshop',
                    'kv.ten_khu_vuc',
                    'p.ngay_dat',
                    DB::raw('COUNT(p.id) as tong_lich_do')
                )
                ->groupBy('kv.id', 'kv.ten_khu_vuc', 'p.ngay_dat')
                ->orderBy('p.ngay_dat')
                ->get();

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getLichLamViecTheoKhuVuc: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi kết xuất lịch độ xe theo khu vực.'], 500);
        }
    }

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

    private function validateAppointmentFilters(Request $request): array
    {
        return $request->validate([
            'tu_ngay' => 'nullable|date',
            'den_ngay' => 'nullable|date|after_or_equal:tu_ngay',
            'id_chuyen_gia' => 'nullable|integer|min:1',
            'id_khu_vuc_workshop' => 'nullable|integer|min:1',
        ]);
    }

    private function applyAppointmentFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['tu_ngay'])) {
            $query->whereDate('p.ngay_dat', '>=', $filters['tu_ngay']);
        }
        if (isset($filters['den_ngay'])) {
            $query->whereDate('p.ngay_dat', '<=', $filters['den_ngay']);
        }
        if (isset($filters['id_chuyen_gia'])) {
            $query->where('p.id_chuyen_gia', $filters['id_chuyen_gia']);
        }
        if (isset($filters['id_khu_vuc_workshop'])) {
            $query->where('p.id_khu_vuc_workshop', $filters['id_khu_vuc_workshop']);
        }

        return $query;
    }
}
