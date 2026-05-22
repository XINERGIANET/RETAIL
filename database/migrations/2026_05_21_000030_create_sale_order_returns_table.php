<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_order_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_order_id')->constrained('sale_orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('sale_order_item_id')->constrained('sale_order_items')->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('quantity', 24, 6)->comment('Unidades devueltas en esta operación');
            $table->decimal('unit_price', 24, 6)->comment('Precio unitario al momento de la devolución');
            $table->decimal('subtotal', 24, 6)->comment('quantity × unit_price');
            $table->timestamp('returned_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('created_by_name', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sale_order_id']);
            $table->index(['sale_order_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_order_returns');
    }
};
