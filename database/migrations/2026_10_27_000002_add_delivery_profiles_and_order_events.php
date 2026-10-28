<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('delivery_name', 120)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('shipping_address')->nullable();
        });
        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 20)->nullable();
            $table->string('type', 32);
            $table->string('from_status', 20)->nullable();
            $table->string('status', 20);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'created_at']);
        });
        // Preserve the known state of older orders without inventing transition times.
        DB::table('orders')->orderBy('id')->chunkById(200, function ($orders) {
            foreach ($orders as $order) {
                DB::table('order_events')->insert([
                    'order_id' => $order->id, 'type' => 'legacy', 'status' => $order->status,
                    'note' => 'Bắt đầu theo dõi lịch sử. Các thay đổi trước thời điểm này chưa được ghi nhận.',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['delivery_name', 'phone', 'shipping_address']));
    }
};
