<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Diisi Admin setelah status proyek 'Selesai' (invoice pelunasan Paid).
            $table->string('final_report_number')->nullable()->after('survey_date');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('final_report_number');
        });
    }
};
