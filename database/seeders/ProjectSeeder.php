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
     */
    public function run(): void
    {
        $uob      = Client::where('client_name', 'PT Bank UOB Indonesia')->firstOrFail();
        $petrona  = Client::where('client_name', 'PT Petrona Inti Chemindo')->firstOrFail();
        $mandiri  = Client::where('client_name', 'PT Bank Mandiri (Persero) Tbk')->firstOrFail();
        $graha    = Client::where('client_name', 'PT Graha Sentosa Abadi')->firstOrFail();
        $ahmad    = Client::where('client_name', 'Ahmad Fauzi')->firstOrFail();
        $multi    = Client::where('client_name', 'PT Multi Guna Sejahtera')->firstOrFail();

        // ===================================================================
        // SKENARIO 1 — Status 'Draft Proposal'
        // Pemberi Tugas (UOB) terpisah dari Pengguna Laporan (Petrona).
        // Jenis laporan Terinci -> SLA otomatis 7 hari kerja.
        // Uji: tombol "Cetak PDF Proposal" & "Klien Setuju (Buat Invoice DP)"
        // ===================================================================
        $project1 = Project::create([
            'proposal_number'       => 'PRO/KJPP/2026/001',
            'instructing_client_id' => $uob->id,
            'property_owner_name'   => 'PT Petrona Inti Chemindo',
            'asset_type'            => 'Pabrik & Mesin Produksi',
            'asset_address'         => 'Kawasan Industri MM2100 Blok DD-1, Cikarang Barat, Bekasi',
            'service_fee'           => 85_000_000,
            'report_style'          => 'Terinci',
            'status'                => Project::STATUS_DRAFT,
        ]);
        $project1->intendedUsers()->sync([$petrona->id]);

        // ===================================================================
        // SKENARIO 2 — Status 'DP Invoicing', Invoice DP masih 'Unpaid'
        // Uji: tombol "Cetak Invoice DP" & "Verifikasi Lunas (Keuangan)"
        // ===================================================================
        $project2 = Project::create([
            'proposal_number'       => 'PRO/KJPP/2026/002',
            'instructing_client_id' => $mandiri->id,
            'property_owner_name'   => 'PT Graha Sentosa Abadi',
            'asset_type'            => 'Ruko Komersial',
            'asset_address'         => 'Ruko Sentra Bisnis Blok C No. 5, Tangerang Selatan',
            'service_fee'           => 35_000_000,
            'report_style'          => 'Ringkas',
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
        // SKENARIO 3 — Status 'In-Progress / Scheduled'
        // survey_date sudah 2 hari yang lalu -> SLA countdown langsung
        // berjalan/terlihat saat halaman dibuka (bukan mulai dari 7 hari penuh).
        // Uji: live SLA countdown & tombol "Cetak PDF Surat Tugas"
        // ===================================================================
        $project3 = Project::create([
            'proposal_number'       => 'PRO/KJPP/2026/003',
            'instructing_client_id' => $ahmad->id,
            'property_owner_name'   => 'Ahmad Fauzi',
            'asset_type'            => 'Rumah Tinggal',
            'asset_address'         => 'Jl. Kemang Selatan No. 22, Jakarta Selatan',
            'service_fee'           => 12_000_000,
            'report_style'          => 'Terinci',
            'status'                => Project::STATUS_IN_PROGRESS,
            'assigned_appraiser'    => 'Rudi Hartono, S.T.',
            'survey_date'           => now()->subDays(2),
        ]);
        $project3->intendedUsers()->sync([$ahmad->id]);

        // Invoice DP untuk skenario 3 sudah harus Paid (syarat masuk In-Progress)
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
        // SKENARIO 4 — Status 'Pelunasan', Invoice Pelunasan masih 'Unpaid'
        // Uji: tombol "Cetak Invoice Pelunasan" & penguncian
        // input final_report_number (harus tetap disabled).
        // ===================================================================
        $project4 = Project::create([
            'proposal_number'       => 'PRO/KJPP/2026/004',
            'instructing_client_id' => $multi->id,
            'property_owner_name'   => 'PT Multi Guna Sejahtera',
            'asset_type'            => 'Gudang & Lahan',
            'asset_address'         => 'Jl. Raya Serpong KM 8, Tangerang',
            'service_fee'           => 60_000_000,
            'report_style'          => 'Ringkas',
            'status'                => Project::STATUS_PELUNASAN,
            'assigned_appraiser'    => 'Siti Nurhaliza, S.T., MAPPI (Cert.)',
            'survey_date'           => now()->subDays(6),
        ]);
        $project4->intendedUsers()->sync([$multi->id]);

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
    }
}
