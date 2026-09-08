<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');       // "Administrator", "Admin Produksi", dst.
            $table->string('slug')->unique(); // "administrator", "admin-produksi", dst.

            // Role sistem (4 role inti) tidak boleh dihapus lewat UI —
            // hanya izinnya yang boleh diubah. Role tambahan yang dibuat
            // admin di kemudian hari boleh dihapus.
            $table->boolean('is_system')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
