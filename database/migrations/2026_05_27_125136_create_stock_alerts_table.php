<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('branch_id');
            $table->integer('current_stock')->default(0);
            $table->integer('stock_minimum')->default(0);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'branch_id']);

            $table->foreign('product_id')
                ->references('id')->on('products')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('branch_id')
                ->references('id')->on('branches')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
