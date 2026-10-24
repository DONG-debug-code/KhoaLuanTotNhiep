<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vnpay_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('don_hang_id')->unique()->constrained('don_hangs')->cascadeOnDelete();
            $table->string('txn_ref', 64)->unique();
            $table->unsignedBigInteger('amount');
            $table->string('status', 30)->default('pending');
            $table->string('transaction_no', 64)->nullable();
            $table->string('bank_code', 30)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('receipt_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('stripe_checkout_payments');
    }

    public function down(): void
    {
        Schema::dropIfExists('vnpay_payments');
    }
};
