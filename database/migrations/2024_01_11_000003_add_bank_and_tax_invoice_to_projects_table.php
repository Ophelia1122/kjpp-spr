<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * projects: + bank_id (rekening yang dipilih di proposal — Feature 5)
 *           + tax_invoice_number / tax_invoice_date (Faktur Pajak — Feature 6).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('bank_id')->nullable()->after('signed_by_user_id')
                ->constrained('banks')->nullOnDelete();

            $table->string('tax_invoice_number')->nullable()->after('approver_name');
            $table->date('tax_invoice_date')->nullable()->after('tax_invoice_number');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_id');
            $table->dropColumn(['tax_invoice_number', 'tax_invoice_date']);
        });
    }
};
