<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();   // "proposals.manage", "invoices.view", dst.
            $table->string('label');           // Label yang tampil di UI Role Management
            $table->string('group');           // Untuk mengelompokkan baris di tabel matrix (mis. "Proposal")
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
