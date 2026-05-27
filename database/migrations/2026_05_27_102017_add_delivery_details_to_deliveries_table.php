<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->string('tracking_number', 100)->nullable()->after('notes');
            $table->string('evidence_photo')->nullable()->after('tracking_number');
            $table->boolean('payment_confirmed')->nullable()->after('evidence_photo');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['tracking_number', 'evidence_photo', 'payment_confirmed']);
        });
    }
};
