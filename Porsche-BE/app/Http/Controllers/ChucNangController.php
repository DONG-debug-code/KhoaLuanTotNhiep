<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ChucNangController extends Controller
{
    public function getData()
    {
        try {
            $data = DB::table('permissions')->orderBy('id', 'desc')->get();
            return response()->json([
                'status'  => true,
                'message' => 'Lấy danh sách chức năng hệ thống thành công.',
                'data'    => $data
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'ten_chuc_nang' => 'required|string|max:255',
            'ma_chuc_nang'  => 'required|string|max:100|unique:permissions,ma_chuc_nang',
            'mo_ta'         => 'nullable|string',
        ]);

        try {
            DB::table('permissions')->insert([
                'ten_chuc_nang' => $request->ten_chuc_nang,
                'ma_chuc_nang'  => $request->ma_chuc_nang,
                'mo_ta'         => $request->mo_ta,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Thêm mới chức năng thành công.'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Không thể thêm chức năng: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateChucNang(Request $request)
    {
        $request->validate([
            'id'            => 'required|integer|exists:permissions,id',
            'ten_chuc_nang' => 'required|string|max:255',
            'ma_chuc_nang'  => 'required|string|max:100|unique:permissions,ma_chuc_nang,' . $request->id,
            'mo_ta'         => 'nullable|string',
        ]);

        try {
            DB::table('permissions')->where('id', $request->id)->update([
                'ten_chuc_nang' => $request->ten_chuc_nang,
                'ma_chuc_nang'  => $request->ma_chuc_nang,
                'mo_ta'         => $request->mo_ta,
                'updated_at'    => now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Cập nhật thông tin chức năng thành công.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Không thể cập nhật chức năng: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyChucNang(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:permissions,id'
        ]);

        try {
            DB::table('permissions')->where('id', $request->id)->delete();
            return response()->json([
                'status'  => true,
                'message' => 'Xóa chức năng thành công.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Không thể xóa chức năng do đang có quyền liên kết: ' . $e->getMessage()
            ], 500);
        }
    }
}