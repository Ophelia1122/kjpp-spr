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
     * Finance menandai invoice sebagai 'Paid' DENGAN tanggal pembayaran
     * manual (default hari ini kalau tidak diisi). Kwitansi baru diterbitkan
     * (nomor dibuat) SAAT INI — kwitansi tidak mungkin ada sebelum uang
     * benar diterima. Transisi status proyek sekarang berdasarkan SALDO
     * (bukan tipe invoice tertentu): lunas penuh -> Selesai; pembayaran
     * pertama masuk -> In-Progress (mulai kerja lapangan).
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

        if ($project->is_fully_paid) {
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
     * GUARD KETAT: hanya invoice berstatus 'Unpaid' yang boleh dihapus.
     * Invoice yang sudah 'Paid' TIDAK BOLEH dihapus — itu catatan
     * akuntansi resmi & sudah punya kwitansi.
     *
     * Karena sekarang proyek boleh punya banyak invoice, status proyek
     * HANYA dikembalikan ke Draft kalau invoice yang dihapus ini SATU-
     * SATUNYA riwayat tagihan proyek (baru saja dibuat, belum ada apa-apa
     * lagi) — kalau masih ada invoice lain, status dibiarkan apa adanya.
     */
    public function destroy(Invoice $invoice)
    {
        if ($invoice->status === Invoice::STATUS_PAID) {
            abort(403, 'Invoice yang sudah Paid tidak dapat dihapus. Hubungi admin sistem jika terjadi kesalahan nominal.');
        }

        $project = $invoice->project;
        $invoiceNumber = $invoice->invoice_number;
        \App\Helpers\AuditLogger::record('invoice.cancelled', "Membatalkan/menghapus Invoice {$invoiceNumber}", $invoice);

        $invoice->delete();

        $project->load('invoices');
        if ($project->invoices->isEmpty() && $project->status === Project::STATUS_DP_INVOICING) {
            $project->update(['status' => Project::STATUS_DRAFT]);
        }

        return back()->with('success', "Invoice {$invoiceNumber} berhasil dibatalkan/dihapus.");
    }

    public function exportInvoice(Invoice $invoice)
    {
        $invoice->load('project.instructingClient', 'project.valuationObjects', 'project.bank');

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

        $invoice->load('project.instructingClient', 'project.valuationObjects', 'project.bank');

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
