<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->timestamp('pickup_at')->nullable()->after('delivered_by')
                ->comment('Fecha y hora programada de recojo en tienda');
            $table->string('pickup_responsible', 255)->nullable()->after('pickup_at')
                ->comment('Nombre del responsable que recogerá el pedido');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['pickup_at', 'pickup_responsible']);
        });
    }
};
