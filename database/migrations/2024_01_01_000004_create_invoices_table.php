<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                  ->constrained('projects')
                  ->cascadeOnDelete();

            $table->string('invoice_number')->unique();
            $table->enum('invoice_type', ['DP', 'Pelunasan']);
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['Unpaid', 'Paid'])->default('Unpaid');
            $table->date('payment_date')->nullable();

            // Berguna untuk mencatat skema termin, mis. "DP 50%"
            $table->string('term_description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
