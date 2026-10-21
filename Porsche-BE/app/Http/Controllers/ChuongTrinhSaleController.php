<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ChuongTrinhSaleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => DB::table('chuong_trinh_sales')->orderByDesc('id')->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $campaign = DB::table('chuong_trinh_sales')->where('id', $id)->first();

        if (!$campaign) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy chương trình Sale.'], 404);
        }

        $images = DB::table('anh_san_phams')
            ->select('san_pham_id', DB::raw('MIN(id) as image_id'))
            ->groupBy('san_pham_id');

        $products = DB::table('chi_tiet_san_pham_sales as sale_items')
            ->join('san_phams as products', 'products.id', '=', 'sale_items.san_pham_id')
            ->leftJoinSub($images, 'product_images', 'product_images.san_pham_id', '=', 'products.id')
            ->leftJoin('anh_san_phams as images', 'images.id', '=', 'product_images.image_id')
            ->where('sale_items.chuong_trinh_sale_id', $id)
            ->select('sale_items.*', 'products.ten as ten_san_pham', 'images.duong_dan as hinh_anh')
            ->get();

        return response()->json([
            'status' => true,
            'data' => ['chi_tiet_su_kien' => $campaign, 'danh_sach_sp' => $products],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ten_su_kien' => 'required|string|max:255',
            'banner_url' => 'nullable|string|max:2048',
            'mo_ta' => 'nullable|string',
            'ngay_bat_dau' => 'required|date',
            'ngay_ket_thuc' => 'required|date|after:ngay_bat_dau',
            'trang_thai' => 'sometimes|boolean',
            'danh_sach_sp' => 'required|array|min:1',
            'danh_sach_sp.*.san_pham_id' => 'required|integer|distinct|exists:san_phams,id',
            'danh_sach_sp.*.loai_giam_gia' => 'required|in:phan_tram,so_tien',
            'danh_sach_sp.*.gia_tri_giam' => 'required|numeric|min:0',
            'danh_sach_sp.*.so_luong_sale' => 'required|integer|min:1',
            'danh_sach_sp.*.gioi_han_moi_khach' => 'nullable|integer|min:1',
        ]);

        foreach ($data['danh_sach_sp'] as $item) {
            if ($item['loai_giam_gia'] === 'phan_tram' && $item['gia_tri_giam'] > 100) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mức giảm theo phần trăm không được vượt quá 100%.',
                ], 422);
            }
        }

        $productIds = array_column($data['danh_sach_sp'], 'san_pham_id');
        if ($this->hasScheduleConflict(null, $productIds, $data['ngay_bat_dau'], $data['ngay_ket_thuc'])) {
            return response()->json([
                'status' => false,
                'message' => 'Có sản phẩm đã nằm trong một chương trình Sale trùng thời gian.',
            ], 422);
        }

        try {
            $id = DB::transaction(function () use ($data) {
                $campaignId = DB::table('chuong_trinh_sales')->insertGetId([
                    'ten_su_kien' => $data['ten_su_kien'],
                    'slug' => Str::slug($data['ten_su_kien']) . '-' . Str::lower(Str::random(8)),
                    'banner_url' => $data['banner_url'] ?? null,
                    'mo_ta' => $data['mo_ta'] ?? null,
                    'ngay_bat_dau' => $data['ngay_bat_dau'],
                    'ngay_ket_thuc' => $data['ngay_ket_thuc'],
                    'trang_thai' => $data['trang_thai'] ?? true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($data['danh_sach_sp'] as $item) {
                    $product = DB::table('san_phams')
                        ->where('id', $item['san_pham_id'])
                        ->lockForUpdate()
                        ->first();
                    $discount = (float) $item['gia_tri_giam'];
                    $salePrice = $item['loai_giam_gia'] === 'phan_tram'
                        ? (float) $product->gia * (1 - $discount / 100)
                        : (float) $product->gia - $discount;

                    DB::table('chi_tiet_san_pham_sales')->insert([
                        'chuong_trinh_sale_id' => $campaignId,
                        'san_pham_id' => $product->id,
                        'gia_goc' => $product->gia,
                        'gia_khuyen_mai' => max(0, round($salePrice, 2)),
                        'loai_giam_gia' => $item['loai_giam_gia'],
                        'gia_tri_giam' => $discount,
                        'so_luong_sale' => $item['so_luong_sale'],
                        'da_ban' => 0,
                        'gioi_han_moi_khach' => $item['gioi_han_moi_khach'] ?? 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return $campaignId;
            });

            return response()->json([
                'status' => true,
                'message' => 'Tạo chương trình Flash Sale thành công.',
                'data' => ['id' => $id],
            ], 201);
        } catch (Throwable $e) {
            Log::error('Không thể tạo chương trình Flash Sale.', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Không thể tạo chương trình Sale.'], 500);
        }
    }

    public function changeStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:chuong_trinh_sales,id',
            'trang_thai' => 'required|boolean',
        ]);

        $campaign = DB::table('chuong_trinh_sales')->where('id', $data['id'])->first();
        if ($data['trang_thai']) {
            $productIds = DB::table('chi_tiet_san_pham_sales')
                ->where('chuong_trinh_sale_id', $campaign->id)
                ->pluck('san_pham_id')
                ->all();
            if ($this->hasScheduleConflict($campaign->id, $productIds, $campaign->ngay_bat_dau, $campaign->ngay_ket_thuc)) {
                return response()->json(['status' => false, 'message' => 'Sản phẩm bị trùng lịch với chương trình Sale khác.'], 422);
            }
        }

        DB::table('chuong_trinh_sales')->where('id', $campaign->id)->update([
            'trang_thai' => $data['trang_thai'],
            'updated_at' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Đã cập nhật trạng thái chương trình Sale.']);
    }

    public function getActiveFlashSale(): JsonResponse
    {
        $now = now();
        $images = DB::table('anh_san_phams')
            ->select('san_pham_id', DB::raw('MIN(id) as image_id'))
            ->groupBy('san_pham_id');

        $products = DB::table('chuong_trinh_sales as campaigns')
            ->join('chi_tiet_san_pham_sales as sale_items', 'campaigns.id', '=', 'sale_items.chuong_trinh_sale_id')
            ->join('san_phams as products', 'sale_items.san_pham_id', '=', 'products.id')
            ->leftJoinSub($images, 'product_images', 'product_images.san_pham_id', '=', 'products.id')
            ->leftJoin('anh_san_phams as images', 'images.id', '=', 'product_images.image_id')
            ->where('campaigns.trang_thai', true)
            ->where('campaigns.ngay_bat_dau', '<=', $now)
            ->where('campaigns.ngay_ket_thuc', '>=', $now)
            ->whereColumn('sale_items.da_ban', '<', 'sale_items.so_luong_sale')
            ->select(
                'products.id as san_pham_id',
                'products.ten as ten_san_pham',
                'images.duong_dan as hinh_anh',
                'sale_items.gia_goc',
                'sale_items.gia_khuyen_mai',
                'sale_items.loai_giam_gia',
                'sale_items.gia_tri_giam',
                'sale_items.so_luong_sale',
                'sale_items.da_ban',
                'sale_items.gioi_han_moi_khach',
                'campaigns.ten_su_kien',
                'campaigns.banner_url',
                'campaigns.ngay_ket_thuc'
            )
            ->orderBy('sale_items.id')
            ->get();

        return response()->json(['status' => true, 'data' => $products]);
    }

    public function destroy(int $id): JsonResponse
    {
        $hasSales = DB::table('chi_tiet_san_pham_sales')
            ->where('chuong_trinh_sale_id', $id)
            ->where('da_ban', '>', 0)
            ->exists();

        if ($hasSales) {
            return response()->json(['status' => false, 'message' => 'Không thể xóa chương trình đã phát sinh đơn Sale.'], 409);
        }

        $deleted = DB::table('chuong_trinh_sales')->where('id', $id)->delete();

        return $deleted
            ? response()->json(['status' => true, 'message' => 'Đã xóa chương trình Sale.'])
            : response()->json(['status' => false, 'message' => 'Không tìm thấy chương trình Sale.'], 404);
    }

    private function hasScheduleConflict(?int $excludedCampaignId, array $productIds, string $start, string $end): bool
    {
        if ($productIds === []) {
            return false;
        }

        return DB::table('chuong_trinh_sales as campaigns')
            ->join('chi_tiet_san_pham_sales as sale_items', 'campaigns.id', '=', 'sale_items.chuong_trinh_sale_id')
            ->where('campaigns.trang_thai', true)
            ->when($excludedCampaignId !== null, fn ($query) => $query->where('campaigns.id', '!=', $excludedCampaignId))
            ->whereIn('sale_items.san_pham_id', $productIds)
            ->where('campaigns.ngay_bat_dau', '<', $end)
            ->where('campaigns.ngay_ket_thuc', '>', $start)
            ->exists();
    }
}
