<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('products', function (Blueprint $table) {
        $table->id(); $table->foreignId('category_id')->constrained()->restrictOnDelete();
        $table->string('name', 180); $table->string('slug')->unique();
        $table->text('description')->nullable(); $table->unsignedBigInteger('price');
        $table->unsignedBigInteger('compare_at_price')->nullable(); $table->unsignedInteger('stock')->default(0);
        $table->string('image_path')->nullable(); $table->boolean('is_active')->default(true);
        $table->boolean('is_featured')->default(false); $table->timestamps();
        $table->index(['is_active', 'category_id']);
    }); }
    public function down(): void { Schema::dropIfExists('products'); }
};
