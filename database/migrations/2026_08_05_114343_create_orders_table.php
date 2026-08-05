<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('channel_id')->constrained()->restrictOnDelete();
            $table->string('order_number');
            $table->string('external_order_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->timestamp('occurred_at');
            $table->string('entry_mode')->default('manual');
            $table->string('status')->default('completed');
            $table->text('cancelled_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->bigInteger('subtotal');
            $table->bigInteger('discount_amount')->default(0);
            $table->bigInteger('total');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'order_number']);
            $table->index(['organization_id', 'occurred_at']);
            $table->index(['channel_id', 'occurred_at']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX orders_channel_id_external_order_id_unique ON orders (channel_id, external_order_id) WHERE external_order_id IS NOT NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
