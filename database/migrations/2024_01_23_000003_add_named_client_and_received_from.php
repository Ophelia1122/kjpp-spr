<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-09-14, feedback user:
 *  - projects.client_id  : "Nama Klien" dipilih dari Database Klien (sama seperti
 *    Pemberi Tugas). Kolom teks lama projects.client_name tetap ada sebagai
 *    cadangan untuk data lama yang belum dipilihkan klien.
 *  - invoices.received_from_client_id : "Telah diterima dari" per invoice
 *    (Pemberi Tugas / Pengguna Laporan / Nama Klien). Kosong = Pemberi Tugas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('instructing_client_id')
                ->constrained('clients')->nullOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('received_from_client_id')->nullable()->after('project_id')
                ->constrained('clients')->nullOnDelete();
        });

        // Nama Klien lama (teks) dicocokkan ke Database Klien bila namanya sama persis.
        DB::table('projects')
            ->whereNull('client_id')
            ->whereNotNull('client_name')
            ->where('client_name', '!=', '')
            ->get(['id', 'client_name'])
            ->each(function ($project) {
                $clientId = DB::table('clients')
                    ->whereRaw('LOWER(TRIM(client_name)) = ?', [mb_strtolower(trim($project->client_name))])
                    ->value('id');

                if ($clientId) {
                    DB::table('projects')->where('id', $project->id)->update(['client_id' => $clientId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('received_from_client_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });
    }
};
