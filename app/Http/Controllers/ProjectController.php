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
     * Isi ATAU edit ulang Penilai Lapangan & Tanggal Survei (kartu
     * tersendiri di halaman proposal, sebelum kartu Surat Tugas — sama
     * seperti Faktur Pajak/Surat Tugas, bisa diisi/diedit di status
     * apa pun selama proyek belum Selesai/Batal). Dulu hanya bisa diisi
     * (SEKALI, tidak bisa diedit) selama status masih In-Progress persis —
     * sekarang dilonggarkan supaya kesalahan input bisa dikoreksi kapan
     * saja sebelum proyek benar-benar tuntas.
     */
    public function inputSurveyData(Request $request, Project $project)
    {
        if ($project->status === Project::STATUS_SELESAI || $project->isCancelled()) {
            abort(403, 'Data penilai lapangan & tanggal survei tidak dapat diubah lagi setelah proyek berstatus Selesai atau Batal.');
        }

        $validated = $request->validate([
            'assigned_appraiser_id' => 'required|exists:users,id',
            'survey_date'           => 'required|date',
        ]);

        $appraiser = \App\Models\User::findOrFail($validated['assigned_appraiser_id']);

        $project->update([
            'assigned_appraiser_id' => $appraiser->id,
            'assigned_appraiser'    => $appraiser->name, // denormalized untuk PDF Surat Tugas
            'survey_date'           => $validated['survey_date'],
        ]);

        \App\Helpers\AuditLogger::record(
            'survey.input',
            "Menetapkan {$appraiser->name} sebagai penilai lapangan untuk proyek {$project->proposal_number}, tanggal survei {$validated['survey_date']}",
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
     * Tandai draf laporan selesai -> status proyek maju ke 'Pelunasan'.
     * SENGAJA TIDAK LAGI otomatis menerbitkan invoice (dulu digabung ke
     * InvoiceController@generateFinal) — sekarang penerbitan invoice
     * sepenuhnya lewat InvoiceController@store yang fleksibel (staf bisa
     * menagih sisa tagihan kapan saja / berapa kali pun lewat kartu
     * "Buat Invoice" di halaman proyek).
     *
     * GUARD (2026-09-12, feedback user): dulu bisa ditekan kapan saja
     * selama status In-Progress, TANPA peduli apakah nilai hasil
     * penilaian sudah benar-benar disetujui lewat alur review SLA Final
     * (Surveyor -> Reviewer -> Admin Produksi). Sekarang WAJIB
     * isReviewApproved() dulu (Admin Produksi sudah konfirmasi) — DP
     * sudah Paid otomatis terpenuhi karena syarat masuk status
     * In-Progress itu sendiri.
     *
     * BUG FIX (2026-09-13, feedback user): kalau proyek KEBETULAN sudah
     * lunas 100% SEBELUM draf ditandai selesai (mis. klien bayar lunas
     * di muka — lihat catatan is_fully_paid di InvoiceController), status
     * dulu SELALU dilempar ke Pelunasan — padahal tidak akan pernah ada
     * pembayaran BARU lagi yang bisa memicu transisi Pelunasan->Selesai
     * (itu hanya terjadi di dalam markAsPaid()), jadi proyek TERJEBAK
     * selamanya di Pelunasan walau kartu tagihan sudah menunjukkan
     * "Lunas" dan tombol input Nomor Laporan Resmi tetap terkunci.
     * Sekarang: kalau sudah lunas penuh, langsung ke Selesai (tidak ada
     * lagi yang perlu ditagih), baru kalau belum lunas mampir ke
     * Pelunasan seperti biasa.
     */
    public function markDraftComplete(Project $project)
    {
        if ($project->status !== Project::STATUS_IN_PROGRESS) {
            abort(403, 'Draf hanya bisa ditandai selesai selama proyek berstatus In-Progress.');
        }
        if (!$project->isReviewApproved()) {
            abort(403, 'Draf hanya bisa ditandai selesai setelah hasil penilaian dikonfirmasi disetujui oleh Admin Produksi.');
        }

        $nextStatus = $project->is_fully_paid ? Project::STATUS_SELESAI : Project::STATUS_PELUNASAN;
        $project->update(['status' => $nextStatus]);

        \App\Helpers\AuditLogger::record(
            'project.draft_completed',
            "Menandai draf laporan selesai untuk proyek {$project->proposal_number}"
                . ($nextStatus === Project::STATUS_SELESAI ? ' (sudah lunas penuh, langsung Selesai)' : ''),
            $project
        );

        $message = $nextStatus === Project::STATUS_SELESAI
            ? 'Draf laporan ditandai selesai. Tagihan sudah lunas penuh — proyek langsung berstatus Selesai.'
            : 'Draf laporan ditandai selesai. Terbitkan invoice untuk sisa tagihan bila diperlukan.';

        return back()->with('success', $message);
    }

    /**
     * =========================================================================
     * ALUR REVIEW SLA FINAL — Surveyor "Submit untuk Review" -> Reviewer
     * (jabatan = Reviewer, LINTAS ROLE) setuju/kembalikan -> Admin Produksi
     * konfirmasi (memulai SLA Laporan Final) / kembalikan ke Reviewer.
     *
     * SENGAJA independen dari status proyek utama & dari tombol "Tandai
     * Draf Selesai (Buat Invoice Pelunasan)" — proses ini murni menentukan
     * kapan SLA Laporan Final mulai dihitung, tidak menahan invoicing.
     * =========================================================================
     */

    /** Tahap 1 (Surveyor/Admin Produksi, izin survey.manage): ajukan review. */
    public function submitForReview(Project $project)
    {
        if ($project->status !== Project::STATUS_IN_PROGRESS || ! $project->survey_date) {
            abort(403, 'Ajukan review hanya bisa dilakukan setelah tanggal survei diisi.');
        }
        if ($project->review_status !== null) {
            abort(403, 'Proyek ini sudah pernah diajukan untuk review.');
        }

        $project->update([
            'review_status'               => Project::REVIEW_SUBMITTED,
            'review_submitted_at'         => now(),
            'review_submitted_by_user_id' => auth()->id(),
        ]);

        \App\Helpers\AuditLogger::record(
            'review.submitted',
            "Mengajukan hasil pekerjaan proyek {$project->proposal_number} untuk direview",
            $project
        );

        return back()->with('success', 'Proyek diajukan untuk direview.');
    }

    /** Tahap 2 (Reviewer, jabatan): setujui hasil review, teruskan ke Admin Produksi. */
    public function approveReview(Project $project)
    {
        abort_unless(auth()->user()->canActAsReviewer(), 403, 'Hanya pengguna berjabatan Reviewer atau Administrator yang dapat melakukan aksi ini.');

        if ($project->review_status !== Project::REVIEW_SUBMITTED) {
            abort(403, 'Proyek ini tidak sedang menunggu review.');
        }

        $project->update([
            'review_status'        => Project::REVIEW_REVIEWED,
            'reviewed_at'          => now(),
            'reviewed_by_user_id'  => auth()->id(),
        ]);

        \App\Helpers\AuditLogger::record(
            'review.approved_by_reviewer',
            "Menyetujui hasil review proyek {$project->proposal_number}, diteruskan ke Admin Produksi",
            $project
        );

        return back()->with('success', 'Hasil pekerjaan disetujui, menunggu konfirmasi Admin Produksi.');
    }

    /** Tahap 2 (Reviewer, jabatan): kembalikan ke Surveyor untuk revisi. */
    public function rejectReviewToSurveyor(Request $request, Project $project)
    {
        abort_unless(auth()->user()->canActAsReviewer(), 403, 'Hanya pengguna berjabatan Reviewer atau Administrator yang dapat melakukan aksi ini.');

        if ($project->review_status !== Project::REVIEW_SUBMITTED) {
            abort(403, 'Proyek ini tidak sedang menunggu review.');
        }

        $validated = $request->validate(['reason' => 'required|string|max:1000']);

        $project->update([
            'review_status'               => null,
            'review_rejected_at'          => now(),
            'review_rejected_by_user_id'  => auth()->id(),
            'review_rejection_note'       => $validated['reason'],
        ]);

        \App\Helpers\AuditLogger::record(
            'review.rejected_to_surveyor',
            "Mengembalikan proyek {$project->proposal_number} ke Surveyor untuk revisi. Alasan: {$validated['reason']}",
            $project
        );

        return back()->with('warning', 'Proyek dikembalikan ke Surveyor untuk revisi.');
    }

    /** Tahap 3 (Admin Produksi, izin proposals.manage): konfirmasi -> mulai SLA Final. */
    public function confirmReviewApproval(Project $project)
    {
        if ($project->review_status !== Project::REVIEW_REVIEWED) {
            abort(403, 'Proyek ini belum disetujui Reviewer.');
        }

        $project->update([
            'review_status'               => Project::REVIEW_APPROVED,
            'review_approved_at'          => now(),
            'review_approved_by_user_id'  => auth()->id(),
            // Jejak penolakan lama sudah tidak relevan setelah disetujui penuh.
            'review_rejected_at'          => null,
            'review_rejected_by_user_id'  => null,
            'review_rejection_note'       => null,
        ]);

        \App\Helpers\AuditLogger::record(
            'review.confirmed',
            "Mengonfirmasi persetujuan pekerjaan proyek {$project->proposal_number}. SLA Laporan Final mulai dihitung.",
            $project
        );

        return back()->with('success', 'Pekerjaan dikonfirmasi disetujui. SLA Laporan Final mulai dihitung.');
    }

    /** Tahap 3 (Admin Produksi, izin proposals.manage): kembalikan ke Reviewer. */
    public function rejectReviewToReviewer(Request $request, Project $project)
    {
        if ($project->review_status !== Project::REVIEW_REVIEWED) {
            abort(403, 'Proyek ini belum disetujui Reviewer.');
        }

        $validated = $request->validate(['reason' => 'required|string|max:1000']);

        $project->update([
            'review_status'               => Project::REVIEW_SUBMITTED,
            'review_rejected_at'          => now(),
            'review_rejected_by_user_id'  => auth()->id(),
            'review_rejection_note'       => $validated['reason'],
            // Perlu direview ulang -- batalkan persetujuan Reviewer sebelumnya.
            'reviewed_at'                 => null,
            'reviewed_by_user_id'         => null,
        ]);

        \App\Helpers\AuditLogger::record(
            'review.rejected_to_reviewer',
            "Mengembalikan proyek {$project->proposal_number} ke Reviewer untuk ditinjau ulang. Alasan: {$validated['reason']}",
            $project
        );

        return back()->with('warning', 'Proyek dikembalikan ke Reviewer untuk ditinjau ulang.');
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
        $validated = $request->validate([
            'assignment_letter_number' => 'nullable|string|max:255',
            'assignment_letter_date'   => 'nullable|date',
        ]);

        $project->update([
            'assignment_letter_number' => $validated['assignment_letter_number'] ?: null,
            'assignment_letter_date'   => $validated['assignment_letter_date'] ?: null,
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
        $request->validate([
            'assignment_letter_barcode' => [
                'required', 'image', 'mimes:png', 'max:5', 'dimensions:width=370,height=370',
            ],
        ], [
            'assignment_letter_barcode.required'   => 'Pilih file barcode terlebih dahulu.',
            'assignment_letter_barcode.mimes'      => 'Barcode harus berformat PNG.',
            'assignment_letter_barcode.max'        => 'Ukuran file barcode maksimal 5 KB.',
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
     * Input/edit Nomor Laporan Resmi, Tanggal Final & Keterangan. Boleh
     * diisi mulai status Pelunasan (2026-09-14, feedback user: kadang
     * nomor laporan sudah bisa diambil sebelum tagihan lunas penuh —
     * status Pelunasan sendiri sudah menjamin draf/pekerjaan disetujui,
     * lihat ProjectController::markDraftComplete()) sampai Selesai. Pola
     * kunci/edit-nya sama seperti Faktur Pajak/Penilai Lapangan: sekali
     * final_report_number terisi, field terkunci di kartu — hanya bisa
     * diubah lagi lewat ikon Edit (bukan lewat guard status di sini).
     */
    public function inputFinalReportNumber(Request $request, Project $project)
    {
        abort_unless(
            in_array($project->status, [Project::STATUS_PELUNASAN, Project::STATUS_SELESAI], true),
            403,
            'Nomor Laporan Resmi hanya bisa diisi mulai status Pelunasan (draf sudah disetujui).'
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
            "Menginput Nomor Laporan Resmi \"{$validated['final_report_number']}\" untuk proyek {$project->proposal_number}"
                . (!empty($validated['final_report_date']) ? ", tanggal final {$validated['final_report_date']}" : ''),
            $project
        );
        return back()->with('success', 'Nomor Laporan Resmi berhasil disimpan.');
    }

        public function show(Project $project)
    {
        $project->load(
            'instructingClient', 'intendedUsers', 'invoices', 'valuationObjects', 'signedBy', 'bank',
            'reviewSubmittedBy', 'reviewedBy', 'reviewApprovedBy', 'reviewRejectedBy'
        )->loadCount('sectionTexts');

        // role di-eager-load: daftar petugas difilter per role (lihat blade),
        // tanpa ini jadi 1 query role per user (N+1, terukur 8 query ekstra).
        $activeUsers = User::with('role')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('proposals.show', compact('project', 'activeUsers'));
    }
}
