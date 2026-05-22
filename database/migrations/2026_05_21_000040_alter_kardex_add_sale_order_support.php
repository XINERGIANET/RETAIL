<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kardex', function (Blueprint $table) {
            // Hacer nullable movimiento_id para soportar entradas de pedidos (sin movement todavía)
            $table->dropForeign(['movimiento_id']);
            $table->unsignedBigInteger('movimiento_id')->nullable()->change();
            $table->foreign('movimiento_id')->references('id')->on('movements')->cascadeOnUpdate()->cascadeOnDelete();

            // Nueva FK hacia el pedido de venta (solo se usa cuando movimiento_id es NULL)
            $table->foreignId('sale_order_id')
                ->nullable()
                ->after('movimiento_id')
                ->constrained('sale_orders')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->index(['sale_order_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::table('kardex', function (Blueprint $table) {
            $table->dropIndex(['sale_order_id', 'producto_id']);
            $table->dropForeign(['sale_order_id']);
            $table->dropColumn('sale_order_id');

            $table->dropForeign(['movimiento_id']);
            $table->unsignedBigInteger('movimiento_id')->nullable(false)->change();
            $table->foreign('movimiento_id')->references('id')->on('movements')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }
};
