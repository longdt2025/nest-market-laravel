<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku', 60)->nullable()->unique();
            $table->string('barcode', 80)->nullable()->unique();
            $table->string('unit', 30)->default('Cái');
            $table->unsignedBigInteger('cost_price')->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['sku', 'barcode', 'unit', 'cost_price', 'vat_rate']));
    }
};
