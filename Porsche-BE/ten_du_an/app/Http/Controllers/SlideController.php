<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class SlideController extends Controller
{
    public function getDataSlide(): JsonResponse
    {
        try {
            $data = DB::table('slides')->orderBy('thu_tu', 'asc')->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getDataSlide: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Không thể kết xuất dữ liệu slide banner.'], 500);
        }
    }

    public function createSlide(Request $request): JsonResponse
    {
        $request->validate([
            'tieu_de'   => 'required|string|max:255',
            'hinh_anh'  => 'required|string', // Đường dẫn hình ảnh dạng chuỗi (URL hoặc Base64/Tên file)
            'duong_dan' => 'nullable|string',
            'thu_tu'    => 'required|integer'
        ]);

        try {
            DB::table('slides')->insert([
                'tieu_de'    => $request->tieu_de,
                'hinh_anh'   => $request->hinh_anh,
                'duong_dan'  => $request->duong_dan,
                'thu_tu'     => $request->thu_tu,
                'trang_thai' => 1, // Mặc định hiển thị kích hoạt
                'created_at' => now(),
                'updated_at' => now()
            ]);
            return response()->json(['status' => true, 'message' => 'Tạo mới Slide Banner Marketing thành công.'], 201);
        } catch (Exception $e) {
            Log::error('Lỗi createSlide: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi máy chủ, không thể thêm slide.'], 500);
        }
    }

    public function updateSlide(Request $request): JsonResponse
    {
        $request->validate([
            'id'        => 'required|integer|exists:slides,id',
            'tieu_de'   => 'required|string|max:255',
            'hinh_anh'  => 'required|string',
            'thu_tu'    => 'required|integer',
            'trang_thai'=> 'required|in:0,1'
        ]);

        try {
            DB::table('slides')->where('id', $request->id)->update([
                'tieu_de'    => $request->tieu_de,
                'hinh_anh'   => $request->hinh_anh,
                'duong_dan'  => $request->duong_dan,
                'thu_tu'     => $request->thu_tu,
                'trang_thai' => $request->trang_thai,
                'updated_at' => now()
            ]);
            return response()->json(['status' => true, 'message' => 'Cập nhật nội dung cấu hình Slide thành công.'], 200);
        } catch (Exception $e) {
            Log::error('Lỗi updateSlide: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Cập nhật thất bại.'], 500);
        }
    }

    public function deleteSlide(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer|exists:slides,id']);
        try {
            $slide = DB::table('slides')->where('id', $request->id)->first();

            // Nếu hinh_anh lưu path tệp vật lý, tiến hành gỡ bỏ khỏi ổ cứng để tiết kiệm không gian hosting
            if ($slide && file_exists(public_path($slide->hinh_anh))) {
                @unlink(public_path($slide->hinh_anh));
            }

            DB::table('slides')->where('id', $request->id)->delete();
            return response()->json(['status' => true, 'message' => 'Gỡ bỏ Slide Banner thành công.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Xóa Slide thất bại do ràng buộc tài nguyên.'], 500);
        }
    }

    public function changeStatusSlide(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer|exists:slides,id']);
        try {
            $slide = DB::table('slides')->where('id', $request->id)->first();
            $newStatus = ($slide->trang_thai == 1) ? 0 : 1;

            DB::table('slides')->where('id', $request->id)->update([
                'trang_thai' => $newStatus,
                'updated_at' => now()
            ]);
            return response()->json(['status' => true, 'trang_thai_moi' => $newStatus, 'message' => 'Thay đổi trạng thái hiển thị Slide thành công.'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
