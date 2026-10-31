<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_settings', fn (Blueprint $table) => $table->string('bank_bin', 6)->nullable());
    }

    public function down(): void
    {
        Schema::table('payment_settings', fn (Blueprint $table) => $table->dropColumn('bank_bin'));
    }
};
