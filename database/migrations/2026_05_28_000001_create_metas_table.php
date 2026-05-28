<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('name', 255);
            $table->enum('period_type', ['week', 'biweek', 'month', 'custom'])->default('month');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('target_amount', 12, 2)->nullable()->comment('Meta en monto (S/)');
            $table->decimal('target_quantity', 12, 4)->nullable()->comment('Meta en unidades vendidas');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metas');
    }
};
