@extends('layouts.app')

@section('title', 'Dashboard Pembayaran')

@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $periodLabel = $periodActive
        ? trim((request('from') ? \Carbon\Carbon::parse(request('from'))->translatedFormat('d M Y') : '…')
            . ' – ' . (request('to') ? \Carbon\Carbon::parse(request('to'))->translatedFormat('d M Y') : '…'))
        : 'Semua waktu';
@endphp

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    {{-- ===================== HEADER + FILTER PERIODE =====================
         Periode memuat ulang halaman penuh (bukan live search) karena kartu
         ringkasan juga ikut berubah (2026-09-13). --}}
    <div class="flex items-end justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard Pembayaran</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Rekap Invoice/DP &amp; Kwitansi lintas proyek &middot; Periode: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $periodLabel }}</span></p>
        </div>
        <form method="GET" action="{{ route('dashboard.pembayaran') }}" class="flex flex-wrap items-end gap-2">
            <input type="hidden" name="q" value="{{ request('q') }}">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Dari</label>
                <input type="date" name="from" value="{{ request('from') }}" lang="id" class="rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Sampai</label>
                <input type="date" name="to" value="{{ request('to') }}" lang="id" class="rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
            </div>
            <button type="submit" class="px-4 py-2 text-sm rounded-md bg-gray-800 text-white hover:bg-gray-900">Terapkan</button>
            <a href="{{ route('dashboard.pembayaran', ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()]) }}"
               class="px-3 py-2 text-sm rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">Bulan ini</a>
            @if ($periodActive)
                <a href="{{ route('dashboard.pembayaran') }}" class="px-3 py-2 text-sm text-gray-500 underline hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Semua waktu</a>
            @endif
        </form>
    </div>

    {{-- ===================== KARTU ANGKA =====================
         Alur uang dari kiri ke kanan: Nilai Kontrak → Ditagih → Diterima,
         plus yang Belum Ditagih (2026-09-13). Proyek Draft/Batal tidak dihitung. --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Nilai Kontrak</span>
            <div class="mt-1 text-2xl font-bold text-gray-900 tabular-nums break-words dark:text-gray-100">{{ $rp($totalContract) }}</div>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Proyek berjalan &amp; selesai (posisi saat ini).</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Sudah Ditagih</span>
            <div class="mt-1 text-2xl font-bold text-blue-700 tabular-nums break-words dark:text-blue-400">{{ $rp($totalBilled) }}</div>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                {{ $periodActive ? 'Invoice terbit dalam periode.' : 'Seluruh invoice terbit.' }}
                Menunggu dibayar: <span class="font-medium text-rose-600 dark:text-rose-400">{{ $rp($totalUnpaid) }}</span>
            </p>
            @if ($overdueCount > 0)
                <a href="{{ route('dashboard.pembayaran', ['status' => \App\Models\Invoice::STATUS_UNPAID]) }}"
                   class="mt-2 inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700 hover:bg-rose-100 dark:bg-rose-900/30 dark:text-rose-300">
                    ⚠ {{ $overdueCount }} invoice tertunggak &gt; {{ $overdueDays }} hari
                </a>
            @endif
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Sudah Diterima</span>
            <div class="mt-1 text-2xl font-bold text-emerald-700 tabular-nums break-words dark:text-emerald-400">{{ $rp($totalPaid) }}</div>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $periodActive ? 'Dibayar dalam periode (tgl. bayar)' : 'Seluruh invoice dibayar' }} — kwitansi terbit.</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Belum Ditagih</span>
            <div class="mt-1 text-2xl font-bold text-amber-600 tabular-nums break-words dark:text-amber-400">{{ $rp($totalNotBilled) }}</div>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Bagian kontrak yang belum dibuatkan invoice.</p>
        </div>
    </div>

    {{-- ===================== PROYEK DENGAN SISA TAGIHAN ===================== --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden lift dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 pt-6 pb-1">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Proyek dengan Sisa Tagihan <span class="font-normal normal-case">({{ $outstandingProjects->count() }})</span></h2>
            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Sisa = nilai kontrak dikurangi yang sudah dibayar. "Belum Ditagih" = bagian yang belum dibuatkan invoice.</p>
        </div>
        {{-- Maks. ±5 baris lalu scroll, header tetap menempel. Seluruh baris bisa diklik. --}}
        <div class="mt-3 max-h-[18rem] overflow-auto">
        <table class="min-w-[860px] w-full text-sm">
            <thead class="sticky top-0 z-10 bg-gray-50 border-y border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">
                    <th class="px-6 py-3">No. Proposal</th>
                    <th class="px-6 py-3">Pemberi Tugas</th>
                    <th class="px-6 py-3 text-right">Nilai Kontrak</th>
                    <th class="px-6 py-3 w-[190px]">Diterima</th>
                    <th class="px-6 py-3 text-right">Belum Ditagih</th>
                    <th class="px-6 py-3 text-right">Sisa Tagihan</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($outstandingProjects as $p)
                    @php
                        $notBilled = max(0, $p->total_fee - (float) $p->invoices->sum('amount'));
                        $paidPct   = $p->total_fee > 0 ? min(100, round($p->total_paid / $p->total_fee * 100)) : 0;
                    @endphp
                    <tr data-href="{{ route('proposals.show', $p) }}" class="clickable-row cursor-pointer hover:bg-blue-50/40 dark:hover:bg-blue-900/20">
                        <td class="px-6 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-gray-100" title="{{ $p->proposal_number }}">
                            {{ $p->proposal_number_short }}
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $p->instructingClient->client_name ?? '-' }}</td>
                        <td class="px-6 py-3 text-right tabular-nums whitespace-nowrap">{{ $rp($p->total_fee) }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-between gap-2 text-xs tabular-nums">
                                <span class="text-emerald-700 dark:text-emerald-400">{{ $rp($p->total_paid) }}</span>
                                <span class="text-gray-400 dark:text-gray-500">{{ $paidPct }}%</span>
                            </div>
                            <div class="mt-1 h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                                <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $paidPct }}%"></div>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-right tabular-nums whitespace-nowrap {{ $notBilled > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-300 dark:text-gray-600' }}">
                            {{ $notBilled > 0 ? $rp($notBilled) : '—' }}
                        </td>
                        <td class="px-6 py-3 text-right tabular-nums whitespace-nowrap font-semibold text-gray-900 dark:text-gray-100">{{ $rp($p->remaining_balance) }}</td>
                        <td class="px-4 py-3 text-center whitespace-nowrap" data-row-actions>
                            @can('invoices.manage')
                                @if ($notBilled > 0)
                                    <a href="{{ route('proposals.show', $p) }}#section-tagihan"
                                       class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                        + Buat Invoice
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">Menunggu bayar</span>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-400 dark:text-gray-500">
                            🎉 Tidak ada proyek dengan sisa tagihan — semua sudah lunas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{-- ===================== FILTER + DAFTAR INVOICE =====================
         Filter menempel langsung di atas tabel yang disaringnya. --}}
    <div>
    <form id="searchForm" method="GET" action="{{ route('dashboard.pembayaran') }}" class="bg-white rounded-t-lg border border-b-0 border-gray-200 shadow-sm p-4 flex flex-wrap gap-3 items-end dark:bg-gray-800 dark:border-gray-700">
        <input type="hidden" name="from" value="{{ request('from') }}">
        <input type="hidden" name="to" value="{{ request('to') }}">
        <div class="flex-1 min-w-[220px]">
            <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Cari</label>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="No. invoice, kwitansi, proposal, atau nama klien..."
                   class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
        </div>
        <div class="min-w-[180px]">
            <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Status</label>
            <select name="status" class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
                <option value="">Semua Status</option>
                <option value="{{ \App\Models\Invoice::STATUS_UNPAID }}" @selected(request('status') === \App\Models\Invoice::STATUS_UNPAID)>Belum Dibayar</option>
                <option value="{{ \App\Models\Invoice::STATUS_PAID }}" @selected(request('status') === \App\Models\Invoice::STATUS_PAID)>Dibayar</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 text-sm rounded-md bg-gray-800 text-white hover:bg-gray-900">
            Terapkan Filter
        </button>
        @if (request('q') || request('status'))
            <a href="{{ route('dashboard.pembayaran', array_filter(['from' => request('from'), 'to' => request('to')])) }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700/60">
                Reset
            </a>
        @endif
    </form>

    <div id="searchResults" class="space-y-6">
        @include('dashboard._invoice_results')
    </div>
    </div>
</div>

{{-- ===================== MODAL TANDAI DIBAYAR ===================== --}}
@can('invoices.manage')
<div id="markPaidModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6 dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Tandai Dibayar</h2>
            <button type="button" onclick="closeMarkPaidModal()" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-200">&times;</button>
        </div>
        <p id="markPaidInfo" class="mb-3 text-sm text-gray-600 dark:text-gray-300"></p>
        <form id="markPaidForm" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Pembayaran Diterima</label>
                <input type="date" name="payment_date" id="mark_paid_date" value="{{ now()->toDateString() }}" required lang="id"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Kwitansi otomatis diterbitkan dengan tanggal ini.</p>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeMarkPaidModal()"
                        class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700/60">Batal</button>
                <button type="submit" class="px-4 py-2 text-sm rounded-md bg-green-600 text-white hover:bg-green-700">Konfirmasi Dibayar</button>
            </div>
        </form>
    </div>
</div>
@endcan

@push('scripts')
    <script>
        initLiveSearch({ form: '#searchForm', results: '#searchResults' });

        // Klik baris = buka detail proyek; kolom aksi dikecualikan. Delegasi di
        // document karena tabel invoice diganti total oleh live search.
        document.addEventListener('click', function (e) {
            const row = e.target.closest('.clickable-row');
            if (!row || e.target.closest('[data-row-actions]')) return;
            window.location = row.dataset.href;
        });

        function openMarkPaidModal(actionUrl, label) {
            document.getElementById('markPaidForm').action = actionUrl;
            document.getElementById('markPaidInfo').textContent = label;
            document.getElementById('mark_paid_date').value = new Date().toISOString().split('T')[0];
            document.getElementById('markPaidModal').classList.remove('hidden');
            document.getElementById('markPaidModal').classList.add('flex');
        }
        function closeMarkPaidModal() {
            document.getElementById('markPaidModal').classList.add('hidden');
            document.getElementById('markPaidModal').classList.remove('flex');
        }
    </script>
@endpush
@endsection
