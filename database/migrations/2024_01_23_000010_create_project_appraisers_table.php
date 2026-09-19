<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penilai lapangan lebih dari satu (2026-09-15, feedback user): survei bisa
 * dilakukan 2-3 orang dengan tanggung jawab SETARA — semuanya "memiliki"
 * proyek (Beranda, Proyek Saya, Timeline, export, notifikasi WhatsApp).
 *
 * Kolom lama projects.assigned_appraiser_id / assigned_appraiser tetap ada
 * sebagai ringkasan (penilai urutan pertama & gabungan nama) supaya pengecekan
 * "sudah ada penilai", pengurutan, dan export tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_appraisers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });

        $now = now();
        DB::table('projects')
            ->whereNotNull('assigned_appraiser_id')
            ->whereIn('assigned_appraiser_id', DB::table('users')->select('id'))
            ->orderBy('id')
            ->select('id', 'assigned_appraiser_id')
            ->each(function ($p) use ($now) {
                DB::table('project_appraisers')->insert([
                    'project_id' => $p->id,
                    'user_id'    => $p->assigned_appraiser_id,
                    'sort_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_appraisers');
    }
};
