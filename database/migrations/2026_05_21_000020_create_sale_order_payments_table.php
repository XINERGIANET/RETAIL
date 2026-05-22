<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_order_id')->constrained('sale_orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('amount', 24, 6);
            $table->foreignId('payment_method_id')->constrained('payment_methods')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('payment_method', 100)->nullable()->comment('Snapshot del método de pago');
            $table->foreignId('digital_wallet_id')->nullable()->constrained('digital_wallets')->cascadeOnUpdate()->nullOnDelete();
            $table->string('digital_wallet', 100)->nullable()->comment('Snapshot de la billetera digital');
            $table->foreignId('card_id')->nullable()->constrained('cards')->cascadeOnUpdate()->nullOnDelete();
            $table->string('card', 100)->nullable()->comment('Snapshot de la tarjeta');
            $table->foreignId('payment_gateway_id')->nullable()->constrained('payment_gateways')->cascadeOnUpdate()->nullOnDelete();
            $table->string('payment_gateway', 100)->nullable()->comment('Snapshot de la pasarela');
            $table->string('reference', 100)->nullable()->comment('N° de operación (Yape, transferencia, etc.)');
            $table->timestamp('paid_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('created_by_name', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sale_order_id']);
            $table->index(['sale_order_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_order_payments');
    }
};
