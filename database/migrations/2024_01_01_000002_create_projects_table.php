<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            // Dokumen & identitas proyek
            $table->string('proposal_number')->unique();

            // "Pemberi Tugas" -> WAJIB, hanya 1 per proyek
            $table->foreignId('instructing_client_id')
                  ->constrained('clients')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();

            // Data objek penilaian
            $table->string('property_owner_name');
            $table->string('asset_type'); // Tanah, Bangunan, Mesin, Bisnis, dll
            $table->text('asset_address');

            // Komersial & jenis laporan (menentukan SLA otomatis)
            $table->decimal('service_fee', 15, 2);
            $table->enum('report_style', ['Terinci', 'Ringkas']);

            // Diisi setelah DP Paid
            $table->string('assigned_appraiser')->nullable();
            $table->date('survey_date')->nullable();

            // Workflow status - lihat konstanta di Model Project
            $table->string('status')->default('Draft Proposal');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
