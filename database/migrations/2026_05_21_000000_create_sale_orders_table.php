<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->comment('Correlativo del pedido por sucursal');
            $table->string('status', 20)->default('draft')->comment('draft | partial | completed | cancelled');
            $table->string('currency', 10)->default('PEN');
            $table->decimal('exchange_rate', 8, 3)->default(1);
            $table->decimal('total', 24, 6)->default(0)->comment('Suma de ítems vigentes (se recalcula con devoluciones)');
            $table->decimal('paid', 24, 6)->default(0)->comment('Suma acumulada de pagos registrados');
            $table->timestamp('due_date')->nullable()->comment('Fecha acordada de pago');
            $table->text('notes')->nullable();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->cascadeOnUpdate()->nullOnDelete();
            $table->string('person_name', 255)->nullable()->comment('Snapshot del nombre del cliente');
            $table->foreignId('movement_id')->nullable()->constrained('movements')->cascadeOnUpdate()->nullOnDelete()->comment('NULL hasta facturar');
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('created_by_name', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'number']);
            $table->index(['person_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_orders');
    }
};
