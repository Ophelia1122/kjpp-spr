<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Izin baru: dashboard.overview — akses halaman "Dashboard" (ringkasan
 * manajemen: nilai kontrak, pipeline, grafik). Sengaja DIPISAH dari
 * dashboard.view supaya Surveyor tetap bisa membuka Beranda / Dashboard
 * Project / Timeline, tapi TIDAK melihat angka nilai kontrak.
 *
 * Default: Admin Produksi + Admin Keuangan (Administrator selalu punya
 * semua izin secara hardcode di Role::hasPermission()).
 * Idempotent — aman dijalankan ulang / tanpa re-seed.
 */
return new class extends Migration
{
    private const PERM = [
        'key'   => 'dashboard.overview',
        'label' => 'Lihat Dashboard Ringkasan Manajemen',
        'group' => 'Dashboard',
    ];

    private const ROLES = [Role::ADMIN_PRODUKSI, Role::ADMIN_KEUANGAN];

    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['key' => self::PERM['key']],
            ['label' => self::PERM['label'], 'group' => self::PERM['group'], 'updated_at' => $now, 'created_at' => $now]
        );

        $permId = DB::table('permissions')->where('key', self::PERM['key'])->value('id');

        if (! $permId) {
            return;
        }

        foreach (self::ROLES as $slug) {
            $roleId = DB::table('roles')->where('slug', $slug)->value('id');

            if ($roleId) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permId],
                    ['updated_at' => $now, 'created_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('key', self::PERM['key'])->delete();
    }
};
