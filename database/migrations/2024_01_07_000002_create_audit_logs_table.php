<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Nullable: kalau suatu saat ada aksi sistem otomatis (bukan
            // dipicu user, mis. cron job), atau user-nya sudah dihapus
            // dari sistem, log tetap tersimpan (tidak ikut terhapus).
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Contoh: "proposal.created", "invoice.marked_paid",
            // "client.deleted", "auth.login". Format {modul}.{aksi}
            // supaya mudah difilter/dicari nanti.
            $table->string('action');

            // Polymorphic: menunjuk ke row spesifik yang kena aksi
            // (Project, Invoice, Client, User, dst) — nullable karena
            // tidak semua aksi (mis. login) berkaitan dengan 1 row data.
            $table->nullableMorphs('subject');

            // Kalimat siap-baca untuk ditampilkan di tabel Audit Log,
            // supaya admin tidak perlu menerjemahkan sendiri kode 'action'.
            $table->text('description');

            // Hanya created_at yang relevan untuk log (kejadian di satu
            // titik waktu) — updated_at tidak berguna karena log tidak
            // pernah "diubah" setelah tercatat.
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
