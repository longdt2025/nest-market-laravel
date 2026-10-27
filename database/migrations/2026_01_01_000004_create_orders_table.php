<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('orders', function (Blueprint $table) {
        $table->id(); $table->string('code', 32)->unique();
        $table->foreignId('user_id')->constrained()->restrictOnDelete();
        $table->string('recipient_name', 120); $table->string('phone', 20);
        $table->text('shipping_address'); $table->text('note')->nullable();
        $table->unsignedBigInteger('subtotal'); $table->unsignedBigInteger('shipping_fee')->default(0);
        $table->unsignedBigInteger('total'); $table->string('payment_method', 20)->default('cod');
        $table->enum('status', ['pending','confirmed','shipping','delivered','cancelled'])->default('pending');
        $table->timestamps(); $table->index(['user_id', 'created_at']);
    }); }
    public function down(): void { Schema::dropIfExists('orders'); }
};
