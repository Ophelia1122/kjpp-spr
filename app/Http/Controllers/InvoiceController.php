<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * Admin memilih skema termin (mis. "DP 50%") + tanggal terbit (manual,
     * default hari ini) -> sistem menerbitkan Invoice DP dan mengubah
     * status proyek jadi 'DP Invoicing'.
     */
    public function generateDp(Request $request, Project $project)
    {
        $validated = $request->validate([
            'dp_percentage'     => 'required|numeric|min:1|max:100',
            'term_description'  => 'nullable|string|max:255',
            'invoice_date'      => 'nullable|date',
        ]);

        $amount = round(
            $project->service_fee * ($validated['dp_percentage'] / 100),
            2
        );

        $invoice = Invoice::create([
            'project_id'        => $project->id,
            'invoice_number'    => $this->generateInvoiceNumber(Invoice::TYPE_DP),
            'invoice_date'      => $validated['invoice_date'] ?? now(),
            'invoice_type'      => Invoice::TYPE_DP,
            'amount'            => $amount,
            'status'            => Invoice::STATUS_UNPAID,
            'term_description'  => $validated['term_description']
                ?? "DP {$validated['dp_percentage']}%",
        ]);

        $project->update(['status' => Project::STATUS_DP_INVOICING]);

        \App\Helpers\AuditLogger::record(
            'invoice.dp_generated',
            "Menerbitkan Invoice DP {$invoice->invoice_number} sebesar Rp " . number_format($amount, 0, ',', '.') . " untuk proyek {$project->proposal_number}",
            $invoice
        );

        return redirect()
            ->route('proposals.show', $project)
            ->with('success', "Invoice DP {$invoice->invoice_number} berhasil diterbitkan.");
    }

    /**
     * Dipanggil langsung dari tombol "Tandai Draf Selesai & Buat Invoice
     * Pelunasan" di dashboard proyek. Method ini SEKARANG merangkap 2 hal
     * sekaligus (sebelumnya terpisah di ProjectController@markDraftCompleted
     * yang di-redirect ke sini — itu BUG karena redirect selalu jadi GET,
     * padahal route ini POST-only, jadi akan 405 kalau benar-benar diklik):
     *   1. Validasi status proyek harus 'In-Progress / Scheduled'
     *   2. Ubah status proyek jadi 'Pelunasan' + buat Invoice Pelunasan
     * Tanggal terbit invoice bisa diinput manual (default hari ini).
     */
    public function generateFinal(Request $request, Project $project)
    {
        if ($project->status !== Project::STATUS_IN_PROGRESS) {
            abort(403, 'Invoice Pelunasan hanya bisa dibuat setelah proyek berstatus In-Progress.');
        }

        $validated = $request->validate([
            'invoice_date' => 'nullable|date',
        ]);

        $paidDp = $project->invoices()
            ->where('invoice_type', Invoice::TYPE_DP)
            ->where('status', Invoice::STATUS_PAID)
            ->sum('amount');

        $remaining = round($project->service_fee - $paidDp, 2);

        $invoice = Invoice::create([
            'project_id'       => $project->id,
            'invoice_number'   => $this->generateInvoiceNumber(Invoice::TYPE_PELUNASAN),
            'invoice_date'     => $validated['invoice_date'] ?? now(),
            'invoice_type'     => Invoice::TYPE_PELUNASAN,
            'amount'           => $remaining,
            'status'           => Invoice::STATUS_UNPAID,
            'term_description' => 'Pelunasan Sisa Tagihan',
        ]);

        $project->update(['status' => Project::STATUS_PELUNASAN]);

        
        \App\Helpers\AuditLogger::record(
            'invoice.final_generated',
            "Menerbitkan Invoice Pelunasan {$invoice->invoice_number} sebesar Rp " . number_format($remaining, 0, ',', '.') . " untuk proyek {$project->proposal_number}",
            $invoice
        );

        return redirect()
            ->route('proposals.show', $project)
            ->with('success', "Draf ditandai selesai. Invoice Pelunasan {$invoice->invoice_number} berhasil dibuat.");
    }

    /**
     * Finance menandai invoice sebagai 'Paid' DENGAN tanggal pembayaran
     * manual (default hari ini kalau tidak diisi). Tanggal ini yang
     * dipakai di PDF Kwitansi.
     */
    public function markAsPaid(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'payment_date' => 'nullable|date',
        ]);

        $invoice->update([
            'status'       => Invoice::STATUS_PAID,
            'payment_date' => $validated['payment_date'] ?? now(),
        ]);

        if ($invoice->invoice_type === Invoice::TYPE_DP) {
            $invoice->project->update(['status' => Project::STATUS_IN_PROGRESS]);
        }

        if ($invoice->invoice_type === Invoice::TYPE_PELUNASAN) {
            $invoice->project->update(['status' => Project::STATUS_SELESAI]);
        }
        
        \App\Helpers\AuditLogger::record(
            'invoice.paid',
            "Mengubah status invoice {$invoice->invoice_number} menjadi Paid",
            $invoice
        );

        return back()->with('success', "Invoice {$invoice->invoice_number} ditandai Paid.");
    }

    /**
     * Batalkan/hapus invoice yang salah input.
     * GUARD KETAT: hanya invoice berstatus 'Unpaid' yang boleh dihapus.
     * Invoice yang sudah 'Paid' TIDAK BOLEH dihapus sama sekali — itu
     * catatan akuntansi resmi (kalaupun salah input nominal, cara yang
     * benar adalah membuat catatan koreksi/void terpisah, bukan
     * menghapus jejaknya). Ini prinsip dasar audit trail.
     *
     * Efek samping: karena invoice ini menandai proyek masuk status
     * 'DP Invoicing' / 'Pelunasan', menghapusnya juga mengembalikan
     * status proyek ke tahap sebelumnya supaya alur tetap konsisten.
     */
    public function destroy(Invoice $invoice)
    {
        if ($invoice->status === Invoice::STATUS_PAID) {
            abort(403, 'Invoice yang sudah Paid tidak dapat dihapus. Hubungi admin sistem jika terjadi kesalahan nominal.');
        }

        $project = $invoice->project;
        $invoiceType = $invoice->invoice_type;
        $invoiceNumber = $invoice->invoice_number;
        \App\Helpers\AuditLogger::record('invoice.cancelled', "Membatalkan/menghapus Invoice {$invoiceNumber}", $invoice);

        $invoice->delete();

        // Kembalikan status proyek ke tahap sebelum invoice ini dibuat.
        if ($invoiceType === Invoice::TYPE_DP) {
            $project->update(['status' => Project::STATUS_DRAFT]);
        } elseif ($invoiceType === Invoice::TYPE_PELUNASAN) {
            $project->update(['status' => Project::STATUS_IN_PROGRESS]);
        }

        return back()->with('success', "Invoice {$invoiceNumber} berhasil dibatalkan/dihapus.");
    }

    public function exportInvoice(Invoice $invoice)
    {
        $invoice->load('project.instructingClient', 'project.valuationObjects');

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice'    => $invoice,
            'project'    => $invoice->project,
            'bank_info'  => config('kjpp.bank_account'),
        ])->setPaper('a4', 'portrait');

        $safeFilename = str_replace(['/', '\\'], '-', $invoice->invoice_number);
        return $pdf->download("Invoice-{$safeFilename}.pdf");
    }

    public function exportKwitansi(Invoice $invoice)
    {
        if ($invoice->status !== Invoice::STATUS_PAID) {
            abort(403, 'Kwitansi hanya dapat dicetak untuk invoice yang sudah berstatus Paid.');
        }

        $invoice->load('project.instructingClient', 'project.valuationObjects');

        $pdf = Pdf::loadView('pdf.kwitansi', [
            'invoice' => $invoice,
            'project' => $invoice->project,
        ])->setPaper('a4', 'landscape');

        $safeFilename = str_replace(['/', '\\'], '-', $invoice->invoice_number);
        return $pdf->download("Kwitansi-{$safeFilename}.pdf");
    }

    private function generateInvoiceNumber(string $type): string
    {
        $year   = now()->year;
        $prefix = $type === Invoice::TYPE_DP ? 'INV-DP' : 'INV-PLN';

        // Cari invoice terakhir berdasarkan 3 digit angka paling akhir di tahun & tipe ini
        $lastInvoice = Invoice::where('invoice_type', $type)
            ->whereYear('created_at', $year)
            ->orderByRaw('CAST(RIGHT(invoice_number, 3) AS UNSIGNED) DESC')
            ->first();

        if ($lastInvoice) {
            // Ambil 3 digit terakhir dari nomor invoice terakhir lalu tambahkan 1
            $lastNumber = (int) substr($lastInvoice->invoice_number, -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s/KJPPSPR-INV-JKT/%s/%03d', $prefix, $year, $nextNumber);
    }

    public function show(Invoice $invoice)
    {
        return redirect()->route('proposals.show', $invoice->project_id);
    }
}
