<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baris "Up." pada kop proposal Jasa Konsultasi (2026-09-26): nama orang yang
 * dituju di kantor klien, tercetak di bawah alamat. Master kantor selalu
 * memuatnya. Proposal penilaian tidak memakai baris ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('letter_attn', 150)->nullable()->after('work_object_description');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('letter_attn');
        });
    }
};
