<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanda Terima Pengiriman Buku (2026-09-23, feedback user). Satu proyek boleh
 * punya lebih dari satu tanda terima (kirim ke beberapa pihak / susulan).
 * Rincian dokumen disimpan sebagai JSON: [{key, label, qty, unit}].
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('number', 50)->unique();          // mis. "214/09/2026"
            $table->date('delivery_date');
            $table->foreignId('recipient_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('recipient_up')->nullable();       // "Up:" pada dokumen
            $table->json('documents');                        // rincian dokumen + qty
            $table->text('note')->nullable();                 // keterangan (otomatis, bisa diedit)
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'delivery_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_receipts');
    }
};
