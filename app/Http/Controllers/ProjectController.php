<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ProjectController extends Controller
{
    /**
     * Fitur ini HANYA boleh diakses jika status proyek sudah
     * 'In-Progress / Scheduled' (artinya Invoice DP sudah 'Paid').
     * Pengecekan status juga sebaiknya di-enforce di route middleware
     * atau di sini sebagai guard tambahan.
     */
    public function inputSurveyData(Request $request, Project $project)
    {
        if ($project->status !== Project::STATUS_IN_PROGRESS) {
            abort(403, 'Data penilai hanya bisa diisi setelah Invoice DP berstatus Paid.');
        }

        $validated = $request->validate([
            'assigned_appraiser' => 'required|string|max:255',
            'survey_date'        => 'required|date',
        ]);

        $project->update($validated);

        return back()->with('success', 'Data penilai lapangan & tanggal survei berhasil disimpan.');
    }

    /**
     * Export PDF "Surat Tugas Penilaian".
     * Hanya bisa diakses setelah assigned_appraiser & survey_date terisi
     * (artinya proyek sudah melewati tahap DP Paid).
     */
    public function exportSuratTugas(Project $project)
    {
        if (!$project->assigned_appraiser || !$project->survey_date) {
            abort(422, 'Surat Tugas belum bisa dicetak: penilai/tanggal survei belum diisi.');
        }

        $project->load('instructingClient');

        $pdf = Pdf::loadView('pdf.surat-tugas', [
            'project' => $project,
        ])->setPaper('a4', 'portrait');

        $safeFilename = str_replace(['/', '\\'], '-', $project->proposal_number);
        return $pdf->download("Surat-Tugas-{$safeFilename}.pdf");
    }

    /**
     * Dipanggil setelah draf laporan selesai dikerjakan penilai.
     * Memindahkan status ke 'Pelunasan' -> memicu InvoiceController
     * untuk generate Invoice Pelunasan.
     */
    public function markDraftCompleted(Project $project)
    {
        if ($project->status !== Project::STATUS_IN_PROGRESS) {
            abort(403, 'Proyek belum dalam tahap pengerjaan.');
        }

        $project->update(['status' => Project::STATUS_PELUNASAN]);

        return redirect()
            ->route('invoices.generateFinal', $project)
            ->with('success', 'Draf ditandai selesai. Invoice Pelunasan sedang dibuat.');
    }

    /**
     * Input Nomor Laporan Resmi. Terkunci sampai Invoice Pelunasan
     * berstatus Paid (yang otomatis mengubah status proyek jadi 'Selesai'
     * lewat InvoiceController@markAsPaid).
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

        return back()->with('success', 'Nomor Laporan Resmi berhasil disimpan.');
    }

    public function show(Project $project)
    {
        $project->load('instructingClient', 'intendedUsers', 'invoices');
        return view('proposals.show', compact('project'));
    }
}
