<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Titik nilai tanah hasil penilaian terdahulu (2026-09-26, permintaan user):
 * bahan untuk estimasi rentang nilai pasar tanah per meter dari sebuah titik
 * koordinat. Diisi dari berkas "Data Aset Pusat" lewat `import:data-aset`.
 *
 * Nama Debitur SENGAJA tidak disimpan — tidak dibutuhkan untuk estimasi dan
 * ini data pribadi. Nomor laporan disimpan hanya untuk telusur balik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('land_value_points', function (Blueprint $table) {
            $table->id();

            $table->string('report_number', 100)->nullable();
            $table->date('valuation_date')->nullable();
            $table->unsignedSmallInteger('valuation_year')->index();

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->string('property_type', 80)->nullable();   // Jenis Properti apa adanya
            $table->string('property_group', 20)->index();     // hunian/komersial/industri/tanah/lain

            $table->unsignedBigInteger('land_rate');           // Rp per m2 tanah
            $table->unsignedBigInteger('land_area')->nullable();
            $table->unsignedBigInteger('building_area')->nullable();

            $table->string('province', 60)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('district', 80)->nullable();        // kecamatan
            $table->string('village', 80)->nullable();         // kelurahan/desa
            $table->string('address', 255)->nullable();

            $table->string('source_file', 120)->nullable();
            $table->timestamps();

            // Prasaring kotak lintang/bujur sebelum hitung jarak sesungguhnya.
            $table->index(['latitude', 'longitude']);
            $table->index(['city', 'property_group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('land_value_points');
    }
};
