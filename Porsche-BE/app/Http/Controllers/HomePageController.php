<?php

namespace App\Http\Controllers;

use App\Models\PhuTung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\JsonResponse;
use Exception;

class HomePageController extends Controller
{
    public function homePageData(): JsonResponse
    {
        try {
            $hangPhuTung = $this->queryGoiDoClient()->take(6)->get();
            $chuyenGia = Schema::hasTable('chuyen_gias')
                ? DB::table('chuyen_gias')->where('trang_thai', 1)->take(4)->get()
                : collect();
            $khuVuc = Schema::hasTable('khu_vuc_workshops')
                ? DB::table('khu_vuc_workshops')->where('trang_thai', 1)->take(3)->get()
                : collect();

            return response()->json([
                'status' => true,
                'data' => [
                    'hang_phu_tung' => $hangPhuTung,
                    'chuyen_gia'    => $chuyenGia,
                    'khu_vuc'       => $khuVuc
                ]
            ], 200);
        } catch (Exception $e) {
            Log::error('Lỗi homePageData: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi tải trang chủ.'], 500);
        }
    }

    public function getChuyenKhoaClient(): JsonResponse
    {
        try {
            $data = DB::table('hang_phu_tungs')->where('trang_thai', 1)->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getChuyenKhoaClient: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi tải danh mục hãng.'], 500);
        }
    }

    public function getChuyenKhoaClientById($id): JsonResponse
    {
        try {
            $data = DB::table('hang_phu_tungs')->where('id', $id)->where('trang_thai', 1)->first();
            if (!$data) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy hãng phụ tùng.'], 404);
            }
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getChuyenKhoaClientById: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi tải chi tiết hãng.'], 500);
        }
    }

    public function getBacSiClient(): JsonResponse
    {
        try {
            $data = DB::table('chuyen_gias')
                ->join('hang_phu_tungs', 'chuyen_gias.id_hang_phu_tung', '=', 'hang_phu_tungs.id')
                ->select('chuyen_gias.*', 'hang_phu_tungs.ten_hang')
                ->where('chuyen_gias.trang_thai', 1)
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getBacSiClient: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi tải danh sách chuyên gia.'], 500);
        }
    }

    public function getBacSiClientByIds($id): JsonResponse
    {
        try {
            $data = DB::table('chuyen_gias')->where('id', $id)->where('trang_thai', 1)->first();
            if (!$data) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy thông tin chuyên gia.'], 404);
            }
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getBacSiClientByIds: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi tải chi tiết chuyên gia.'], 500);
        }
    }

    public function getGoiDoClient(): JsonResponse
    {
        try {
            $data = $this->queryGoiDoClient()
                ->orderBy('san_phams.id')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $this->ganAnhSanPham($data),
            ], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getGoiDoClient: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi tải danh sách gói độ.'], 500);
        }
    }

    public function getGoiDoClientById($id): JsonResponse
    {
        try {
            $sanPham = $this->queryGoiDoClient()
                ->where(function ($query) use ($id) {
                    $query->where('san_phams.id', $id)
                        ->orWhere('san_phams.slug', $id);
                })
                ->first();

            if (!$sanPham) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy gói độ.'], 404);
            }

            $data = $this->ganAnhSanPham(collect([$sanPham]))->first();

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getGoiDoClientById: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi tải chi tiết gói độ.'], 500);
        }
    }

    public function getPhuTungClient(): JsonResponse
    {
        try {
            $data = PhuTung::query()
                ->orderBy('id')
                ->get(['id', 'ten', 'gia', 'so_luong', 'loai', 'mo_ta', 'bao_gom', 'dong_xe', 'anh']);

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getPhuTungClient: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi tải danh sách phụ tùng.'], 500);
        }
    }

    public function getPhuTungClientById($id): JsonResponse
    {
        try {
            $data = PhuTung::query()
                ->where('id', $id)
                ->first(['id', 'ten', 'gia', 'so_luong', 'loai', 'mo_ta', 'bao_gom', 'dong_xe', 'anh']);

            if (!$data) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy phụ tùng.'], 404);
            }

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getPhuTungClientById: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi tải chi tiết phụ tùng.'], 500);
        }
    }

    public function getMauXeClient(): JsonResponse
    {
        try {
            $data = DB::table('mau_xes')
                ->join('hang_xes', 'mau_xes.hang_xe_id', '=', 'hang_xes.id')
                ->orderBy('mau_xes.id')
                ->get([
                    'mau_xes.id',
                    'mau_xes.ten',
                    'mau_xes.mo_ta',
                    'mau_xes.hang_xe_id',
                    'hang_xes.ten as hang_xe',
                    'hang_xes.slug as hang_xe_slug',
                    'hang_xes.logo as hang_xe_logo',
                ]);

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getMauXeClient: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi tải danh sách mẫu xe.'], 500);
        }
    }

    private function queryGoiDoClient()
    {
        return DB::table('san_phams')
            ->leftJoin('mau_xes', 'san_phams.mau_xe_id', '=', 'mau_xes.id')
            ->select('san_phams.*', 'mau_xes.ten as mau_xe_tuong_thich')
            ->whereNotNull('san_phams.slug')
            ->where('san_phams.slug', '!=', '');
    }

    private function ganAnhSanPham($sanPhams)
    {
        if ($sanPhams->isEmpty()) {
            return $sanPhams;
        }

        $anhTheoSanPham = DB::table('anh_san_phams')
            ->whereIn('san_pham_id', $sanPhams->pluck('id'))
            ->orderByDesc('la_anh_chinh')
            ->orderBy('id')
            ->get(['san_pham_id', 'duong_dan'])
            ->groupBy('san_pham_id');

        foreach ($sanPhams as $sanPham) {
            $anh = $anhTheoSanPham->get($sanPham->id, collect())->first();
            $sanPham->anh = $anh ? $anh->duong_dan : null;
        }

        return $sanPhams;
    }
}
