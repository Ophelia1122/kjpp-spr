<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * SPJ Surveyor (2026-09-19, feedback user) — rekap proyek yang tanggal
 * surveinya jatuh pada rentang tertentu, dikelompokkan per penilai lapangan,
 * lengkap dengan objek yang disurvei. Dipakai General Admin untuk membuat
 * surat pertanggungjawaban bulanan.
 *
 * Satu proyek bisa punya beberapa penilai; proyek itu muncul di setiap
 * penilainya, dan seluruh objek proyek dihitung untuk masing-masing
 * (pembagian objek per penilai belum dicatat — keputusan user 2026-09-19).
 */
class SurveyReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'from'      => 'nullable|date',
            'to'        => 'nullable|date',
            'appraiser' => 'nullable|exists:users,id',
        ]);

        $from = $request->filled('from') ? $request->date('from') : now()->startOfMonth();
        $to   = $request->filled('to') ? $request->date('to') : now()->endOfMonth();

        // Rentang bawaan = bulan berjalan, jadi biasanya pendek. Paginasi
        // dipasang sebagai jaga-jaga bila staf memilih rentang panjang
        // (2026-09-20, feedback user).
        $paginator = Project::query()
            ->with(['appraisers', 'assignedAppraiser', 'valuationObjects', 'instructingClient', 'namedClient'])
            ->where('status', '!=', Project::STATUS_BATAL)
            ->whereNotNull('survey_date')
            ->whereBetween('survey_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('survey_date')
            ->paginate(50)
            ->withQueryString();

        $projects = $paginator->getCollection();

        // Kelompokkan per penilai. Proyek tanpa relasi appraisers (data lama)
        // memakai kolom ringkasan assigned_appraiser_id.
        $groups = [];
        foreach ($projects as $project) {
            $people = $project->appraisers->isNotEmpty()
                ? $project->appraisers
                : collect([$project->assignedAppraiser])->filter();

            foreach ($people as $person) {
                if ($request->filled('appraiser') && (int) $request->appraiser !== $person->id) {
                    continue;
                }
                $groups[$person->id]['user'] = $person;
                $groups[$person->id]['projects'][] = $project;
            }
        }

        $rows = collect($groups)
            ->map(fn ($g) => [
                'user'     => $g['user'],
                'projects' => collect($g['projects']),
                'objects'  => collect($g['projects'])->sum(fn ($p) => $p->valuationObjects->count()),
            ])
            ->sortByDesc(fn ($g) => $g['projects']->count())
            ->values();

        return view('reports.spj-surveyor', [
            'paginator'        => $paginator,
            'from'             => $from,
            'to'               => $to,
            'rows'             => $rows,
            'totalProjects'    => $projects->count(),
            'totalObjects'     => $projects->sum(fn ($p) => $p->valuationObjects->count()),
            'appraiserOptions' => User::whereIn('id', \Illuminate\Support\Facades\DB::table('project_appraisers')->select('user_id'))
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
