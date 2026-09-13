<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nama Marketing yang membawa proposal — field proposal (bukan data
     * klien), dipilih dari daftar config('kjpp.marketing_names'). Dipakai
     * juga nanti untuk kolom "Nama Marketing" di export Excel produksi.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('marketing_name')->nullable()->after('approver_client_id');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('marketing_name');
        });
    }
};
