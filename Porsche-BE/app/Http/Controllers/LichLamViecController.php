<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class LichLamViecController extends Controller
{
    public function getNgayLamViecChuyenGia($id_chuyen_gia): JsonResponse
    {
        try {
            $data = DB::table('lich_lam_viecs')
                ->where('id_chuyen_gia', $id_chuyen_gia)
                ->orderBy('ngay_lam_viec', 'asc')
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getNgayLamViecChuyenGia: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi kết xuất lịch làm việc.'], 500);
        }
    }

    public function getDataLichLamViecChuyenGia(): JsonResponse
    {
        try {
            $data = DB::table('lich_lam_viecs')
                ->join('chuyen_gias', 'lich_lam_viecs.id_chuyen_gia', '=', 'chuyen_gias.id')
                ->select('lich_lam_viecs.*', 'chuyen_gias.ho_ten as ten_chuyen_gia')
                ->orderBy('lich_lam_viecs.id', 'desc')
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getDataLichLamViecChuyenGia: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Không thể tải toàn bộ roster.'], 500);
        }
    }

    public function createLichLamViecChuyenGia(Request $request): JsonResponse
    {
        $request->validate([
            'id_chuyen_gia' => 'required|integer|exists:chuyen_gias,id',
            'ngay_lam_viec' => 'required|date',
            'ca_lam_viec'   => 'required|string'
        ]);
        try {
            $check = DB::table('lich_lam_viecs')
                ->where('id_chuyen_gia', $request->id_chuyen_gia)
                ->where('ngay_lam_viec', $request->ngay_lam_viec)
                ->where('ca_lam_viec', $request->ca_lam_viec)
                ->exists();

            if ($check) {
                return response()->json(['status' => false, 'message' => 'Lịch trực ca này đã được đăng ký trước đó.'], 400);
            }

            DB::table('lich_lam_viecs')->insert([
                'id_chuyen_gia' => $request->id_chuyen_gia,
                'ngay_lam_viec' => $request->ngay_lam_viec,
                'ca_lam_viec'   => $request->ca_lam_viec,
                'created_at'    => now(),
                'updated_at'    => now()
            ]);
            return response()->json(['status' => true, 'message' => 'Xếp lịch làm việc thành công.'], 201);
        } catch (Exception $e) {
            Log::error('Lỗi createLichLamViecChuyenGia: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi phân bổ lịch trực.'], 500);
        }
    }
}
