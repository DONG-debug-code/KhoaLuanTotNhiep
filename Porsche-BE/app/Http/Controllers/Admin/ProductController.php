<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * THÊM MỚI SẢN PHẨM / CÔNG NGHỆ ĐỘ XE ĐA DẠNG
     * URL: POST /api/admin/products
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ten_san_pham'     => 'required|string|max:255|unique:san_phams,ten',
            'loai_san_pham'    => 'required|string|in:phan_cung,phan_mem,dien_tu,cong_nghe_moi',
            'gia'              => 'required|numeric|min:0',
            'so_luong_kho'     => 'required|integer|min:0',
            'danh_muc_id'      => 'required|integer',
            'thuong_hieu_id'   => 'required|integer',
            'mo_ta'            => 'nullable|string',
            'thong_so_ky_thuat' => 'nullable|array',
            'hinh_anh_urls'    => 'nullable|array',
            'hinh_anh_urls.*'  => 'url'
        ], [
            'ten_san_pham.required' => 'Tên sản phẩm nâng cấp không được để trống.',
            'ten_san_pham.unique'   => 'Sản phẩm độ xe này đã tồn tại trên hệ thống.',
            'gia.required'          => 'Chi phí nâng cấp không được để trống.'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $slug = Str::slug($request->input('ten_san_pham'));
            $thongSoJson = $request->has('thong_so_ky_thuat') ? json_encode($request->input('thong_so_ky_thuat'), JSON_UNESCAPED_UNICODE) : null;

            $productId = DB::table('san_phams')->insertGetId([
                'ten'               => $request->input('ten_san_pham'),
                'slug'              => $slug,
                'loai'              => $request->input('loai_san_pham'),
                'gia'               => $request->input('gia'),
                'so_luong_kho'      => $request->input('so_luong_kho'),
                'danh_muc_id'       => $request->input('danh_muc_id'),
                'thuong_hieu_id'    => $request->input('thuong_hieu_id'),
                'mo_ta'             => $request->input('mo_ta'),
                'thong_so_chi_tiet' => $thongSoJson,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            if ($request->has('hinh_anh_urls') && is_array($request->input('hinh_anh_urls'))) {
                $imagesData = [];
                foreach ($request->input('hinh_anh_urls') as $url) {
                    $imagesData[] = [
                        'san_pham_id' => $productId,
                        'duong_dan'   => $url,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }
                DB::table('hinh_anh_san_pham_models')->insert($imagesData);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Hạng mục nâng cấp đã được lưu thành công!', 'data' => ['id' => $productId, 'slug' => $slug]], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Lỗi thêm sản phẩm: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Lỗi hệ thống, không thể thêm sản phẩm.'], 500);
        }
    }

    /**
     * CẬP NHẬT THÔNG TIN SẢN PHẨM ĐỘ XE
     * URL: PUT /api/admin/products/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $product = DB::table('san_phams')->where('id', $id)->first();
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Sản phẩm không tồn tại.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'ten_san_pham' => 'required|string|max:255|unique:san_phams,ten,' . $id,
            'gia'          => 'required|numeric|min:0',
            'so_luong_kho' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        DB::beginTransaction();
        try {
            $thongSoJson = $request->has('thong_so_ky_thuat') ? json_encode($request->input('thong_so_ky_thuat'), JSON_UNESCAPED_UNICODE) : null;

            DB::table('san_phams')->where('id', $id)->update([
                'ten'               => $request->input('ten_san_pham'),
                'slug'              => Str::slug($request->input('ten_san_pham')),
                'gia'               => $request->input('gia'),
                'so_luong_kho'      => $request->input('so_luong_kho'),
                'mo_ta'             => $request->input('mo_ta'),
                'thong_so_chi_tiet' => $thongSoJson,
                'updated_at'        => now(),
            ]);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Cập nhật thông tin thành công!'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Lỗi sửa sản phẩm ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Cập nhật thất bại.'], 500);
        }
    }

    /**
     * XÓA SẢN PHẨM KHỎI HỆ THỐNG
     * URL: DELETE /api/admin/products/{id}
     */
    public function destroy($id): JsonResponse
    {
        $product = DB::table('san_phams')->where('id', $id)->first();
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Sản phẩm không tồn tại.'], 404);
        }

        DB::beginTransaction();
        try {
            DB::table('hinh_anh_san_pham_models')->where('san_pham_id', $id)->delete();
            DB::table('san_phams')->where('id', $id)->delete();
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Xóa sản phẩm thành công!'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Lỗi xóa sản phẩm ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Xóa sản phẩm thất bại.'], 500);
        }
    }
}
