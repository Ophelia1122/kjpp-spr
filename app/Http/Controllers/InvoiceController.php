<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * Admin memilih skema termin (mis. "DP 50%") -> sistem menerbitkan
     * Invoice DP dan mengubah status proyek jadi 'DP Invoicing'.
     */
    public function generateDp(Request $request, Project $project)
    {
        $validated = $request->validate([
            'dp_percentage'     => 'required|numeric|min:1|max:100',
            'term_description'  => 'nullable|string|max:255',
        ]);

        $amount = round(
            $project->service_fee * ($validated['dp_percentage'] / 100),
            2
        );

        $invoice = Invoice::create([
            'project_id'        => $project->id,
            'invoice_number'    => $this->generateInvoiceNumber(Invoice::TYPE_DP),
            'invoice_type'      => Invoice::TYPE_DP,
            'amount'            => $amount,
            'status'            => Invoice::STATUS_UNPAID,
            'term_description'  => $validated['term_description']
                ?? "DP {$validated['dp_percentage']}%",
        ]);

        $project->update(['status' => Project::STATUS_DP_INVOICING]);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', "Invoice DP {$invoice->invoice_number} berhasil diterbitkan.");
    }

    /**
     * Sisa tagihan (Pelunasan) = service_fee - total invoice DP yang sudah Paid.
     * Dipanggil otomatis oleh ProjectController@markDraftCompleted.
     */
    public function generateFinal(Project $project)
    {
        $paidDp = $project->invoices()
            ->where('invoice_type', Invoice::TYPE_DP)
            ->where('status', Invoice::STATUS_PAID)
            ->sum('amount');

        $remaining = round($project->service_fee - $paidDp, 2);

        $invoice = Invoice::create([
            'project_id'       => $project->id,
            'invoice_number'   => $this->generateInvoiceNumber(Invoice::TYPE_PELUNASAN),
            'invoice_type'     => Invoice::TYPE_PELUNASAN,
            'amount'           => $remaining,
            'status'           => Invoice::STATUS_UNPAID,
            'term_description' => 'Pelunasan Sisa Tagihan',
        ]);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', "Invoice Pelunasan {$invoice->invoice_number} berhasil dibuat.");
    }

    /**
     * Finance menandai invoice sebagai 'Paid'. Ini adalah TRIGGER UTAMA
     * workflow: jika invoice DP -> buka fitur input penilai (status
     * proyek jadi 'In-Progress / Scheduled').
     */
    public function markAsPaid(Request $request, Invoice $invoice)
    {
        $invoice->update([
            'status'       => Invoice::STATUS_PAID,
            'payment_date' => $request->input('payment_date', now()),
        ]);

        if ($invoice->invoice_type === Invoice::TYPE_DP) {
            $invoice->project->update([
                'status' => Project::STATUS_IN_PROGRESS,
            ]);
        }

        if ($invoice->invoice_type === Invoice::TYPE_PELUNASAN) {
            $invoice->project->update([
                'status' => Project::STATUS_SELESAI,
            ]);
        }

        return back()->with('success', "Invoice {$invoice->invoice_number} ditandai Paid.");
    }

    /**
     * Export PDF Invoice resmi (Kop Surat, Detail Termin, Nominal,
     * No Rekening Bank KJPP). Data rekening bank sebaiknya disimpan
     * di config/kjpp.php agar mudah diubah tanpa sentuh kode.
     */
    public function exportInvoice(Invoice $invoice)
    {
        $invoice->load('project.instructingClient');

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice'    => $invoice,
            'project'    => $invoice->project,
            'bank_info'  => config('kjpp.bank_account'), // lihat config/kjpp.php
        ])->setPaper('a4', 'portrait');

        $safeFilename = str_replace(['/', '\\'], '-', $invoice->invoice_number);
        return $pdf->download("Invoice-{$safeFilename}.pdf");
    }

    private function generateInvoiceNumber(string $type): string
    {
        $year   = now()->year;
        $prefix = $type === Invoice::TYPE_DP ? 'INV-DP' : 'INV-PLN';
        $count  = Invoice::where('invoice_type', $type)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('%s/KJPP/%s/%03d', $prefix, $year, $count);
    }
}
