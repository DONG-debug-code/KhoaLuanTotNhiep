<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        $migration = require database_path('migrations/2026_11_17_000001_create_password_resets_table.php');
        $migration->up();

        DB::table('users')->insert([
            'email' => 'customer@example.com',
            'password' => Hash::make('old-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_customer_can_request_and_use_a_password_reset_code(): void
    {
        $sentToken = null;
        Mail::shouldReceive('send')
            ->once()
            ->with('emails.quen_mat_khau', Mockery::on(function (array $data) use (&$sentToken): bool {
                $sentToken = $data['otp'];
                $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $sentToken);
                $this->assertSame('customer@example.com', $data['email']);
                $this->assertStringContainsString($sentToken, view('emails.quen_mat_khau', $data)->render());

                return true;
            }), Mockery::type('Closure'));

        $this->postJson('/api/khach-hang/quen-mat-khau', [
            'email' => 'customer@example.com',
        ])->assertOk()->assertJsonPath('status', true);

        $storedToken = DB::table('password_resets')
            ->where('email', 'customer@example.com')
            ->value('token');
        $this->assertNotSame($sentToken, $storedToken);
        $this->assertTrue(Hash::check($sentToken, $storedToken));

        $this->postJson('/api/khach-hang/doi-mat-khau', [
            'email' => 'customer@example.com',
            'token' => $sentToken,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk()->assertJsonPath('status', true);

        $this->assertTrue(Hash::check(
            'new-password',
            DB::table('users')->where('email', 'customer@example.com')->value('password')
        ));
        $this->assertDatabaseMissing('password_resets', ['email' => 'customer@example.com']);
    }

    public function test_password_reset_is_blocked_after_too_many_wrong_codes(): void
    {
        DB::table('password_resets')->insert([
            'email' => 'customer@example.com',
            'token' => Hash::make('ABC123'),
            'created_at' => now(),
        ]);
        $payload = [
            'email' => 'customer@example.com',
            'token' => 'ZZZZZZ',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/khach-hang/doi-mat-khau', $payload)->assertStatus(400);
        }

        $this->postJson('/api/khach-hang/doi-mat-khau', [...$payload, 'token' => 'abc123'])
            ->assertStatus(429);
    }

    public function test_expired_password_reset_codes_are_rejected(): void
    {
        DB::table('password_resets')->insert([
            'email' => 'customer@example.com',
            'token' => Hash::make('ABC123'),
            'created_at' => now()->subMinutes(11),
        ]);

        $this->postJson('/api/khach-hang/doi-mat-khau', [
            'email' => 'customer@example.com',
            'token' => 'ABC123',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertStatus(400)->assertJsonPath('status', false);

        $this->assertTrue(Hash::check(
            'old-password',
            DB::table('users')->where('email', 'customer@example.com')->value('password')
        ));
    }
}
