<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dasar Permintaan Penilaian pada Surat Tugas selalu mengikuti Dasar
 * Permintaan proposal (2026-09-24, feedback user), jadi kolom khususnya tidak
 * dipakai lagi dan dibuang supaya tidak ada dua sumber untuk satu nilai.
 *
 * Isi kolom ini sudah tidak dibaca aplikasi sejak perubahan tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('projects', 'assignment_letter_request_basis')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('assignment_letter_request_basis');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('projects', 'assignment_letter_request_basis')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->text('assignment_letter_request_basis')->nullable()->after('assignment_letter_date');
            });
        }
    }
};
