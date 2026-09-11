<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\Request;

/**
 * Dashboard Pembayaran — satu tempat memantau SEMUA Invoice/DP & Kwitansi
 * Pelunasan lintas proyek. Model tagihan sekarang fleksibel (bukan lagi
 * cuma 2 tahap DP+Pelunasan), jadi dashboard ini menjumlah langsung dari
 * tabel invoices & accessor sisa tagihan Project (bukan asumsi jenis).
 */
class PaymentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('project.instructingClient')->latest('id');

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

        $invoices = $query->paginate(20)->withQueryString();

        // Ringkasan GLOBAL (tidak ikut filter/paginasi di atas) supaya
        // kartu angka tetap stabil apa pun pencarian yang sedang aktif.
        $amounts = Invoice::selectRaw('status, SUM(amount) as total')->groupBy('status')->pluck('total', 'status');
        $totalBilled = (float) ($amounts->sum() ?? 0);
        $totalPaid   = (float) ($amounts[Invoice::STATUS_PAID] ?? 0);
        $totalUnpaid = (float) ($amounts[Invoice::STATUS_UNPAID] ?? 0);

        // Proyek yang masih punya sisa tagihan (belum lunas), diurutkan
        // dari sisa terbesar — "dijelaskan pada proyek tersebut berapa
        // yang kurang" langsung kelihatan tanpa buka satu-satu.
        $outstandingProjects = Project::query()
            ->with('instructingClient', 'invoices')
            ->whereNotIn('status', [Project::STATUS_DRAFT, Project::STATUS_BATAL])
            ->get()
            ->filter(fn ($p) => $p->remaining_balance > 0)
            ->sortByDesc('remaining_balance')
            ->values();

        return view('dashboard.pembayaran', [
            'invoices'            => $invoices,
            'totalBilled'         => $totalBilled,
            'totalPaid'           => $totalPaid,
            'totalUnpaid'         => $totalUnpaid,
            'outstandingProjects' => $outstandingProjects,
            'statusOptions'       => [Invoice::STATUS_UNPAID, Invoice::STATUS_PAID],
        ]);
    }
}
