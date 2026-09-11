<?php

namespace App\Http\Controllers;

use App\Exports\ProjectsExport;
use App\Models\Invoice;
use App\Models\Project;
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
        $canSeeInvoices = auth()->user()->hasPermission('invoices.view');
        $mineId         = auth()->id();

        $active = Project::with('instructingClient')
            ->active()
            ->where('assigned_appraiser_id', $mineId)
            ->get();

        // Proyek yang sudah punya jadwal (survey_date + sla_draft_days terisi).
        $scheduled = $active->filter(fn ($p) => $p->estimated_completion_date !== null);
        $overdue   = $scheduled->filter(fn ($p) => $p->sla_days_remaining < 0)
                               ->sortBy('sla_days_remaining');
        $dueSoon   = $scheduled->filter(fn ($p) => $p->sla_days_remaining >= 0 && $p->sla_days_remaining <= 2)
                               ->sortBy('sla_days_remaining');

        $surveyWeekCount = Project::where('status', '!=', Project::STATUS_BATAL)
            ->where('assigned_appraiser_id', $mineId)
            ->whereBetween('survey_date', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ])
            ->count();

        $unpaid = collect();
        if ($canSeeInvoices) {
            $unpaid = Invoice::with('project.instructingClient')
                ->where('status', Invoice::STATUS_UNPAID)
                ->whereHas('project', fn ($q) => $q->where('assigned_appraiser_id', $mineId))
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
        ]);
    }

    /**
     * Halaman utama (taskbar/menu masuk dari sini). Menampilkan seluruh
     * proyek dalam bentuk tabel, dengan filter status & pencarian ringan
     * di sisi query (bukan JS DataTables) supaya tetap ringan.
     */
    public function index(Request $request)
    {
        $query = Project::with('instructingClient')->latest();
        // Filter "Proyek Saya" — menampilkan HANYA proyek yang
        // assigned_appraiser_id-nya cocok dengan user yang sedang login.
        // Tersedia untuk SEMUA role (bukan cuma Surveyor) karena Admin
        // Produksi pun kadang mau lihat "yang jadi tanggung jawab saya".
        if ($request->boolean('mine')) {
            $query->where('assigned_appraiser_id', auth()->id());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('proposal_number', 'like', "%{$keyword}%")
                  ->orWhereHas('instructingClient', function ($sub) use ($keyword) {
                      $sub->where('client_name', 'like', "%{$keyword}%");
                  });
            });
        }

        $projects = $query->paginate(15)->withQueryString();

        // Dipakai untuk filter dropdown status di view (termasuk "Batal").
        $statusOptions = Project::STATUSES;

        return view('dashboard.index', compact('projects', 'statusOptions'));
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
                'transport_cost', 'fee_ppn_included', 'created_at',
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
