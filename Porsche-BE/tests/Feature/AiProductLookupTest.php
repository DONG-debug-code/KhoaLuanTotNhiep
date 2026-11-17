<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiProductLookupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('ai_configs');
        Schema::create('ai_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('value')->nullable();
        });
        Cache::flush();
        config(['services.gemini.key' => 'test-key', 'services.gemini.web_search' => true]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('ai_configs');
        Cache::flush();

        parent::tearDown();
    }

    public function test_product_questions_use_web_search_and_return_external_sources(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Showroom chưa bán ống xả này. Xem thêm: https://akrapovic.example/exhaust']]],
                    'groundingMetadata' => ['groundingChunks' => [
                        ['web' => ['uri' => 'https://akrapovic.example/exhaust', 'title' => 'Akrapovič Exhaust']],
                    ]],
                ]],
            ]),
        ]);

        $response = $this->postJson('/api/ai/ask', ['message' => 'Tôi muốn tìm hiểu ống xả Akrapovič cho 911', 'mode' => 'goi_do']);

        $response->assertOk()
            ->assertJsonPath('data.web', true)
            ->assertJsonPath('data.sources.0.url', 'https://akrapovic.example/exhaust');

        Http::assertSent(function ($request) {
            $system = $request['systemInstruction']['parts'][0]['text'];

            return isset($request['tools'][0]['google_search'])
                && str_contains($system, 'TRA CỨU TRÊN MẠNG')
                && str_contains($system, 'tuyệt đối không tự bịa link');
        });
    }
}
