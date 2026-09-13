<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * projects.proposal_date — tanggal yang tercetak di kop proposal
 * ("Jakarta, <tanggal>"). Diinput manual karena proposal sering
 * dibuat mundur (bertanggal kemarin / beberapa hari lalu). Nullable
 * supaya baris lama tidak error; builder fallback ke created_at.
 * Baris lama di-backfill dari created_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->date('proposal_date')->nullable()->after('proposal_number');
        });

        \Illuminate\Support\Facades\DB::table('projects')
            ->whereNull('proposal_date')
            ->update(['proposal_date' => \Illuminate\Support\Facades\DB::raw('DATE(created_at)')]);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('proposal_date');
        });
    }
};
