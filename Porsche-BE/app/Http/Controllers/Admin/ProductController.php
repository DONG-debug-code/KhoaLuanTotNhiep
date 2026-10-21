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
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'tu_khoa' => 'nullable|string|max:255',
            'loai' => 'nullable|string|max:100',
            'danh_muc_id' => 'nullable|integer|min:1',
            'thuong_hieu_id' => 'nullable|integer|min:1',
            'gia_tu' => 'nullable|numeric|min:0',
            'gia_den' => 'nullable|numeric|min:0|gte:gia_tu',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $query = DB::table('san_phams');
        $filters = $validator->validated();

        if (!empty($filters['tu_khoa'])) {
            $query->where('ten', 'like', '%' . trim($filters['tu_khoa']) . '%');
        }
        if (!empty($filters['loai'])) {
            $query->where('loai_san_pham', $filters['loai']);
        }
        if (!empty($filters['danh_muc_id'])) {
            $query->where('danh_muc_id', $filters['danh_muc_id']);
        }
        if (!empty($filters['thuong_hieu_id'])) {
            $query->where('thuong_hieu_id', $filters['thuong_hieu_id']);
        }
        if (isset($filters['gia_tu'])) {
            $query->where('gia', '>=', $filters['gia_tu']);
        }
        if (isset($filters['gia_den'])) {
            $query->where('gia', '<=', $filters['gia_den']);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderByDesc('id')->paginate($filters['per_page'] ?? 12),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $product = DB::table('san_phams')->where('id', $id)->first();

        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Sản phẩm không tồn tại.'], 404);
        }

        $product->anh_san_phams = DB::table('anh_san_phams')
            ->where('san_pham_id', $id)
            ->orderByDesc('la_anh_chinh')
            ->get();

        return response()->json(['status' => 'success', 'data' => $product]);
    }

    public function restock(Request $request, int $id): JsonResponse
    {
        return $this->adjustStock($request, $id, 'nhap');
    }

    public function exportStock(Request $request, int $id): JsonResponse
    {
        return $this->adjustStock($request, $id, 'xuat');
    }

    public function stockHistory(Request $request, int $id): JsonResponse
    {
        if (!DB::table('san_phams')->where('id', $id)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Sản phẩm không tồn tại.'], 404);
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $history = DB::table('nhat_ky_khos')
            ->leftJoin('users', 'users.id', '=', 'nhat_ky_khos.user_id')
            ->where('nhat_ky_khos.san_pham_id', $id)
            ->select('nhat_ky_khos.*', 'users.ho_ten as ten_nguoi_thuc_hien')
            ->orderByDesc('nhat_ky_khos.id')
            ->paginate($perPage);

        return response()->json(['status' => 'success', 'data' => $history]);
    }

    private function adjustStock(Request $request, int $id, string $type): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'so_luong' => 'required|integer|min:1',
            'ly_do' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            $result = DB::transaction(function () use ($request, $id, $type, $validator) {
                $product = DB::table('san_phams')->where('id', $id)->lockForUpdate()->first();

                if (!$product) {
                    return null;
                }

                $quantity = (int) $validator->validated()['so_luong'];
                $current = (int) $product->so_luong;

                if ($type === 'xuat' && $current < $quantity) {
                    return false;
                }

                $updated = $type === 'nhap' ? $current + $quantity : $current - $quantity;
                DB::table('san_phams')->where('id', $id)->update([
                    'so_luong' => $updated,
                    'so_luong_kho' => $updated,
                    'updated_at' => now(),
                ]);

                $actor = $request->user();
                DB::table('nhat_ky_khos')->insert([
                    'user_id' => $actor instanceof \App\Models\User ? $actor->id : null,
                    'san_pham_id' => $id,
                    'loai' => $type,
                    'so_luong' => $quantity,
                    'ly_do' => $validator->validated()['ly_do'] ?? ($type === 'nhap' ? 'Nhập kho' : 'Xuất kho'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return ['so_luong_cu' => $current, 'so_luong_moi' => $updated];
            });

            if ($result === null) {
                return response()->json(['status' => 'error', 'message' => 'Sản phẩm không tồn tại.'], 404);
            }
            if ($result === false) {
                return response()->json(['status' => 'error', 'message' => 'Số lượng tồn kho không đủ.'], 422);
            }

            return response()->json([
                'status' => 'success',
                'message' => $type === 'nhap' ? 'Nhập kho thành công.' : 'Xuất kho thành công.',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('Lỗi cập nhật tồn kho sản phẩm', ['product_id' => $id, 'type' => $type, 'error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Không thể cập nhật tồn kho.'], 500);
        }
    }

    /**
     * THÊM MỚI SẢN PHẨM / CÔNG NGHỆ ĐỘ XE ĐA DẠNG
     * URL: POST /api/admin/products
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ten_san_pham'     => 'required|string|max:255|unique:san_phams,ten',
            'loai_san_pham'    => 'required|string|in:xe_nguyen_chiec,phan_cung,phan_mem,dien_tu,cong_nghe_moi',
            'gia'              => 'required|numeric|min:0',
            'so_luong_kho'     => 'required|integer|min:0',
            'danh_muc_id'      => 'nullable|integer',
            'thuong_hieu_id'   => 'nullable|integer',
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
                'loai_san_pham'     => $request->input('loai_san_pham'),
                'gia'               => $request->input('gia'),
                'so_luong'          => $request->input('so_luong_kho'),
                'so_luong_kho'      => $request->input('so_luong_kho'),
                'danh_muc_id'       => $request->input('danh_muc_id'),
                'thuong_hieu_id'    => $request->input('thuong_hieu_id'),
                'mo_ta'             => $request->input('mo_ta'),
                'thong_so_chi_tiet' => $thongSoJson,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            if ((int) $request->input('so_luong_kho') > 0) {
                $actor = $request->user();
                DB::table('nhat_ky_khos')->insert([
                    'user_id' => $actor instanceof \App\Models\User ? $actor->id : null,
                    'san_pham_id' => $productId,
                    'loai' => 'nhap',
                    'so_luong' => $request->input('so_luong_kho'),
                    'ly_do' => 'Khởi tạo sản phẩm và tồn kho ban đầu',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

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
                DB::table('anh_san_phams')->insert($imagesData);
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
        $validator = Validator::make($request->all(), [
            'ten_san_pham' => 'required|string|max:255|unique:san_phams,ten,' . $id,
            'gia'          => 'required|numeric|min:0',
            'so_luong_kho' => 'required|integer|min:0',
            'mo_ta' => 'nullable|string',
            'thong_so_ky_thuat' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        try {
            $updated = DB::transaction(function () use ($request, $id, $validator) {
                $product = DB::table('san_phams')->where('id', $id)->lockForUpdate()->first();
                if (!$product) {
                    return null;
                }

                $data = $validator->validated();
                $newQuantity = (int) $data['so_luong_kho'];
                $oldQuantity = (int) $product->so_luong;
                $update = [
                    'ten' => $data['ten_san_pham'],
                    'slug' => Str::slug($data['ten_san_pham']),
                    'gia' => $data['gia'],
                    'so_luong' => $newQuantity,
                    'so_luong_kho' => $newQuantity,
                    'mo_ta' => $data['mo_ta'] ?? $product->mo_ta,
                    'updated_at' => now(),
                ];

                if (array_key_exists('thong_so_ky_thuat', $data)) {
                    $update['thong_so_chi_tiet'] = json_encode($data['thong_so_ky_thuat'], JSON_UNESCAPED_UNICODE);
                }

                DB::table('san_phams')->where('id', $id)->update($update);

                $difference = $newQuantity - $oldQuantity;
                if ($difference !== 0) {
                    $actor = $request->user();
                    DB::table('nhat_ky_khos')->insert([
                        'user_id' => $actor instanceof \App\Models\User ? $actor->id : null,
                        'san_pham_id' => $id,
                        'loai' => $difference > 0 ? 'nhap' : 'xuat',
                        'so_luong' => abs($difference),
                        'ly_do' => 'Điều chỉnh tồn kho khi cập nhật sản phẩm',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return true;
            });

            if ($updated === null) {
                return response()->json(['status' => 'error', 'message' => 'Sản phẩm không tồn tại.'], 404);
            }

            return response()->json(['status' => 'success', 'message' => 'Cập nhật thông tin thành công!'], 200);
        } catch (\Throwable $e) {
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
            DB::table('anh_san_phams')->where('san_pham_id', $id)->delete();
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
