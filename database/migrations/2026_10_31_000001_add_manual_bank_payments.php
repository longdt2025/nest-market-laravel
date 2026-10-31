<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('bank_name', 120);
            $table->string('account_number', 50);
            $table->string('account_holder', 120);
            $table->string('qr_path');
            $table->timestamps();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status', 30)->default('unpaid')->index();
            $table->timestamp('paid_at')->nullable();
            $table->json('payment_details')->nullable();
            $table->string('payment_qr_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropColumn(['payment_status', 'paid_at', 'payment_details', 'payment_qr_path']);
        });
        Schema::dropIfExists('payment_settings');
    }
};
