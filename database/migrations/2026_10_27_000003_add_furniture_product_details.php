<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('room', 30)->nullable()->index();
            $table->string('material', 180)->nullable();
            $table->string('dimensions', 180)->nullable();
            $table->string('color', 100)->nullable();
            $table->unsignedSmallInteger('warranty_months')->nullable();
            $table->text('care_instructions')->nullable();
            $table->json('gallery')->nullable();
            $table->string('catalog_code', 80)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['catalog_code']);
            $table->dropIndex(['room']);
            $table->dropColumn(['room', 'material', 'dimensions', 'color', 'warranty_months', 'care_instructions', 'gallery', 'catalog_code']);
        });
    }
};
