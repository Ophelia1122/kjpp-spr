<?php

namespace App\Http\Controllers;

use App\Exports\SpjSurveyorExport;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * SPJ Surveyor (2026-09-19, feedback user) — rekap proyek yang tanggal
 * surveinya jatuh pada rentang tertentu, dikelompokkan per penilai lapangan,
 * lengkap dengan objek yang disurvei. Dipakai General Admin untuk membuat
 * surat pertanggungjawaban bulanan.
 *
 * Satu proyek bisa punya beberapa penilai; proyek itu muncul di setiap
 * penilainya. Sejak 2026-09-25 objek dihitung sesuai penilai yang benar-benar
 * turun ke objek tersebut (lihat ProjectValuationObject::surveyors()); objek
 * tanpa pilihan khusus dihitung untuk seluruh penilai proyek. Satu objek yang
 * disurvei dua orang memberi SPJ penuh bagi keduanya.
 */
class SurveyReportController extends Controller
{
    /**
     * Unduh SPJ sesuai filter yang sedang aktif (2026-09-25, permintaan user).
     * Memakai data yang sama dengan halamannya, tanpa paginasi.
     */
    public function export(Request $request)
    {
        [$rows, $from, $to] = $this->kumpulkan($request, true);

        $nama = 'SPJ Surveyor ' . $from->format('Y-m-d') . ' sd ' . $to->format('Y-m-d') . '.xlsx';

        return Excel::download(new SpjSurveyorExport($rows, $from, $to), $nama);
    }

    /**
     * Susun data SPJ dari filter yang aktif. Dipakai halaman (dengan paginasi)
     * dan export Excel (tanpa paginasi) supaya isinya dijamin sama.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Carbon\Carbon, 2: \Carbon\Carbon, 3: mixed}
     */
    private function kumpulkan(Request $request, bool $untukExport = false): array
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
        $query = Project::query()
            ->with(['appraisers', 'assignedAppraiser', 'valuationObjects.appraisers', 'instructingClient', 'namedClient'])
            ->where('status', '!=', Project::STATUS_BATAL)
            ->whereNotNull('survey_date')
            ->whereBetween('survey_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('survey_date');

        // Export mengambil seluruh baris dalam rentang, dibatasi 2000 sebagai
        // pengaman bila rentangnya kelewat panjang.
        if ($untukExport) {
            $paginator = null;
            $projects  = $query->limit(2000)->get();
        } else {
            $paginator = $query->paginate(50)->withQueryString();
            $projects  = $paginator->getCollection();
        }

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
                // Objek yang benar-benar jadi tanggung jawab orang ini.
                $objekOrangIni = $project->valuationObjects
                    ->filter(fn ($obj) => $obj->surveyors()->contains('id', $person->id));

                if ($objekOrangIni->isEmpty()) {
                    continue;
                }

                $groups[$person->id]['user'] = $person;
                $groups[$person->id]['projects'][] = $project;
                $groups[$person->id]['objects'][$project->id] = $objekOrangIni;
            }
        }

        $rows = collect($groups)
            ->map(fn ($g) => [
                'user'       => $g['user'],
                'projects'   => collect($g['projects']),
                'objects'    => collect($g['objects'])->sum(fn ($objek) => $objek->count()),
                // Objek per proyek untuk orang ini — dipakai daftar di bawah kartu.
                'objectsPer' => collect($g['objects']),
            ])
            ->sortByDesc(fn ($g) => $g['projects']->count())
            ->values();

        return [$rows, $from, $to, $paginator, $projects];
    }

    public function index(Request $request)
    {
        [$rows, $from, $to, $paginator, $projects] = $this->kumpulkan($request);

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
