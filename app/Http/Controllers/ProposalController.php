<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ProposalController extends Controller
{
    public function create()
    {
        $clients = \App\Models\Client::orderBy('client_name')->get();
        return view('proposals.create_proposal', compact('clients'));
    }

    /**
     * Admin input data proyek & harga -> proposal_number di-generate otomatis
     * -> status awal 'Draft Proposal'. SLA TIDAK disimpan manual, cukup
     * dari accessor $project->sla_days (lihat Model Project).
     */
    public function store(Request $request)
    {
        // Multi-select psak_classification datang sebagai array dari form;
        // gabungkan jadi satu string sebelum divalidasi (kolom DB-nya string).
        if ($request->has('psak_classification') && is_array($request->psak_classification)) {
            $request->merge([
                'psak_classification' => implode(', ', $request->psak_classification),
            ]);
        }

        $validated = $request->validate([
            'instructing_client_id'    => 'required|exists:clients,id',
            'intended_user_ids'        => 'required|array|min:1',
            'intended_user_ids.*'      => 'exists:clients,id',
            'property_owner_name'      => 'required|string|max:255',
            'asset_type'               => 'required|string|max:255',
            'asset_address'            => 'required|string',
            'service_fee'              => 'required|numeric|min:0',
            'report_style'             => 'required|in:Terinci,Ringkas',
            'proposal_purpose'         => 'required|in:Jual Beli,Penjaminan Utang,Lelang,Pelaporan Keuangan',

            // Hanya wajib diisi jika proposal_purpose = 'Pelaporan Keuangan'
            'psak_classification'      => 'required_if:proposal_purpose,Pelaporan Keuangan|nullable|string|max:255',
            'financial_reporting_date' => 'required_if:proposal_purpose,Pelaporan Keuangan|nullable|date',
            'is_public_company'        => 'nullable|boolean',
        ]);

        $project = Project::create([
            'proposal_number'          => $this->generateProposalNumber(),
            'instructing_client_id'    => $validated['instructing_client_id'],
            'property_owner_name'      => $validated['property_owner_name'],
            'asset_type'               => $validated['asset_type'],
            'asset_address'            => $validated['asset_address'],
            'service_fee'              => $validated['service_fee'],
            'report_style'             => $validated['report_style'],
            'proposal_purpose'         => $validated['proposal_purpose'],
            'psak_classification'      => $validated['psak_classification'] ?? null,
            'financial_reporting_date' => $validated['financial_reporting_date'] ?? null,
            'is_public_company'        => $request->boolean('is_public_company'),
            'status'                   => Project::STATUS_DRAFT,
        ]);

        // Simpan multi Pengguna Laporan (bisa lebih dari 1 instansi)
        $project->intendedUsers()->sync($validated['intended_user_ids']);

        return redirect()
            ->route('proposals.show', $project)
            ->with('success', "Proposal {$project->proposal_number} berhasil dibuat.");
    }

    private function generateProposalNumber(): string
    {
        $year  = now()->year;
        $count = Project::whereYear('created_at', $year)->count() + 1;

        return sprintf('PRO/KJPP/%s/%03d', $year, $count);
    }

    /**
     * Generate PDF Proposal Resmi: gabungan data proyek + TEXT BAKU KJPP.
     * Teks baku (klausul standar, syarat & ketentuan) disimpan terpisah
     * di view resources/views/pdf/proposal.blade.php agar mudah di-maintain
     * oleh non-developer (mis. tim legal) tanpa menyentuh controller.
     */
    public function exportPdf(Project $project)
    {
        $project->load('instructingClient', 'intendedUsers');

        $pdf = Pdf::loadView('pdf.proposal', [
            'project'      => $project,
            'sla_days'     => $project->sla_days, // otomatis dari report_style
            'generated_at' => now()->format('d F Y'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download("Proposal-{$project->proposal_number}.pdf");
    }

    /**
     * Dipanggil saat klien SETUJU -> admin lanjut ke pemilihan skema termin.
     * (Pembuatan invoice DP sebenarnya ada di InvoiceController@generateDp
     * agar tanggung jawab tetap terpisah / single responsibility).
     */
    public function markApproved(Project $project)
    {
        $project->update(['status' => Project::STATUS_WAITING_APPROVAL]);

        return back()->with('success', 'Proposal ditandai disetujui klien. Silakan buat Invoice DP.');
    }
}
