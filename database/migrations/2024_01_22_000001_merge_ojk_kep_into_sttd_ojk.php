<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Nomor KEP OJK" sebenarnya adalah nomor Surat Tanda Terdaftar (STTD) OJK
     * yang sama (2026-09-15, feedback user) — dua isian itu duplikat. Isi KEP
     * digabung ke STTD, lalu kolom KEP dibuang:
     *  - STTD kosong            -> nomor & tanggal KEP dipindah ke STTD
     *  - nomor sama, tanggal STTD kosong -> tanggal KEP dipakai
     *  - nomor berbeda & keduanya terisi -> STTD dipertahankan (isian utama)
     */
    public function up(): void
    {
        foreach (DB::table('users')->whereNotNull('ojk_kep_no')->where('ojk_kep_no', '!=', '')->get() as $user) {
            $sttdNo  = trim((string) $user->sttd_ojk_no);
            $kepNo   = trim((string) $user->ojk_kep_no);
            $updates = [];

            if ($sttdNo === '') {
                $updates['sttd_ojk_no']   = $kepNo;
                $updates['sttd_ojk_date'] = $user->ojk_kep_date;
            } elseif ($sttdNo === $kepNo && ! $user->sttd_ojk_date && $user->ojk_kep_date) {
                $updates['sttd_ojk_date'] = $user->ojk_kep_date;
            }

            if ($updates) {
                DB::table('users')->where('id', $user->id)->update($updates);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ojk_kep_no', 'ojk_kep_date']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ojk_kep_no')->nullable()->after('sttd_ojk_date');
            $table->date('ojk_kep_date')->nullable()->after('ojk_kep_no');
        });
    }
};
