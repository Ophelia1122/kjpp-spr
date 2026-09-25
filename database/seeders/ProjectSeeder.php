<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectValuationObject;
use Database\Seeders\Concerns\DataContoh;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    use DataContoh;

    /**
     * PENTING: seeder ini menyimpan status, invoice, & objek penilaian
     * secara LANGSUNG (bukan lewat ProposalController@store), khusus untuk
     * keperluan testing UI. Di alur nyata, asset_type & asset_address di
     * projects TIDAK diisi manual — keduanya dirangkum otomatis dari
     * objek-objek penilaian (lihat ProposalController@summarizeAssetTypes).
     * Di sini saya set manual supaya konsisten dengan objek yang dibuat.
     *
     * Skenario 4 (Lelang) sengaja dibuat dengan 2 OBJEK sekaligus untuk
     * menunjukkan kemampuan multi-objek per proposal.
     */
    public function run(): void
    {
        if (! $this->bolehIsiDataContoh()) {
            return;
        }

        $uob      = Client::where('client_name', 'PT Bank UOB Indonesia')->firstOrFail();
        $mandiri  = Client::where('client_name', 'PT Bank Mandiri (Persero) Tbk')->firstOrFail();
        $petrona  = Client::where('client_name', 'PT Petrona Inti Chemindo')->firstOrFail();
        $graha    = Client::where('client_name', 'PT Graha Sentosa Abadi')->firstOrFail();
        $multi    = Client::where('client_name', 'PT Multi Guna Sejahtera')->firstOrFail();
        $sinar    = Client::where('client_name', 'PT Sinar Abadi Teknik')->firstOrFail();
        $ahmad    = Client::where('client_name', 'Ahmad Fauzi')->firstOrFail();

        // ===================================================================
        // SKENARIO 1 — Draft Proposal | 1 Objek: Tanah
        // ===================================================================
        $project1 = Project::create([
            'proposal_number'       => '0001/2.0131-00/KJPPSPR-PRO/APP/VII/2026',
            'instructing_client_id' => $uob->id,
            'asset_type'            => 'Tanah',
            'asset_address'         => 'Kawasan Industri MM2100 Blok DD-1, Cikarang Barat, Bekasi',
            'service_fee'           => 85_000_000,
            'report_style'          => 'Long Report',
            'sla_draft_days'        => 3,
            'sla_final_days'        => 5,
            'proposal_purpose'      => Project::PURPOSE_JUAL_BELI,
            'status'                => Project::STATUS_DRAFT,
        ]);
        $project1->intendedUsers()->sync([$petrona->id]);

        ProjectValuationObject::create([
            'project_id'      => $project1->id,
            'sort_order'      => 1,
            'asset_category'  => ProjectValuationObject::CATEGORY_TANAH,
            'land_area'       => 5200.00,
            'location'        => 'Kawasan Industri MM2100 Blok DD-1, Cikarang Barat, Bekasi, Jawa Barat',
            'ownership_form'  => 'Tunggal - SHGB No. 8821',
            'owner_name'      => 'PT Petrona Inti Chemindo',
            'notes'           => 'Tanah kosong siap bangun',
        ]);

        // ===================================================================
        // SKENARIO 2 — DP Invoicing (Unpaid) | 1 Objek: Bangunan
        // ===================================================================
        $project2 = Project::create([
            'proposal_number'       => '0002/2.0131-00/KJPPSPR-PRO/APP/VII/2026',
            'instructing_client_id' => $mandiri->id,
            'asset_type'            => 'Bangunan',
            'asset_address'         => 'Ruko Sentra Bisnis Blok C No. 5, Tangerang Selatan',
            'service_fee'           => 35_000_000,
            'report_style'          => 'Short Report',
            'sla_draft_days'        => 2,
            'sla_final_days'        => 3,
            'proposal_purpose'      => Project::PURPOSE_PENJAMINAN_UTANG,
            'status'                => Project::STATUS_DP_INVOICING,
        ]);
        $project2->intendedUsers()->sync([$mandiri->id, $graha->id]);

        ProjectValuationObject::create([
            'project_id'      => $project2->id,
            'sort_order'      => 1,
            'asset_category'  => ProjectValuationObject::CATEGORY_TANAH_BANGUNAN,
            'building_area'   => 180.50,
            'location'        => 'Ruko Sentra Bisnis Blok C No. 5, Tangerang Selatan, Banten',
            'ownership_form'  => 'Tunggal - SHM No. 4410',
            'owner_name'      => 'PT Graha Sentosa Abadi',
            'notes'           => 'Ruko 3 lantai',
        ]);

        Invoice::create([
            'project_id'        => $project2->id,
            'invoice_number'    => 'INV-DP/KJPP/2026/001',
            'invoice_type'      => Invoice::TYPE_DP,
            'amount'            => 17_500_000,
            'status'            => Invoice::STATUS_UNPAID,
            'term_description'  => 'DP 50%',
        ]);

        // ===================================================================
        // SKENARIO 3 — In-Progress (DP Paid) | 1 Objek: Mesin & Peralatan
        // ===================================================================
        $project3 = Project::create([
            'proposal_number'       => '0003/2.0131-00/KJPPSPR-PRO/APP/VIII/2026',
            'instructing_client_id' => $ahmad->id,
            'asset_type'            => 'Mesin dan Peralatan',
            'asset_address'         => 'Jl. Kemang Selatan No. 22, Jakarta Selatan',
            'service_fee'           => 12_000_000,
            'report_style'          => 'Long Report',
            'sla_draft_days'        => 3,
            'sla_final_days'        => 5,
            'proposal_purpose'      => Project::PURPOSE_JUAL_BELI,
            'status'                => Project::STATUS_IN_PROGRESS,
            'assigned_appraiser'    => 'Rudi Hartono, S.T.',
            'survey_date'           => now()->subDays(2),
        ]);
        $project3->intendedUsers()->sync([$ahmad->id]);

        ProjectValuationObject::create([
            'project_id'      => $project3->id,
            'sort_order'      => 1,
            'asset_category'  => ProjectValuationObject::CATEGORY_MESIN,
            'unit_quantity'   => 8,
            'location'        => 'Jl. Kemang Selatan No. 22, Jakarta Selatan',
            'ownership_form'  => 'Invoice/Faktur Pembelian',
            'owner_name'      => 'Ahmad Fauzi',
            'notes'           => 'Sesuai dengan list yang diterima',
        ]);

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
        // SKENARIO 4 — Proses cetak buku (Invoice Pelunasan Unpaid) | 2 OBJEK SEKALIGUS
        // (Tanah & Bangunan + Mesin & Peralatan) — CONTOH MULTI-OBJEK
        // Jenis: Lelang (Pemberi Tugas = Bank, sesuai aturan bisnis Lelang).
        // ===================================================================
        $project4 = Project::create([
            'proposal_number'       => '0004/2.0131-00/KJPPSPR-PRO/APP/VIII/2026',
            'instructing_client_id' => $mandiri->id,
            'asset_type'            => 'Tanah dan Bangunan, Mesin dan Peralatan',
            'asset_address'         => 'Jl. Raya Serpong KM 8, Tangerang',
            'service_fee'           => 60_000_000,
            'report_style'          => 'Short Report',
            'sla_draft_days'        => 2,
            'sla_final_days'        => 3,
            'proposal_purpose'      => Project::PURPOSE_LELANG,
            'status'                => Project::STATUS_IN_PROGRESS,
            'review_status'         => Project::STAGE_DRAFT_REVIEWED,
            'assigned_appraiser'    => 'Siti Nurhaliza, S.T., MAPPI (Cert.)',
            'survey_date'           => now()->subDays(6),
        ]);
        $project4->intendedUsers()->sync([$mandiri->id]);

        ProjectValuationObject::create([
            'project_id'      => $project4->id,
            'sort_order'      => 1,
            'asset_category'  => ProjectValuationObject::CATEGORY_TANAH_BANGUNAN_SARANA,
            'land_area'       => 3100.00,
            'building_area'   => 1450.00,
            'location'        => 'Jl. Raya Serpong KM 8, Kel. Serpong, Tangerang, Banten',
            'ownership_form'  => 'Tunggal - SHGB No. 5567',
            'owner_name'      => 'PT Multi Guna Sejahtera',
            'notes'           => 'Pabrik & gudang',
        ]);

        ProjectValuationObject::create([
            'project_id'      => $project4->id,
            'sort_order'      => 2,
            'asset_category'  => ProjectValuationObject::CATEGORY_MESIN,
            'unit_quantity'   => 14,
            'location'        => 'Jl. Raya Serpong KM 8, Kel. Serpong, Tangerang, Banten',
            'ownership_form'  => 'Invoice/Faktur Pembelian',
            'owner_name'      => 'PT Multi Guna Sejahtera',
            'notes'           => 'Mesin produksi di dalam pabrik yang sama',
        ]);

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
        // SKENARIO 5 — Selesai (semua invoice Paid) | 1 Objek: Mesin & Peralatan
        // Jenis: Pelaporan Keuangan (LK Properti) + Perusahaan Terbuka
        // ===================================================================
        $project5 = Project::create([
            'proposal_number'          => '0005/2.0131-00/KJPPSPR-PRO/APP/IX/2026',
            'instructing_client_id'    => $sinar->id,
            'asset_type'               => 'Mesin dan Peralatan',
            'asset_address'            => 'Jl. Industri Raya No. 45, Cikarang, Bekasi',
            'service_fee'              => 45_000_000,
            'report_style'             => 'Long Report',
            'sla_draft_days'        => 3,
            'sla_final_days'        => 5,
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

        ProjectValuationObject::create([
            'project_id'      => $project5->id,
            'sort_order'      => 1,
            'asset_category'  => ProjectValuationObject::CATEGORY_MESIN,
            'unit_quantity'   => 22,
            'location'        => 'Jl. Industri Raya No. 45, Cikarang, Bekasi, Jawa Barat',
            'ownership_form'  => 'Invoice/Faktur Pembelian & Kartu Aset Tetap',
            'owner_name'      => 'PT Sinar Abadi Teknik',
            'notes'           => 'Aset tetap operasional pabrik, sesuai daftar aset per akhir periode',
        ]);

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
