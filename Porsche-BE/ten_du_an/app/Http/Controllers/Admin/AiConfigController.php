<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;

class AiConfigController extends Controller
{
    /**
     * LẤY CẤU HÌNH HỆ THỐNG AI HIỆN TẠI
     * URL: GET /api/admin/ai/settings
     */
    public function index(): JsonResponse
    {
        try {
            $systemPrompt = DB::table('ai_configs')->where('key', 'system_prompt')->value('value');
            $temperature = DB::table('ai_configs')->where('key', 'temperature')->value('value');

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'system_prompt' => $systemPrompt ?? "Bạn là chuyên gia tư vấn nâng cấp xe Porsche cao cấp...",
                    'temperature'   => $temperature ?? "0.7"
                ]
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Lỗi lấy cấu hình AI: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Không thể tải cấu hình AI.'], 500);
        }
    }

    /**
     * CẬP NHẬT CẤU HÌNH AI
     * URL: POST /api/admin/ai/settings
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'system_prompt' => 'required|string|min:10|max:2000',
            'temperature'   => 'required|numeric|between:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        DB::beginTransaction();
        try {
            DB::table('ai_configs')->updateOrInsert(
                ['key' => 'system_prompt'],
                ['value' => $request->input('system_prompt'), 'updated_at' => now()]
            );

            DB::table('ai_configs')->updateOrInsert(
                ['key' => 'temperature'],
                ['value' => $request->input('temperature'), 'updated_at' => now()]
            );

            DB::commit();

            // Xóa cache cấu hình cũ ngay lập tức để AI nhận diện prompt mới của Admin
            Cache::forget('porsche_ai_system_prompt');
            Cache::forget('porsche_ai_temperature');

            return response()->json(['status' => 'success', 'message' => 'Cập nhật cấu hình AI thành công!'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Lỗi lưu cấu hình AI: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Lưu cấu hình thất bại.'], 500);
        }
    }
}
