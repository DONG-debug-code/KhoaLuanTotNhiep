<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class PhanQuyenController extends Controller
{
    public function getData()
    {
        try {
            $roles = DB::table('chuc_vus')->orderBy('id')->get();
            $functions = DB::table('chuc_nangs')->orderBy('id')->get();
            $permissions = DB::table('phan_quyens')->get();

            return response()->json([
                'status' => true,
                'data' => [
                    'chuc_vus' => $roles,
                    'chuc_nangs' => $functions,
                    'phan_quyens' => $permissions,
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Không thể tải ma trận phân quyền.',
            ], 500);
        }
    }

    public function createPhanQuyen(Request $request)
    {
        return $this->capNhatPhanQuyen($request);
    }

    public function getQuyenTheoChucVu($id_chuc_vu)
    {
        try {
            $chucVu = DB::table('chuc_vus')->where('id', $id_chuc_vu)->first();
            if (!$chucVu) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Chức vụ không tồn tại.'
                ], 404);
            }

            $quyenHienTai = DB::table('phan_quyens')
                ->where('id_chuc_vu', $id_chuc_vu)
                ->pluck('id_chuc_nang')
                ->toArray();

            return response()->json([
                'status'  => true,
                'message' => 'Lấy danh sách quyền thành công.',
                'id_chuc_vu' => $id_chuc_vu,
                'quyen_sở_hữu' => $quyenHienTai
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi xử lý: ' . $e->getMessage()
            ], 500);
        }
    }

    public function capNhatPhanQuyen(Request $request)
    {
        $request->validate([
            'id_chuc_vu'    => 'required|integer|exists:chuc_vus,id',
            'id_chuc_nangs' => 'present|array',
            'id_chuc_nangs.*' => 'integer|exists:chuc_nangs,id'
        ]);

        DB::beginTransaction();
        try {
            DB::table('phan_quyens')->where('id_chuc_vu', $request->id_chuc_vu)->delete();

            $insertData = [];
            foreach ($request->id_chuc_nangs as $idChucNang) {
                $insertData[] = [
                    'id_chuc_vu'   => $request->id_chuc_vu,
                    'id_chuc_nang' => $idChucNang,
                    'created_at'   => now(),
                    'updated_at'   => now()
                ];
            }

            if (!empty($insertData)) {
                DB::table('phan_quyens')->insert($insertData);
            }

            DB::commit();
            return response()->json([
                'status'  => true,
                'message' => 'Cập nhật phân quyền cho chức vụ thành công.'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi giao dịch phân quyền: ' . $e->getMessage()
            ], 500);
        }
    }
}
