<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class HoaDonController extends Controller
{
    public function getDataHoaDon(): JsonResponse
    {
        try {
            $data = DB::table('hoa_dons')
                ->join('khach_hangs', 'hoa_dons.id_khach_hang', '=', 'khach_hangs.id')
                ->select('hoa_dons.*', 'khach_hangs.ho_ten as ten_khach_hang')
                ->orderBy('hoa_dons.id', 'desc')
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getDataHoaDon: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Không thể tải danh sách hóa đơn.'], 500);
        }
    }
}
