<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->string('variant_name');
            $table->bigInteger('unit_price');
            $table->bigInteger('unit_cost');
            $table->integer('quantity');
            $table->bigInteger('modifiers_total')->default(0);
            $table->bigInteger('line_total');
            $table->integer('sort_order')->default(0);

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
