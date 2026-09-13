<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Project;
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
        $query = Invoice::with('project.instructingClient')
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [Invoice::STATUS_UNPAID])
            ->orderByRaw('CASE WHEN status = ? THEN ' . self::INVOICE_DATE_SQL . ' END asc', [Invoice::STATUS_UNPAID])
            ->orderByDesc('payment_date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
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

        $invoices = $query->paginate(20)->withQueryString();

        if ($request->ajax()) {
            return view('dashboard._invoice_results', [
                'invoices'    => $invoices,
                'overdueDays' => self::OVERDUE_DAYS,
            ]);
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
            ->with('instructingClient', 'invoices')
            ->whereNotIn('status', [Project::STATUS_DRAFT, Project::STATUS_BATAL])
            ->get();

        $totalContract  = (float) $projects->sum(fn ($p) => $p->total_fee);
        $totalNotBilled = (float) $projects->sum(fn ($p) => max(0, $p->total_fee - (float) $p->invoices->sum('amount')));

        // Proyek yang masih punya sisa tagihan, sisa terbesar dulu.
        $outstandingProjects = $projects
            ->filter(fn ($p) => $p->remaining_balance > 0)
            ->sortByDesc('remaining_balance')
            ->values();

        return view('dashboard.pembayaran', [
            'invoices'            => $invoices,
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
        ]);
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
