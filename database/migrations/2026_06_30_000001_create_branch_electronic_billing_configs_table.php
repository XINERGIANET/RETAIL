<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_electronic_billing_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->unique()->constrained('branches')->cascadeOnDelete();
            $table->string('provider', 50)->default('apisunat');
            $table->boolean('enabled')->default(false);
            $table->string('api_url')->nullable();
            $table->string('persona_id')->nullable();
            $table->string('persona_token')->nullable();
            $table->string('series_boleta', 8)->nullable();
            $table->string('series_factura', 8)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_electronic_billing_configs');
    }
};
