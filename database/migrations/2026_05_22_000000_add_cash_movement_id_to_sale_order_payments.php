<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_order_payments', function (Blueprint $table) {
            $table->foreignId('cash_movement_id')
                ->nullable()
                ->after('created_by_name')
                ->constrained('cash_movements')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('NULL hasta que el pago sea registrado en caja; se asigna en recordPayment()');
        });
    }

    public function down(): void
    {
        Schema::table('sale_order_payments', function (Blueprint $table) {
            $table->dropForeign(['cash_movement_id']);
            $table->dropColumn('cash_movement_id');
        });
    }
};
