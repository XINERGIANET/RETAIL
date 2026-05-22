<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_order_id')->constrained('sale_orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->json('product_snapshot')->nullable()->comment('Snapshot del producto al momento del pedido');
            $table->decimal('quantity', 24, 6)->comment('Cantidad original pedida');
            $table->decimal('unit_price', 24, 6)->comment('Precio unitario al momento del pedido');
            $table->decimal('subtotal', 24, 6)->comment('quantity × unit_price');
            $table->decimal('returned_qty', 24, 6)->default(0)->comment('Cantidad devuelta acumulada');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sale_order_id']);
            $table->index(['sale_order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_order_items');
    }
};
