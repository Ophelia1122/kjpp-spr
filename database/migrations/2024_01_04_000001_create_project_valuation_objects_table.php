<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu proyek (projects) bisa punya BANYAK objek penilaian —
     * sesuai dokumen asli KJPP bagian "Identifikasi Obyek Penilaian dan
     * Kepemilikan" yang selalu berbentuk tabel bernomor (No. 1, 2, 3, ...),
     * bukan 1 baris tunggal seperti skema lama.
     *
     * Tiap objek WAJIB punya kepemilikan sendiri (Bentuk/Jenis Hak Atas
     * Tanah & Atas Nama) karena dalam praktiknya kepemilikan bisa beda
     * antar objek meski satu proyek yang sama (mis. tanah atas nama PT,
     * mesin atas nama pemilik lain / masih leasing).
     */
    public function up(): void
    {
        Schema::create('project_valuation_objects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                  ->constrained('projects')
                  ->cascadeOnDelete();

            // Urutan tampil di tabel PDF (No. 1, 2, 3, ...)
            $table->unsignedSmallInteger('sort_order')->default(1);

            // Kategori menentukan field mana yang relevan (lihat accessor
            // di Model: Real Properti pakai luas tanah/bangunan, Personal
            // Properti pakai jumlah unit).
            $table->enum('asset_category', [
                'Real Properti - Tanah',
                'Real Properti - Bangunan',
                'Real Properti - Tanah dan Bangunan',
                'Personal Properti - Mesin dan Peralatan',
                'Personal Properti - Kendaraan',
                'Personal Properti - Alat Berat',
                'Bisnis / Perusahaan',
                'Lainnya',
            ]);

            // Khusus Real Properti (nullable karena tidak relevan utk kategori lain)
            $table->decimal('land_area', 12, 2)->nullable();      // Luas Tanah (m2)
            $table->decimal('building_area', 12, 2)->nullable();  // Luas Bangunan (m2)

            // Khusus Personal Properti (mesin/kendaraan/alat berat)
            $table->unsignedInteger('unit_quantity')->nullable(); // Jumlah unit

            // Lokasi spesifik objek ini. Boleh beda dari asset_address
            // proyek (mis. objek 1 di lokasi A, objek 2 di lokasi B).
            $table->text('location');

            // Diisi MANUAL oleh admin — bentuk ini bervariasi tiap objek
            // (SHM/SHGB/Girik/Invoice Pembelian/BPKB, dst) sehingga tidak
            // dibuatkan enum, cukup teks bebas sesuai dokumen yang diterima.
            $table->string('ownership_form'); // Bentuk/Jenis Hak Atas Tanah

            // Nama pemegang hak — bisa berbeda dari Pemberi Tugas / Pemilik
            // Aset umum di level proyek (mis. pemilik lama sebelum jual-beli).
            $table->string('owner_name'); // Atas Nama

            // Catatan bebas, contoh: "Rumah Tinggal 2 Lantai",
            // "sesuai dengan list yang diterima", dsb.
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_valuation_objects');
    }
};
