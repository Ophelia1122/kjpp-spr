<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Admin Produksi tidak lagi melihat menu "Ringkasan Project" (2026-09-25,
 * feedback user) — halaman itu berisi angka kontrak dan komposisi klien
 * seluruh kantor. Izin lain tidak disentuh, dan bisa dikembalikan kapan saja
 * lewat Pengaturan Sistem > Kelola Role & Izin.
 */
return new class extends Migration
{
    public function up(): void
    {
        $role       = DB::table('roles')->where('slug', 'admin-produksi')->value('id');
        $permission = DB::table('permissions')->where('key', 'dashboard.overview')->value('id');

        if ($role && $permission) {
            DB::table('permission_role')
                ->where('role_id', $role)
                ->where('permission_id', $permission)
                ->delete();
        }
    }

    public function down(): void
    {
        $role       = DB::table('roles')->where('slug', 'admin-produksi')->value('id');
        $permission = DB::table('permissions')->where('key', 'dashboard.overview')->value('id');

        if (! $role || ! $permission) {
            return;
        }

        $sudahAda = DB::table('permission_role')
            ->where('role_id', $role)
            ->where('permission_id', $permission)
            ->exists();

        if (! $sudahAda) {
            DB::table('permission_role')->insert(['role_id' => $role, 'permission_id' => $permission]);
        }
    }
};
