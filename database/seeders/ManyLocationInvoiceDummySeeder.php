<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectValuationObject;
use Illuminate\Database\Seeder;

/**
 * DATA DUMMY: proyek dengan 7 lokasi objek untuk melihat invoice yang
 * rincian lokasinya diringkas (> 5 lokasi) — 2026-09-14, permintaan user.
 *
 *   php artisan db:seed --class=ManyLocationInvoiceDummySeeder
 *
 * Dibuat dengan menyalin proyek berjalan yang sudah ada, nomor proposal
 * diawali "DUMMY/". Skema Bayar Nanti supaya kwitansi bisa langsung dicetak
 * tanpa menandai invoice Dibayar (angka "Sudah Diterima" tidak berubah).
 * Aman dijalankan ulang: dummy lama dihapus dulu. Hapus manual:
 *   php artisan tinker --execute="(new Database\Seeders\ManyLocationInvoiceDummySeeder)->purge();"
 */
class ManyLocationInvoiceDummySeeder extends Seeder
{
    private const NUMBER = 'DUMMY/KJPP/2026/7-LOKASI';

    private const LOCATIONS = [
        'Jl. Raya Bogor KM 27 No. 12, Cijantung, Pasar Rebo, Jakarta Timur',
        'Jl. Industri Selatan 5 Blok GG-3, Kawasan Jababeka II, Cikarang, Bekasi',
        'Jl. Ahmad Yani No. 88, Kel. Sukajadi, Kec. Karawaci, Kota Tangerang',
        'Jl. Raya Serang KM 18,5, Desa Sentul Jaya, Balaraja, Kab. Tangerang',
        'Jl. Pahlawan No. 21, Kel. Kebon Pedes, Kec. Tanah Sareal, Kota Bogor',
        'Jl. Soekarno-Hatta No. 590, Kel. Sekejati, Kec. Buahbatu, Kota Bandung',
        'Jl. Gatot Subroto Kav. 36-38, Kel. Kuningan Barat, Mampang Prapatan, Jakarta Selatan',
    ];

    public function run(): void
    {
        $this->purge();

        $source = Project::with('valuationObjects')
            ->where('proposal_number', 'not like', 'DUMMY/%')
            ->whereNotIn('status', [Project::STATUS_DRAFT, Project::STATUS_BATAL])
            ->orderByDesc('id')
            ->first();

        if (! $source) {
            $this->command?->warn('Tidak ada proyek berjalan untuk disalin.');
            return;
        }

        $project = $source->replicate();
        // Kolom nomor lain (surat tugas, faktur, laporan resmi) dikosongkan
        // supaya tidak bentrok dengan proyek asli.
        foreach (array_keys($project->getAttributes()) as $column) {
            if (str_contains($column, 'number') && $column !== 'proposal_number') {
                $project->{$column} = null;
            }
        }
        $project->proposal_number = self::NUMBER;
        $project->status          = Project::STATUS_IN_PROGRESS;
        $project->payment_scheme  = Project::PAYMENT_SCHEME_LATER;
        $project->save();

        $template = $source->valuationObjects->first();
        foreach (self::LOCATIONS as $i => $location) {
            ProjectValuationObject::create([
                'project_id'     => $project->id,
                'sort_order'     => $i + 1,
                'asset_category' => $template->asset_category ?? ProjectValuationObject::CATEGORY_TANAH_BANGUNAN,
                'land_area'      => 250 + $i * 40,
                'building_area'  => 120 + $i * 25,
                'location'       => $location,
                'ownership_form' => 'SHGB',
                'owner_name'     => $template->owner_name ?? 'PT Contoh Dummy',
            ]);
        }

        $invoice = Invoice::create([
            'project_id'       => $project->id,
            'invoice_number'   => app(\App\Services\DocumentNumbering::class)->next('invoice'),
            'invoice_date'     => now(),
            'invoice_type'     => Invoice::TYPE_DP,
            'amount'           => round($project->total_fee * 0.5, -3),
            'percentage'       => 50,
            'status'           => Invoice::STATUS_UNPAID,
            'term_description' => '[DUMMY] DP 50%',
        ]);

        $this->command?->info("Proyek {$project->proposal_number} (id {$project->id}) · 7 lokasi · invoice {$invoice->invoice_number}");
        $this->command?->info('Buka: /proposals/' . $project->id . ' → Tagihan → cetak Invoice / Kwitansi');
    }

    /** Hapus proyek dummy beserta objek & invoice-nya. */
    public function purge(): void
    {
        Project::where('proposal_number', self::NUMBER)->get()->each(function (Project $p) {
            Invoice::where('project_id', $p->id)->delete();
            ProjectValuationObject::where('project_id', $p->id)->delete();
            $p->intendedUsers()->detach();
            $p->delete();
        });
    }
}
