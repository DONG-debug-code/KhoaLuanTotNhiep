<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AiController extends Controller
{
    /**
     * Xử lý gửi câu hỏi lên Google Gemini API có tích hợp KHO HÀNG THỰC TẾ
     * Tự động nhận biết sản phẩm CÒN HÀNG / HẾT HÀNG để tư vấn chính xác
     * URL: POST /api/ai/ask
     */
    public function askAI(Request $request): JsonResponse
    {
        // 1. Validate dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|min:3|max:1000',
        ], [
            'message.required' => 'Câu hỏi không được để trống.',
            'message.string'   => 'Câu hỏi phải là một chuỗi văn bản.',
            'message.min'      => 'Câu hỏi quá ngắn, vui lòng nhập tối thiểu 3 ký tự.',
            'message.max'      => 'Câu hỏi vượt quá giới hạn cho phép.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors()->first()
            ], 422);
        }

        $userInput = $request->input('message');
        $userId = Auth::id();

        try {
            // 2. TRUY VẤN TOÀN BỘ DỮ LIỆU TỪ CSDL (Bao gồm cả mặt hàng hết hàng)
            // A. Lấy tất cả phụ tùng từ bảng phu_tungs
            $khoPhuTung = DB::table('phu_tungs')
                ->select('ten', 'gia', 'loai', 'so_luong', 'mo_ta')
                ->get();

            // B. Lấy tất cả xe/sản phẩm từ bảng san_phams
            $khoXe = DB::table('san_phams')
                ->select('ten', 'gia', 'so_luong', 'mo_ta')
                ->get();

            // 3. XÂY DỰNG PROMPT NỀN KÈM TRẠNG THÁI TỒN KHO THỰC TẾ
            $systemPrompt = "BẠN LÀ AI CHUYÊN GIA TƯ VẤN KỸ THUẬT, MUA BÁN XE VÀ NÂNG CẤP GÓI ĐỘ CAO CẤP TẠI GARAGE PORSCHE WORKSHOP.\n";
            $systemPrompt .= "Nhiệm vụ của bạn là tư vấn mua xe, gói độ (nội thất, ngoại thất, hiệu suất, công nghệ) và giải đáp thắc mắc cho khách hàng dựa trên dữ liệu kho hàng dưới đây.\n\n";
            
            $systemPrompt .= "=== DANH MỤC PHỤ TÙNG & TRẠNG THÁI KHO CỦA GARAGE ===\n";
            if ($khoPhuTung->isEmpty()) {
                $systemPrompt .= "- Danh mục phụ tùng hiện chưa có dữ liệu trên hệ thống.\n";
            } else {
                foreach ($khoPhuTung as $pt) {
                    $trangThai = $pt->so_luong > 0 ? "Còn hàng (Sẵn có: {$pt->so_luong})" : "TẠM HẾT HÀNG (Tồn kho: 0)";
                    $systemPrompt .= "- {$pt->ten} (Loại: {$pt->loai}) | Giá: " . number_format($pt->gia) . " VNĐ | Trạng thái: {$trangThai}\n";
                }
            }

            $systemPrompt .= "\n=== DANH MỤC XE PORSCHE & TRẠNG THÁI KHO GIAO NGAY ===\n";
            if ($khoXe->isEmpty()) {
                $systemPrompt .= "- Danh mục xe nguyên chiếc hiện chưa có dữ liệu trên hệ thống.\n";
            } else {
                foreach ($khoXe as $xe) {
                    $trangThaiXe = $xe->so_luong > 0 ? "Còn hàng (Sẵn có: {$xe->so_luong} chiếc)" : "TẠM HẾT HÀNG (Tồn kho: 0)";
                    $systemPrompt .= "- {$xe->ten} | Giá: " . number_format($xe->gia) . " VNĐ | Trạng thái: {$trangThaiXe}\n";
                }
            }

            $systemPrompt .= "\nQUY TẮC TƯ VẤN VÀ PHẢN HỒI KHÁCH HÀNG:\n";
            $systemPrompt .= "1. Trả lời bằng tiếng Việt chuyên nghiệp, sang trọng, am hiểu kỹ thuật xe Porsche.\n";
            $systemPrompt .= "2. Khi khách hàng hỏi về một sản phẩm/phụ tùng/xe đang ở trạng thái 'TẠM HẾT HÀNG' (so_luong = 0), hãy THÔNG BÁO RÕ RÀNG là sản phẩm đó hiện tại vừa HẾT HÀNG, sau đó lịch sự gợi ý các món tương tự đang 'Còn hàng' trong danh sách trên.\n";
            $systemPrompt .= "3. Nếu khách hỏi sản phẩm/phụ tùng hoàn toàn KHÔNG CÓ tên trong danh mục ở trên, hãy lịch sự giải thích garage chưa kinh doanh mặt hàng này và hướng khách tham khảo các tùy chọn có sẵn.\n";
            $systemPrompt .= "4. Nếu câu hỏi không liên quan đến ô tô, hãy từ chối lịch sự và hướng khách về các dịch vụ chăm sóc xe Porsche.\n\n";
            $systemPrompt .= "Thắc mắc/Yêu cầu từ khách hàng: " . $userInput;

            // 4. Lấy API Key từ file .env
            $apiKey = env('GEMINI_API_KEY');

            if (!$apiKey) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Hệ thống chưa cấu hình API Key cho AI trong file .env.'
                ], 500);
            }

            // 5. Endpoint Gemini API (Model gemini-1.5-flash)
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
            
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $systemPrompt]
                        ]
                    ]
                ]
            ];

            // 6. Thực thi cURL gửi Request sang Gemini API
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $result = curl_exec($ch);

            if (curl_errno($ch)) {
                $errorMsg = curl_error($ch);
                curl_close($ch);
                return response()->json(['status' => 'error', 'message' => 'Lỗi cURL: ' . $errorMsg], 500);
            }

            curl_close($ch);
            $responseData = json_decode($result, true);

            if (isset($responseData['error'])) {
                $googleError = $responseData['error']['message'] ?? json_encode($responseData['error']);
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Lỗi từ Google AI API: ' . $googleError
                ], 400);
            }

            $aiOutput = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$aiOutput) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Không nhận được dữ liệu phản hồi hợp lệ từ AI.'
                ], 502);
            }

            // 7. Lưu lịch sử hội thoại vào Database nếu người dùng đã đăng nhập ($userId khác null)
            if ($userId) {
                try {
                    DB::table('ai_goi_y_ket_quas')->insert([
                        'user_id'    => $userId,
                        'input_user' => $userInput,
                        'output_ai'  => $aiOutput,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Throwable $dbEx) {
                    Log::error('Lỗi lưu lịch sử AI DB: ' . $dbEx->getMessage());
                }
            }

            // 8. Trả về Response thành công
            return response()->json([
                'status' => 'success',
                'data'   => [
                    'question' => $userInput,
                    'answer'   => $aiOutput
                ]
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Lỗi hệ thống AI tại dòng ' . $e->getLine() . ': ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Xảy ra lỗi hệ thống cục bộ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách lịch sử chat với AI của người dùng
     * URL: GET /api/ai/history
     */
    public function getHistory(): JsonResponse
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Vui lòng đăng nhập để xem lịch sử tư vấn.'
                ], 401);
            }

            $history = DB::table('ai_goi_y_ket_quas')
                ->where('user_id', $userId)
                ->select('id', 'input_user', 'output_ai', 'created_at')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $history
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Lỗi lấy lịch sử AI: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Không thể tải lịch sử hỏi đáp.'
            ], 500);
        }
    }

    /**
     * Xóa toàn bộ lịch sử tư vấn AI của người dùng
     * URL: DELETE /api/ai/clear-history
     */
    public function clearHistory(): JsonResponse
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Thao tác không hợp lệ. Bạn chưa đăng nhập.'
                ], 401);
            }

            DB::table('ai_goi_y_ket_quas')->where('user_id', $userId)->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Đã xóa toàn bộ lịch sử tư vấn thành công.'
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Lỗi xóa lịch sử AI: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Không thể xóa lịch sử hệ thống lúc này.'
            ], 500);
        }
    }
}