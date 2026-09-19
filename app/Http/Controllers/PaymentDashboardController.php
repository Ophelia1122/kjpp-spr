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
        $perPage = (int) $request->get('per_page', 15);
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
        $this->applyPeriod($billedQuery, self::INVOICE_DATE_SQL, $from, $to);
        $totalBilled = (float) $billedQuery->sum('amount');

        $paidQuery = $validInvoices()->where('status', Invoice::STATUS_PAID);
        $this->applyPeriod($paidQuery, 'payment_date', $from, $to);
        $totalPaid = (float) $paidQuery->sum('amount');

        // Posisi saat ini (tidak ikut periode): yang masih menunggu dibayar.
        $unpaidQuery   = $validInvoices()->where('status', Invoice::STATUS_UNPAID);
        $totalUnpaid   = (float) (clone $unpaidQuery)->sum('amount');
        $overdueCount  = (clone $unpaidQuery)
            ->whereRaw(self::INVOICE_DATE_SQL . ' < ?', [now()->subDays(self::OVERDUE_DAYS)->toDateString()])
            ->count();

        // Nilai kontrak & bagian yang BELUM PERNAH ditagihkan — dari proyek
        // yang sudah jalan (bukan Draft/Batal). Posisi saat ini, tanpa periode.
        $projects = Project::query()
            ->with('instructingClient', 'namedClient', 'invoices')
            ->whereNotIn('status', [Project::STATUS_DRAFT, Project::STATUS_BATAL])
            ->get();

        $totalContract  = (float) $projects->sum(fn ($p) => $p->total_fee);
        $totalNotBilled = (float) $projects->sum(fn ($p) => max(0, $p->total_fee - (float) $p->invoices->sum('amount')));

        // Proyek yang masih punya sisa tagihan, sisa terbesar dulu. Hanya skema
        // DP di Awal (2026-09-14, feedback user) — proyek Bayar Nanti memang
        // ditagih di akhir, jadi belum layak dianggap sisa tagihan.
        $outstandingProjects = $projects
            ->filter(fn ($p) => $p->payment_scheme !== Project::PAYMENT_SCHEME_LATER)
            ->filter(fn ($p) => $p->remaining_balance > 0)
            ->sortByDesc('remaining_balance')
            ->values();

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
