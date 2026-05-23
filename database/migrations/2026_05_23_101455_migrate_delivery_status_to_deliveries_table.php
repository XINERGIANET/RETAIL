<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrar sale_orders con delivery_status existente
        DB::table('sale_orders')
            ->whereNotNull('delivery_status')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->each(function ($order) {
                DB::table('deliveries')->insertOrIgnore([
                    'deliverable_type' => 'App\\Models\\SaleOrder',
                    'deliverable_id'   => $order->id,
                    'status'           => $order->delivery_status,
                    'delivered_at'     => $order->delivery_status === 'ENTREGADO' ? now() : null,
                    'delivered_by'     => null,
                    'notes'            => null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            });

        // Migrar sales_movements con delivery_status existente
        DB::table('sales_movements')
            ->whereNotNull('delivery_status')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->each(function ($sm) {
                if (!$sm->movement_id) return;

                DB::table('deliveries')->insertOrIgnore([
                    'deliverable_type' => 'App\\Models\\Movement',
                    'deliverable_id'   => $sm->movement_id,
                    'status'           => $sm->delivery_status,
                    'delivered_at'     => $sm->delivery_status === 'ENTREGADO' ? now() : null,
                    'delivered_by'     => null,
                    'notes'            => null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            });

        // Eliminar columnas obsoletas
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropColumn('delivery_status');
        });

        Schema::table('sales_movements', function (Blueprint $table) {
            $table->dropColumn('delivery_status');
        });
    }

    public function down(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->string('delivery_status', 20)->nullable()->after('status');
        });

        Schema::table('sales_movements', function (Blueprint $table) {
            $table->string('delivery_status', 20)->nullable()->after('billing_number');
        });

        // Restaurar datos desde deliveries
        DB::table('deliveries')
            ->where('deliverable_type', 'App\\Models\\SaleOrder')
            ->each(function ($d) {
                DB::table('sale_orders')->where('id', $d->deliverable_id)
                    ->update(['delivery_status' => $d->status]);
            });

        DB::table('deliveries')
            ->where('deliverable_type', 'App\\Models\\Movement')
            ->each(function ($d) {
                DB::table('sales_movements')->where('movement_id', $d->deliverable_id)
                    ->update(['delivery_status' => $d->status]);
            });
    }
};
