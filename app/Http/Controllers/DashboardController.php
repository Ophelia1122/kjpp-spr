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
    /** Batas penagihan pekerjaan Selesai yang belum lunas: N hari sejak buku dicetak. */
    public const BILLING_DUE_DAYS = 3;

    /**
     * Beranda = PR utama hari ini, dibedakan per peran (2026-09-15, feedback user):
     *  - Surveyor/Penilai : proyek aktif miliknya (tahap, tanggal survei, SLA, catatan terakhir)
     *  - Reviewer         : antrean review nilai & draft laporan, plus tab "Sebagai Penilai"
     *  - Admin Produksi   : tindakan produksi & proyek dalam SLA Laporan Final
     *  - General Admin    : Admin Produksi + invoice tertunggak & pekerjaan selesai belum lunas
     *  - Administrator    : seperti General Admin, plus tab "Sebagai Reviewer"
     * Ringkasan angka bulanan cukup di Ringkasan Project & Dashboard Pembayaran.
     */
    public function home(Request $request)
    {
        $user = auth()->user();

        $modes = [];
        if ($user->seesOfficeWide()) {
            $modes['kantor'] = $user->hasPermission('invoices.manage') ? 'Produksi & Keuangan' : 'Produksi';
            if ($user->isAdministrator()) {
                $modes['reviewer'] = 'Sebagai Reviewer';
            }
        } elseif ($user->isReviewer()) {
            $modes['reviewer'] = 'Sebagai Reviewer';
            $modes['penilai']  = 'Sebagai Penilai';
        } else {
            $modes['penilai'] = 'Proyek Saya';
        }
        $requested = (string) $request->query('mode', '');
        $mode = isset($modes[$requested]) ? $requested : array_key_first($modes);

        $data = ['modes' => $modes, 'mode' => $mode, 'canFinance' => false, 'notes' => collect()];
        // Hasil di-cache 3 jam per pengguna+mode (2026-09-20, hasil audit skala):
        // perhitungan skor SLA membaca proyek setahun terakhir, tidak perlu
        // diulang tiap kali Beranda dibuka.
        $data['profile'] = \Illuminate\Support\Facades\Cache::remember(
            "mini-profile:{$user->id}:{$mode}:" . now()->format('Y-m-d-H'),
            now()->addHours(3),
            fn () => $this->miniProfile($user, $mode)
        );
        $with = ['instructingClient', 'namedClient'];

        if ($mode === 'penilai') {
            $mine   = fn () => Project::forAppraiser($user->id);
            $active = $mine()->with($with)->active()->get()
                ->sortBy(fn ($p) => ($p->active_sla['date'] ?? null)?->timestamp ?? PHP_INT_MAX)
                ->values();

            $data['penilai'] = [
                'active'           => $active,
                'activeCount'      => $active->count(),
                'surveyMonthCount' => $mine()->where('status', '!=', Project::STATUS_BATAL)
                    ->whereBetween('survey_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                    ->count(),
                'doneCount'        => $mine()->where('status', Project::STATUS_SELESAI)->count(),
            ];
            $data['notes'] = $this->latestNotes($active->pluck('id'));
        }

        if ($mode === 'reviewer') {
            $queue = Project::with([...$with, 'reviewSubmittedBy'])
                ->where('status', Project::STATUS_IN_PROGRESS)
                ->whereIn('review_status', [Project::REVIEW_SUBMITTED, Project::REVIEW_RELEASED, Project::STAGE_DRAFT_CONFIRMED])
                ->get()
                ->sortBy(fn ($p) => $p->stage_since?->timestamp ?? 0)
                ->values();

            $data['reviewer'] = [
                'queue'              => $queue,
                'valueCount'         => $queue->whereIn('review_status', [Project::REVIEW_SUBMITTED, Project::REVIEW_RELEASED])->count(),
                'draftCount'         => $queue->where('review_status', Project::STAGE_DRAFT_CONFIRMED)->count(),
                'reviewedMonthCount' => \App\Models\AuditLog::where('user_id', $user->id)
                    ->whereIn('action', ['review.value_approved', 'draft.reviewed', 'review.approved_by_reviewer'])
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->count(),
            ];
            $data['notes'] = $this->latestNotes($queue->pluck('id'));
        }

        if ($mode === 'kantor') {
            $inProgress = Project::with($with)
                ->where('status', Project::STATUS_IN_PROGRESS)
                ->whereNotNull('review_status')
                ->get();

            $actions = $inProgress
                ->whereIn('review_status', [Project::REVIEW_SUBMITTED, Project::REVIEW_RELEASED, Project::STAGE_DRAFT_SUBMITTED, Project::STAGE_DRAFT_REVIEWED])
                ->sortBy(fn ($p) => $p->stage_since?->timestamp ?? 0)
                ->values();
            $finalSla = $inProgress
                ->filter(fn ($p) => $p->isReviewApproved())
                ->sortBy(fn ($p) => $p->estimated_final_completion_date?->timestamp ?? PHP_INT_MAX)
                ->values();

            $data['produksi'] = [
                'actions'           => $actions,
                'finalSla'          => $finalSla,
                'valueCount'        => $inProgress->whereIn('review_status', [Project::REVIEW_SUBMITTED, Project::REVIEW_RELEASED])->count(),
                'confirmCount'      => $inProgress->where('review_status', Project::STAGE_DRAFT_SUBMITTED)->count(),
                'printCount'        => $inProgress->where('review_status', Project::STAGE_DRAFT_REVIEWED)->count(),
                'finalOverdueCount' => $finalSla->filter(fn ($p) => ($p->final_sla_days_remaining ?? 0) < 0)->count(),
            ];
            $data['notes'] = $this->latestNotes($actions->pluck('id'));

            if ($user->hasPermission('invoices.manage')) {
                $data['canFinance'] = true;

                // Dibatasi 200 invoice tertunggak terlama (2026-09-20, audit skala).
                $overdue = Invoice::with('project.instructingClient', 'project.namedClient')
                    ->where('status', Invoice::STATUS_UNPAID)
                    ->orderBy('invoice_date')
                    ->limit(200)
                    ->get()
                    ->filter(fn ($inv) => $inv->project && ! $inv->project->isCancelled()
                        && $inv->age_days > PaymentDashboardController::OVERDUE_DAYS)
                    ->sortByDesc('age_days')
                    ->values();

                // Dibatasi 200 pekerjaan selesai terbaru (2026-09-20, audit skala):
                // daftar ini hanya pengingat tagih, bukan arsip lengkap.
                $unpaidDone = Project::with([...$with, 'invoices'])
                    ->where('status', Project::STATUS_SELESAI)
                    ->orderByDesc('printed_at')
                    ->limit(200)
                    ->get()
                    ->reject(fn ($p) => $p->is_fully_paid)
                    ->map(function ($p) {
                        $doneAt = $p->printed_at ?? $p->updated_at;
                        $due    = $doneAt->copy()->startOfDay()->addDays(self::BILLING_DUE_DAYS);

                        return [
                            'project'   => $p,
                            'doneAt'    => $doneAt,
                            'due'       => $due,
                            'dueDays'   => (int) now()->startOfDay()->diffInDays($due, false),
                            'notBilled' => max(0, round((float) $p->total_fee - (float) $p->invoices->sum('amount'), 2)),
                        ];
                    })
                    ->sortBy(fn ($row) => $row['due']->timestamp)
                    ->values();

                // Proposal yang masih di tahap awal (Draft / DP Invoicing) — reminder
                // sudah berapa hari sejak DIBUAT (created_at, bukan proposal_date
                // manual), supaya tidak "hilang" tanpa tindak lanjut. Yang paling
                // lama menunggu ditaruh paling atas.
                $followUps = Project::with('instructingClient', 'namedClient')
                    ->whereIn('status', [Project::STATUS_DRAFT, Project::STATUS_DP_INVOICING])
                    ->get(['id', 'proposal_number', 'instructing_client_id', 'status', 'created_at'])
                    ->sortByDesc(fn ($p) => $p->created_at->diffInDays(now()))
                    ->values();

                $data['keuangan'] = [
                    'overdue'       => $overdue,
                    'overdueSum'    => $overdue->sum('amount'),
                    'unpaidDone'    => $unpaidDone,
                    'unpaidDoneSum' => $unpaidDone->sum(fn ($row) => $row['project']->remaining_balance),
                    'overdueDays'   => PaymentDashboardController::OVERDUE_DAYS,
                    'dueDays'       => self::BILLING_DUE_DAYS,
                ];
                $data['followUps'] = $followUps;
            }
        }

        return view('dashboard.home', $data);
    }

    /** Catatan terakhir (dari Riwayat Proyek) per proyek, key = project id. */
    private function latestNotes($projectIds)
    {
        $ids = collect($projectIds);
        if ($ids->isEmpty()) {
            return collect();
        }

        return \App\Models\AuditLog::with('user')
            ->where('subject_type', Project::class)
            ->whereIn('subject_id', $ids)
            ->whereNotNull('note')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->keyBy('subject_id');
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

    /**
     * Kartu mini profile di Beranda (2026-09-19, feedback user): avatar,
     * sapaan, skor SLA draft, dan statistik bulan berjalan.
     *
     * Angka mengikuti tab aktif: mode "kantor" memakai data seluruh kantor,
     * mode lain memakai proyek milik user sendiri (sebagai penilai lapangan).
     * Mode reviewer menambah antrean review & jumlah yang sudah dia review
     * bulan ini, karena reviewer juga bisa turun survei.
     */
    private function miniProfile(User $user, string $mode): array
    {
        $officeWide = $mode === 'kantor';
        $base = fn () => $officeWide ? Project::query() : Project::forAppraiser($user->id);

        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();

        // Skor SLA Draft: rata-rata hari survei -> submit nilai, nilai yang
        // diajukan BULAN INI saja (2026-09-21, feedback user; dulu 1 tahun).
        $done = $base()
            ->whereNotNull('survey_date')
            ->whereBetween('review_submitted_at', [$monthStart, $monthEnd])
            ->get();

        $avgDays = $done->isEmpty() ? null : round($done->avg(
            fn ($p) => $p->survey_date->diffInDays($p->review_submitted_at)
        ), 1);

        $withTarget = $done->filter(fn ($p) => $p->estimated_completion_date !== null);
        $onTimePct  = $withTarget->isEmpty() ? null : (int) round(
            $withTarget->filter(fn ($p) => $p->review_submitted_at->lte($p->estimated_completion_date))->count()
                / $withTarget->count() * 100
        );

        $profile = [
            'office_wide'  => $officeWide,
            'avg_days'     => $avgDays,
            'on_time_pct'  => $onTimePct,
            'sample'       => $done->count(),
            'month_survey' => $base()->where('status', '!=', Project::STATUS_BATAL)
                ->whereBetween('survey_date', [$monthStart->toDateString(), $monthEnd->toDateString()])->count(),
            'month_done'   => $base()->whereBetween('printed_at', [$monthStart, $monthEnd])->count(),
            'review_queue' => null,
            'review_done'  => null,
        ];

        if ($mode === 'reviewer') {
            $profile['review_queue'] = Project::where('status', Project::STATUS_IN_PROGRESS)
                ->whereIn('review_status', [Project::REVIEW_SUBMITTED, Project::REVIEW_RELEASED, Project::STAGE_DRAFT_CONFIRMED])
                ->count();
            $profile['review_done'] = Project::where('review_approved_by_user_id', $user->id)
                ->whereBetween('review_approved_at', [$monthStart, $monthEnd])
                ->count();
        }

        return $profile;
    }

    public function index(Request $request)
    {
        // invoices di-eager-load karena kolom "Sisa Tagihan" memakai accessor
        // remaining_balance yang menghitung dari relasi invoices — tanpa ini
        // jadi N+1 (1 query per baris tabel).
        $query = Project::with(['instructingClient', 'namedClient', 'assignedAppraiser', 'invoices', 'valuationObjects']);

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
            $query->forAppraiser(auth()->id());
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
        } elseif ($focus === 'survey_month') {
            // Kartu "Survei bulan ini" di Beranda Surveyor (2026-09-15).
            $query->where('status', '!=', Project::STATUS_BATAL)
                ->whereBetween('survey_date', [
                    now()->startOfMonth()->toDateString(),
                    now()->endOfMonth()->toDateString(),
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
                  ->orWhereHas('namedClient', function ($sub) use ($keyword) {
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
            $query->forAppraiser($request->appraiser);
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
        if (!in_array($perPage, [15, 25], true)) {
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
                $q->whereIn('id', \Illuminate\Support\Facades\DB::table('project_appraisers')->select('user_id'))
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

        $recent = Project::with('instructingClient', 'namedClient')
            ->latest()
            ->limit(6)
            ->get(['id', 'proposal_number', 'instructing_client_id', 'proposal_purpose', 'status', 'created_at']);

        return view('dashboard.overview', [
            'workload'           => $this->appraiserWorkload(),
            'topBanks'           => $this->topBanks(),
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
        ]);
    }

    /**
     * Beban kerja per penilai lapangan (2026-09-21, feedback user): jumlah
     * proyek In-Progress yang sedang dipegang, dipecah per tahap, supaya
     * terlihat siapa yang penuh dan siapa yang kosong. Semua user aktif
     * berjabatan Pelaksana Inspeksi, Penilai, atau Reviewer ikut tampil walau
     * tidak memegang proyek.
     */
    private function appraiserWorkload(): \Illuminate\Support\Collection
    {
        $today = now()->startOfDay();
        $active = Project::with('appraisers:id')
            ->where('status', Project::STATUS_IN_PROGRESS)
            ->get(['id', 'survey_date', 'review_status', 'assigned_appraiser_id']);
        $doneMonth = Project::with('appraisers:id')
            ->whereBetween('printed_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->get(['id', 'assigned_appraiser_id']);

        $ids = fn ($p) => $p->appraisers->pluck('id')->push($p->assigned_appraiser_id)->filter()->unique();

        // Hanya jabatan lapangan & review (2026-09-21, feedback user).
        $users = User::whereIn('jabatan', [User::JABATAN_PELAKSANA_INSPEKSI, User::JABATAN_PENILAI, User::JABATAN_REVIEWER])
            ->where('is_active', true)
            ->get(['id', 'name', 'jabatan'])
            ->keyBy('id');

        $review = [Project::REVIEW_SUBMITTED, Project::REVIEW_RELEASED];

        return $users->map(function ($u) use ($active, $doneMonth, $ids, $today, $review) {
            $mine = $active->filter(fn ($p) => $ids($p)->contains($u->id));

            return [
                'user'      => $u,
                'upcoming'  => $mine->filter(fn ($p) => ! $p->review_status && $p->survey_date?->gt($today))->count(),
                'surveying' => $mine->filter(fn ($p) => ! $p->review_status && (! $p->survey_date || $p->survey_date->lte($today)))->count(),
                'review'    => $mine->filter(fn ($p) => in_array($p->review_status, $review, true))->count(),
                'draft'     => $mine->filter(fn ($p) => $p->review_status && ! in_array($p->review_status, $review, true))->count(),
                'total'     => $mine->count(),
                'doneMonth' => $doneMonth->filter(fn ($p) => $ids($p)->contains($u->id))->count(),
            ];
        })->sortBy([['total', 'desc'], [fn ($r) => $r['user']->name, 'asc']])->values();
    }

    /**
     * 5 bank Pemberi Tugas dengan proyek terbanyak (2026-09-21, feedback
     * user), proyek batal tidak dihitung. Klien bank dengan nama sama
     * digabung walau alamatnya beda (mis. cabang berbeda): nama dinormalkan
     * tanpa "PT", "Tbk", "(Persero)", tanda baca, dan huruf besar/kecil.
     */
    private function topBanks(): array
    {
        $rows = Project::query()
            ->where('status', '!=', Project::STATUS_BATAL)
            ->join('clients', 'clients.id', '=', 'projects.instructing_client_id')
            ->selectRaw('clients.client_name, COUNT(*) AS total')
            ->groupBy('clients.client_name')
            ->get();

        $norm = fn (string $n) => trim(preg_replace('/\s+/', ' ', preg_replace(
            '/\b(pt|tbk|persero)\b/', ' ', preg_replace('/[^a-z0-9 ]+/', ' ', mb_strtolower($n))
        )));

        $banks = $rows->filter(fn ($r) => str_contains(mb_strtolower($r->client_name), 'bank'))
            ->groupBy(fn ($r) => $norm($r->client_name))
            ->map(fn ($group) => [
                // Nama tampil tanpa PT / (Persero) / Tbk, huruf besar semua
                // supaya seragam dan muat di legenda (2026-09-21, feedback user).
                'label' => $this->shortBankName($group->sortByDesc('total')->first()->client_name),
                'value' => (int) $group->sum('total'),
            ])
            ->sortByDesc('value')
            ->values();

        $palette = ['#1B5E7B', '#E8702A', '#1E7B34', '#1098D6', '#9B2D96'];
        $slices  = $banks->take(5)->values()->map(fn ($b, $i) => $b + ['color' => $palette[$i]])->all();
        $others  = (int) $banks->slice(5)->sum('value');
        if ($others > 0) {
            $slices[] = ['label' => 'Bank lainnya', 'value' => $others, 'color' => '#9CA3AF'];
        }

        return [
            'slices'  => $slices,
            'nonBank' => (int) $rows->reject(fn ($r) => str_contains(mb_strtolower($r->client_name), 'bank'))->sum('total'),
        ];
    }

    /** "PT Bank Mandiri (Persero) Tbk" -> "BANK MANDIRI". */
    private function shortBankName(string $name): string
    {
        $name = preg_replace('/\(\s*persero\s*\)|\bpersero\b|\bpt\b\.?|\btbk\b\.?/i', ' ', $name);
        $name = preg_replace('/\s*,(\s*,)*\s*/', ', ', $name);         // koma ganda sisa pembuangan
        $name = trim(preg_replace('/\s+/', ' ', $name), " ,.");

        return mb_strtoupper($name);
    }

    /**
     * Export seluruh data proyek (sesuai filter yang sedang aktif di
     * dashboard) menjadi file Excel (.xlsx).
     * Membutuhkan package: composer require maatwebsite/excel
     */
    /** Export Excel dengan rentang waktu & filter dari modal (2026-09-14, feedback user). */
    public function exportExcel(Request $request)
    {
        $filters = $request->validate([
            'date_field' => 'nullable|in:proposal_date,created_at,survey_date',
            'from'       => 'nullable|date',
            'to'         => 'nullable|date|after_or_equal:from',
            'status'     => 'nullable|string|max:100',
            'purpose'    => 'nullable|string|max:100',
            'appraiser'  => 'nullable|integer',
            'q'          => 'nullable|string|max:255',
            'mine'       => 'nullable|boolean',
        ], ['to.after_or_equal' => 'Tanggal "Sampai" harus sama atau setelah tanggal "Dari".']);

        if (auth()->user()->seesOfficeWide()) {
            $filters['mine'] = false;
        }

        $range = ! empty($filters['from']) || ! empty($filters['to'])
            ? '-' . ($filters['from'] ?? 'awal') . '_sd_' . ($filters['to'] ?? now()->toDateString())
            : '-' . now()->format('Y-m-d');

        return Excel::download(new ProjectsExport($filters), 'Daftar-Proyek-KJPP' . $range . '.xlsx');
    }
}
