<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiClient
{
    private const NO_WEB_NOTE = "\n\nLƯU Ý HỆ THỐNG: Hiện KHÔNG có công cụ tra cứu web. Chỉ dựa trên kiến thức sẵn có và dữ liệu showroom; không khẳng định thông tin mới nhất/tin tức và nói rõ khi không chắc chắn.";

    /**
     * Gọi Gemini generateContent, tự động:
     *  - bỏ Google Search nếu hết hạn mức (429) hoặc không được hỗ trợ,
     *  - chuyển sang model dự phòng khi model chính quá tải / lỗi tạm thời.
     *
     * @param  array<int, array{role: string, text: string}>  $messages  role: 'user' | 'model'
     * @return array{text: string, sources: array<int, array{title: string, url: string}>, web: bool}
     */
    public function generate(string $systemInstruction, array $messages, float $temperature = 0.7, bool $useWebSearch = true): array
    {
        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            throw new RuntimeException('Hệ thống chưa cấu hình GEMINI_API_KEY.');
        }

        $models = array_values(array_unique(array_filter([
            config('services.gemini.model'),
            config('services.gemini.fallback_model'),
        ])));
        $webAllowed = $useWebSearch && config('services.gemini.web_search') && !Cache::has('porsche_ai_web_blocked');
        $webModes = $webAllowed ? [true, false] : [false];

        $lastError = null;
        foreach ($models as $model) {
            foreach ($webModes as $web) {
                try {
                    $response = $this->request($apiKey, $model, $systemInstruction, $messages, $temperature, $web);

                    // Model đang quá tải (503): thử lại 1 lần rồi mới chuyển model dự phòng
                    if ($response->status() === 503) {
                        usleep(800000);
                        $response = $this->request($apiKey, $model, $systemInstruction, $messages, $temperature, $web);
                    }
                } catch (RuntimeException $e) {
                    $lastError = $e;
                    Log::warning($e->getMessage());
                    break;
                }

                if ($response->successful()) {
                    return $this->parse($response, $web);
                }

                $status = $response->status();
                $lastError = new RuntimeException(
                    'Gemini API lỗi (' . $model . ', web=' . ($web ? '1' : '0') . '): ' . ($response->json('error.message') ?? "HTTP {$status}"),
                    $status
                );
                Log::warning($lastError->getMessage());

                // Hết hạn mức / tool không hỗ trợ -> thử lại không tra web; còn lại -> sang model khác
                if ($web && in_array($status, [400, 403, 429], true)) {
                    // Nhớ tạm để các câu hỏi kế tiếp không mất thêm 1 lượt gọi vô ích
                    Cache::put('porsche_ai_web_blocked', true, 600);
                    continue;
                }
                break;
            }
        }

        throw $lastError ?? new RuntimeException('Không gọi được Gemini API.');
    }

    private function request(string $apiKey, string $model, string $system, array $messages, float $temperature, bool $web): Response
    {
        $payload = [
            'systemInstruction' => ['parts' => [['text' => $web ? $system : $system . self::NO_WEB_NOTE]]],
            'contents' => array_map(fn ($m) => [
                'role' => $m['role'],
                'parts' => [['text' => $m['text']]],
            ], $messages),
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => 4096,
            ],
        ];

        // Giảm "suy nghĩ" để phản hồi chat nhanh hơn
        if ($level = config('services.gemini.thinking_level')) {
            $payload['generationConfig']['thinkingConfig'] = ['thinkingLevel' => $level];
        }

        if ($web) {
            $payload['tools'] = [['google_search' => new \stdClass()]];
        }

        try {
            return Http::timeout((int) config('services.gemini.timeout', 45))
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->acceptJson()
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new RuntimeException('Không kết nối được Gemini API: ' . $e->getMessage(), 503, $e);
        }
    }

    private function parse(Response $response, bool $web): array
    {
        $candidate = $response->json('candidates.0');
        $parts = $candidate['content']['parts'] ?? [];
        $text = trim(implode('', array_map(fn ($p) => $p['text'] ?? '', $parts)));

        if ($text === '') {
            $reason = $candidate['finishReason'] ?? $response->json('promptFeedback.blockReason') ?? 'EMPTY';
            throw new RuntimeException('AI không trả về nội dung hợp lệ (' . $reason . ').', 502);
        }

        return ['text' => $text, 'sources' => $web ? $this->extractSources($candidate) : [], 'web' => $web];
    }

    private function extractSources(array $candidate): array
    {
        $sources = [];
        foreach ($candidate['groundingMetadata']['groundingChunks'] ?? [] as $chunk) {
            $url = $chunk['web']['uri'] ?? null;
            if (!$url || isset($sources[$url])) {
                continue;
            }
            $sources[$url] = [
                'title' => $chunk['web']['title'] ?? parse_url($url, PHP_URL_HOST) ?? $url,
                'url' => $url,
            ];
        }

        return array_slice(array_values($sources), 0, 5);
    }
}
