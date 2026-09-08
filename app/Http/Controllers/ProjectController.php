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
        $project->load('instructingClient', 'intendedUsers', 'invoices', 'valuationObjects');

        $activeUsers = User::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('proposals.show', compact('project', 'activeUsers'));
    }
}
