<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ProjectController extends Controller
{
    /**
     * Penilai lapangan, tanggal survei & Surat Tugas (2026-09-15, feedback user):
     * hanya selama In-Progress — DP di awal setelah DP dibayar, Bayar Nanti
     * setelah "Mulai Tanpa DP". Lihat Project::canPrepareFieldwork().
     */
    private function ensureFieldworkOpen(Project $project): void
    {
        abort_unless(
            $project->canPrepareFieldwork(),
            403,
            match (true) {
                in_array($project->status, [Project::STATUS_SELESAI, Project::STATUS_BATAL], true)
                    => 'Proyek sudah Selesai/Batal — data penilai & Surat Tugas tidak dapat diubah.',
                $project->isPaymentDeferred()
                    => 'Penilai lapangan & Surat Tugas baru bisa diisi setelah tombol "Mulai Tanpa DP" ditekan.',
                default
                    => 'Penilai lapangan & Surat Tugas baru bisa diisi setelah invoice DP ditandai Dibayar.',
            }
        );
    }

    /** Isi ATAU edit ulang Penilai Lapangan & Tanggal Survei. */
    public function inputSurveyData(Request $request, Project $project)
    {
        $this->ensureFieldworkOpen($project);

        // 1-5 penilai lapangan setara (2026-09-15, feedback user).
        $request->merge(['appraiser_ids' => array_values(array_filter((array) $request->input('appraiser_ids', [])))]);
        $validated = $request->validate([
            'appraiser_ids'   => 'required|array|min:1|max:' . Project::MAX_APPRAISERS,
            'appraiser_ids.*' => 'distinct|exists:users,id',
            'survey_date'     => 'required|date',
        ], [
            'appraiser_ids.required' => 'Pilih minimal satu penilai lapangan.',
            'appraiser_ids.max'      => 'Maksimal ' . Project::MAX_APPRAISERS . ' penilai lapangan.',
            'appraiser_ids.*.distinct' => 'Penilai lapangan tidak boleh dipilih dua kali.',
        ]);

        $ids        = array_map('intval', $validated['appraiser_ids']);
        $users      = \App\Models\User::whereIn('id', $ids)->get()->keyBy('id');
        $appraisers = collect($ids)->map(fn ($id) => $users[$id]);
        $names      = $appraisers->pluck('name')->implode(', ');

        \Illuminate\Support\Facades\DB::transaction(function () use ($project, $ids, $appraisers, $names, $validated) {
            $project->appraisers()->sync(
                collect($ids)->mapWithKeys(fn ($id, $i) => [$id => ['sort_order' => $i]])->all()
            );
            $project->update([
                'assigned_appraiser_id' => $appraisers->first()->id,
                'assigned_appraiser'    => $names, // ringkasan teks untuk tabel/export/PDF
                'survey_date'           => $validated['survey_date'],
            ]);
        });

        \App\Helpers\AuditLogger::record(
            'survey.input',
            "Menetapkan {$names} sebagai penilai lapangan untuk proyek {$project->proposal_number}, tanggal survei {$validated['survey_date']}",
            $project
        );

        return back()->with('success', 'Data penilai lapangan & tanggal survei berhasil disimpan.');
    }

    /**
     * Skema "Bayar Nanti" (2026-09-14, feedback user): sebagian klien baru
     * bayar di tengah/akhir pengerjaan, tanpa DP di muka. Normalnya kerja
     * lapangan baru bisa mulai (status In-Progress) setelah invoice
     * PERTAMA ditandai Lunas (lihat InvoiceController::markAsPaid()) —
     * tombol ini melompati syarat itu KHUSUS untuk proyek yang sejak awal
     * (masih Draft/Menunggu Persetujuan) sudah dipilih skema
     * PAYMENT_SCHEME_LATER. Invoicing sesudahnya tetap lewat jalur biasa
     * (InvoiceController::store, boleh 1x di akhir atau beberapa termin
     * di tengah jalan) — tidak ada perubahan pada logika itu.
     */
    public function startWorkWithoutDp(Project $project)
    {
        abort_unless(
            in_array($project->status, [Project::STATUS_DRAFT, Project::STATUS_WAITING_APPROVAL], true),
            403,
            'Mulai Pekerjaan Tanpa DP hanya bisa dilakukan selama proyek berstatus Draft/Menunggu Persetujuan.'
        );
        abort_unless($project->isPaymentDeferred(), 403, 'Proyek ini menggunakan skema DP di Awal, bukan Bayar Nanti.');

        $project->update(['status' => Project::STATUS_IN_PROGRESS]);

        \App\Helpers\AuditLogger::record(
            'project.started_without_dp',
            "Memulai pekerjaan lapangan proyek {$project->proposal_number} tanpa DP (skema Bayar Nanti)",
            $project
        );

        return back()->with('success', 'Pekerjaan lapangan dimulai tanpa DP. Terbitkan invoice kapan saja lewat kartu "Daftar Tagihan & Pembayaran".');
    }

    /**
     * =========================================================================
     * ALUR PRODUKSI LAPORAN (2026-09-15, feedback user) — satu pintu untuk
     * semua tombol perpindahan peran. Tahap (review_status):
     *   null             Surveyor survei & menilai      -> Submit Review Nilai
     *   submitted        Reviewer / Admin Produksi      -> Nilai Disetujui (SLA Final mulai) / kembalikan
     *   approved         Surveyor menyusun draft laporan -> Draft Laporan Sudah Dibuat
     *   draft_submitted  Admin Produksi                 -> Konfirmasi Draft / kembalikan
     *   draft_confirmed  Reviewer                       -> Draft Laporan Telah Direview / kembalikan
     *   draft_reviewed   Admin Produksi (proses cetak)  -> Buku Selesai Dicetak (proyek Selesai)
     * Definisi tiap langkah: Project::WORKFLOW_STEPS. Pembayaran tidak
     * memengaruhi alur ini — proyek bisa Selesai walau tagihan belum lunas.
     * =========================================================================
     */
    public function advanceWorkflow(Request $request, Project $project, string $step)
    {
        $def = Project::WORKFLOW_STEPS[$step] ?? abort(404);

        abort_unless(
            $project->status === Project::STATUS_IN_PROGRESS && $project->assigned_appraiser && $project->survey_date,
            403,
            'Alur produksi hanya berjalan untuk proyek In-Progress yang sudah memiliki penilai lapangan & tanggal survei.'
        );
        abort_unless($project->review_status === $def['from'], 403, 'Tahap proyek sudah berubah. Muat ulang halaman.');
        abort_unless(Project::userCanActAs(auth()->user(), $def['actor']), 403, 'Anda tidak memiliki akses untuk langkah ini.');

        // 'stay' (banding Draft Resume): catatan wajib tetapi bukan pengembalian —
        // tahap tidak berubah dan tidak memunculkan peringatan "dikembalikan".
        $stays    = ! empty($def['stay']);
        $isReturn = $def['note'] === 'required' && ! $stays;
        $note = $request->validate(
            ['note' => ($def['note'] === 'required' ? 'required' : 'nullable') . '|string|max:1000'],
            ['note.required' => $stays ? 'Catatan banding wajib diisi.' : 'Alasan pengembalian wajib diisi.']
        )['note'] ?? null;

        if ($step === 'mark_printed' && ! $project->final_report_number) {
            return back()->with('error', 'Isi Nomor Laporan Final terlebih dahulu sebelum mengonfirmasi buku selesai dicetak.');
        }

        $now = now();
        $uid = auth()->id();

        // Catatan pengembalian terakhir tampil sebagai peringatan sampai ada langkah maju.
        $changes = ['review_status' => $def['to']] + match (true) {
            $stays    => [],
            $isReturn => ['review_rejected_at' => $now, 'review_rejected_by_user_id' => $uid, 'review_rejection_note' => $note],
            default   => ['review_rejected_at' => null, 'review_rejected_by_user_id' => null, 'review_rejection_note' => null],
        };

        $changes += match ($step) {
            'submit_value'   => ['review_submitted_at' => $now, 'review_submitted_by_user_id' => $uid],
            'release_resume' => ['reviewed_at' => $now, 'reviewed_by_user_id' => $uid],
            'approve_value'  => ['review_approved_at' => $now, 'review_approved_by_user_id' => $uid],
            'submit_draft'  => ['draft_submitted_at' => $now],
            'confirm_draft' => ['draft_confirmed_at' => $now],
            'review_draft'  => ['draft_reviewed_at' => $now],
            'mark_printed'  => ['printed_at' => $now, 'status' => Project::STATUS_SELESAI],
            default         => [],
        };

        $project->update($changes);

        \App\Helpers\AuditLogger::record($def['action'], "{$def['desc']} — proyek {$project->proposal_number}", $project, $note);

        // Notifikasi WhatsApp dikirim setelah respons — tidak memperlambat tombol.
        $actor = auth()->user();
        if (in_array($step, ['submit_value', 'confirm_draft'], true)) {
            $title = $step === 'submit_value' ? 'Pengajuan Review Nilai' : 'Review Draft Laporan';
            dispatch(fn () => \App\Services\WhatsAppNotifier::reviewSubmitted($project, $actor, $note, $title))->afterResponse();
        } elseif ($isReturn) {
            $title = $def['title'];
            dispatch(fn () => \App\Services\WhatsAppNotifier::reviewReturned($project, $actor, (string) $note, $title))->afterResponse();
        }

        return back()->with($isReturn ? 'warning' : ($stays ? 'info' : 'success'), $def['flash']);
    }

    /**
     * Export PDF "Surat Tugas Penilaian".
     */
    public function exportSuratTugas(Project $project)
    {
        if (!$project->assigned_appraiser || !$project->survey_date) {
            abort(422, 'Surat Tugas belum bisa dicetak: penilai/tanggal survei belum diisi.');
        }

        $project->load('instructingClient', 'valuationObjects', 'assignmentStaff.user', 'signedBy');

        $pdf = Pdf::loadView('pdf.surat-tugas', [
            'project' => $project,
        ])->setPaper('a4', 'portrait');

        $safeFilename = str_replace(['/', '\\'], '-', $project->proposal_number);
        return $pdf->download("Surat-Tugas-{$safeFilename}.pdf");
    }

    /**
     * Unduh Surat Tugas sebagai .docx (2026-09-14, feedback user) — syarat
     * sama dengan PDF: penilai lapangan & tanggal survei sudah diisi.
     */
    public function exportSuratTugasWord(Project $project)
    {
        if (!$project->assigned_appraiser || !$project->survey_date) {
            abort(422, 'Surat Tugas belum bisa diunduh: penilai/tanggal survei belum diisi.');
        }

        $path = (new \App\Services\SuratTugasDocxBuilder($project))->save();

        $safeFilename = str_replace(['/', '\\'], '-', $project->proposal_number);
        return response()
            ->download($path, "Surat-Tugas-{$safeFilename}.docx")
            ->deleteFileAfterSend(true);
    }

    /**
     * Isi Nomor/Tanggal Surat Tugas (izin assignment_letter.manage —
     * Administrator & Admin Keuangan). Dikunci di UI begitu terisi (lihat
     * proposals/show.blade.php), tapi tetap boleh diedit ulang lewat
     * tombol "Edit" eksplisit — jadi validasi di sini tidak menolak
     * overwrite, hanya menyimpan apa adanya.
     *
     * BARCODE dikelola TERPISAH lewat uploadAssignmentLetterBarcode/
     * deleteAssignmentLetterBarcode di bawah (2026-09-12, feedback user:
     * upload harus otomatis tersimpan begitu file dipilih, tanpa tombol
     * Simpan terpisah/reload halaman) — makanya tidak lagi ada di sini.
     *
     * Daftar PETUGAS (siapa saja yang dicetak di tabel "Adapun petugas
     * kami") dikelola terpisah lewat addAssignmentStaff/removeAssignmentStaff
     * di bawah — jumlah & jabatannya bebas per proyek (mis. 2 Penilai + 1
     * Reviewer, atau 1 Reviewer + 1 Penilai + 1 Pelaksana Inspeksi), jadi
     * tidak cocok sebagai field tunggal di form ini.
     */
    public function updateAssignmentLetter(Request $request, Project $project)
    {
        $this->ensureFieldworkOpen($project);

        $validated = $request->validate([
            'assignment_letter_number' => 'nullable|string|max:255',
            'assignment_letter_date'   => 'nullable|date',
            // "Penilaian Aset atas nama" — hanya pihak terkait proyek ini (2026-09-14).
            'assignment_letter_recipient_client_id' => ['nullable', \Illuminate\Validation\Rule::in($project->receivedFromOptions()->pluck('id')->all())],
            'assignment_letter_on_behalf_client_id' => ['nullable', \Illuminate\Validation\Rule::in($project->receivedFromOptions()->pluck('id')->all())],
            'assignment_letter_request_basis'       => 'nullable|string|max:1000',
        ]);

        $project->update([
            'assignment_letter_number' => $validated['assignment_letter_number'] ?: null,
            'assignment_letter_date'   => $validated['assignment_letter_date'] ?: null,
            'assignment_letter_recipient_client_id' => $validated['assignment_letter_recipient_client_id'] ?? null,
            'assignment_letter_on_behalf_client_id' => $validated['assignment_letter_on_behalf_client_id'] ?? null,
            'assignment_letter_request_basis'       => trim((string) ($validated['assignment_letter_request_basis'] ?? '')) ?: null,
        ]);

        \App\Helpers\AuditLogger::record(
            'project.assignment_letter_set',
            "Menyetel Surat Tugas proyek {$project->proposal_number}: No. "
                . ($project->assignment_letter_number ?: '(kosong)')
                . ', Tgl ' . ($project->assignment_letter_date?->format('d-m-Y') ?: '(kosong)'),
            $project
        );

        return back()->with('success', 'Data Surat Tugas berhasil disimpan.');
    }

    /**
     * Upload/ganti barcode Surat Tugas — TERSIMPAN OTOMATIS begitu file
     * dipilih (lihat JS di proposals/show.blade.php: submit AJAX langsung
     * saat <input type=file> berubah, tanpa tombol Simpan/reload halaman).
     * File lama dihapus otomatis kalau diganti.
     */
    public function uploadAssignmentLetterBarcode(Request $request, Project $project)
    {
        $this->ensureFieldworkOpen($project);

        $request->validate([
            'assignment_letter_barcode' => [
                // PNG atau JPG/JPEG (2026-09-14, feedback user; sebelumnya PNG saja).
                // Maks. 100 KB (2026-09-14, feedback user; sebelumnya 5 KB).
                'required', 'image', 'mimes:png,jpg,jpeg', 'max:100', 'dimensions:width=370,height=370',
            ],
        ], [
            'assignment_letter_barcode.required'   => 'Pilih file barcode terlebih dahulu.',
            'assignment_letter_barcode.mimes'      => 'Barcode harus berformat PNG atau JPG/JPEG.',
            'assignment_letter_barcode.max'        => 'Ukuran file barcode maksimal 100 KB.',
            'assignment_letter_barcode.dimensions' => 'Dimensi gambar barcode harus tepat 370x370 piksel.',
        ]);

        if ($project->assignment_letter_barcode) {
            Storage::disk('public')->delete($project->assignment_letter_barcode);
        }
        $project->update([
            'assignment_letter_barcode' => $request->file('assignment_letter_barcode')->store('assignment-letters', 'public'),
        ]);

        \App\Helpers\AuditLogger::record(
            'project.assignment_letter_barcode_uploaded',
            "Mengunggah barcode Surat Tugas untuk proyek {$project->proposal_number}",
            $project
        );

        if ($request->ajax()) {
            return view('proposals._assignment_letter_barcode', compact('project'));
        }

        return back()->with('success', 'Barcode berhasil diunggah.');
    }

    /** Hapus barcode Surat Tugas (izin assignment_letter.manage). */
    public function deleteAssignmentLetterBarcode(Request $request, Project $project)
    {
        $this->ensureFieldworkOpen($project);

        if ($project->assignment_letter_barcode) {
            Storage::disk('public')->delete($project->assignment_letter_barcode);
            $project->update(['assignment_letter_barcode' => null]);

            \App\Helpers\AuditLogger::record(
                'project.assignment_letter_barcode_deleted',
                "Menghapus barcode Surat Tugas untuk proyek {$project->proposal_number}",
                $project
            );
        }

        if ($request->ajax()) {
            return view('proposals._assignment_letter_barcode', compact('project'));
        }

        return back()->with('success', 'Barcode dihapus.');
    }

    /**
     * Tambah 1 petugas ke daftar "Adapun petugas kami" (izin
     * assignment_letter.manage). Jabatan & No. MAPPI yang tercetak nanti
     * diambil langsung dari biodata user ini — tidak diinput ulang di sini.
     */
    public function addAssignmentStaff(Request $request, Project $project)
    {
        $this->ensureFieldworkOpen($project);

        $validated = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                \Illuminate\Validation\Rule::unique('project_assignment_staff')->where('project_id', $project->id),
            ],
        ], [
            'user_id.unique' => 'Orang ini sudah ada di daftar petugas.',
        ]);

        $project->assignmentStaff()->create([
            'user_id'    => $validated['user_id'],
            'sort_order' => $project->assignmentStaff()->max('sort_order') + 1,
        ]);

        $user = User::find($validated['user_id']);
        \App\Helpers\AuditLogger::record(
            'project.assignment_staff_added',
            "Menambahkan {$user->name} ke daftar petugas Surat Tugas proyek {$project->proposal_number}",
            $project
        );

        if ($request->ajax()) {
            return $this->assignmentStaffPartial($project);
        }

        return back()->with('success', 'Petugas ditambahkan.');
    }

    /**
     * Hapus 1 petugas dari daftar "Adapun petugas kami" (izin
     * assignment_letter.manage).
     */
    public function removeAssignmentStaff(Request $request, Project $project, \App\Models\ProjectAssignmentStaff $staff)
    {
        $this->ensureFieldworkOpen($project);

        abort_unless($staff->project_id === $project->id, 404);

        $name = $staff->user->name ?? '(user terhapus)';
        $staff->delete();

        \App\Helpers\AuditLogger::record(
            'project.assignment_staff_removed',
            "Menghapus {$name} dari daftar petugas Surat Tugas proyek {$project->proposal_number}",
            $project
        );

        if ($request->ajax()) {
            return $this->assignmentStaffPartial($project);
        }

        return back()->with('success', 'Petugas dihapus.');
    }

    /**
     * Fragmen HTML daftar petugas terkini — dipakai kedua aksi di atas
     * saat diminta lewat AJAX (lihat initAssignmentStaffAjax() di
     * proposals/show.blade.php), supaya tambah/hapus petugas tidak perlu
     * reload seluruh halaman (perceptibly lambat kalau harus tambah 3
     * orang satu-satu).
     */
    private function assignmentStaffPartial(Project $project)
    {
        $project->load('assignmentStaff.user');
        // role di-eager-load: daftar petugas difilter per role (lihat blade),
        // tanpa ini jadi 1 query role per user (N+1).
        $activeUsers = User::with('role')->where('is_active', true)->orderBy('name')->get();

        return view('proposals._assignment_staff', compact('project', 'activeUsers'));
    }

    /**
     * Input/edit Nomor Laporan Final, Tanggal Final & Keterangan. Boleh
     * diisi mulai draft laporan telah direview (tahap proses cetak) sampai
     * Selesai — wajib sebelum "Buku Selesai Dicetak". Pola
     * kunci/edit-nya sama seperti Faktur Pajak/Penilai Lapangan: sekali
     * final_report_number terisi, field terkunci di kartu — hanya bisa
     * diubah lagi lewat ikon Edit (bukan lewat guard status di sini).
     */
    public function inputFinalReportNumber(Request $request, Project $project)
    {
        abort_unless(
            $project->isFinalReportStage(),
            403,
            'Nomor Laporan Final bisa diisi setelah draft laporan telah direview.'
        );

        $validated = $request->validate([
            'final_report_number' => 'required|string|max:255',
            'final_report_date'   => 'nullable|date',
            'final_report_notes'  => 'nullable|string|max:2000',
        ]);

        $project->update([
            'final_report_number' => $validated['final_report_number'],
            'final_report_date'   => $validated['final_report_date'] ?: null,
            'final_report_notes'  => $validated['final_report_notes'] ?: null,
        ]);
        \App\Helpers\AuditLogger::record(
            'project.final_report_number_set',
            "Menginput Nomor Laporan Final \"{$validated['final_report_number']}\" untuk proyek {$project->proposal_number}"
                . (!empty($validated['final_report_date']) ? ", tanggal final {$validated['final_report_date']}" : ''),
            $project
        );
        return back()->with('success', 'Nomor Laporan Final berhasil disimpan.');
    }

        public function show(Project $project)
    {
        $project->load(
            'instructingClient', 'intendedUsers', 'invoices', 'valuationObjects', 'signedBy', 'bank',
            'reviewRejectedBy'
        )->loadCount('sectionTexts');

        // role di-eager-load: daftar petugas difilter per role (lihat blade),
        // tanpa ini jadi 1 query role per user (N+1, terukur 8 query ekstra).
        $activeUsers = User::with('role')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Riwayat alur proyek (2026-09-14, feedback user) — dari log aktivitas
        // proyek ini & invoice-nya, urut kronologis. Hanya langkah alur kerja /
        // perpindahan peran; aksi yang sifatnya edit (ubah proposal, ubah
        // invoice, surat tugas, barcode, personil, faktur, teks) tidak ditampilkan.
        $invoiceIds   = $project->invoices->pluck('id');
        $activityLogs = \App\Models\AuditLog::with('user')
            ->where(function ($q) use ($project, $invoiceIds) {
                $q->where(fn ($s) => $s->where('subject_type', Project::class)->where('subject_id', $project->id))
                  ->orWhere(fn ($s) => $s->where('subject_type', \App\Models\Invoice::class)->whereIn('subject_id', $invoiceIds));
            })
            ->whereNotIn('action', [
                'proposal.updated', 'proposal.text_edited', 'proposal.text_reset', 'proposal.text_reset_all',
                'proposal.tax_invoice_set', 'invoice.updated',
                'project.assignment_letter_set', 'project.assignment_letter_barcode_uploaded',
                'project.assignment_letter_barcode_deleted', 'project.assignment_staff_added', 'project.assignment_staff_removed',
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return view('proposals.show', compact('project', 'activeUsers', 'activityLogs'));
    }
}
