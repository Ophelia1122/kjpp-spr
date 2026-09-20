<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\DocumentNumbering;
use Illuminate\Http\Request;

class PaymentDashboardController extends Controller
{
    /**
     * Invoice belum dibayar dianggap TERTUNGGAK setelah sekian hari sejak
     * tanggal terbit (2026-09-13, feedback user: "buat aja dulu 14 hari").
     */
    public const OVERDUE_DAYS = 14;

    /** Tanggal terbit invoice; data lama tanpa invoice_date jatuh ke created_at. */
    private const INVOICE_DATE_SQL = 'COALESCE(invoice_date, DATE(created_at))';

    public function index(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date',
        ]);
        $from = $request->filled('from') ? $request->date('from')->toDateString() : null;
        $to   = $request->filled('to') ? $request->date('to')->toDateString() : null;

        // Kartu ringkasan memakai performa 12 BULAN TERAKHIR bila staf tidak
        // memilih periode sendiri (2026-09-19, feedback user) — sebelumnya
        // menjumlah sepanjang waktu, sehingga angka lama ikut terus.
        $summaryFrom = $from ?? now()->subYear()->toDateString();
        $summaryTo   = $to;

        // ---------- Daftar invoice ----------
        // Urutan default (2026-09-13): yang belum dibayar paling atas, dimulai
        // dari yang PALING LAMA tertunggak; lalu yang sudah dibayar, terbaru dulu.
        $query = Invoice::with('project.instructingClient', 'project.namedClient');

        // Filter status: Belum Dibayar / Dibayar, atau "overdue" = belum dibayar
        // lebih dari OVERDUE_DAYS sejak terbit, proyek tidak Batal (2026-09-14).
        if ($request->get('status') === 'overdue') {
            $query->where('status', Invoice::STATUS_UNPAID)
                ->whereHas('project', fn ($p) => $p->where('status', '!=', Project::STATUS_BATAL))
                ->whereRaw(self::INVOICE_DATE_SQL . ' < ?', [now()->subDays(self::OVERDUE_DAYS)->toDateString()]);
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('invoice_number', 'like', "%{$keyword}%")
                  ->orWhere('kwitansi_number', 'like', "%{$keyword}%")
                  ->orWhereHas('project', fn ($p) => $p->where('proposal_number', 'like', "%{$keyword}%"))
                  ->orWhereHas('project.instructingClient', fn ($c) => $c->where('client_name', 'like', "%{$keyword}%"));
            });
        }

        // Filter periode pada daftar = berdasarkan tanggal terbit invoice.
        $this->applyPeriod($query, self::INVOICE_DATE_SQL, $from, $to);

        // ---------- Pengurutan (header kolom bisa diklik, seperti List Project) ----------
        // Whitelist supaya query string tidak bisa menyuntik orderBy.
        $sort = in_array($request->get('sort'), ['invoice_number', 'amount', 'date', 'status'], true) ? $request->get('sort') : null;
        $dir  = $request->get('dir') === 'asc' ? 'asc' : 'desc';

        match ($sort) {
            'invoice_number' => $query->orderBy('invoice_number', $dir),
            'amount'         => $query->orderBy('amount', $dir),
            'date'           => $query->orderByRaw(self::INVOICE_DATE_SQL . ' ' . $dir),
            'status'         => $query->orderBy('status', $dir)->orderByRaw(self::INVOICE_DATE_SQL . ' asc'),
            // Default: belum dibayar paling atas, dimulai dari yang PALING LAMA
            // tertunggak; lalu yang sudah dibayar, terbaru dulu.
            default          => $query
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [Invoice::STATUS_UNPAID])
                ->orderByRaw('CASE WHEN status = ? THEN ' . self::INVOICE_DATE_SQL . ' END asc', [Invoice::STATUS_UNPAID])
                ->orderByDesc('payment_date'),
        };
        $query->orderByDesc('id'); // urutan sekunder yang stabil antar halaman

        // Cukup 15 & 25 per halaman (2026-09-14, feedback user) — 50/100 berat dimuat.
        // Dikunci 15 baris (2026-09-20, feedback user).
        $perPage = 15;
        if (! in_array($perPage, [15, 25], true)) {
            $perPage = 15;
        }

        $invoices = $query->paginate($perPage)->withQueryString();

        $tableData = [
            'invoices'    => $invoices,
            'overdueDays' => self::OVERDUE_DAYS,
            'currentSort' => $sort,
            'currentDir'  => $dir,
        ];

        if ($request->ajax()) {
            return view('dashboard._invoice_results', $tableData);
        }

        // ---------- Kartu ringkasan ----------
        // Invoice milik proyek BATAL tidak ikut dijumlahkan (2026-09-13) —
        // sebelumnya tagihan proyek batal terus terlihat sebagai piutang.
        $validInvoices = fn () => Invoice::whereHas('project', fn ($p) => $p->where('status', '!=', Project::STATUS_BATAL));

        // Sudah Ditagih mengikuti tanggal terbit; Sudah Diterima mengikuti
        // tanggal bayar — keduanya ikut filter periode.
        $billedQuery = $validInvoices();
        $this->applyPeriod($billedQuery, self::INVOICE_DATE_SQL, $summaryFrom, $summaryTo);
        $totalBilled = (float) $billedQuery->sum('amount');

        $paidQuery = $validInvoices()->where('status', Invoice::STATUS_PAID);
        $this->applyPeriod($paidQuery, 'payment_date', $summaryFrom, $summaryTo);
        $totalPaid = (float) $paidQuery->sum('amount');

        // Posisi saat ini (tidak ikut periode): yang masih menunggu dibayar.
        $unpaidQuery   = $validInvoices()->where('status', Invoice::STATUS_UNPAID);
        $totalUnpaid   = (float) (clone $unpaidQuery)->sum('amount');
        $overdueCount  = (clone $unpaidQuery)
            ->whereRaw(self::INVOICE_DATE_SQL . ' < ?', [now()->subDays(self::OVERDUE_DAYS)->toDateString()])
            ->count();

        // Nilai kontrak & bagian yang BELUM PERNAH ditagihkan.
        //
        // Dihitung di SQL (2026-09-20, hasil audit skala): sebelumnya seluruh
        // proyek beserta relasi invoice ditarik ke memori lalu dijumlahkan di
        // PHP — aman untuk belasan proyek, tapi berat begitu datanya ribuan.
        // Rumus total_fee diulang di SQL; jaga tetap sama dengan
        // Project::getTotalFeeAttribute().
        $rate    = (float) config('kjpp.ppn_rate', 0.11);
        $taxable = '(service_fee + CASE WHEN transport_reimbursed = 1 THEN 0 ELSE COALESCE(transport_cost, 0) END)';
        $totalFeeSql = "(CASE WHEN fee_ppn_included = 1 THEN {$taxable} ELSE {$taxable} * (1 + ?) END)";

        $scope = fn () => Project::query()
            ->whereNotIn('status', [Project::STATUS_DRAFT, Project::STATUS_BATAL])
            ->whereDate('created_at', '>=', $summaryFrom)
            ->when($summaryTo, fn ($q) => $q->whereDate('created_at', '<=', $summaryTo));

        $billedSub = 'COALESCE((select sum(amount) from invoices where invoices.project_id = projects.id), 0)';

        $totals = $scope()
            ->selectRaw("SUM({$totalFeeSql}) as kontrak", [$rate])
            ->selectRaw("SUM(GREATEST({$totalFeeSql} - {$billedSub}, 0)) as belum_ditagih", [$rate])
            ->first();

        $totalContract  = (float) ($totals->kontrak ?? 0);
        $totalNotBilled = (float) ($totals->belum_ditagih ?? 0);

        // Proyek yang masih punya sisa tagihan, sisa terbesar dulu. Hanya skema
        // DP di Awal (2026-09-14, feedback user) — proyek Bayar Nanti memang
        // ditagih di akhir, jadi belum layak dianggap sisa tagihan.
        // Dibatasi 50 baris teratas (2026-09-20) supaya kartu ini tidak ikut
        // membesar seiring jumlah proyek.
        $paidSub = "COALESCE((select sum(amount) from invoices where invoices.project_id = projects.id and invoices.status = '" . Invoice::STATUS_PAID . "'), 0)";

        $outstandingProjects = $scope()
            ->with('instructingClient', 'namedClient', 'invoices')
            ->where('payment_scheme', '!=', Project::PAYMENT_SCHEME_LATER)
            ->selectRaw("projects.*, ({$totalFeeSql} - {$paidSub}) as sisa_tagihan", [$rate])
            ->havingRaw('sisa_tagihan > 0')
            ->orderByDesc('sisa_tagihan')
            ->limit(50)
            ->get();

        return view('dashboard.pembayaran', $tableData + [
            'totalContract'       => $totalContract,
            'totalBilled'         => $totalBilled,
            'totalPaid'           => $totalPaid,
            'totalUnpaid'         => $totalUnpaid,
            'totalNotBilled'      => $totalNotBilled,
            'overdueCount'        => $overdueCount,
            'overdueDays'         => self::OVERDUE_DAYS,
            'outstandingProjects' => $outstandingProjects,
            'statusOptions'       => [Invoice::STATUS_UNPAID, Invoice::STATUS_PAID],
            'periodActive'        => $from || $to,
            'summaryFrom'         => $summaryFrom,
            'summaryTo'           => $summaryTo,
            'numbering'           => app(DocumentNumbering::class)->summary(),
        ]);
    }

    /**
     * Simpan "nomor terakhir" Invoice & Kwitansi yang terbit di luar aplikasi
     * (ikon pengaturan di Dashboard Pembayaran, 2026-09-14, feedback user).
     */
    public function updateNumbering(Request $request, DocumentNumbering $numbering)
    {
        $validated = $request->validate([
            'invoice_last'  => 'required|integer|min:0|max:999999',
            'kwitansi_last' => 'required|integer|min:0|max:999999',
        ], [], [
            'invoice_last'  => 'nomor terakhir invoice',
            'kwitansi_last' => 'nomor terakhir kwitansi',
        ]);

        $numbering->setManualLast('invoice', (int) $validated['invoice_last']);
        $numbering->setManualLast('kwitansi', (int) $validated['kwitansi_last']);

        $nextInvoice  = $numbering->next('invoice');
        $nextKwitansi = $numbering->next('kwitansi');

        AuditLogger::record(
            'numbering.updated',
            'Mengatur nomor terakhir tahun ' . now()->year . ": Invoice {$validated['invoice_last']}, Kwitansi {$validated['kwitansi_last']}. "
                . "Nomor berikutnya: {$nextInvoice} dan {$nextKwitansi}"
        );

        return redirect()
            ->route('dashboard.pembayaran')
            ->with('success', "Penomoran disimpan. Invoice berikutnya {$nextInvoice}, kwitansi berikutnya {$nextKwitansi}.");
    }

    private function applyPeriod($query, string $dateSql, ?string $from, ?string $to): void
    {
        if ($from) {
            $query->whereRaw("$dateSql >= ?", [$from]);
        }
        if ($to) {
            $query->whereRaw("$dateSql <= ?", [$to]);
        }
    }
}
