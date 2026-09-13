<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Sebelumnya "tanggal terbit" di PDF selalu ambil dari
            // created_at (otomatis, tidak bisa diubah). Sekarang admin
            // bisa input manual — mis. invoice dibuat hari ini tapi
            // tanggal terbit resminya mundur/maju sesuai kesepakatan.
            // Nullable: kalau kosong, fallback ke created_at (lihat
            // accessor getDisplayDateAttribute() di Model Invoice).
            $table->date('invoice_date')->nullable()->after('invoice_number');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('invoice_date');
        });
    }
};
