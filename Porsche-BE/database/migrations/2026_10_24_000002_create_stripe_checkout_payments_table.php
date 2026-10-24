<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_checkout_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('don_hang_id')->unique()->constrained('don_hangs')->cascadeOnDelete();
            $table->string('checkout_session_id')->unique();
            $table->string('payment_intent_id')->nullable()->index();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('vnd');
            $table->string('status', 30)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('receipt_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_checkout_payments');
    }
};
