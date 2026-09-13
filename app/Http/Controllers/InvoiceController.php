<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /** Bulan -> angka romawi, dipakai kedua format nomor (Invoice & Kwitansi). */
    private const ROMAN_MONTHS = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    /**
     * SATU pintu terbit invoice — menggantikan generateDp()/generateFinal()
     * lama yang mengasumsikan proyek SELALU tepat 2 tahap (DP + Pelunasan).
     * Sekarang sistem TIDAK peduli ini termin ke berapa: staf isi Persentase
     * ATAU Nominal (disinkronkan di form), lalu sistem cukup memvalidasi
     * nominalnya tidak melebihi SISA tagihan (total_fee - yang sudah Paid).
     * "invoice_type" tetap diisi (DP/Pelunasan) sekadar label riwayat —
     * otomatis "Pelunasan" kalau invoice ini melunasi sisa tagihan.
     */
    public function store(Request $request, Project $project)
    {
        if ($project->isCancelled()) {
            abort(403, 'Proyek berstatus Batal tidak dapat diterbitkan invoice.');
        }

        $validated = $request->validate([
            'amount'           => 'required|numeric|min:1',
            'percentage'       => 'nullable|numeric|min:0.01|max:100',
            'term_description' => 'nullable|string|max:255',
            'invoice_date'     => 'nullable|date',
        ]);

        $amount    = round((float) $validated['amount'], 2);
        $remaining = $project->remaining_balance;

        if ($remaining <= 0) {
            return back()->with('info', 'Tagihan proyek ini sudah lunas — tidak perlu invoice tambahan.');
        }

        // Toleransi Rp 1 utk pembulatan persentase/PPN.
        if ($amount > $remaining + 1) {
            return back()
                ->withErrors(['amount' => 'Nominal melebihi sisa tagihan (Rp ' . number_format($remaining, 0, ',', '.') . ').'])
                ->withInput();
        }

        $isFinal = $amount >= $remaining - 1; // invoice ini melunasi sisa tagihan
        $type    = $isFinal ? Invoice::TYPE_PELUNASAN : Invoice::TYPE_DP;

        $invoice = Invoice::create([
            'project_id'       => $project->id,
            'invoice_number'   => $this->nextInvoiceNumber(),
            'invoice_date'     => $validated['invoice_date'] ?? now(),
            'invoice_type'     => $type,
            'amount'           => $amount,
            'percentage'       => $validated['percentage'] ?? null,
            'status'           => Invoice::STATUS_UNPAID,
            'term_description' => $validated['term_description'] ?? ($isFinal ? 'Pelunasan Sisa Tagihan' : 'Termin Pembayaran'),
        ]);

        // Invoice PERTAMA pada proyek yang masih Draft/Menunggu Persetujuan
        // -> mulai proses penagihan (status DP Invoicing).
        if (in_array($project->status, [Project::STATUS_DRAFT, Project::STATUS_WAITING_APPROVAL], true)) {
            $project->update(['status' => Project::STATUS_DP_INVOICING]);
        }

        $sisaSetelahIni = max(0, $remaining - $amount);
        \App\Helpers\AuditLogger::record(
            'invoice.generated',
            "Menerbitkan Invoice {$invoice->invoice_number} sebesar Rp " . number_format($amount, 0, ',', '.')
                . " untuk proyek {$project->proposal_number} (sisa tagihan setelah ini: Rp " . number_format($sisaSetelahIni, 0, ',', '.') . ")",
            $invoice
        );

        return redirect()
            ->route('proposals.show', $project)
            ->with('success', "Invoice {$invoice->invoice_number} berhasil diterbitkan. Sisa tagihan: Rp " . number_format($sisaSetelahIni, 0, ',', '.') . '.');
    }

    /**
     * Edit nominal/keterangan invoice yang SUDAH ADA — mitigasi human
     * error (2026-09-12, feedback user): salah input nominal/keterangan,
     * atau baru sadar salahnya SETELAH invoice ditandai Paid. SENGAJA
     * tidak dibatasi status (boleh untuk invoice Paid juga) — beda dari
     * store() yang punya guard proyek-Batal, di sini cukup cek nominal
     * baru tidak melebihi jatah yang tersedia (sisa tagihan + nominal
     * invoice ini sendiri, karena invoice ini akan DIGANTI bukan ditambah).
     * TIDAK mengubah status Paid/Unpaid atau kwitansi_number — itu tetap
     * lewat markAsPaid() terpisah.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $project = $invoice->project;

        $validated = $request->validate([
            'amount'           => 'required|numeric|min:1',
            'term_description' => 'nullable|string|max:255',
        ]);

        $amount    = round((float) $validated['amount'], 2);
        $available = (float) $project->remaining_balance + (float) $invoice->amount;

        if ($amount > $available + 1) {
            return back()
                ->withErrors(['amount' => 'Nominal melebihi batas yang tersedia (Rp ' . number_format($available, 0, ',', '.') . ').'])
                ->withInput();
        }

        $oldAmount = (float) $invoice->amount;
        $isFinal   = $amount >= $available - 1;

        $invoice->update([
            'amount'           => $amount,
            'term_description' => $validated['term_description'] ?: null,
            'invoice_type'     => $isFinal ? Invoice::TYPE_PELUNASAN : Invoice::TYPE_DP,
        ]);

        \App\Helpers\AuditLogger::record(
            'invoice.updated',
            "Mengubah Invoice {$invoice->invoice_number}: nominal Rp " . number_format($oldAmount, 0, ',', '.')
                . ' -> Rp ' . number_format($amount, 0, ',', '.')
                . ($invoice->status === Invoice::STATUS_PAID ? ' (invoice sudah Lunas)' : ''),
            $invoice
        );

        $this->revertToPelunasanIfNoLongerFullyPaid($project);

        return back()->with('success', "Invoice {$invoice->invoice_number} berhasil diperbarui.");
    }

    /**
     * Jaga konsistensi status vs saldo (2026-09-14, feedback user): kalau
     * proyek SUDAH Selesai (artinya sempat lunas penuh) tapi invoice-nya
     * diedit/dihapus sehingga saldo TIDAK lagi lunas penuh, status harus
     * mundur ke Pelunasan — jangan dibiarkan "Selesai" dengan sisa
     * tagihan yang menggantung. Dipanggil dari update() & destroy().
     */
    private function revertToPelunasanIfNoLongerFullyPaid(Project $project): void
    {
        $project->load('invoices');

        if ($project->status === Project::STATUS_SELESAI && !$project->is_fully_paid) {
            $project->update(['status' => Project::STATUS_PELUNASAN]);

            \App\Helpers\AuditLogger::record(
                'project.status_reverted',
                "Status proyek {$project->proposal_number} dikembalikan dari Selesai ke Pelunasan — saldo tidak lagi lunas penuh (sisa Rp "
                    . number_format($project->remaining_balance, 0, ',', '.') . ') setelah invoice diedit/dihapus',
                $project
            );
        }
    }

    /**
     * Finance menandai invoice sebagai 'Paid' DENGAN tanggal pembayaran
     * manual (default hari ini kalau tidak diisi). Kwitansi baru diterbitkan
     * (nomor dibuat) SAAT INI — kwitansi tidak mungkin ada sebelum uang
     * benar diterima.
     *
     * Transisi status proyek BUKAN semata dari saldo (lunas != selesai
     * kerja!) — harus tetap lewat gerbang pekerjaan lapangan:
     *   DP Invoicing -> In-Progress   : pembayaran PERTAMA masuk, mulai
     *     kerja lapangan (assign penilai, tanggal survei, dst).
     *   Pelunasan -> Selesai          : HANYA kalau proyek sudah lewat
     *     tahap Pelunasan (artinya draf laporan sudah ditandai selesai
     *     lewat markDraftComplete()) DAN saldo lunas.
     * BUG lama: kalau invoice PERTAMA kebetulan langsung menutup 100%
     * sisa tagihan (mis. klien bayar lunas di muka tanpa termin DP), kode
     * lama langsung lompat ke Selesai walau status masih DP Invoicing —
     * padahal kerja lapangan (assign penilai/tanggal survei/SLA) belum
     * pernah terjadi sama sekali. Sekarang status HANYA bisa mencapai
     * Selesai lewat Pelunasan, jadi pembayaran 100% di muka tetap
     * berhenti dulu di In-Progress sampai draf benar-benar ditandai
     * selesai.
     */
    public function markAsPaid(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'payment_date' => 'nullable|date',
        ]);

        $invoice->update([
            'status'          => Invoice::STATUS_PAID,
            'payment_date'    => $validated['payment_date'] ?? now(),
            'kwitansi_number' => $invoice->kwitansi_number ?: $this->nextKwitansiNumber(),
        ]);

        $project = $invoice->project;
        $project->load('invoices');

        if ($project->status === Project::STATUS_PELUNASAN && $project->is_fully_paid) {
            $project->update(['status' => Project::STATUS_SELESAI]);
        } elseif ($project->status === Project::STATUS_DP_INVOICING) {
            $project->update(['status' => Project::STATUS_IN_PROGRESS]);
        }

        \App\Helpers\AuditLogger::record(
            'invoice.paid',
            "Mengubah status invoice {$invoice->invoice_number} menjadi Paid — kwitansi {$invoice->kwitansi_number} diterbitkan",
            $invoice
        );

        return back()->with('success', "Invoice {$invoice->invoice_number} ditandai Paid. Kwitansi {$invoice->kwitansi_number} diterbitkan.");
    }

    /**
     * Batalkan/hapus invoice yang salah input.
     *
     * GUARD DILONGGARKAN (2026-09-12, feedback user): sebelumnya invoice
     * berstatus 'Paid' TIDAK BOLEH dihapus sama sekali (dianggap catatan
     * akuntansi resmi). Sekarang boleh, khusus utk mitigasi human error —
     * mis. staf salah klik "Verifikasi Lunas" padahal klien belum benar-
     * benar transfer. Konfirmasi di UI (lihat proposals/show.blade.php)
     * sengaja beda teksnya kalau invoice sudah Lunas, supaya tidak
     * terklik tanpa sadar.
     *
     * KOREKSI STATUS OTOMATIS (2026-09-14, feedback user — merevisi
     * catatan lama di sini yang bilang status TIDAK otomatis dikoreksi):
     * kalau proyek sudah Selesai lalu invoice Paid-nya dihapus sehingga
     * saldo tidak lagi lunas penuh, status dikembalikan ke Pelunasan
     * lewat revertToPelunasanIfNoLongerFullyPaid() — supaya tidak ada
     * proyek "Selesai" dengan sisa tagihan yang menggantung.
     *
     * Karena sekarang proyek boleh punya banyak invoice, status proyek
     * HANYA dikembalikan ke Draft kalau invoice yang dihapus ini SATU-
     * SATUNYA riwayat tagihan proyek (baru saja dibuat, belum ada apa-apa
     * lagi) — kalau masih ada invoice lain, status dibiarkan apa adanya.
     */
    public function destroy(Invoice $invoice)
    {
        $project = $invoice->project;
        $invoiceNumber = $invoice->invoice_number;
        $wasPaid = $invoice->status === Invoice::STATUS_PAID;

        \App\Helpers\AuditLogger::record(
            'invoice.cancelled',
            "Membatalkan/menghapus Invoice {$invoiceNumber}" . ($wasPaid ? ' (SUDAH LUNAS, kwitansi ' . $invoice->kwitansi_number . ')' : ''),
            $invoice
        );

        $invoice->delete();

        $project->load('invoices');
        if ($project->invoices->isEmpty() && $project->status === Project::STATUS_DP_INVOICING) {
            $project->update(['status' => Project::STATUS_DRAFT]);
        }

        $this->revertToPelunasanIfNoLongerFullyPaid($project);

        return back()->with('success', "Invoice {$invoiceNumber} berhasil dibatalkan/dihapus.");
    }

    public function exportInvoice(Invoice $invoice)
    {
        $invoice->load('project.instructingClient', 'project.valuationObjects', 'project.bank', 'project.signedBy');

        // Rekening = pilihan proposal -> bank default -> fallback config lama.
        $bank = $invoice->project->effectiveBank();

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice'    => $invoice,
            'project'    => $invoice->project,
            'bank_info'  => $bank ? $bank->toClauseArray() : config('kjpp.bank_account'),
        ])->setPaper('a4', 'portrait');

        $safeFilename = str_replace(['/', '\\'], '-', $invoice->invoice_number);
        return $pdf->download("Invoice-{$safeFilename}.pdf");
    }

    public function exportKwitansi(Invoice $invoice)
    {
        if ($invoice->status !== Invoice::STATUS_PAID) {
            abort(403, 'Kwitansi hanya dapat dicetak untuk invoice yang sudah berstatus Paid.');
        }

        // Jaga-jaga data lama (Paid sebelum kolom kwitansi_number ada).
        if (! $invoice->kwitansi_number) {
            $invoice->update(['kwitansi_number' => $this->nextKwitansiNumber()]);
        }

        $invoice->load('project.instructingClient', 'project.valuationObjects', 'project.bank', 'project.signedBy');

        $bank = $invoice->project->effectiveBank();

        $pdf = Pdf::loadView('pdf.kwitansi', [
            'invoice'   => $invoice,
            'project'   => $invoice->project,
            'bank_info' => $bank ? $bank->toClauseArray() : config('kjpp.bank_account'),
        ])->setPaper('a4', 'portrait');

        $safeFilename = str_replace(['/', '\\'], '-', $invoice->kwitansi_number);
        return $pdf->download("Kwitansi-{$safeFilename}.pdf");
    }

    /**
     * Format baru: "000/KJPPSPR-INV-JKT/<romawi bulan>/<tahun>" — SATU
     * urutan global (bukan lagi terpisah per tipe DP/Pelunasan), reset
     * tiap tahun. 3 digit awal = nomor urut (bukan lagi 3 digit akhir
     * seperti skema lama, karena posisi tahun & tipe di string sudah beda).
     */
    private function nextInvoiceNumber(): string
    {
        return $this->nextSequentialNumber('invoice_number', 'KJPPSPR-INV-JKT', 'created_at');
    }

    /** Format baru Kwitansi: "000/KJPPSPR-KEU-JK/<romawi bulan>/<tahun>". */
    private function nextKwitansiNumber(): string
    {
        return $this->nextSequentialNumber('kwitansi_number', 'KJPPSPR-KEU-JK', 'payment_date');
    }

    private function nextSequentialNumber(string $numberColumn, string $suffix, string $dateColumn): string
    {
        $year  = now()->year;
        $roman = self::ROMAN_MONTHS[now()->month - 1];

        $last = Invoice::whereYear($dateColumn, $year)
            ->whereNotNull($numberColumn)
            ->orderByDesc('id')
            ->value($numberColumn);

        $next = $last ? ((int) explode('/', $last)[0]) + 1 : 1;

        return sprintf('%03d/%s/%s/%d', $next, $suffix, $roman, $year);
    }

    public function show(Invoice $invoice)
    {
        return redirect()->route('proposals.show', $invoice->project_id);
    }
}
