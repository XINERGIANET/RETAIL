<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->string('delivery_status', 20)
                ->nullable()
                ->after('status')
                ->comment('NULL = sin delivery · PENDIENTE = por entregar · ENTREGADO = entregado');
        });
    }

    public function down(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropColumn('delivery_status');
        });
    }
};
