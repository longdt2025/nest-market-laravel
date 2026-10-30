<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(20) NOT NULL DEFAULT 'customer'");
        } else {
            Schema::table('users', fn (Blueprint $table) => $table->string('role', 20)->default('customer')->change());
        }
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 60)->nullable()->unique();
            $table->boolean('is_active')->default(true);
        });
        DB::table('users')->where('email', 'admin@nest.test')->where('role', 'admin')->update(['username' => 'admin']);
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('label', 120);
            $table->string('path')->unique();
            $table->string('disk', 30);
            $table->string('sha256', 64);
            $table->unsignedBigInteger('size');
            $table->json('counts');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['username', 'is_active']));
    }
};
