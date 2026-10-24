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
            $experts = DB::table('chuyen_gias')->where('trang_thai', 1)->orderBy('id')->get();
            $ratings = DB::table('danh_gia_chuyen_gias')
                ->selectRaw('id_chuyen_gia, AVG(so_sao) as diem, COUNT(*) as so_luong')
                ->groupBy('id_chuyen_gia')
                ->get()
                ->keyBy('id_chuyen_gia');

            $data = $experts->map(fn ($expert) => $this->presentExpert($expert, $ratings->get($expert->id)))->values();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getBacSiClient: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi tải danh sách chuyên gia.'], 500);
        }
    }

    public function getBacSiClientByIds($id): JsonResponse
    {
        try {
            $expert = DB::table('chuyen_gias')->where('id', $id)->where('trang_thai', 1)->first();
            if (!$expert) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy thông tin chuyên gia.'], 404);
            }

            $rating = DB::table('danh_gia_chuyen_gias')
                ->selectRaw('AVG(so_sao) as diem, COUNT(*) as so_luong')
                ->where('id_chuyen_gia', $expert->id)
                ->first();

            $data = $this->presentExpert($expert, $rating);
            $data['danh_gia_chi_tiet'] = DB::table('danh_gia_chuyen_gias')
                ->where('id_chuyen_gia', $expert->id)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(['ten_khach_hang', 'so_sao', 'noi_dung', 'created_at']);
            $data['lich_lam_viec'] = DB::table('lich_lam_viecs')
                ->where('id_chuyen_gia', $expert->id)
                ->whereDate('ngay_lam_viec', '>=', now()->toDateString())
                ->orderBy('ngay_lam_viec')
                ->orderBy('ca_lam_viec')
                ->limit(30)
                ->get(['ngay_lam_viec', 'ca_lam_viec']);
            $data['lich_su_cong_viec'] = DB::table('lich_su_cong_viec_chuyen_gias')
                ->where('id_chuyen_gia', $expert->id)
                ->orderByDesc('ngay_hoan_thanh')
                ->limit(10)
                ->get(['tieu_de', 'ten_xe', 'mo_ta', 'ngay_hoan_thanh']);

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getBacSiClientByIds: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi tải chi tiết chuyên gia.'], 500);
        }
    }

    private function presentExpert(object $expert, ?object $rating): array
    {
        $list = function ($value): array {
            $decoded = is_string($value) ? json_decode($value, true) : $value;
            return is_array($decoded) ? array_values($decoded) : [];
        };
        $photo = $expert->anh ?? null;
        if ($photo && !preg_match('#^(https?:)?//#i', $photo)) {
            $photo = url(ltrim($photo, '/'));
        }

        return [
            'id' => $expert->id,
            'ho_ten' => $expert->ho_ten,
            'email' => $expert->email ?? null,
            'so_dien_thoai' => $expert->so_dien_thoai ?? null,
            'gioi_tinh' => $expert->gioi_tinh ?? null,
            'ngay_sinh' => $expert->ngay_sinh ?? null,
            'dia_chi' => $expert->dia_chi ?? null,
            'ma_so_tuner' => $expert->ma_so_tuner ?? null,
            'chuc_danh' => $expert->chuc_danh ?? null,
            'mo_ta' => $expert->mo_ta ?? null,
            'anh' => $photo,
            'so_nam_kinh_nghiem' => (int) ($expert->so_nam_kinh_nghiem ?? 0),
            'so_du_an_hoan_thanh' => (int) ($expert->so_du_an_hoan_thanh ?? 0),
            'chuyen_mon' => $list($expert->chuyen_mon ?? null),
            'dong_xe' => $list($expert->dong_xe ?? null),
            'trang_thai' => $expert->tinh_trang ?? 'san-sang',
            'diem_danh_gia' => $rating && $rating->so_luong ? round((float) $rating->diem, 1) : null,
            'so_danh_gia' => (int) ($rating->so_luong ?? 0),
        ];
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
