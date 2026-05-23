<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();

            // Relación polimórfica: SaleOrder o Movement (venta directa)
            $table->morphs('deliverable'); // deliverable_type + deliverable_id + índice

            $table->string('status', 20)->default('PENDIENTE')
                ->comment('PENDIENTE | ENTREGADO');

            $table->timestamp('delivered_at')->nullable()
                ->comment('Fecha/hora real de entrega');

            $table->unsignedBigInteger('delivered_by')->nullable()
                ->comment('Usuario que registró la entrega');
            $table->foreign('delivered_by')->references('id')->on('users')->nullOnDelete();

            $table->text('notes')->nullable()
                ->comment('Observaciones de la entrega');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
