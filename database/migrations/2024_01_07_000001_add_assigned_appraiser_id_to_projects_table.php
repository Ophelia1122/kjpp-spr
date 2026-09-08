<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // assigned_appraiser (teks) TETAP dipertahankan untuk PDF
            // Surat Tugas — supaya tidak perlu ubah semua Blade PDF yang
            // sudah bergantung padanya. Kolom BARU ini yang dipakai untuk
            // filter "Proyek Saya" karena teks bebas tidak reliable untuk
            // dicocokkan (rawan typo/variasi penulisan nama).
            $table->foreignId('assigned_appraiser_id')->nullable()->after('assigned_appraiser')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_appraiser_id');
        });
    }
};
