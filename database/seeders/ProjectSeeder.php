<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * PENTING: seeder ini menyimpan status & invoice secara LANGSUNG
     * (bukan lewat controller/route), khusus untuk keperluan testing UI.
     * Di alur nyata aplikasi, perpindahan status selalu lewat controller
     * (ProposalController, InvoiceController, ProjectController) supaya
     * validasi & efek samping (mis. buka fitur input penilai) tetap terjaga.
     *
     * 5 skenario dirancang supaya mencakup:
     * - 3 kategori aset: Tanah, Bangunan, Mesin & Peralatan
     * - Seluruh status workflow: Draft, DP Invoicing (Unpaid),
     *   In-Progress, Pelunasan (Unpaid), Selesai (semua Paid)
     * - Minimal 1 invoice yang sudah Paid supaya PDF Kwitansi bisa
     *   langsung dites tanpa perlu klik "Verifikasi Lunas" manual dulu.
     */
    public function run(): void
    {
        $uob      = Client::where('client_name', 'PT Bank UOB Indonesia')->firstOrFail();
        $mandiri  = Client::where('client_name', 'PT Bank Mandiri (Persero) Tbk')->firstOrFail();
        $petrona  = Client::where('client_name', 'PT Petrona Inti Chemindo')->firstOrFail();
        $graha    = Client::where('client_name', 'PT Graha Sentosa Abadi')->firstOrFail();
        $multi    = Client::where('client_name', 'PT Multi Guna Sejahtera')->firstOrFail();
        $sinar    = Client::where('client_name', 'PT Sinar Abadi Teknik')->firstOrFail();
        $ahmad    = Client::where('client_name', 'Ahmad Fauzi')->firstOrFail();

        // ===================================================================
        // SKENARIO 1 — Status 'Draft Proposal' | Kategori: TANAH
        // Pemberi Tugas (UOB) terpisah dari Pengguna Laporan (Petrona).
        // Uji: tombol "Cetak PDF Proposal" & modal "Klien Setuju & Buat Invoice DP"
        // ===================================================================
        $project1 = Project::create([
            'proposal_number'       => 'PRO/KJPP/2026/001',
            'instructing_client_id' => $uob->id,
            'property_owner_name'   => 'PT Petrona Inti Chemindo',
            'asset_type'            => 'Tanah',
            'asset_address'         => 'Kawasan Industri MM2100 Blok DD-1, Cikarang Barat, Bekasi',
            'service_fee'           => 85_000_000,
            'report_style'          => 'Terinci',
            'proposal_purpose'      => Project::PURPOSE_JUAL_BELI,
            'status'                => Project::STATUS_DRAFT,
        ]);
        $project1->intendedUsers()->sync([$petrona->id]);

        // ===================================================================
        // SKENARIO 2 — Status 'DP Invoicing', Invoice DP 'Unpaid' | Kategori: BANGUNAN
        // Uji: tombol "Cetak Invoice DP" & "Verifikasi Lunas (Keuangan)"
        // ===================================================================
        $project2 = Project::create([
            'proposal_number'       => 'PRO/KJPP/2026/002',
            'instructing_client_id' => $mandiri->id,
            'property_owner_name'   => 'PT Graha Sentosa Abadi',
            'asset_type'            => 'Bangunan',
            'asset_address'         => 'Ruko Sentra Bisnis Blok C No. 5, Tangerang Selatan',
            'service_fee'           => 35_000_000,
            'report_style'          => 'Ringkas',
            'proposal_purpose'      => Project::PURPOSE_PENJAMINAN_UTANG,
            'status'                => Project::STATUS_DP_INVOICING,
        ]);
        $project2->intendedUsers()->sync([$mandiri->id, $graha->id]);

        Invoice::create([
            'project_id'        => $project2->id,
            'invoice_number'    => 'INV-DP/KJPP/2026/001',
            'invoice_type'      => Invoice::TYPE_DP,
            'amount'            => 17_500_000, // 50% dari service_fee
            'status'            => Invoice::STATUS_UNPAID,
            'term_description'  => 'DP 50%',
        ]);

        // ===================================================================
        // SKENARIO 3 — Status 'In-Progress / Scheduled' | Kategori: MESIN & PERALATAN
        // survey_date sudah 2 hari yang lalu -> SLA countdown langsung berjalan.
        // Invoice DP sudah 'Paid' -> bisa langsung dites cetak PDF Kwitansi.
        // Uji: live SLA countdown, "Cetak PDF Surat Tugas", "Cetak Kwitansi"
        // ===================================================================
        $project3 = Project::create([
            'proposal_number'       => 'PRO/KJPP/2026/003',
            'instructing_client_id' => $ahmad->id,
            'property_owner_name'   => 'Ahmad Fauzi',
            'asset_type'            => 'Mesin & Peralatan',
            'asset_address'         => 'Jl. Kemang Selatan No. 22, Jakarta Selatan',
            'service_fee'           => 12_000_000,
            'report_style'          => 'Terinci',
            'proposal_purpose'      => Project::PURPOSE_JUAL_BELI,
            'status'                => Project::STATUS_IN_PROGRESS,
            'assigned_appraiser'    => 'Rudi Hartono, S.T.',
            'survey_date'           => now()->subDays(2),
        ]);
        $project3->intendedUsers()->sync([$ahmad->id]);

        Invoice::create([
            'project_id'        => $project3->id,
            'invoice_number'    => 'INV-DP/KJPP/2026/002',
            'invoice_type'      => Invoice::TYPE_DP,
            'amount'            => 6_000_000,
            'status'            => Invoice::STATUS_PAID,
            'payment_date'      => now()->subDays(3),
            'term_description'  => 'DP 50%',
        ]);

        // ===================================================================
        // SKENARIO 4 — Status 'Pelunasan', Invoice Pelunasan 'Unpaid' | Kategori: TANAH & BANGUNAN
        // Jenis proposal: Lelang (Pemberi Tugas = Bank, sesuai aturan bisnis Lelang).
        // Uji: "Cetak Invoice Pelunasan" & penguncian input final_report_number
        // ===================================================================
        $project4 = Project::create([
            'proposal_number'       => 'PRO/KJPP/2026/004',
            'instructing_client_id' => $mandiri->id,
            'property_owner_name'   => 'PT Multi Guna Sejahtera',
            'asset_type'            => 'Tanah dan Bangunan',
            'asset_address'         => 'Jl. Raya Serpong KM 8, Tangerang',
            'service_fee'           => 60_000_000,
            'report_style'          => 'Ringkas',
            'proposal_purpose'      => Project::PURPOSE_LELANG,
            'status'                => Project::STATUS_PELUNASAN,
            'assigned_appraiser'    => 'Siti Nurhaliza, S.T., MAPPI (Cert.)',
            'survey_date'           => now()->subDays(6),
        ]);
        $project4->intendedUsers()->sync([$mandiri->id]);

        Invoice::create([
            'project_id'        => $project4->id,
            'invoice_number'    => 'INV-DP/KJPP/2026/003',
            'invoice_type'      => Invoice::TYPE_DP,
            'amount'            => 30_000_000,
            'status'            => Invoice::STATUS_PAID,
            'payment_date'      => now()->subDays(7),
            'term_description'  => 'DP 50%',
        ]);

        Invoice::create([
            'project_id'        => $project4->id,
            'invoice_number'    => 'INV-PLN/KJPP/2026/001',
            'invoice_type'      => Invoice::TYPE_PELUNASAN,
            'amount'            => 30_000_000,
            'status'            => Invoice::STATUS_UNPAID,
            'term_description'  => 'Pelunasan Sisa Tagihan',
        ]);

        // ===================================================================
        // SKENARIO 5 — Status 'Selesai', SEMUA invoice 'Paid' | Kategori: MESIN & PERALATAN
        // Jenis proposal: Pelaporan Keuangan (LK Properti) + Perusahaan Terbuka
        // (supaya klausul POJK 28 ikut tercetak di PDF Proposal).
        // Uji: Cetak Invoice + Kwitansi DP maupun Pelunasan, input
        // final_report_number (harus TERBUKA karena status sudah Selesai).
        // ===================================================================
        $project5 = Project::create([
            'proposal_number'          => 'PRO/KJPP/2026/005',
            'instructing_client_id'    => $sinar->id,
            'property_owner_name'      => 'PT Sinar Abadi Teknik',
            'asset_type'               => 'Mesin & Peralatan',
            'asset_address'            => 'Jl. Industri Raya No. 45, Cikarang, Bekasi',
            'service_fee'              => 45_000_000,
            'report_style'             => 'Terinci',
            'proposal_purpose'         => Project::PURPOSE_LK_PROPERTI,
            'psak_classification'      => 'Aset Tetap (PSAK 16)',
            'financial_reporting_date' => now()->subMonths(1)->endOfMonth(),
            'is_public_company'        => true,
            'status'                   => Project::STATUS_SELESAI,
            'assigned_appraiser'       => 'Rudi Hartono, S.T.',
            'survey_date'              => now()->subDays(14),
            'final_report_number'      => 'LP/KJPP/2026/001',
        ]);
        $project5->intendedUsers()->sync([$sinar->id]);

        Invoice::create([
            'project_id'        => $project5->id,
            'invoice_number'    => 'INV-DP/KJPP/2026/004',
            'invoice_type'      => Invoice::TYPE_DP,
            'amount'            => 22_500_000,
            'status'            => Invoice::STATUS_PAID,
            'payment_date'      => now()->subDays(15),
            'term_description'  => 'DP 50%',
        ]);

        Invoice::create([
            'project_id'        => $project5->id,
            'invoice_number'    => 'INV-PLN/KJPP/2026/002',
            'invoice_type'      => Invoice::TYPE_PELUNASAN,
            'amount'            => 22_500_000,
            'status'            => Invoice::STATUS_PAID,
            'payment_date'      => now()->subDays(2),
            'term_description'  => 'Pelunasan Sisa Tagihan',
        ]);
    }
}
