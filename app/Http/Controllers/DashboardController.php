<?php

namespace App\Http\Controllers;

use App\Exports\ProjectsExport;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    /**
     * BERANDA — dashboard operasional untuk SEMUA role (termasuk Surveyor).
     * Isinya angka ringkas + daftar "butuh perhatian hari ini", TANPA data
     * nilai kontrak (itu ada di overview() yang butuh izin dashboard.overview).
     *
     * SELALU DIPERSEMPIT ke proyek yang DITUGASKAN kepada user yang login
     * (assigned_appraiser_id), jadi ini murni "pekerjaan saya" — proyek yang
     * bukan garapannya tidak muncul. Pandangan menyeluruh ada di Dashboard
     * Project & Timeline (punya tombol "Semua Proyek").
     *
     * Kartu "Invoice belum lunas" & item tagihan di daftar hanya muncul bagi
     * user yang punya izin invoices.view (Surveyor tidak).
     */
    public function home()
    {
        $user           = auth()->user();
        // Jabatan Reviewer tidak perlu kartu & daftar "Invoice belum lunas"
        // (2026-09-15, feedback user) — pekerjaannya review, bukan penagihan,
        // walaupun role akunnya (mis. Administrator) punya izin invoices.view.
        $canSeeInvoices = $user->hasPermission('invoices.view')
            && $user->jabatan !== User::JABATAN_REVIEWER;
        $mineId         = auth()->id();

        // Administrator, Admin Produksi, General Admin & jabatan Admin melihat
        // angka SELURUH kantor (2026-09-13, feedback user). Jabatan lapangan
        // (Penilai, Pelaksana Inspeksi) & Reviewer tetap hanya tugasnya sendiri,
        // walaupun role akunnya Administrator.
        $officeWide = $user->seesOfficeWide();
        $scope = fn ($q) => $officeWide ? $q : $q->where('assigned_appraiser_id', $mineId);

        // Kartu "Proyek Selesai" — khusus jabatan Penilai/Pelaksana Inspeksi
        // (dihitung dari assigned_appraiser_id, proyek yg jadi tanggung
        // jawab lapangannya) dan Reviewer (dihitung dari reviewed_by_user_id,
        // proyek yg pernah dia tandai "Sudah Direview"). Null = jabatan lain,
        // kartu tidak ditampilkan.
        $completedCount = null;
        if (in_array($user->jabatan, [User::JABATAN_PENILAI, User::JABATAN_PELAKSANA_INSPEKSI], true)) {
            $completedCount = Project::where('assigned_appraiser_id', $mineId)
                ->where('status', Project::STATUS_SELESAI)
                ->count();
        } elseif ($user->jabatan === User::JABATAN_REVIEWER) {
            $completedCount = Project::where('reviewed_by_user_id', $mineId)
                ->where('status', Project::STATUS_SELESAI)
                ->count();
        }

        // "Menunggu Review Anda" — proyek yang sudah disubmit Surveyor tapi
        // belum ditandai direview siapa pun (review_status = submitted).
        // Ditampilkan utk jabatan Reviewer (bisa langsung bertindak) DAN
        // Administrator (supaya tetap bisa memantau/verifikasi walau bukan
        // Reviewer). Reviewer TIDAK dibatasi ke proyek tertentu — siapa pun
        // berjabatan Reviewer boleh ambil proyek mana pun yang mengantre
        // (lihat ProjectController@approveReview, gerbangnya jabatan bukan
        // penugasan per-proyek).
        $pendingReview = collect();
        if ($user->jabatan === User::JABATAN_REVIEWER || $user->isAdministrator()) {
            $pendingReview = Project::with('instructingClient', 'reviewSubmittedBy')
                ->where('review_status', Project::REVIEW_SUBMITTED)
                ->orderBy('review_submitted_at')
                ->get();
        }

        $active = Project::with('instructingClient')
            ->active()
            ->tap($scope)
            ->get();

        // Proyek yang sudah punya jadwal (survey_date + sla_draft_days terisi).
        $scheduled = $active->filter(fn ($p) => $p->estimated_completion_date !== null);
        $overdue   = $scheduled->filter(fn ($p) => $p->sla_days_remaining < 0)
                               ->sortBy('sla_days_remaining');
        $dueSoon   = $scheduled->filter(fn ($p) => $p->sla_days_remaining >= 0 && $p->sla_days_remaining <= 2)
                               ->sortBy('sla_days_remaining');

        $surveyWeekCount = Project::where('status', '!=', Project::STATUS_BATAL)
            ->tap($scope)
            ->whereBetween('survey_date', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ])
            ->count();

        $unpaid = collect();
        if ($canSeeInvoices) {
            $unpaid = Invoice::with('project.instructingClient')
                ->where('status', Invoice::STATUS_UNPAID)
                ->whereHas('project', $scope)
                ->get()
                ->filter(fn ($inv) => $inv->project && ! $inv->project->isCancelled());
        }

        // Daftar "butuh perhatian": lewat deadline dulu, lalu mepet deadline,
        // terakhir tagihan yang belum lunas.
        $attention = collect();

        foreach ($overdue as $p) {
            $attention->push(['project' => $p, 'note' => $p->sla_label, 'tone' => 'red', 'rank' => 0]);
        }
        foreach ($dueSoon as $p) {
            $attention->push(['project' => $p, 'note' => $p->sla_label, 'tone' => 'amber', 'rank' => 1]);
        }
        foreach ($unpaid as $inv) {
            $attention->push([
                'project' => $inv->project,
                'note'    => ($inv->invoice_type === Invoice::TYPE_DP ? 'DP' : 'Pelunasan') . ' belum lunas',
                'tone'    => 'slate',
                'rank'    => 2,
            ]);
        }

        return view('dashboard.home', [
            'activeCount'     => $active->count(),
            'overdueCount'    => $overdue->count(),
            'surveyWeekCount' => $surveyWeekCount,
            'unpaidCount'     => $unpaid->count(),
            'canSeeInvoices'  => $canSeeInvoices,
            'attention'       => $attention->sortBy('rank')->values()->take(8),
            'weekRange'       => now()->startOfWeek()->translatedFormat('d M')
                                 . ' – ' . now()->endOfWeek()->translatedFormat('d M'),
            'completedCount'  => $completedCount,
            'pendingReview'   => $pendingReview,
            'officeWide'      => $officeWide,
        ]);
    }

    /**
     * Halaman utama (taskbar/menu masuk dari sini). Menampilkan seluruh
     * proyek dalam bentuk tabel, dengan filter status & pencarian ringan
     * di sisi query (bukan JS DataTables) supaya tetap ringan.
     */
    /**
     * Ekspresi SQL: tambahkan N HARI KERJA (Senin–Jumat, Sabtu/Minggu libur)
     * ke sebuah tanggal — padanan Carbon::addWeekdays() yang dipakai accessor
     * estimated_completion_date & estimated_final_completion_date di model.
     *
     * Dipakai untuk mengurutkan kolom SLA. Tanpa ini urutannya harus memakai
     * hari kalender dan bisa meleset dari tenggat yang sebenarnya ditampilkan.
     *
     * Rumusnya: dari hari kerja, N hari kerja = N hari kalender + 2 hari untuk
     * setiap akhir pekan yang terlewati, yaitu FLOOR((dow + N) / 5) * 2 dengan
     * dow = WEEKDAY() (0 = Senin ... 6 = Minggu). Kalau tanggal awalnya jatuh
     * di akhir pekan, Carbon menggeser ke Senin berikutnya lalu menghitung
     * sisanya — jadi ditangani sebagai cabang terpisah.
     */
    private function addBusinessDaysSql(string $dateExpr, string $daysExpr): string
    {
        // Cabang akhir pekan: geser ke Senin, lalu sisa (N-1) hari kerja.
        $shifted    = "DATE_ADD($dateExpr, INTERVAL (7 - WEEKDAY($dateExpr)) DAY)";
        $restDays   = "GREATEST($daysExpr - 1, 0)";
        $weekendArm = "DATE_ADD($shifted, INTERVAL (FLOOR($restDays / 5) * 2 + $restDays) DAY)";

        // Cabang hari kerja biasa.
        $weekdayArm = "DATE_ADD($dateExpr, INTERVAL (FLOOR((WEEKDAY($dateExpr) + $daysExpr) / 5) * 2 + $daysExpr) DAY)";

        // N = 0 berarti tanggal itu sendiri (Carbon juga begitu, termasuk kalau
        // tanggalnya jatuh di akhir pekan) — harus dicek lebih dulu, kalau tidak
        // cabang akhir pekan akan menggesernya ke Senin.
        return "CASE WHEN ($daysExpr) <= 0 THEN $dateExpr"
            . " WHEN WEEKDAY($dateExpr) >= 5 THEN $weekendArm"
            . " ELSE $weekdayArm END";
    }

    public function index(Request $request)
    {
        // invoices di-eager-load karena kolom "Sisa Tagihan" memakai accessor
        // remaining_balance yang menghitung dari relasi invoices — tanpa ini
        // jadi N+1 (1 query per baris tabel).
        $query = Project::with(['instructingClient', 'assignedAppraiser', 'invoices', 'valuationObjects']);

        // Filter "Proyek Saya" — menampilkan HANYA proyek yang
        // assigned_appraiser_id-nya cocok dengan user yang sedang login.
        // Tersedia untuk SEMUA role (bukan cuma Surveyor) karena Admin
        // Produksi pun kadang mau lihat "yang jadi tanggung jawab saya".
        //
        // Default toggle-nya: kalau parameter `mine` SAMA SEKALI tidak ada
        // di URL (baru masuk dari sidebar, bukan habis klik salah satu
        // pill), Surveyor diutamakan lihat "Proyek Saya" duluan — kerjaan
        // hariannya cuma proyek yang jadi tanggung jawabnya, jadi lebih
        // relevan daripada daftar SEMUA proyek. Role lain tetap default ke
        // "Semua Proyek" seperti biasa. Pill "Semua Proyek" sendiri kirim
        // `mine=0` eksplisit (lihat view), jadi klik eksplisit tetap dihormati.
        $mine = $request->has('mine')
            ? $request->boolean('mine')
            : auth()->user()->role?->slug === \App\Models\Role::SURVEYOR;

        // Role admin (bukan Reviewer) tidak punya tab "Proyek Saya"
        // (2026-09-13, feedback user) — selalu Semua Proyek.
        if (auth()->user()->seesOfficeWide()) {
            $mine = false;
        }

        if ($mine) {
            $query->where('assigned_appraiser_id', auth()->id());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter dari kartu angka Beranda (2026-09-13, feedback user). Rumusnya
        // disamakan dengan hitungan kartu di DashboardController@home.
        $focus = $request->get('focus');
        if ($focus === 'active') {
            $query->active();
        } elseif ($focus === 'overdue') {
            $query->active()
                ->whereNotNull('survey_date')
                ->where('sla_draft_days', '>', 0)
                ->whereRaw($this->addBusinessDaysSql('survey_date', 'sla_draft_days') . ' < CURDATE()');
        } elseif ($focus === 'survey_week') {
            $query->where('status', '!=', Project::STATUS_BATAL)
                ->whereBetween('survey_date', [
                    now()->startOfWeek()->toDateString(),
                    now()->endOfWeek()->toDateString(),
                ]);
        }

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('proposal_number', 'like', "%{$keyword}%")
                  ->orWhere('client_name', 'like', "%{$keyword}%")
                  ->orWhereHas('instructingClient', function ($sub) use ($keyword) {
                      $sub->where('client_name', 'like', "%{$keyword}%");
                  })
                  ->orWhereHas('intendedUsers', function ($sub) use ($keyword) {
                      $sub->where('client_name', 'like', "%{$keyword}%");
                  });
            });
        }

        // ---------- Filter tambahan (2026-09-14, feedback user) ----------
        if ($request->filled('purpose')) {
            $query->where('proposal_purpose', $request->purpose);
        }
        if ($request->filled('appraiser')) {
            $query->where('assigned_appraiser_id', $request->appraiser);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // ---------- Pengurutan ----------
        // Whitelist kolom supaya isi query string tidak bisa dipakai menyuntik
        // SQL lewat orderBy.
        $sortable = ['created_at', 'proposal_number', 'purpose', 'status', 'fee', 'deadline'];

        // Default berbeda per tab (2026-09-14, feedback user):
        //  - "Semua Proyek" : terbaru dulu — hasilnya mudah ditebak.
        //  - "Proyek Saya"  : tenggat SLA paling mepet dulu — tab ini dibuka
        //    untuk melihat tanggung jawab sendiri, jadi praktis jadi daftar
        //    "kerjakan dari atas". Klik header manapun tetap menimpa default.
        $defaultSort = $mine ? 'deadline' : 'created_at';
        $defaultDir  = $mine ? 'asc' : 'desc';

        $sort = $request->get('sort', $defaultSort);
        if (!in_array($sort, $sortable, true)) {
            $sort = $defaultSort;
        }
        $dir = $request->filled('dir')
            ? ($request->get('dir') === 'asc' ? 'asc' : 'desc')
            : $defaultDir;

        if ($sort === 'fee') {
            // Kolom yang DITAMPILKAN adalah total_fee (accessor: Fee + PPN bila
            // belum termasuk + Transport), bukan kolom service_fee. Mengurutkan
            // service_fee mentah bikin urutan terlihat kacau — mis. proyek
            // Rp 5.500.000 belum-PPN tampil Rp 6.105.000, jadi harusnya di ATAS
            // proyek Rp 6.000.000 sudah-PPN. Jadi rumus total_fee diulang di SQL.
            // PPN atas Fee + TA yang ditagih (TA reimburse tidak dihitung).
            $rate    = (float) config('kjpp.ppn_rate', 0.11);
            $taxable = '(service_fee + CASE WHEN transport_reimbursed = 1 THEN 0 ELSE COALESCE(transport_cost, 0) END)';
            $query->orderByRaw(
                "(CASE WHEN fee_ppn_included = 1 THEN $taxable ELSE $taxable * (1 + ?) END) " . $dir,
                [$rate]
            );
        } elseif ($sort === 'deadline') {
            // Harus mengikuti FASE SLA yang sedang ditampilkan di kolom SLA:
            // kalau hasil penilaian sudah dikonfirmasi, yang berlaku adalah SLA
            // Laporan Final (review_approved_at + sla_final_days); kalau belum,
            // SLA Draft (survey_date + sla_draft_days). Mengurutkan selalu pakai
            // tenggat Draft bikin isi kolom terlihat tidak urut.
            $deadlineExpr = 'CASE WHEN review_approved_at IS NOT NULL AND sla_final_days IS NOT NULL'
                . ' THEN ' . $this->addBusinessDaysSql('review_approved_at', 'sla_final_days')
                . ' ELSE ' . $this->addBusinessDaysSql('survey_date', 'COALESCE(sla_draft_days, 0)') . ' END';

            // Proyek yang kolom SLA-nya kosong (belum ada penilai/tanggal survei,
            // atau sudah Selesai/Batal) selalu ditaruh paling bawah — bukan
            // nyelip di tengah hanya karena tanggal surveinya kebetulan cocok.
            $query->orderByRaw(
                '(survey_date IS NULL OR assigned_appraiser IS NULL OR status IN (?, ?)) asc',
                [Project::STATUS_SELESAI, Project::STATUS_BATAL]
            )->orderByRaw($deadlineExpr . ' ' . $dir);
        } else {
            $column = match ($sort) {
                'proposal_number' => 'proposal_number',
                'purpose'         => 'proposal_purpose',
                'status'          => 'status',
                default           => 'created_at',
            };
            $query->orderBy($column, $dir);
        }

        // Urutan sekunder yang stabil — tanpa ini, baris dengan nilai sortir
        // sama bisa berpindah-pindah posisi antar halaman.
        if ($sort !== 'created_at') {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $projects = $query->paginate($perPage)->withQueryString();

        // Dipakai untuk filter dropdown status di view (termasuk "Batal").
        $statusOptions    = Project::STATUSES;
        $purposeOptions   = Project::query()->select('proposal_purpose')->distinct()
            ->orderBy('proposal_purpose')->pluck('proposal_purpose')->filter()->values();
        // Pilihan filter Penilai Lapangan (2026-09-15, feedback user): akun
        // aktif berJABATAN (biodata, bukan role) Penilai, Pelaksana Inspeksi,
        // atau Reviewer — ditambah siapa pun yang sudah pernah ditugaskan
        // (supaya penilai lama/nonaktif tetap bisa dicari riwayatnya).
        $appraiserOptions = \App\Models\User::query()
            ->where(function ($q) {
                $q->whereIn('id', Project::whereNotNull('assigned_appraiser_id')->distinct()->pluck('assigned_appraiser_id'))
                  ->orWhere(function ($active) {
                      $active->where('is_active', true)->whereIn('jabatan', [
                          \App\Models\User::JABATAN_PENILAI,
                          \App\Models\User::JABATAN_PELAKSANA_INSPEKSI,
                          \App\Models\User::JABATAN_REVIEWER,
                      ]);
                  });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'jabatan']);

        // Live search (lihat initLiveSearch() di layouts/app.blade.php) minta
        // fragmen hasil saja lewat header X-Requested-With, bukan halaman
        // penuh — supaya browser tak perlu render ulang seluruh layout +
        // kompilasi CSS Tailwind tiap pencarian.
        // Dikirim ke view supaya panah indikator kolom aktif ikut benar saat
        // urutannya berasal dari default per-tab (bukan dari query string).
        $currentSort = $sort;
        $currentDir  = $dir;

        if ($request->ajax()) {
            return view('dashboard._project_results', compact('projects', 'currentSort', 'currentDir'));
        }

        return view('dashboard.index', compact(
            'projects', 'statusOptions', 'purposeOptions', 'appraiserOptions', 'mine',
            'currentSort', 'currentDir'
        ));
    }

    /**
     * Dashboard monitoring — angka & grafik ringkas (bukan tabel).
     *
     *  - Total Proposal      = jumlah seluruh proyek (1 proyek = 1 proposal).
     *  - Pending / Belum Deal = proyek aktif yang BELUM punya pembayaran
     *    (belum ada invoice ber-status Paid).
     *  - Deal                = proyek yang SUDAH punya minimal 1 pembayaran.
     *  - Batal               = proyek berstatus "Batal".
     *  - Nilai Kontrak        = SUM(total_fee). total_fee adalah accessor
     *    (bukan kolom), jadi dijumlah di PHP — volume data internal kecil.
     */
    public function overview()
    {
        $projects = Project::query()
            ->select([
                'id', 'status', 'proposal_purpose', 'service_fee',
                'transport_cost', 'transport_reimbursed', 'fee_ppn_included', 'created_at',
            ])
            ->withCount(['invoices as paid_invoices_count' => function ($q) {
                $q->where('status', 'Paid');
            }])
            ->get();

        $active    = $projects->reject->isCancelled();
        $deal      = $active->filter(fn ($p) => $p->paid_invoices_count > 0);
        $pending   = $active->filter(fn ($p) => $p->paid_invoices_count === 0);
        $cancelled = $projects->filter->isCancelled();

        // Jumlah proyek per status, mengikuti urutan kanonik.
        $statusCounts = collect(Project::STATUSES)
            ->mapWithKeys(fn ($s) => [$s => $projects->where('status', $s)->count()]);

        // Jumlah proyek per jenis proposal (proyek aktif saja).
        $purposeCounts = collect([
            Project::PURPOSE_JUAL_BELI,
            Project::PURPOSE_PENJAMINAN_UTANG,
            Project::PURPOSE_LELANG,
            Project::PURPOSE_LK_PROPERTI,
        ])->mapWithKeys(fn ($p) => [$p => $active->where('proposal_purpose', $p)->count()]);

        // Proposal masuk per bulan — 6 bulan terakhir (termasuk bulan ini).
        $monthly = collect(range(5, 0))->map(function ($back) use ($projects) {
            $month = now()->startOfMonth()->subMonths($back);

            return [
                'label' => $month->translatedFormat('M Y'),
                'count' => $projects->filter(
                    fn ($p) => $p->created_at->isSameMonth($month)
                )->count(),
            ];
        });

        $recent = Project::with('instructingClient')
            ->latest()
            ->limit(6)
            ->get(['id', 'proposal_number', 'instructing_client_id', 'proposal_purpose', 'status', 'created_at']);

        // Proposal yang masih di tahap awal (Draft / DP Invoicing) — reminder
        // sudah berapa hari sejak DIBUAT (created_at, bukan proposal_date
        // manual), supaya tidak "hilang" tanpa tindak lanjut. Yang paling
        // lama menunggu ditaruh paling atas.
        $followUps = Project::with('instructingClient')
            ->whereIn('status', [Project::STATUS_DRAFT, Project::STATUS_DP_INVOICING])
            ->get(['id', 'proposal_number', 'instructing_client_id', 'status', 'created_at'])
            ->sortByDesc(fn ($p) => $p->created_at->diffInDays(now()))
            ->values();

        return view('dashboard.overview', [
            'totalProposals'     => $projects->count(),
            'pendingCount'       => $pending->count(),
            'dealCount'          => $deal->count(),
            'cancelledCount'     => $cancelled->count(),
            'totalContractValue' => $active->sum('total_fee'),
            'dealContractValue'  => $deal->sum('total_fee'),
            'statusCounts'       => $statusCounts,
            'purposeCounts'      => $purposeCounts,
            'monthly'            => $monthly,
            'recent'             => $recent,
            'followUps'          => $followUps,
        ]);
    }

    /**
     * Export seluruh data proyek (sesuai filter yang sedang aktif di
     * dashboard) menjadi file Excel (.xlsx).
     * Membutuhkan package: composer require maatwebsite/excel
     */
    public function exportExcel(Request $request)
    {
        $filename = 'Daftar-Proyek-KJPP-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new ProjectsExport($request->all()), $filename);
    }
}
