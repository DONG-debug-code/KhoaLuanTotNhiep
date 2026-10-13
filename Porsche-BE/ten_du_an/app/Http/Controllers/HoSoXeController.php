<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class HoSoXeController extends Controller
{
    public function getDataHoSoXe(): JsonResponse
    {
        try {
            $data = DB::table('ho_so_xes')
                ->join('khach_hangs', 'ho_so_xes.id_khach_hang', '=', 'khach_hangs.id')
                ->select('ho_so_xes.*', 'khach_hangs.ho_ten as chu_xe')
                ->orderBy('ho_so_xes.id', 'desc')
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getDataHoSoXe: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi kết xuất hồ sơ xe.'], 500);
        }
    }

    public function timKiemHoSoXe(Request $request): JsonResponse
    {
        try {
            $key = $request->input('value');
            $data = DB::table('ho_so_xes')
                ->join('khach_hangs', 'ho_so_xes.id_khach_hang', '=', 'khach_hangs.id')
                ->select('ho_so_xes.*', 'khach_hangs.ho_ten as chu_xe')
                ->where('ho_so_xes.bien_so', 'LIKE', "%{$key}%")
                ->orWhere('ho_so_xes.so_khung', 'LIKE', "%{$key}%")
                ->orWhere('khach_hangs.ho_ten', 'LIKE', "%{$key}%")
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi timKiemHoSoXe: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi truy vấn dữ liệu hồ sơ.'], 500);
        }
    }

    public function createHoSoXe(Request $request): JsonResponse
    {
        $request->validate([
            'id_khach_hang' => 'required|integer|exists:khach_hangs,id',
            'bien_so'       => 'required|string|unique:ho_so_xes,bien_so',
            'ten_xe'        => 'required|string',
            'so_khung'      => 'nullable|string',
            'thong_so_tuning' => 'nullable|string'
        ]);
        try {
            DB::table('ho_so_xes')->insert([
                'id_khach_hang'  => $request->id_khach_hang,
                'bien_so'        => strtoupper($request->bien_so),
                'ten_xe'         => $request->ten_xe,
                'so_khung'       => strtoupper($request->so_khung),
                'thong_so_tuning' => $request->thong_so_tuning,
                'trang_thai'     => 1,
                'created_at'     => now(),
                'updated_at'     => now()
            ]);
            return response()->json(['status' => true, 'message' => 'Lập hồ sơ kỹ thuật xe thành công.'], 201);
        } catch (Exception $e) {
            Log::error('Lỗi createHoSoXe: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Không thể tạo mới hồ sơ bệnh án xe.'], 500);
        }
    }

    public function updateHoSoXe(Request $request): JsonResponse
    {
        $request->validate([
            'id'       => 'required|integer|exists:ho_so_xes,id',
            'bien_so'  => 'required|string|unique:ho_so_xes,bien_so,' . $request->id,
            'ten_xe'   => 'required|string'
        ]);
        try {
            DB::table('ho_so_xes')->where('id', $request->id)->update([
                'bien_so'        => strtoupper($request->bien_so),
                'ten_xe'         => $request->ten_xe,
                'so_khung'       => strtoupper($request->so_khung),
                'thong_so_tuning' => $request->thong_so_tuning,
                'updated_at'     => now()
            ]);
            return response()->json(['status' => true, 'message' => 'Cập nhật cấu hình xe thành công.'], 200);
        } catch (Exception $e) {
            Log::error('Lỗi updateHoSoXe: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Cập nhật thất bại.'], 500);
        }
    }

    public function deleteHoSoXe(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer|exists:ho_so_xes,id']);
        try {
            DB::table('ho_so_xes')->where('id', $request->id)->delete();
            return response()->json(['status' => true, 'message' => 'Xóa hồ sơ kỹ thuật xe thành công.'], 200);
        } catch (Exception $e) {
            Log::error('Lỗi deleteHoSoXe: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi khóa ngoại hệ thống.'], 500);
        }
    }

    public function changeStatusHoSoXe(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer|exists:ho_so_xes,id']);
        try {
            $current = DB::table('ho_so_xes')->where('id', $request->id)->first();
            $newStatus = $current->trang_thai == 1 ? 0 : 1;
            DB::table('ho_so_xes')->where('id', $request->id)->update(['trang_thai' => $newStatus, 'updated_at' => now()]);
            return response()->json(['status' => true, 'message' => 'Thay đổi trạng thái hồ sơ thành công.', 'trang_thai' => $newStatus], 200);
        } catch (Exception $e) {
            Log::error('Lỗi changeStatusHoSoXe: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Gặp sự cố khi xử lý dữ liệu.'], 500);
        }
    }
}
