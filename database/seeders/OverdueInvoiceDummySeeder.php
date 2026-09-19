<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * DATA DUMMY untuk menguji tampilan invoice tertunggak di Dashboard
 * Pembayaran (2026-09-14, permintaan user). Jalankan:
 *   php artisan db:seed --class=OverdueInvoiceDummySeeder
 *
 * Semua invoice dummy memakai keterangan berawalan "[DUMMY]". Hapus lewat
 * tombol hapus invoice di halaman proyek, atau sekaligus:
 *   php artisan tinker --execute="App\Models\Invoice::where('term_description','like','[DUMMY]%')->delete();"
 *
 * Aman dijalankan ulang: invoice [DUMMY] lama dihapus dulu. Hanya memakai
 * proyek skema DP di Awal yang sudah berjalan (bukan Draft/Batal/Selesai)
 * dan masih punya sisa tagihan, supaya status proyek tidak ikut berubah.
 */
class OverdueInvoiceDummySeeder extends Seeder
{
    public function run(): void
    {
        Invoice::where('term_description', 'like', '[DUMMY]%')->delete();

        $projects = Project::with('invoices')
            ->where('status', Project::STATUS_IN_PROGRESS)
            ->where('payment_scheme', Project::PAYMENT_SCHEME_DP)
            ->get()
            ->filter(fn ($p) => $p->remaining_balance > 0)
            ->sortByDesc('remaining_balance')
            ->values();

        if ($projects->isEmpty()) {
            $this->command?->warn('Tidak ada proyek DP di Awal yang berjalan dengan sisa tagihan. Tidak ada dummy yang dibuat.');
            return;
        }

        // [hari lalu, porsi dari sisa tagihan, keterangan]
        $scenarios = [
            [35, 0.30, '[DUMMY] Termin 2 — tertunggak 35 hari'],
            [21, 0.50, '[DUMMY] Termin 3 — tertunggak 21 hari'],
            [5,  0.20, '[DUMMY] Termin 4 — belum tertunggak (5 hari)'],
        ];

        foreach ($scenarios as $i => [$daysAgo, $portion, $label]) {
            $project = $projects[$i % $projects->count()];
            $amount  = max(100000, round($project->remaining_balance * $portion, -3));
            $date    = now()->subDays($daysAgo)->startOfDay();

            $invoice = Invoice::create([
                'project_id'       => $project->id,
                'invoice_number'   => $this->nextInvoiceNumber(),
                'invoice_date'     => $date,
                'invoice_type'     => Invoice::TYPE_DP,
                'amount'           => $amount,
                'status'           => Invoice::STATUS_UNPAID,
                'term_description' => $label,
            ]);

            $this->command?->info("{$invoice->invoice_number} · {$project->proposal_number} · Rp " . number_format($amount, 0, ',', '.') . " · terbit {$date->format('d/m/Y')}");
        }
    }

    /** Aturan penomoran yang sama dengan aplikasi (termasuk nomor terakhir manual). */
    private function nextInvoiceNumber(): string
    {
        return app(\App\Services\DocumentNumbering::class)->next('invoice');
    }
}
