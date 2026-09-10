<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectValuationObject;
use App\Models\User;
use App\Services\DocxToPdf;
use App\Services\ProposalDocxBuilder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProposalController extends Controller
{
    public function create()
    {
        return view('proposals.create', [
            'signers' => User::penanggungJawab()->orderBy('name')->get(),
        ]);
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
            'request_basis'            => $validated['request_basis'] ?? null,
            'instructing_client_id'    => $validated['instructing_client_id'],
            'signed_by_user_id'        => $validated['signed_by_user_id'] ?? null,
            'approver_name'            => $validated['approver_name'] ?? null,
            'asset_type'               => $this->summarizeAssetTypes($validated['objects']),
            'asset_address'            => $this->summarizeAssetAddress($validated['objects']),
            'service_fee'              => $validated['service_fee'],
            'fee_ppn_included'         => $request->boolean('fee_ppn_included'),
            'fee_breakdown'            => $request->boolean('fee_breakdown'),
            'transport_cost'          => $request->boolean('fee_breakdown') ? ($validated['transport_cost'] ?? 0) : null,
            'report_style'             => $validated['report_style'],
            'sla_draft_days'           => $validated['sla_draft_days'],
            'sla_final_days'           => $validated['sla_final_days'],
            'proposal_purpose'         => $validated['proposal_purpose'],
            'psak_classification'      => $validated['psak_classification'] ?? null,
            'financial_reporting_date' => $validated['financial_reporting_date'] ?? null,
            'is_public_company'        => $request->boolean('is_public_company'),
            'status'                   => Project::STATUS_DRAFT,
        ]);

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
        if ($project->status !== Project::STATUS_DRAFT) {
            abort(403, 'Proposal hanya bisa diedit selama masih berstatus Draft Proposal.');
        }

        $project->load('instructingClient', 'intendedUsers', 'valuationObjects', 'signedBy');

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
        ]);
    }

    public function update(Request $request, Project $project)
    {
        if ($project->status !== Project::STATUS_DRAFT) {
            abort(403, 'Proposal hanya bisa diedit selama masih berstatus Draft Proposal.');
        }

        if ($request->has('psak_classification') && is_array($request->psak_classification)) {
            $request->merge([
                'psak_classification' => implode(', ', $request->psak_classification),
            ]);
        }

        $validated = $this->validateProposal($request, $project);

        $project->update([
            'proposal_number'          => $validated['proposal_number'],
            'request_basis'            => $validated['request_basis'] ?? null,
            'instructing_client_id'    => $validated['instructing_client_id'],
            'signed_by_user_id'        => $validated['signed_by_user_id'] ?? null,
            'approver_name'            => $validated['approver_name'] ?? null,
            'asset_type'               => $this->summarizeAssetTypes($validated['objects']),
            'asset_address'            => $this->summarizeAssetAddress($validated['objects']),
            'service_fee'              => $validated['service_fee'],
            'fee_ppn_included'         => $request->boolean('fee_ppn_included'),
            'fee_breakdown'            => $request->boolean('fee_breakdown'),
            'transport_cost'          => $request->boolean('fee_breakdown') ? ($validated['transport_cost'] ?? 0) : null,
            'report_style'             => $validated['report_style'],
            'sla_draft_days'           => $validated['sla_draft_days'],
            'sla_final_days'           => $validated['sla_final_days'],
            'proposal_purpose'         => $validated['proposal_purpose'],
            'psak_classification'      => $validated['psak_classification'] ?? null,
            'financial_reporting_date' => $validated['financial_reporting_date'] ?? null,
            'is_public_company'        => $request->boolean('is_public_company'),
        ]);

        $project->intendedUsers()->sync($validated['intended_user_ids']);

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
        if ($project->status !== Project::STATUS_DRAFT) {
            abort(403, 'Hanya proposal berstatus Draft yang dapat dihapus.');
        }

        if ($project->invoices()->exists()) {
            abort(403, 'Proposal ini sudah memiliki invoice dan tidak dapat dihapus. Batalkan invoice-nya terlebih dahulu jika diperlukan.');
        }

        $proposalNumber = $project->proposal_number;
        \App\Helpers\AuditLogger::record('proposal.deleted', "Menghapus proposal {$proposalNumber}", $project);
        $project->delete();

        return redirect()
            ->route('dashboard')
            ->with('success', "Proposal {$proposalNumber} berhasil dihapus.");
    }

    /**
     * Unduh proposal sebagai .docx (MASTER — bisa diedit staf untuk
     * penyesuaian SPM). Teks baku mengikuti config/proposal_clauses.php.
     */
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

    public function markApproved(Project $project)
    {
        $project->update(['status' => Project::STATUS_WAITING_APPROVAL]);

        return back()->with('success', 'Proposal ditandai disetujui klien. Silakan buat Invoice DP.');
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
            'request_basis'            => 'nullable|string|max:1000',
            'instructing_client_id'    => 'required|exists:clients,id',
            // Penandatangan proposal — opsional. Kosong = pakai penandatangan
            // baku config('kjpp.signatory'). Pilihan di form sudah dibatasi ke
            // user aktif berjabatan "Penanggung Jawab"; di sini cukup pastikan
            // user-nya ada (tidak memblokir edit lama bila jabatannya berubah).
            'signed_by_user_id'        => 'nullable|exists:users,id',
            // Pihak yang menyetujui (blok tanda tangan kolom kanan) — bisa
            // bank atau klien (PT), tergantung kasus. Kosong = pakai nama
            // Pemberi Tugas.
            'approver_name'            => 'nullable|string|max:255',
            'intended_user_ids'        => 'required|array|min:1',
            'intended_user_ids.*'      => 'exists:clients,id',
            'service_fee'              => 'required|numeric|min:0',
            // Biaya: nilai dasar (service_fee) + status PPN + mode tampil +
            // komponen transport (mode rincian). Turunan (total, PPN, dll)
            // dihitung di accessor Project.
            'fee_ppn_included'         => 'nullable|boolean',
            'fee_breakdown'            => 'nullable|boolean',
            'transport_cost'          => 'nullable|numeric|min:0',
            'report_style'             => 'required|in:Long Report,Short Report',
            // SLA diinput MANUAL dalam hari kerja — dua jangka waktu terpisah
            // sesuai dokumen resmi (Draft/Resume, lalu Final setelah disetujui).
            'sla_draft_days'           => 'required|integer|min:1|max:365',
            'sla_final_days'           => 'required|integer|min:1|max:365',
            'proposal_purpose'         => 'required|in:Jual Beli,Penjaminan Utang,Lelang,Pelaporan Keuangan',
            'psak_classification'      => 'required_if:proposal_purpose,Pelaporan Keuangan|nullable|string|max:255',
            'financial_reporting_date' => 'required_if:proposal_purpose,Pelaporan Keuangan|nullable|date',
            'is_public_company'        => 'nullable|boolean',

            'objects'                          => 'required|array|min:1',
            'objects.*.asset_category'         => 'required|in:'
                . 'Real Properti - Tanah,'
                . 'Real Properti - Bangunan,'
                . 'Real Properti - Tanah dan Bangunan,'
                . 'Personal Properti - Mesin dan Peralatan,'
                . 'Personal Properti - Kendaraan,'
                . 'Personal Properti - Alat Berat,'
                . 'Bisnis / Perusahaan,'
                . 'Lainnya',
            // Wajib diisi HANYA kalau kategori objek tersebut = "Lainnya".
            'objects.*.custom_category' => 'nullable|required_if:objects.*.asset_category,Lainnya|string|max:255',
            'objects.*.land_area'       => 'nullable|numeric|min:0',
            'objects.*.building_area'   => 'nullable|numeric|min:0',
            'objects.*.unit_quantity'   => 'nullable|integer|min:0',
            'objects.*.location'        => 'required|string',
            'objects.*.ownership_form'  => 'required|string|max:255',
            'objects.*.owner_name'      => 'required|string|max:255',
            'objects.*.notes'           => 'nullable|string',
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
