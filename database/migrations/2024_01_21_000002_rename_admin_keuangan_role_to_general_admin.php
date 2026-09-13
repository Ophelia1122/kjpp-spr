<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Role "Admin Keuangan" berganti nama menjadi "General Admin" (2026-09-15,
     * feedback user). Hanya nama tampilan — slug 'admin-keuangan' TETAP,
     * karena dipakai kode (Role::ADMIN_KEUANGAN) & pemetaan izin.
     */
    public function up(): void
    {
        DB::table('roles')->where('slug', 'admin-keuangan')->update(['name' => 'General Admin']);
    }

    public function down(): void
    {
        DB::table('roles')->where('slug', 'admin-keuangan')->update(['name' => 'Admin Keuangan']);
    }
};
