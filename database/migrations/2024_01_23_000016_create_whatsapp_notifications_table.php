<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan notifikasi WhatsApp per tombol alur proyek (2026-09-22):
 * aktif/tidak, siapa yang di-mention, grup tujuan, dan isi pesan. Tabel
 * baru saja; baris yang belum ada memakai bawaan dari kode
 * (App\Models\WhatsAppNotification::defaults()), sama dengan pesan lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('step', 50)->unique();        // key Project::WORKFLOW_STEPS
            $table->boolean('enabled')->default(false);
            $table->json('recipients')->nullable();       // ['reviewers', 'appraisers', 'submitter', 'jabatan:Admin', 'user:12']
            $table->string('group_jid', 100)->nullable(); // null = grup utama
            $table->text('template');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_notifications');
    }
};
