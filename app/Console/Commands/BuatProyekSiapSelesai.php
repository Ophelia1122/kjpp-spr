<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\DeliveryReceipt;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectValuationObject;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Membuat SATU proyek contoh yang tinggal satu tombol lagi selesai:
 * "Laporan Siap Dikirim" di kartu Aksi Tersedia. Dipakai untuk mencoba
 * animasi konfeti penutupan proyek tanpa harus menjalani seluruh alur.
 *
 * Perintah ini HANYA MENAMBAH data — tidak mengubah atau menghapus apa pun
 * yang sudah ada. Jalankan di komputer lokal saja.
 */
class BuatProyekSiapSelesai extends Command
{
    protected $signature = 'dummy:siap-selesai {--belum-lunas : Sisakan tagihan supaya berakhir "Selesai - Belum Lunas"}';

    protected $description = 'Buat satu proyek contoh yang tinggal ditekan tombol "Laporan Siap Dikirim"';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Perintah ini tidak boleh dijalankan di lingkungan production.');

            return self::FAILURE;
        }

        $penandaTangan = User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMINISTRATOR))->first();

        if (! $penandaTangan) {
            $this->error('Tidak ada pengguna dengan role Administrator. Jalankan seeder dasar dulu.');

            return self::FAILURE;
        }

        $lunas = ! $this->option('belum-lunas');

        $proyek = DB::transaction(function () use ($penandaTangan, $lunas) {
            $klien = Client::firstOrCreate(
                ['client_name' => 'PT Contoh Konfeti Sejahtera'],
                ['client_type' => 'Korporat', 'address' => 'Jl. Percobaan No. 17, Bandung'],
            );

            $urut  = Project::withTrashed()->count() + 1;
            $waktu = now();

            $proyek = Project::create([
                'proposal_number'       => sprintf('DUMMY/%03d/%s', $urut, $waktu->format('m/Y')),
                'proposal_date'         => $waktu->copy()->subDays(30)->toDateString(),
                'instructing_client_id' => $klien->id,
                'signed_by_user_id'     => $penandaTangan->id,

                'service_fee'           => 12_000_000,
                'initial_service_fee'   => 12_000_000,
                'fee_ppn_included'      => true,
                'payment_scheme'        => Project::PAYMENT_SCHEME_DP,
                'payment_terms'         => '50,50',

                'report_style'          => Project::REPORT_LONG,
                'sla_draft_days'        => 5,
                'sla_final_days'        => 7,
                'proposal_purpose'      => Project::PURPOSE_PENJAMINAN_UTANG,

                'asset_type'            => 'Tanah dan Bangunan',
                'asset_address'         => 'Jl. Percobaan No. 17, Bandung',

                // Survei sudah jalan — syarat tombol alur produksi muncul.
                'assigned_appraiser'    => $penandaTangan->name,
                'survey_date'           => $waktu->copy()->subDays(20)->toDateString(),
                'valuation_date_manual' => $waktu->copy()->subDays(20)->toDateString(),

                // Seluruh tahap sebelum pengiriman sudah dilewati.
                'status'                => Project::STATUS_PENGIRIMAN,
                'review_status'         => Project::STAGE_SIGNED,
                'review_submitted_at'   => $waktu->copy()->subDays(18),
                'review_approved_at'    => $waktu->copy()->subDays(16),
                'review_approved_by_user_id' => $penandaTangan->id,
                'draft_submitted_at'    => $waktu->copy()->subDays(10),
                'draft_confirmed_at'    => $waktu->copy()->subDays(8),
                'draft_reviewed_at'     => $waktu->copy()->subDays(6),
                'printed_at'            => $waktu->copy()->subDays(3),
                'signed_at'             => $waktu->copy()->subDay(),

                'final_report_number'   => sprintf('DUMMY-LAP/%03d/%s', $urut, $waktu->format('m/Y')),
                'final_report_date'     => $waktu->copy()->subDays(2)->toDateString(),
            ]);

            $proyek->intendedUsers()->sync([$klien->id]);

            ProjectValuationObject::create([
                'project_id'     => $proyek->id,
                'asset_category' => 'Real Properti - Tanah dan Bangunan',
                'location'       => 'Jl. Percobaan No. 17, Bandung',
                'ownership_form' => 'SHM No. 1234',
                'owner_name'     => $klien->client_name,
            ]);

            // Dua invoice 50/50. Yang kedua dibiarkan belum lunas kalau
            // diminta, supaya status akhirnya "Selesai - Belum Lunas".
            $separuh = round((float) $proyek->total_fee / 2, 2);

            Invoice::create([
                'project_id'     => $proyek->id,
                'invoice_number' => sprintf('DUMMY-INV/%03d-1/%s', $urut, $waktu->format('m/Y')),
                'invoice_date'   => $waktu->copy()->subDays(25)->toDateString(),
                'invoice_type'   => Invoice::TYPE_DP,
                'amount'         => $separuh,
                'percentage'     => 50,
                'status'         => Invoice::STATUS_PAID,
                'payment_date'   => $waktu->copy()->subDays(24)->toDateString(),
            ]);

            Invoice::create([
                'project_id'     => $proyek->id,
                'invoice_number' => sprintf('DUMMY-INV/%03d-2/%s', $urut, $waktu->format('m/Y')),
                'invoice_date'   => $waktu->copy()->subDays(4)->toDateString(),
                'invoice_type'   => Invoice::TYPE_PELUNASAN,
                'amount'         => (float) $proyek->total_fee - $separuh,
                'percentage'     => 50,
                'status'         => $lunas ? Invoice::STATUS_PAID : Invoice::STATUS_UNPAID,
                'payment_date'   => $lunas ? $waktu->copy()->subDays(3)->toDateString() : null,
            ]);

            // Tanda Terima wajib ada sebelum tombol "Laporan Siap Dikirim"
            // boleh ditekan (lihat ProjectController::workflowStep).
            DeliveryReceipt::create([
                'project_id'          => $proyek->id,
                'number'              => sprintf('%03d/%s', $urut, $waktu->format('m/Y')),
                'delivery_date'       => $waktu->copy()->subDay()->toDateString(),
                'recipient_client_id' => $klien->id,
                'recipient_up'        => 'Bpk. Contoh Penerima',
                'documents'           => [
                    'laporan'  => ['qty' => 2],
                    'invoice'  => ['qty' => 1],
                    'kwitansi' => ['qty' => 1],
                ],
                'created_by_user_id'  => $penandaTangan->id,
            ]);

            return $proyek;
        });

        $this->info('Proyek contoh dibuat: ' . $proyek->proposal_number);
        $this->line('  Status       : ' . $proyek->status);
        $this->line('  Tahap        : ' . $proyek->review_status . ' (tinggal tombol "Laporan Siap Dikirim")');
        $this->line('  Status akhir : ' . $proyek->fresh()->finalStatusAfterDelivery());
        $this->line('  Buka         : ' . route('proposals.show', $proyek));

        return self::SUCCESS;
    }
}
