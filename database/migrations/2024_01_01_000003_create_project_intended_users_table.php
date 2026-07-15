<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot table: satu proyek bisa punya BANYAK "Pengguna Laporan"
     * (mis. Bank A & Bank B sama-sama jadi intended user di 1 laporan).
     * Karena butuh dipakai lepas sebagai Model tersendiri (bukan cuma
     * belongsToMany bawaan), tabel ini punya id() sendiri.
     */
    public function up(): void
    {
        Schema::create('project_intended_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                  ->constrained('projects')
                  ->cascadeOnDelete();
            $table->foreignId('client_id')
                  ->constrained('clients')
                  ->cascadeOnDelete();
            $table->timestamps();

            // Cegah duplikasi: klien yang sama tidak boleh didaftarkan
            // dua kali sebagai intended user di proyek yang sama.
            $table->unique(['project_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_intended_users');
    }
};
