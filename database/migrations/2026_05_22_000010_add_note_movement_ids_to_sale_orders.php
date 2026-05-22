<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->json('note_movement_ids')
                ->nullable()
                ->after('movement_id')
                ->comment('IDs de Movements "Nota de Venta" creados por pagos parciales; se anulan al emitir comprobante formal.');
        });
    }

    public function down(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropColumn('note_movement_ids');
        });
    }
};
