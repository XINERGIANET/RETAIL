<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->string('electronic_invoice_provider', 50)->nullable();
            $table->string('electronic_invoice_status', 30)->nullable();
            $table->string('electronic_invoice_external_id')->nullable();
            $table->string('electronic_invoice_series', 8)->nullable();
            $table->string('electronic_invoice_number', 30)->nullable();
            $table->string('electronic_invoice_file_name')->nullable();
            $table->text('electronic_invoice_pdf_ticket_url')->nullable();
            $table->text('electronic_invoice_pdf_a4_url')->nullable();
            $table->text('electronic_invoice_xml_url')->nullable();
            $table->text('electronic_invoice_cdr_url')->nullable();
            $table->json('electronic_invoice_response')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->dropColumn([
                'electronic_invoice_provider',
                'electronic_invoice_status',
                'electronic_invoice_external_id',
                'electronic_invoice_series',
                'electronic_invoice_number',
                'electronic_invoice_file_name',
                'electronic_invoice_pdf_ticket_url',
                'electronic_invoice_pdf_a4_url',
                'electronic_invoice_xml_url',
                'electronic_invoice_cdr_url',
                'electronic_invoice_response',
            ]);
        });
    }
};
