<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel clients bersifat "generic".
     * Satu row bisa berperan sebagai Pemberi Tugas (instructing_client)
     * ATAU sebagai Pengguna Laporan (intended_user), tergantung
     * relasi yang dipakai di tabel projects / project_intended_users.
     * Ini yang membuat 1 klien fleksibel dipakai di banyak konteks.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->enum('client_type', ['Perbankan', 'Korporat', 'Perorangan'])
                  ->default('Perorangan');
            $table->text('address')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();

            $table->index('client_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
