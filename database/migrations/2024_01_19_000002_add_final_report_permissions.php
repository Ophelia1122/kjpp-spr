<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Izin baru (idempotent — aman dijalankan sebelum/tanpa re-seed
 * RolePermissionSeeder), menggantikan gerbang lama 'survey.manage' /
 * 'survey.view' untuk kartu "Nomor Laporan Resmi" (2026-09-14, feedback
 * user: field ini urusan Admin Produksi & Admin Keuangan, BUKAN
 * Surveyor — beda dari data survei lapangan):
 *  - final_report.manage : isi Nomor Laporan Resmi/Tanggal Final/
 *    Keterangan. Default Admin Produksi + Admin Keuangan (Administrator
 *    otomatis punya semua izin secara hardcode).
 *  - final_report.view   : lihat saja (read-only). Default Surveyor,
 *    supaya tidak kehilangan visibilitas yang sebelumnya didapat lewat
 *    survey.view.
 */
return new class extends Migration
{
    private const PERMS = [
        ['key' => 'final_report.manage', 'label' => 'Isi Nomor Laporan Resmi & Tanggal Final', 'group' => 'Laporan Akhir'],
        ['key' => 'final_report.view',   'label' => 'Lihat Nomor Laporan Resmi & Tanggal Final', 'group' => 'Laporan Akhir'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::PERMS as $p) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $p['key']],
                ['label' => $p['label'], 'group' => $p['group'], 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $produksiId  = DB::table('roles')->where('slug', Role::ADMIN_PRODUKSI)->value('id');
        $keuanganId  = DB::table('roles')->where('slug', Role::ADMIN_KEUANGAN)->value('id');
        $surveyorId  = DB::table('roles')->where('slug', Role::SURVEYOR)->value('id');
        $manageId    = DB::table('permissions')->where('key', 'final_report.manage')->value('id');
        $viewId      = DB::table('permissions')->where('key', 'final_report.view')->value('id');

        $grant = function (?int $roleId, ?int $permId) use ($now) {
            if ($roleId && $permId) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permId],
                    ['updated_at' => $now, 'created_at' => $now]
                );
            }
        };

        $grant($produksiId, $manageId);
        $grant($keuanganId, $manageId);
        $grant($surveyorId, $viewId);
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('key', array_column(self::PERMS, 'key'))->delete();
    }
};
