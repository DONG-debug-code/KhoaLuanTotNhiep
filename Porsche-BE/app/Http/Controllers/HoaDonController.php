<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class HoaDonController extends Controller
{
    public function getLichSuMuaHangKhachHang(Request $request): JsonResponse
    {
        $customerId = $request->attributes->get('id_khach_hang') ?? $request->user()->id;

        try {
            $data = DB::table('hoa_dons')
                ->where(function ($query) use ($customerId) {
                    $query->where('id_khach_hang', $customerId)
                        ->orWhere('users_id', $customerId);
                })
                ->where('trang_thai', 1)
                ->orderByDesc('id')
                ->get();

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Không thể tải lịch sử hóa đơn của khách hàng.', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['status' => false, 'message' => 'Không thể tải lịch sử mua hàng.'], 500);
        }
    }

    public function getDataHoaDon(): JsonResponse
    {
        try {
            $data = DB::table('hoa_dons')
                ->leftJoin('users', 'hoa_dons.users_id', '=', 'users.id')
                ->select('hoa_dons.*', 'users.ho_ten as ten_khach_hang')
                ->orderBy('hoa_dons.id', 'desc')
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getDataHoaDon: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Không thể tải danh sách hóa đơn.'], 500);
        }
    }
}
