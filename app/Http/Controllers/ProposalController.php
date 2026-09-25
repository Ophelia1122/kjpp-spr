<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Project;
use App\Models\ProjectValuationObject;
use App\Models\User;
use App\Services\DocxToPdf;
use App\Services\ProposalDocxBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProposalController extends Controller
{
    public function create()
    {
        return view('proposals.create', [
            'signers' => User::penanggungJawab()->orderBy('name')->get(),
            'banks'   => $this->bankOptions(),
        ]);
    }

    /** Daftar rekening bank untuk dropdown proposal (default di atas). */
    private function bankOptions()
    {
        return Bank::orderByDesc('is_default')->orderBy('bank_name')->get();
    }

    /**
     * Nilai TA yang disimpan: hanya dicatat kalau format Rincian DAN TA tidak
     * ditanggung klien. All-in = TA dianggap sudah di dalam Fee; reimburse =
     * TA tidak ditagih sama sekali.
     */
    /**
     * Nama marketing yang disimpan: pilihan dropdown, KECUALI "Lainnya" — maka
     * yang disimpan adalah nama yang diketik manual (2026-09-15, feedback user).
     */
    private function resolveMarketingName(array $validated): ?string
    {
        $choice = $validated['marketing_name'] ?? null;

        if ($choice === 'Lainnya') {
            return trim((string) ($validated['marketing_name_other'] ?? '')) ?: null;
        }

        return $choice ?: null;
    }

    private function billableTransportInput(Request $request, array $validated): ?float
    {
        if (! $request->boolean('fee_breakdown') || $request->boolean('transport_reimbursed')) {
            return null;
        }

        return (float) ($validated['transport_cost'] ?? 0);
    }

    /**
     * Duplikat proposal (2026-09-24, feedback user): klien langganan sering
     * memakai isian yang hampir sama, jadi staf tidak perlu mengetik ulang
     * 20-an kolom. Yang TIDAK ikut disalin: nomor & tanggal proposal, seluruh
     * penomoran dokumen, tanda tangan/stempel, dan semua jejak pelaksanaan —
     * salinan selalu lahir sebagai Draft Proposal yang bersih.
     */
    public function duplicate(Project $project)
    {
        $project->load('valuationObjects', 'intendedUsers');

        $salinan = $project->replicate([
            // Penomoran & tanggal: wajib diisi ulang.
            'proposal_number', 'proposal_date',
            'assignment_letter_number', 'assignment_letter_date', 'assignment_letter_barcode',
            'assignment_letter_on_behalf_client_id',
            'assignment_letter_recipient_client_id',
            'tax_invoice_number', 'tax_invoice_date',
            'final_report_number', 'final_report_date', 'final_report_notes',
            // Tanda tangan & stempel: berkasnya milik proposal asal.
            'use_signature_barcode', 'signature_barcode', 'use_stamp',
            // Jejak pelaksanaan.
            'status', 'review_status', 'assigned_appraiser', 'assigned_appraiser_id',
            'survey_date', 'valuation_date_manual',
            'review_submitted_at', 'review_approved_at', 'review_approved_by_user_id',
            'reviewed_at', 'reviewed_by_user_id', 'review_submitted_by_user_id',
            'review_rejected_at', 'review_rejected_by_user_id', 'review_rejection_note',
            'draft_submitted_at', 'draft_confirmed_at', 'draft_reviewed_at',
            'printed_at', 'signed_at', 'delivered_at',
            'status_before_cancel', 'cancelled_at',
        ]);

        $salinan->proposal_number = $this->nomorSalinan($project->proposal_number);
        $salinan->proposal_date   = now()->toDateString();
        $salinan->status          = Project::STATUS_DRAFT;
        $salinan->save();

        $salinan->intendedUsers()->sync($project->intendedUsers->pluck('id')->all());

        foreach ($project->valuationObjects as $objek) {
            $baru = $objek->replicate(['survey_start_date', 'survey_end_date']);
            $baru->project_id = $salinan->id;
            $baru->save();
        }

        \App\Helpers\AuditLogger::record(
            'proposal.duplicated',
            "Menduplikat proposal {$project->proposal_number} menjadi {$salinan->proposal_number}",
            $salinan
        );

        return redirect()
            ->route('proposals.edit', [$salinan, 'baru' => 1])
            ->with('success', 'Proposal disalin. Ganti Nomor Proposal dan periksa isinya sebelum disimpan.');
    }

    /**
     * Buang salinan yang baru dibuat (tombol Batal pada halaman Edit sesudah
     * Duplikat, 2026-09-24 feedback user). Dihapus permanen supaya tidak
     * menumpuk di Sampah. Dijaga ketat: hanya Draft bertanda SALINAN-, belum
     * punya invoice, dan belum pernah masuk alur produksi.
     */
    public function discardDuplicate(Project $project)
    {
        abort_unless(
            $project->status === Project::STATUS_DRAFT
                && str_starts_with((string) $project->proposal_number, 'SALINAN-')
                && $project->review_status === null
                && $project->invoices()->count() === 0,
            403,
            'Hanya salinan proposal yang baru dibuat yang bisa dibuang lewat tombol ini.'
        );

        $nomor = $project->proposal_number;

        $project->valuationObjects()->delete();
        $project->intendedUsers()->detach();
        $project->forceDelete();

        \App\Helpers\AuditLogger::record('proposal.duplicate_discarded', "Membatalkan salinan proposal {$nomor}");

        return redirect()->route('dashboard')->with('info', 'Salinan proposal dibatalkan dan dihapus.');
    }

    /**
     * Catat perubahan biaya jasa / transport sebagai satu baris Riwayat
     * Proyek beserta alasannya (2026-09-25, feedback user). Dipanggil setelah
     * proyek tersimpan; $lama berisi nilai sebelum disimpan.
     */
    private function catatNegoBiaya(Request $request, Project $project, array $lama): void
    {
        $project->refresh();

        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');

        $ubah = [];
        if (round($lama['fee'], 2) !== round((float) $project->service_fee, 2)) {
            $ubah[] = 'Biaya jasa ' . $rp($lama['fee']) . ' menjadi ' . $rp($project->service_fee);
        }
        if (round($lama['transport'], 2) !== round((float) $project->transport_cost, 2)) {
            $ubah[] = 'Transport ' . $rp($lama['transport']) . ' menjadi ' . $rp($project->transport_cost);
        }

        if (! $ubah) {
            return;
        }

        \App\Helpers\AuditLogger::record(
            'proposal.fee_changed',
            implode('. ', $ubah) . ' pada proposal ' . $project->proposal_number,
            $project,
            trim((string) $request->input('fee_change_reason'))
        );
    }

    /** Nomor sementara untuk salinan; wajib diganti staf sebelum dipakai. */
    private function nomorSalinan(string $asal): string
    {
        $calon = 'SALINAN-' . now()->format('His') . '/' . $asal;

        return mb_substr($calon, 0, 191);
    }

    public function store(Request $request)
    {
        if ($request->has('psak_classification') && is_array($request->psak_classification)) {
            $request->merge([
                'psak_classification' => implode(', ', $request->psak_classification),
            ]);
        }

        $validated = $this->validateProposal($request);

        $project = Project::create([
            'proposal_number'          => $validated['proposal_number'],
            'proposal_date'            => $validated['proposal_date'],
            'request_basis'            => $validated['request_basis'] ?? null,
            'instructing_client_id'    => $validated['instructing_client_id'],
            // Nama Klien dipilih dari Database Klien (2026-09-14, feedback user).
            'client_id'                => $validated['client_id'] ?? null,
            'signed_by_user_id'        => $validated['signed_by_user_id'] ?? null,
            'approver_client_id'       => $validated['approver_client_id'] ?? null,
            'marketing_name'           => $this->resolveMarketingName($validated),
            'bank_id'                  => $validated['bank_id'] ?? null,
            'asset_type'               => $this->summarizeAssetTypes($validated['objects']),
            'asset_address'            => $this->summarizeAssetAddress($validated['objects']),
            'service_fee'              => $validated['service_fee'],
            // Nilai penawaran AWAL disimpan sekali saat proposal dibuat, lalu
            // tidak pernah ikut berubah walau dinego (2026-09-25).
            'initial_service_fee'      => $validated['service_fee'],
            'initial_transport_cost'   => $this->billableTransportInput($request, $validated),
            'fee_ppn_included'         => $request->boolean('fee_ppn_included'),
            'fee_breakdown'            => $request->boolean('fee_breakdown'),
            'transport_reimbursed'     => $request->boolean('transport_reimbursed'),
            'transport_cost'           => $this->billableTransportInput($request, $validated),
            'report_style'             => $validated['report_style'],
            'sla_draft_days'           => $validated['sla_draft_days'],
            'sla_final_days'           => $validated['sla_final_days'],
            'proposal_purpose'         => $validated['proposal_purpose'],
            'payment_scheme'           => $validated['payment_scheme'] ?? Project::PAYMENT_SCHEME_DP,
            'payment_terms'            => $this->parsePaymentTerms($validated['payment_terms'] ?? null),
            'psak_classification'      => $validated['psak_classification'] ?? null,
            'financial_reporting_date' => $validated['financial_reporting_date'] ?? null,
            'is_public_company'        => $request->boolean('is_public_company'),
            'status'                   => Project::STATUS_DRAFT,
        ] + $this->signatureData($request));

        $project->intendedUsers()->sync($validated['intended_user_ids']);
        $this->syncValuationObjects($project, $validated['objects']);

        \App\Helpers\AuditLogger::record(
            'proposal.created',
            "Membuat proposal {$project->proposal_number} ({$project->proposal_purpose}) untuk {$project->instructingClient->client_name}",
            $project
        );
        
        return redirect()
            ->route('proposals.show', $project)
            ->with('success', "Proposal {$project->proposal_number} berhasil dibuat dengan " . count($validated['objects']) . " objek penilaian.");
    }

    /**
     * Form edit proposal. DIBATASI hanya untuk status 'Draft Proposal' —
     * begitu proposal sudah diproses lebih lanjut (ada Invoice DP, dst),
     * mengedit data dasar (fee, objek, klien) bisa bikin invoice/PDF yang
     * sudah terlanjur dicetak jadi tidak sinkron dengan data terbaru.
     * Kalau ada kesalahan setelah tahap itu, harus dibatalkan (hapus
     * invoice-nya dulu lewat invoices.destroy) baru proyeknya bisa diedit.
     */
    public function edit(Project $project)
    {
        // Administrator tetap boleh memperbaiki proyek yang sudah selesai
        // (2026-09-23, feedback user); peran lain terkunci seperti biasa.
        if (($project->isDone() && ! auth()->user()->isAdministrator()) || $project->isCancelled()) {
            abort(403, 'Proposal tidak dapat diedit lagi setelah berstatus Selesai atau Batal. Minta Administrator bila perlu diperbaiki.');
        }

        $project->load('instructingClient', 'intendedUsers', 'valuationObjects', 'signedBy', 'approverClient', 'namedClient');

        // Daftar penandatangan = Penanggung Jawab aktif. Kalau proposal ini
        // sudah punya penandatangan yang kini tidak lagi memenuhi syarat
        // (mis. jabatannya berubah), tetap sertakan supaya nilainya tidak
        // hilang diam-diam saat form disimpan ulang.
        $signers = User::penanggungJawab()->orderBy('name')->get();
        if ($project->signedBy && ! $signers->contains('id', $project->signedBy->id)) {
            $signers->push($project->signedBy);
        }

        return view('proposals.edit', [
            'project' => $project,
            'signers' => $signers,
            'banks'   => $this->bankOptions(),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        // Administrator tetap boleh memperbaiki proyek yang sudah selesai
        // (2026-09-23, feedback user); peran lain terkunci seperti biasa.
        if (($project->isDone() && ! auth()->user()->isAdministrator()) || $project->isCancelled()) {
            abort(403, 'Proposal tidak dapat diedit lagi setelah berstatus Selesai atau Batal. Minta Administrator bila perlu diperbaiki.');
        }

        if ($request->has('psak_classification') && is_array($request->psak_classification)) {
            $request->merge([
                'psak_classification' => implode(', ', $request->psak_classification),
            ]);
        }

        $validated = $this->validateProposal($request, $project);

        $updateData = [
            'proposal_number'          => $validated['proposal_number'],
            'proposal_date'            => $validated['proposal_date'],
            'request_basis'            => $validated['request_basis'] ?? null,
            'instructing_client_id'    => $validated['instructing_client_id'],
            // Nama Klien dipilih dari Database Klien (2026-09-14). Teks lama
            // dipertahankan sampai diganti dengan pilihan klien.
            'client_id'                => $validated['client_id'] ?? null,
            'client_name'              => ! empty($validated['client_id']) ? null : $project->client_name,
            'signed_by_user_id'        => $validated['signed_by_user_id'] ?? null,
            'approver_client_id'       => $validated['approver_client_id'] ?? null,
            'marketing_name'           => $this->resolveMarketingName($validated),
            'bank_id'                  => $validated['bank_id'] ?? null,
            'asset_type'               => $this->summarizeAssetTypes($validated['objects']),
            'asset_address'            => $this->summarizeAssetAddress($validated['objects']),
            'service_fee'              => $validated['service_fee'],
            'fee_ppn_included'         => $request->boolean('fee_ppn_included'),
            'fee_breakdown'            => $request->boolean('fee_breakdown'),
            'transport_reimbursed'     => $request->boolean('transport_reimbursed'),
            'transport_cost'           => $this->billableTransportInput($request, $validated),
            'report_style'             => $validated['report_style'],
            'sla_draft_days'           => $validated['sla_draft_days'],
            'sla_final_days'           => $validated['sla_final_days'],
            'proposal_purpose'         => $validated['proposal_purpose'],
            'psak_classification'      => $validated['psak_classification'] ?? null,
            'financial_reporting_date' => $validated['financial_reporting_date'] ?? null,
            'is_public_company'        => $request->boolean('is_public_company'),
        ];

        $biayaLama = [
            'fee'       => (float) $project->service_fee,
            'transport' => (float) $project->transport_cost,
        ];

        // Alasan WAJIB bila biaya jasa atau transport berubah (2026-09-25,
        // feedback user) — supaya hasil nego dengan klien selalu ada
        // keterangannya di Riwayat Proyek.
        $biayaBerubah = round($biayaLama['fee'], 2) !== round((float) ($validated['service_fee'] ?? 0), 2)
            || round($biayaLama['transport'], 2) !== round((float) ($this->billableTransportInput($request, $validated) ?? 0), 2);

        if ($biayaBerubah) {
            $request->validate(
                ['fee_change_reason' => 'required|string|min:5|max:500'],
                [
                    'fee_change_reason.required' => 'Biaya berubah — tulis alasannya (mis. hasil nego dengan klien).',
                    'fee_change_reason.min'      => 'Alasan terlalu pendek, tulis minimal 5 karakter.',
                ]
            );
        }

        // Skema pembayaran hanya boleh diubah selagi Draft/Menunggu
        // Persetujuan (field-nya juga cuma dirender editable di blade
        // pada status itu) — di luar itu nilai lama dipertahankan supaya
        // tidak ada perubahan diam-diam lewat request yang dimanipulasi.
        if (in_array($project->status, [Project::STATUS_DRAFT, Project::STATUS_WAITING_APPROVAL], true)) {
            $updateData['payment_scheme'] = $validated['payment_scheme'] ?? Project::PAYMENT_SCHEME_DP;
        }
        // Termin boleh berubah lebih lama daripada skema — sampai proyek masuk
        // tahap pencetakan buku (2026-09-24, feedback user). Sesudah itu
        // angkanya sudah dipakai di dokumen, jadi request diabaikan.
        if (array_key_exists('payment_terms', $validated) && $project->canEditPaymentTerms($request->user())) {
            $updateData['payment_terms'] = $this->parsePaymentTerms($validated['payment_terms']);
        }

        $project->update($updateData + $this->signatureData($request, $project));

        $project->intendedUsers()->sync($validated['intended_user_ids']);

        // Nego biaya (2026-09-25, feedback user): kalau biaya jasa atau
        // transport berubah, alasannya wajib diisi dan dicatat di Riwayat
        // Proyek. Nilai penawaran awal tidak pernah ikut berubah.
        $this->catatNegoBiaya($request, $project, $biayaLama);

        // Cara paling aman untuk sinkronisasi objek saat edit: hapus semua
        // baris lama, buat ulang dari input form. Karena ini masih status
        // Draft (belum ada invoice/PDF resmi yang bergantung pada ID objek
        // lama), tidak ada risiko data anak yang jadi yatim.
        $project->valuationObjects()->delete();
        $this->syncValuationObjects($project, $validated['objects']);

        \App\Helpers\AuditLogger::record(
            'proposal.updated',
            "Mengubah data proposal {$project->proposal_number}",
            $project
        );
        
        return redirect()
            ->route('proposals.show', $project)
            ->with('success', "Proposal {$project->proposal_number} berhasil diperbarui.");
    }

    /**
     * Hapus proposal. GUARD KETAT: hanya boleh selama status Draft DAN
     * belum ada invoice sama sekali — supaya tidak ada jejak transaksi
     * finansial yang hilang tanpa sengaja. Relasi valuationObjects &
     * intendedUsers ikut terhapus otomatis lewat cascadeOnDelete di
     * migration, tidak perlu dihapus manual di sini.
     */
    public function destroy(Project $project)
    {
        // Proyek Batal juga boleh dihapus permanen (2026-09-14, feedback user) —
        // invoice-nya ikut terhapus (FK cascade), jadi dikonfirmasi di UI.
        $isCancelled = $project->status === Project::STATUS_BATAL;

        if ($project->status !== Project::STATUS_DRAFT && ! $isCancelled) {
            abort(403, 'Hanya proposal Draft atau proyek Batal yang dapat dihapus.');
        }

        if (! $isCancelled && $project->invoices()->exists()) {
            abort(403, 'Proposal ini sudah memiliki invoice dan tidak dapat dihapus. Batalkan invoice-nya terlebih dahulu jika diperlukan.');
        }

        $proposalNumber = $project->proposal_number;
        $invoiceCount   = $project->invoices()->count();
        \App\Helpers\AuditLogger::record(
            'proposal.deleted',
            $isCancelled
                ? "Menghapus permanen proyek batal {$proposalNumber} beserta {$invoiceCount} invoice"
                : "Menghapus proposal {$proposalNumber}",
            $project
        );
        $project->delete();

        return redirect()
            ->route('dashboard')
            ->with('success', "Proposal {$proposalNumber} berhasil dihapus.");
    }

    /**
     * Unduh proposal sebagai .docx (MASTER — bisa diedit staf untuk
     * penyesuaian SPM). Teks baku mengikuti config/proposal_clauses.php.
     */
    /** Surat Representasi (.docx) untuk dikirim ke klien bersama proposal. */
    public function exportRepresentatif(Project $project)
    {
        $doc = \App\Services\RepresentatifDocx::for($project);

        return response()
            ->download($doc->save(), $doc->fileName() . '.docx')
            ->deleteFileAfterSend(true);
    }

    public function exportWord(Project $project)
    {
        $builder = ProposalDocxBuilder::for($project);
        $docx    = $builder->save();

        return response()
            ->download($docx, $builder->safeName() . '.docx')
            ->deleteFileAfterSend(true);
    }

    /**
     * PDF proposal = hasil render LibreOffice atas .docx master (di atas),
     * sehingga tata letak Word == PDF. ?view=1 -> tampil di browser.
     */
    public function exportPdf(Project $project, Request $request)
    {
        $builder = ProposalDocxBuilder::for($project);
        $docx    = $builder->save();

        try {
            $pdf = DocxToPdf::convert($docx);
        } finally {
            @unlink($docx);
        }

        $name = $builder->safeName() . '.pdf';

        $response = $request->boolean('view')
            ? response()->file($pdf, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $name . '"',
            ])
            : response()->download($pdf, $name);

        return $response->deleteFileAfterSend(true);
    }

    /**
     * Draft -> Menunggu Persetujuan Klien (2026-09-15, feedback user): menandai
     * proposal sudah dikirim ke klien, supaya alur sebelum DP jelas.
     */
    public function markSentToClient(Project $project)
    {
        abort_unless($project->status === Project::STATUS_DRAFT, 403, 'Hanya proposal Draft yang bisa ditandai terkirim ke klien.');

        $project->update(['status' => Project::STATUS_WAITING_APPROVAL]);

        \App\Helpers\AuditLogger::record(
            'proposal.sent_to_client',
            "Mengirim proposal {$project->proposal_number} ke klien, menunggu persetujuan",
            $project
        );

        return back()->with('success', 'Proposal ditandai terkirim ke klien. Setelah klien setuju, terbitkan invoice DP.');
    }

    /**
     * Tandai proyek BATAL (Batch 7). Non-destruktif: seluruh data proyek,
     * objek, invoice, dan teks proposal tetap tersimpan — hanya status
     * yang berubah. Status terakhir disimpan supaya bisa "diaktifkan
     * kembali" ke tahap yang tepat. Edit proposal otomatis terkunci
     * (edit() sudah membatasi ke status Draft).
     */
    /**
     * Batalkan beberapa proyek sekaligus dari List Project (2026-09-24,
     * feedback user). Aturannya sama persis dengan cancel() satuan: status
     * lama disimpan supaya bisa diaktifkan lagi, dan tiap proyek dicatat
     * sendiri di Audit Log. Proyek yang sudah Batal dilewati.
     */
    public function cancelMany(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|max:100',
            'ids.*' => 'integer',
        ], ['ids.required' => 'Pilih dulu proyek yang mau dibatalkan.']);

        $proyek = Project::whereIn('id', $data['ids'])
            ->where('status', '!=', Project::STATUS_BATAL)
            ->get();

        foreach ($proyek as $satu) {
            $sebelum = $satu->status;

            $satu->update([
                'status_before_cancel' => $sebelum,
                'cancelled_at'         => now(),
                'status'               => Project::STATUS_BATAL,
            ]);

            \App\Helpers\AuditLogger::record(
                'proposal.cancelled',
                "Membatalkan proyek {$satu->proposal_number} (status sebelumnya: {$sebelum}). Dibatalkan massal dari List Project.",
                $satu
            );
        }

        $dilewati = count($data['ids']) - $proyek->count();

        return back()->with(
            $proyek->isEmpty() ? 'info' : 'success',
            $proyek->count() . ' proyek ditandai Batal'
                . ($dilewati > 0 ? ", {$dilewati} dilewati karena sudah Batal" : '')
                . '. Data tetap tersimpan dan bisa diaktifkan kembali.'
        );
    }

    public function cancel(Project $project)
    {
        if ($project->status === Project::STATUS_BATAL) {
            return back()->with('info', 'Proyek ini sudah berstatus Batal.');
        }

        $previous = $project->status;

        $project->update([
            'status_before_cancel' => $previous,
            'cancelled_at'         => now(),
            'status'               => Project::STATUS_BATAL,
        ]);

        \App\Helpers\AuditLogger::record(
            'proposal.cancelled',
            "Membatalkan proyek {$project->proposal_number} (status sebelumnya: {$previous}). Data tidak dihapus.",
            $project
        );

        return back()
            ->with('success', "Proyek {$project->proposal_number} ditandai Batal.")
            ->with('undo', ['url' => route('proposals.reactivate', $project), 'label' => 'Urungkan']);
    }

    /**
     * Aktifkan kembali proyek yang berstatus Batal — kembali ke status
     * terakhir sebelum dibatalkan (fallback: Draft Proposal).
     */
    public function reactivate(Project $project)
    {
        if ($project->status !== Project::STATUS_BATAL) {
            return back()->with('info', 'Proyek ini tidak sedang dibatalkan.');
        }

        $restored = $project->status_before_cancel ?: Project::STATUS_DRAFT;

        $project->update([
            'status'               => $restored,
            'status_before_cancel' => null,
            'cancelled_at'         => null,
        ]);

        \App\Helpers\AuditLogger::record(
            'proposal.reactivated',
            "Mengaktifkan kembali proyek {$project->proposal_number} ke status \"{$restored}\"",
            $project
        );

        return back()->with('success', "Proyek {$project->proposal_number} diaktifkan kembali ke status \"{$restored}\".");
    }

    /**
     * Nomor & Tanggal Faktur Pajak (Feature 6). Hanya Administrator + Admin
     * Keuangan (izin tax_invoice.manage). Bisa diisi/dikoreksi di status apa
     * pun — di UI dikunci setelah terisi & harus klik "Edit" untuk mengubah.
     */
    public function updateTaxInvoice(Request $request, Project $project)
    {
        $data = $request->validate([
            'tax_invoice_number' => 'nullable|string|max:255',
            'tax_invoice_date'   => 'nullable|date',
        ]);

        $project->update([
            'tax_invoice_number' => $data['tax_invoice_number'] ?: null,
            'tax_invoice_date'   => $data['tax_invoice_date'] ?: null,
        ]);

        \App\Helpers\AuditLogger::record(
            'proposal.tax_invoice_set',
            "Menyetel Faktur Pajak proposal {$project->proposal_number}: No. "
                . ($project->tax_invoice_number ?: '(kosong)')
                . ", Tgl " . ($project->tax_invoice_date?->format('d-m-Y') ?: '(kosong)'),
            $project
        );

        return back()->with('success', 'Nomor & Tanggal Faktur Pajak disimpan.');
    }

    /**
     * =========================================================================
     * EDITOR TEKS BAKU PROPOSAL PER-BAB (Batch 3)
     *
     * Menyimpan OVERRIDE per bab di proposal_section_texts. Bab tanpa baris
     * override otomatis pakai teks baku config/proposal_clauses.php. Tabel &
     * elemen struktural tiap bab tetap dibuat otomatis oleh ProposalDocxBuilder.
     *
     * Bisa diakses di STATUS APA PUN (tidak dikunci ke Draft) — revisi wording
     * proposal kadang masih diperlukan setelah tahap invoice.
     * =========================================================================
     */
    public function editTexts(Project $project)
    {
        $project->load('instructingClient', 'sectionTexts');

        return view('proposals.texts', [
            'project'  => $project,
            'sections' => ProposalDocxBuilder::for($project)->sectionsForEditor(),
        ]);
    }

    public function updateText(Request $request, Project $project, string $key)
    {
        $section = $this->editableSection($project, $key);

        $data = $request->validate([
            'body' => 'required|string|max:20000',
        ]);

        $project->sectionTexts()->updateOrCreate(
            ['section_key' => $key],
            ['body' => $data['body']],
        );

        \App\Helpers\AuditLogger::record(
            'proposal.text_edited',
            "Mengubah teks bab \"{$section['title']}\" pada proposal {$project->proposal_number}",
            $project
        );

        return redirect()
            ->route('proposals.texts', $project)
            ->with('success', "Teks bab \"{$section['title']}\" disimpan.")
            ->withFragment('bab-' . $key);
    }

    public function resetText(Project $project, string $key)
    {
        $section = $this->editableSection($project, $key);

        $deleted = $project->sectionTexts()->where('section_key', $key)->delete();

        if ($deleted) {
            \App\Helpers\AuditLogger::record(
                'proposal.text_reset',
                "Mengembalikan teks bab \"{$section['title']}\" ke baku pada proposal {$project->proposal_number}",
                $project
            );
        }

        return redirect()
            ->route('proposals.texts', $project)
            ->with('success', "Teks bab \"{$section['title']}\" dikembalikan ke teks baku.")
            ->withFragment('bab-' . $key);
    }

    public function resetAllTexts(Project $project)
    {
        $count = $project->sectionTexts()->count();
        $project->sectionTexts()->delete();

        if ($count) {
            \App\Helpers\AuditLogger::record(
                'proposal.text_reset_all',
                "Mengembalikan SEMUA teks bab ({$count}) ke baku pada proposal {$project->proposal_number}",
                $project
            );
        }

        return redirect()
            ->route('proposals.texts', $project)
            ->with('success', "Semua teks bab dikembalikan ke baku ({$count} bab).");
    }

    /**
     * Pastikan $key adalah bab yang memang bisa di-override untuk proposal
     * ini (mempertimbangkan bab kondisional per jenis proposal). Selain itu
     * kembalikan metadata bab (judul, dll) untuk audit log & flash message.
     */
    private function editableSection(Project $project, string $key): array
    {
        $section = collect(ProposalDocxBuilder::for($project)->sectionsForEditor())
            ->firstWhere('key', $key);

        abort_unless($section && $section['editable'], 404);

        return $section;
    }

    /**
     * Validasi bersama untuk store() & update() — supaya aturan validasi
     * tidak dobel-tulis dan berisiko berbeda antara create vs edit.
     */
    /**
     * Ubah isian "50,50" jadi array persen. Kosong = null (ikut default skema).
     */
    private function parsePaymentTerms(?string $value): ?array
    {
        $parts = array_filter(array_map('trim', explode(',', (string) $value)), fn ($v) => $v !== '');
        if (! $parts) {
            return null;
        }

        return array_values(array_map(fn ($v) => (float) str_replace(',', '.', $v), $parts));
    }

    /**
     * Kolom barcode tanda tangan & stempel dari form (2026-09-21). File
     * baru menggantikan file lama; memilih "Tidak" menghapus file lama.
     */
    private function signatureData(Request $request, ?Project $project = null): array
    {
        $useBarcode = $request->boolean('use_signature_barcode');
        $path       = $project?->signature_barcode;

        if (! $useBarcode || $request->hasFile('signature_barcode')) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            $path = $useBarcode ? $request->file('signature_barcode')->store('proposal-signatures', 'public') : null;
        }

        return [
            'use_signature_barcode' => $useBarcode,
            'signature_barcode'     => $path,
            'use_stamp'             => $request->boolean('use_stamp'),
            'representative_limited' => $request->boolean('representative_limited'),
        ];
    }

    private function validateProposal(Request $request, ?Project $project = null): array
    {
        return $request->validate([
            // Nomor proposal diinput MANUAL — sistem kantor pusat yang
            // menerbitkan nomor resmi, jadi tidak di-generate di sini.
            // Tetap wajib unik supaya tidak ada dua proyek bernomor sama.
            'proposal_number'          => [
                'required', 'string', 'max:255',
                Rule::unique('projects', 'proposal_number')->ignore($project?->id),
            ],
            // Tanggal proposal (kop dokumen "Jakarta, <tanggal>"). Boleh mundur
            // — proposal sering dibuat bertanggal beberapa hari lalu.
            'proposal_date'            => 'required|date',
            'request_basis'            => 'nullable|string|max:1000',
            'instructing_client_id'    => 'required|exists:clients,id',
            // Nama Klien (debitur/pemilik aset) — opsional, kosong = nama
            // Pemberi Tugas. Dipakai di baris "Hal" proposal.
            'client_name'              => 'nullable|string|max:255',
            'client_id'                => 'nullable|exists:clients,id',
            // Penandatangan proposal — opsional. Kosong = pakai penandatangan
            // baku config('kjpp.signatory'). Pilihan di form sudah dibatasi ke
            // user aktif berjabatan "Penanggung Jawab"; di sini cukup pastikan
            // user-nya ada (tidak memblokir edit lama bila jabatannya berubah).
            // WAJIB sejak 2026-09-15 (feedback user): pilihan "Penanggung Jawab
            // baku kantor" dihapus dari dropdown — data bakunya sudah dipindah
            // ke akun user-nya sendiri.
            'signed_by_user_id'        => 'required|exists:users,id',
            // Barcode tanda tangan & stempel (2026-09-21). Aturan file sama
            // dengan barcode Surat Tugas. Wajib diunggah bila memilih barcode
            // dan proposal belum punya barcode tersimpan.
            'use_signature_barcode'    => 'nullable|boolean',
            'use_stamp'                => 'nullable|boolean',
            'representative_limited'   => 'nullable|boolean',
            'signature_barcode'        => [
                ($request->boolean('use_signature_barcode') && ! $project?->signature_barcode) ? 'required' : 'nullable',
                'image', 'mimes:png,jpg,jpeg', 'max:100', 'dimensions:width=370,height=370',
            ],
            // Pihak yang menyetujui (blok tanda tangan kolom kanan) — dipilih
            // dari Database Klien, bisa bank atau PT tergantung kasus. Kosong =
            // pakai nama Pemberi Tugas.
            'approver_client_id'       => 'nullable|exists:clients,id',
            // Marketing pembawa proposal — opsional, dari daftar config.
            'marketing_name'           => ['nullable', Rule::in(config('kjpp.marketing_names', []))],
            // Pilihan "Lainnya" -> nama marketing diketik manual, dan itulah yang disimpan.
            'marketing_name_other'     => 'nullable|required_if:marketing_name,Lainnya|string|max:255',
            // Rekening bank untuk blok "Rekening Bank" & PDF Invoice. Kosong =
            // pakai bank ber-is_default (dropdown sudah membatasi pilihan).
            'bank_id'                  => 'nullable|exists:banks,id',
            'intended_user_ids'        => 'required|array|min:1',
            'intended_user_ids.*'      => 'exists:clients,id',
            'service_fee'              => 'required|numeric|min:0',
            // Biaya: nilai dasar (service_fee) + status PPN + mode tampil +
            // komponen transport (mode rincian). Turunan (total, PPN, dll)
            // dihitung di accessor Project.
            'fee_ppn_included'         => 'nullable|boolean',
            'fee_breakdown'            => 'nullable|boolean',
            'transport_cost'          => 'nullable|numeric|min:0',
            'transport_reimbursed'     => 'nullable|boolean',
            'report_style'             => 'required|in:Long Report,Short Report',
            // SLA diinput MANUAL dalam hari kerja — dua jangka waktu terpisah
            // sesuai dokumen resmi (Draft/Resume, lalu Final setelah disetujui).
            'sla_draft_days'           => 'required|integer|min:1|max:365',
            'sla_final_days'           => 'required|integer|min:1|max:365',
            'proposal_purpose'         => 'required|in:Jual Beli,Penjaminan Utang,Lelang,Pelaporan Keuangan',
            // Skema pembayaran hanya relevan/bisa diubah selagi Draft/
            // Menunggu Persetujuan (lihat blade create/edit) — kalau field
            // tidak dikirim (mis. edit setelah lewat tahap itu), diabaikan
            // di store()/update() dan nilai lama dipertahankan.
            'payment_scheme'           => 'nullable|in:' . implode(',', Project::PAYMENT_SCHEMES),
            // Termin: daftar persen dipisah koma, totalnya harus 100 (2026-09-19).
            'payment_terms'            => ['nullable', 'string', 'max:60', function ($attr, $value, $fail) {
                $parts = array_filter(array_map('trim', explode(',', (string) $value)), fn ($v) => $v !== '');
                if (! $parts) {
                    return;
                }
                foreach ($parts as $part) {
                    if (! is_numeric(str_replace(',', '.', $part)) || (float) str_replace(',', '.', $part) <= 0) {
                        $fail('Termin harus berupa angka persen dipisah koma, contoh 50,50.');
                        return;
                    }
                }
                if (round(array_sum(array_map(fn ($v) => (float) str_replace(',', '.', $v), $parts)), 2) !== 100.0) {
                    $fail('Jumlah seluruh termin harus 100%.');
                }
            }],
            'psak_classification'      => 'required_if:proposal_purpose,Pelaporan Keuangan|nullable|string|max:255',
            'financial_reporting_date' => 'required_if:proposal_purpose,Pelaporan Keuangan|nullable|date',
            'is_public_company'        => 'nullable|boolean',

            'objects'                          => 'required|array|min:1',
            // Daftar kategori disesuaikan 2026-09-15 (feedback user). Pakai
            // Rule::in, bukan string "in:a,b" — nama kategori baru mengandung koma.
            'objects.*.asset_category'         => ['required', Rule::in([
                'Real Properti - Tanah',
                'Real Properti - Tanah dan Bangunan',
                'Real Properti - Tanah, Bangunan dan Sarana Pelengkap',
                // Tambahan 2026-09-25 (feedback user), tetap dalam kelompok
                // Real Properti supaya urutannya tidak melompat.
                'Real Properti - Ruko',
                'Real Properti - Office Space',
                'Real Properti - Unit Apartemen',
                'Personal Properti - Mesin dan Peralatan',
                'Personal Properti - Kendaraan',
                'Personal Properti - Alat Berat',
                'Lainnya',
            ])],
            // Wajib diisi HANYA kalau kategori objek tersebut = "Lainnya".
            'objects.*.custom_category' => 'nullable|required_if:objects.*.asset_category,Lainnya|string|max:255',
            'objects.*.land_area'       => 'nullable|numeric|min:0',
            'objects.*.building_area'   => 'nullable|numeric|min:0',
            'objects.*.unit_quantity'   => 'nullable|integer|min:0',
            'objects.*.location'        => 'required|string',
            'objects.*.ownership_form'  => 'required|string|max:255',
            'objects.*.owner_name'      => 'required|string|max:255',
            'objects.*.notes'           => 'nullable|string',
        ], [
            'signature_barcode.required'   => 'Unggah file barcode tanda tangan, atau pilih "Tidak".',
            'signature_barcode.mimes'      => 'Barcode harus berformat PNG atau JPG/JPEG.',
            'signature_barcode.max'        => 'Ukuran file barcode maksimal 100 KB.',
            'signature_barcode.dimensions' => 'Dimensi gambar barcode harus tepat 370x370 piksel.',
        ]);
    }

    private function syncValuationObjects(Project $project, array $objects): void
    {
        foreach ($objects as $index => $objectData) {
            ProjectValuationObject::create([
                'project_id'      => $project->id,
                'sort_order'      => $index + 1,
                'asset_category'  => $objectData['asset_category'],
                'custom_category' => $objectData['asset_category'] === 'Lainnya'
                    ? ($objectData['custom_category'] ?? null)
                    : null,
                'land_area'       => $objectData['land_area'] ?? null,
                'building_area'   => $objectData['building_area'] ?? null,
                'unit_quantity'   => $objectData['unit_quantity'] ?? null,
                'location'        => $objectData['location'],
                'ownership_form'  => $objectData['ownership_form'],
                'owner_name'      => $objectData['owner_name'],
                'notes'           => $objectData['notes'] ?? null,
            ]);
        }
    }

    private function summarizeAssetTypes(array $objects): string
    {
        $labels = collect($objects)
            ->pluck('asset_category')
            ->map(fn ($cat) => str_replace(['Real Properti - ', 'Personal Properti - '], '', $cat))
            ->unique()
            ->implode(', ');

        return $labels ?: 'Lainnya';
    }

    private function summarizeAssetAddress(array $objects): string
    {
        return $objects[0]['location'] ?? '-';
    }
}
