<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    // Format & urutan nomor Invoice/Kwitansi: lihat App\Services\DocumentNumbering.

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
            // "Telah diterima dari" & "an." — hanya pihak yang terkait proyek ini (2026-09-14).
            'received_from_client_id' => ['nullable', \Illuminate\Validation\Rule::in($project->receivedFromOptions()->pluck('id')->all())],
            'on_behalf_of_client_id'  => ['nullable', \Illuminate\Validation\Rule::in($project->receivedFromOptions()->pluck('id')->all())],
        ]);

        // Cegah invoice ganda karena tombol diklik 2x saat loading (2026-09-15,
        // feedback user): kunci singkat per proyek + tolak invoice identik yang
        // baru saja dibuat.
        $lock = \Illuminate\Support\Facades\Cache::lock("invoice-store-project-{$project->id}", 10);
        if (! $lock->get()) {
            return back()->with('info', 'Invoice sedang diproses. Tunggu sebentar.');
        }

        try {
            return $this->storeInvoice($project, $validated);
        } finally {
            $lock->release();
        }
    }

    private function storeInvoice(Project $project, array $validated)
    {
        $amount    = round((float) $validated['amount'], 2);

        $duplicate = Invoice::where('project_id', $project->id)
            ->where('amount', $amount)
            ->where('created_at', '>=', now()->subSeconds(30))
            ->exists();
        if ($duplicate) {
            return back()->with('info', 'Invoice dengan nominal yang sama baru saja diterbitkan — tidak dibuat ulang.');
        }

        // Batas invoice baru = Sisa Tagihan (nilai kontrak yang belum
        // di-invoice), bukan sisa yang belum dibayar — supaya total invoice
        // tidak pernah melebihi nilai kontrak (2026-09-21).
        $remaining = $project->uninvoiced_balance;

        if ($remaining <= 0) {
            return back()->with('error', 'Seluruh nilai kontrak sudah ditagihkan (invoice/kwitansi sudah terbit). Hapus invoice yang ada dulu bila ingin membuat invoice baru.');
        }

        // Toleransi Rp 1 utk pembulatan persentase/PPN.
        if ($amount > $remaining + 1) {
            return back()
                ->withErrors(['amount' => 'Nominal melebihi sisa tagihan yang belum di-invoice (Rp ' . number_format($remaining, 0, ',', '.') . ').'])
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
            'received_from_client_id' => $validated['received_from_client_id'] ?? $project->instructing_client_id,
            'on_behalf_of_client_id'  => $validated['on_behalf_of_client_id'] ?? ($project->client_id ?? $project->instructing_client_id),
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
            'received_from_client_id' => ['nullable', \Illuminate\Validation\Rule::in($project->receivedFromOptions()->pluck('id')->all())],
            'on_behalf_of_client_id'  => ['nullable', \Illuminate\Validation\Rule::in($project->receivedFromOptions()->pluck('id')->all())],
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
            'received_from_client_id' => $validated['received_from_client_id'] ?? $invoice->received_from_client_id,
            'on_behalf_of_client_id'  => $validated['on_behalf_of_client_id'] ?? $invoice->on_behalf_of_client_id,
        ]);

        \App\Helpers\AuditLogger::record(
            'invoice.updated',
            "Mengubah Invoice {$invoice->invoice_number}: nominal Rp " . number_format($oldAmount, 0, ',', '.')
                . ' -> Rp ' . number_format($amount, 0, ',', '.')
                . ($invoice->status === Invoice::STATUS_PAID ? ' (invoice sudah Lunas)' : ''),
            $invoice
        );

        return back()->with('success', "Invoice {$invoice->invoice_number} berhasil diperbarui.");
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
     *   Selesai TIDAK lagi dipicu pembayaran (2026-09-15) — ditentukan alur
     *     produksi (buku selesai dicetak, ProjectController::advanceWorkflow()).
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

        // Status Selesai kini ditentukan alur produksi (buku dicetak), bukan
        // pelunasan (2026-09-15, feedback user) — pembayaran hanya memulai
        // pekerjaan dari tahap DP.
        if ($project->status === Project::STATUS_DP_INVOICING) {
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
     * Status Selesai tidak dikoreksi saat invoice dihapus (2026-09-15):
     * Selesai = buku dicetak, jadi proyek Selesai boleh punya sisa tagihan.
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

        return back()->with('success', "Invoice {$invoiceNumber} berhasil dibatalkan/dihapus.");
    }

    public function exportInvoice(Invoice $invoice)
    {
        $pdf = Pdf::loadView('pdf.invoice', $this->documentData($invoice))->setPaper('a4', 'portrait');

        return $pdf->download('Invoice-' . $this->safeFilename($invoice->invoice_number) . '.pdf');
    }

    public function exportKwitansi(Invoice $invoice)
    {
        $this->prepareKwitansi($invoice);

        $pdf = Pdf::loadView('pdf.kwitansi', $this->documentData($invoice))->setPaper('a4', 'portrait');

        return $pdf->download('Kwitansi-' . $this->safeFilename($invoice->kwitansi_number) . '.pdf');
    }

    /**
     * Unduh Invoice/Kwitansi sebagai .docx (2026-09-14, feedback user) —
     * untuk kasus yang isinya perlu disesuaikan manual sebelum dikirim.
     */
    public function exportInvoiceWord(Invoice $invoice)
    {
        $data = $this->documentData($invoice);
        $path = (new \App\Services\InvoiceDocxBuilder($invoice, $data['bank_info']))->saveInvoice();

        return response()
            ->download($path, 'Invoice-' . $this->safeFilename($invoice->invoice_number) . '.docx')
            ->deleteFileAfterSend(true);
    }

    public function exportKwitansiWord(Invoice $invoice)
    {
        $this->prepareKwitansi($invoice);

        $data = $this->documentData($invoice);
        $path = (new \App\Services\InvoiceDocxBuilder($invoice, $data['bank_info']))->saveKwitansi();

        return response()
            ->download($path, 'Kwitansi-' . $this->safeFilename($invoice->kwitansi_number) . '.docx')
            ->deleteFileAfterSend(true);
    }

    /**
     * Kwitansi normalnya hanya untuk invoice yang sudah Paid. Proyek skema
     * Bayar Nanti boleh mencetak kwitansi sebelum pembayaran diverifikasi
     * (2026-09-14, feedback user) — sebagian klien meminta invoice DAN
     * kwitansi sekaligus sebagai syarat proses pembayarannya.
     */
    private function prepareKwitansi(Invoice $invoice): void
    {
        $invoice->loadMissing('project');

        if ($invoice->status !== Invoice::STATUS_PAID && ! $invoice->project->isPaymentDeferred()) {
            abort(403, 'Kwitansi hanya dapat dicetak untuk invoice yang sudah berstatus Paid, kecuali proyek berskema Bayar Nanti.');
        }

        // Data lama (Paid sebelum kolom kwitansi_number ada) atau kwitansi
        // Bayar Nanti yang baru pertama kali dicetak. Nomor ini dipakai
        // terus saat invoice nanti ditandai Paid (lihat markAsPaid).
        if (! $invoice->kwitansi_number) {
            $invoice->update(['kwitansi_number' => $this->nextKwitansiNumber()]);
        }
    }

    /** Data bersama PDF & Word: relasi proyek + rekening efektif. */
    private function documentData(Invoice $invoice): array
    {
        $invoice->load('project.instructingClient', 'project.valuationObjects', 'project.bank', 'project.signedBy', 'receivedFromClient', 'onBehalfOfClient');

        // Rekening = pilihan proposal -> bank default -> fallback config lama.
        $bank = $invoice->project->effectiveBank();

        return [
            'invoice'   => $invoice,
            'project'   => $invoice->project,
            'bank_info' => $bank ? $bank->toClauseArray() : config('kjpp.bank_account'),
        ];
    }

    private function safeFilename(string $number): string
    {
        return str_replace(['/', '\\'], '-', $number);
    }

    /**
     * Format baru: "000/KJPPSPR-INV-JKT/<romawi bulan>/<tahun>" — SATU
     * urutan global (bukan lagi terpisah per tipe DP/Pelunasan), reset
     * tiap tahun. 3 digit awal = nomor urut (bukan lagi 3 digit akhir
     * seperti skema lama, karena posisi tahun & tipe di string sudah beda).
     */
    private function nextInvoiceNumber(): string
    {
        return app(\App\Services\DocumentNumbering::class)->next('invoice');
    }

    /** Format baru Kwitansi: "000/KJPPSPR-KEU-JK/<romawi bulan>/<tahun>". */
    private function nextKwitansiNumber(): string
    {
        return app(\App\Services\DocumentNumbering::class)->next('kwitansi');
    }
}
