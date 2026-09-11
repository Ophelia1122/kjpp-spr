<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ProjectController extends Controller
{
    /**
     * Fitur ini HANYA boleh diakses jika status proyek sudah
     * 'In-Progress / Scheduled' (artinya Invoice DP sudah 'Paid').
     */
    public function inputSurveyData(Request $request, Project $project)
    {
        if ($project->status !== Project::STATUS_IN_PROGRESS) {
            abort(403, 'Data penilai hanya bisa diisi setelah Invoice DP berstatus Paid.');
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
     * Tandai draf laporan selesai -> status proyek maju ke 'Pelunasan'.
     * SENGAJA TIDAK LAGI otomatis menerbitkan invoice (dulu digabung ke
     * InvoiceController@generateFinal) — sekarang penerbitan invoice
     * sepenuhnya lewat InvoiceController@store yang fleksibel (staf bisa
     * menagih sisa tagihan kapan saja / berapa kali pun lewat kartu
     * "Buat Invoice" di halaman proyek).
     */
    public function markDraftComplete(Project $project)
    {
        if ($project->status !== Project::STATUS_IN_PROGRESS) {
            abort(403, 'Draf hanya bisa ditandai selesai selama proyek berstatus In-Progress.');
        }

        $project->update(['status' => Project::STATUS_PELUNASAN]);

        \App\Helpers\AuditLogger::record(
            'project.draft_completed',
            "Menandai draf laporan selesai untuk proyek {$project->proposal_number}",
            $project
        );

        return back()->with('success', 'Draf laporan ditandai selesai. Terbitkan invoice untuk sisa tagihan bila diperlukan.');
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
        abort_unless(auth()->user()->isReviewer(), 403, 'Hanya pengguna berjabatan Reviewer yang dapat melakukan aksi ini.');

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
        abort_unless(auth()->user()->isReviewer(), 403, 'Hanya pengguna berjabatan Reviewer yang dapat melakukan aksi ini.');

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

        $project->load('instructingClient', 'valuationObjects');

        $pdf = Pdf::loadView('pdf.surat-tugas', [
            'project' => $project,
        ])->setPaper('a4', 'portrait');

        $safeFilename = str_replace(['/', '\\'], '-', $project->proposal_number);
        return $pdf->download("Surat-Tugas-{$safeFilename}.pdf");
    }

    /**
     * Input Nomor Laporan Resmi. Terkunci sampai Invoice Pelunasan
     * berstatus Paid (yang otomatis mengubah status proyek jadi 'Selesai'
     * lewat InvoiceController@markAsPaid).
     *
     * NOTE: Method markDraftCompleted() yang dulu ada di sini SUDAH
     * DIHAPUS — logikanya (validasi status + transisi ke 'Pelunasan')
     * sekarang digabung langsung ke InvoiceController@generateFinal,
     * karena sebelumnya method ini melakukan redirect() ke route POST
     * yang menyebabkan 405 error (redirect selalu jadi GET request).
     */
    public function inputFinalReportNumber(Request $request, Project $project)
    {
        if ($project->status !== Project::STATUS_SELESAI) {
            abort(403, 'Nomor Laporan Resmi hanya bisa diisi setelah Invoice Pelunasan berstatus Paid.');
        }

        $validated = $request->validate([
            'final_report_number' => 'required|string|max:255',
        ]);

        $project->update($validated);
        \App\Helpers\AuditLogger::record(
            'project.final_report_number_set',
            "Menginput Nomor Laporan Resmi \"{$validated['final_report_number']}\" untuk proyek {$project->proposal_number}",
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

        $activeUsers = User::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('proposals.show', compact('project', 'activeUsers'));
    }
}
