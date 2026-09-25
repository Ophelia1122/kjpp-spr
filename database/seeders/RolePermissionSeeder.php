<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Daftar izin: {modul}.view (lihat) dan {modul}.manage (buat/edit/
     * hapus/aksi). 2 modul terakhir (roles, users) sengaja hanya punya
     * 'manage' — tidak ada gunanya "lihat" pengaturan sistem tanpa bisa
     * mengubahnya, jadi digabung jadi 1 izin saja per modul.
     */
    private const PERMISSIONS = [
        ['key' => 'dashboard.view',     'label' => 'Lihat Beranda, Dashboard Project & Timeline',      'group' => 'Dashboard'],
        ['key' => 'dashboard.overview', 'label' => 'Lihat Dashboard Ringkasan Manajemen',              'group' => 'Dashboard'],

        ['key' => 'proposals.view',   'label' => 'Lihat Proposal & Detail Proyek',                    'group' => 'Proposal'],
        ['key' => 'proposals.manage', 'label' => 'Kelola Proposal (Buat/Edit/Hapus/Cetak PDF)',        'group' => 'Proposal'],

        ['key' => 'invoices.view',    'label' => 'Lihat Invoice',                                     'group' => 'Invoice & Pembayaran'],
        ['key' => 'invoices.manage',  'label' => 'Kelola Invoice (Terbitkan/Verifikasi Lunas/Batalkan/Cetak)', 'group' => 'Invoice & Pembayaran'],
        ['key' => 'tax_invoice.manage', 'label' => 'Isi Nomor & Tanggal Faktur Pajak',                 'group' => 'Invoice & Pembayaran'],

        ['key' => 'survey.view',      'label' => 'Lihat Data Survei Lapangan',                        'group' => 'Survei Lapangan'],
        ['key' => 'survey.manage',    'label' => 'Kelola Data Survei (Input Penilai/Tanggal, Cetak Surat Tugas)', 'group' => 'Survei Lapangan'],
        ['key' => 'assignment_letter.manage', 'label' => 'Isi Nomor/Tanggal/Barcode Surat Tugas & Pilih Reviewer', 'group' => 'Survei Lapangan'],

        ['key' => 'final_report.manage', 'label' => 'Isi Nomor Laporan Resmi & Tanggal Final',        'group' => 'Laporan Akhir'],
        ['key' => 'final_report.view',   'label' => 'Lihat Nomor Laporan Resmi & Tanggal Final',       'group' => 'Laporan Akhir'],

        ['key' => 'clients.view',     'label' => 'Lihat Daftar Klien',                                'group' => 'Klien'],
        ['key' => 'clients.manage',   'label' => 'Kelola Klien (Tambah/Edit/Hapus)',                   'group' => 'Klien'],

        ['key' => 'reports.view',     'label' => 'Lihat Laporan/Dashboard',                           'group' => 'Laporan'],
        ['key' => 'reports.export',   'label' => 'Export Laporan ke Excel',                            'group' => 'Laporan'],

        ['key' => 'roles.manage',     'label' => 'Kelola Role & Hak Akses',                            'group' => 'Pengaturan Sistem'],
        ['key' => 'users.manage',     'label' => 'Kelola Pengguna',                                    'group' => 'Pengaturan Sistem'],
        ['key' => 'banks.manage',     'label' => 'Kelola Master Rekening Bank',                         'group' => 'Pengaturan Sistem'],
        ['key' => 'audit.view',       'label' => 'Lihat Log Aktivitas (Audit Log)',                    'group' => 'Pengaturan Sistem'],
        ['key' => 'proposal_defaults.manage', 'label' => 'Kelola Teks Baku Proposal per Tujuan Penilaian', 'group' => 'Pengaturan Sistem'],
    ];

    /**
     * Mapping default izin per role — mencerminkan tanggung jawab
     * masing-masing di kantor KJPP:
     * - Admin Produksi: fokus operasional (proposal, survei, klien),
     *   TIDAK bisa verifikasi pembayaran (itu ranah Keuangan).
     * - Admin Keuangan: fokus billing (invoice, kwitansi, export laporan),
     *   hanya bisa LIHAT proposal/klien (tidak mengedit data operasional).
     * - Surveyor: hanya bisa urus data survei miliknya + lihat proposal
     *   yang ditugaskan; tidak ada akses ke invoice sama sekali.
     */
    private const ROLE_PERMISSIONS = [
        Role::ADMIN_PRODUKSI => [
            'dashboard.view', 'dashboard.overview', 'proposals.view', 'proposals.manage',
            'survey.view', 'survey.manage', 'final_report.manage',
            'clients.view', 'clients.manage',
            'invoices.view', 'reports.view',
        ],
        Role::ADMIN_KEUANGAN => [
            'dashboard.view', 'dashboard.overview', 'proposals.view',
            'invoices.view', 'invoices.manage', 'tax_invoice.manage', 'final_report.manage',
            'survey.view', 'assignment_letter.manage', 'clients.view',
            'reports.view', 'reports.export',
            // Klausul proposal berubah sewaktu-waktu; General Admin boleh
            // memperbaruinya sendiri (2026-09-25, permintaan user).
            'proposal_defaults.manage',
        ],
        // Surveyor SENGAJA tanpa dashboard.overview — tidak melihat angka
        // nilai kontrak / pipeline keuangan. Beranda & Timeline tetap bisa.
        // final_report.view (bukan .manage) — Nomor Laporan Resmi urusan
        // Admin Produksi/Keuangan, Surveyor cuma perlu lihat (2026-09-14).
        Role::SURVEYOR => [
            'dashboard.view', 'proposals.view',
            'survey.view', 'survey.manage', 'final_report.view',
            'clients.view',
        ],
    ];

    public function run(): void
    {
        Role::updateOrCreate(['slug' => Role::ADMINISTRATOR], ['name' => 'Administrator', 'is_system' => true]);
        Role::updateOrCreate(['slug' => Role::ADMIN_PRODUKSI], ['name' => 'Admin Produksi', 'is_system' => true]);
        Role::updateOrCreate(['slug' => Role::ADMIN_KEUANGAN], ['name' => 'General Admin', 'is_system' => true]);
        Role::updateOrCreate(['slug' => Role::SURVEYOR], ['name' => 'Surveyor', 'is_system' => true]);

        foreach (self::PERMISSIONS as $permission) {
            Permission::updateOrCreate(['key' => $permission['key']], $permission);
        }

        // Administrator TIDAK perlu di-assign permission apapun di pivot
        // table — hasPermission() di Model Role sudah hardcode selalu
        // true untuk role ini (lihat app/Models/Role.php).

        foreach (self::ROLE_PERMISSIONS as $roleSlug => $permissionKeys) {
            $role = Role::where('slug', $roleSlug)->first();
            $permissionIds = Permission::whereIn('key', $permissionKeys)->pluck('id');
            $role->permissions()->sync($permissionIds);
        }
    }
}
