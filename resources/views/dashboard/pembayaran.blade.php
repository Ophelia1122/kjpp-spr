@extends('layouts.app')

@section('title', 'Dashboard Pembayaran')

@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $periodLabel = $periodActive
        ? trim((request('from') ? \Carbon\Carbon::parse(request('from'))->translatedFormat('d M Y') : '…')
            . ' – ' . (request('to') ? \Carbon\Carbon::parse(request('to'))->translatedFormat('d M Y') : '…'))
        : '12 bulan terakhir';
@endphp

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    {{-- ===================== HEADER + FILTER PERIODE =====================
         Periode memuat ulang halaman penuh (bukan live search) karena kartu
         ringkasan juga ikut berubah (2026-09-13). --}}
    {{-- Header + filter periode dalam satu kartu (2026-09-20, feedback user). --}}
    @php
        $dateInput = 'rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100';
    @endphp
    <x-page-header title="Dashboard Pembayaran"
        subtitle='Rekap Invoice/DP &amp; Kwitansi lintas proyek &middot; Periode: <span class="font-medium text-gray-800 dark:text-gray-200">{{ $periodLabel }}</span>'>
    <form method="GET" action="{{ route('dashboard.pembayaran') }}"
              class="flex flex-wrap items-end gap-2">
            <input type="hidden" name="q" value="{{ request('q') }}">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <input type="hidden" name="sort" value="{{ request('sort') }}">
            <input type="hidden" name="dir" value="{{ request('dir') }}">
            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1 dark:text-gray-300">Dari</label>
                <input type="date" name="from" value="{{ request('from') }}" lang="id" class="{{ $dateInput }}">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1 dark:text-gray-300">Sampai</label>
                <input type="date" name="to" value="{{ request('to') }}" lang="id" class="{{ $dateInput }}">
            </div>
            <button type="submit" class="inline-flex h-[38px] items-center justify-center gap-1.5 rounded-md border border-blue-600 bg-blue-600 px-4 text-sm font-medium text-white transition hover:border-blue-700 hover:bg-blue-700">Terapkan</button>
            <a href="{{ route('dashboard.pembayaran', ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()]) }}"
               class="inline-flex h-[38px] items-center justify-center gap-1.5 rounded-md border border-gray-300 px-4 text-sm font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">Bulan ini</a>
            @if ($periodActive)
                <a href="{{ route('dashboard.pembayaran') }}" class="px-3 py-2 text-sm text-gray-500 underline hover:text-gray-700 dark:text-gray-500 dark:hover:text-gray-200">Semua waktu</a>
            @endif
            {{-- Pengaturan nomor terakhir Invoice & Kwitansi (2026-09-14, feedback user). --}}
            @can('invoices.manage')
                <button type="button" onclick="openNumberingModal()" title="Pengaturan nomor invoice & kwitansi" aria-label="Pengaturan nomor invoice & kwitansi"
                        class="inline-flex h-[38px] items-center justify-center gap-1.5 rounded-md border border-gray-300 px-3 text-xs font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 sm:w-[38px] sm:px-0 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                    <span class="sm:hidden">Nomor</span>
                </button>
            @endcan
    </form>
    </x-page-header>

    {{-- ===================== KARTU ANGKA =====================
         Alur uang dari kiri ke kanan: Nilai Kontrak → Ditagih → Diterima,
         plus yang Belum Ditagih (2026-09-13). Proyek Draft/Batal tidak dihitung. --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-500">Nilai Kontrak</span>
            <div class="mt-1 text-2xl font-bold text-gray-900 tabular-nums break-words dark:text-gray-100">{{ $rp($totalContract) }}</div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Proyek berjalan &amp; selesai (posisi saat ini).</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-500">Sudah Ditagih</span>
            <div class="mt-1 text-2xl font-bold text-blue-700 tabular-nums break-words dark:text-blue-400">{{ $rp($totalBilled) }}</div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $periodActive ? 'Invoice terbit dalam periode.' : 'Invoice terbit 12 bulan terakhir.' }}
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
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-500">Sudah Diterima</span>
            <div class="mt-1 text-2xl font-bold text-emerald-700 tabular-nums break-words dark:text-emerald-400">{{ $rp($totalPaid) }}</div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $periodActive ? 'Dibayar dalam periode (tgl. bayar)' : 'Dibayar 12 bulan terakhir' }} — kwitansi terbit.</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-500">Belum Ditagih</span>
            <div class="mt-1 text-2xl font-bold text-amber-600 tabular-nums break-words dark:text-amber-400">{{ $rp($totalNotBilled) }}</div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Bagian kontrak yang belum dibuatkan invoice.</p>
        </div>
    </div>

    {{-- ===================== PROYEK DENGAN SISA TAGIHAN ===================== --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden lift dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 pt-6 pb-1">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-500">Proyek dengan Sisa Pelunasan <span class="font-normal normal-case">({{ $outstandingProjects->count() }})</span></h2>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Sisa = nilai kontrak dikurangi yang sudah dibayar. "Belum Ditagih" = bagian yang belum dibuatkan invoice.</p>
        </div>
        {{-- Maks. ±5 baris lalu scroll, header tetap menempel. Seluruh baris bisa diklik. --}}
        <div class="hidden md:block mt-3 max-h-[18rem] overflow-auto">
        <table class="min-w-[860px] w-full text-sm">
            <thead class="sticky top-0 z-10 bg-gray-50 border-y border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-500">
                    <th class="px-6 py-3">No. Proposal</th>
                    <th class="px-6 py-3">Nama Klien</th>
                    <th class="px-6 py-3 text-right">Nilai Kontrak</th>
                    <th class="px-6 py-3 w-[190px]">Diterima</th>
                    <th class="px-6 py-3 text-right">Belum Ditagih</th>
                    <th class="px-6 py-3 text-right">Sisa Pelunasan</th>
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
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-500">{{ $p->effective_client_name ?: '-' }}</td>
                        <td class="px-6 py-3 text-right tabular-nums whitespace-nowrap text-gray-900 dark:text-gray-100">{{ $rp($p->total_fee) }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-between gap-2 text-xs tabular-nums">
                                <span class="text-emerald-700 dark:text-emerald-400 tabular-nums">{{ $rp($p->total_paid) }}</span>
                                <span class="text-gray-500 dark:text-gray-400">{{ $paidPct }}%</span>
                            </div>
                            <div class="mt-1 h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                                <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $paidPct }}%"></div>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-right tabular-nums whitespace-nowrap {{ $notBilled > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-300 dark:text-gray-600' }}">
                            <span class="tabular-nums">{{ $notBilled > 0 ? $rp($notBilled) : '—' }}</span>
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
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Menunggu bayar</span>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                            🎉 Tidak ada proyek dengan sisa pelunasan — semua sudah lunas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        {{-- Ponsel: kartu proyek dengan sisa tagihan (2026-09-15, feedback user). --}}
        <div class="mt-3 max-h-[28rem] divide-y divide-gray-100 overflow-y-auto border-t border-gray-100 md:hidden dark:divide-gray-700 dark:border-gray-700">
            @forelse ($outstandingProjects as $p)
                @php
                    $notBilled = max(0, $p->total_fee - (float) $p->invoices->sum('amount'));
                    $paidPct   = $p->total_fee > 0 ? min(100, round($p->total_paid / $p->total_fee * 100)) : 0;
                @endphp
                <div data-href="{{ route('proposals.show', $p) }}"
                     onclick="if (!event.target.closest('[data-row-actions]')) location.href = this.dataset.href"
                     class="cursor-pointer space-y-2 px-4 py-3 hover:bg-blue-50/40 dark:hover:bg-blue-900/20">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $p->proposal_number_short }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-500">{{ $p->effective_client_name ?: '-' }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">Sisa pelunasan</p>
                            <p class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $rp($p->remaining_balance) }}</p>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-xs tabular-nums">
                            <span class="text-emerald-700 dark:text-emerald-400 tabular-nums">Diterima {{ $rp($p->total_paid) }}</span>
                            <span class="text-gray-500 dark:text-gray-400 tabular-nums">{{ $paidPct }}% dari {{ $rp($p->total_fee) }}</span>
                        </div>
                        <div class="mt-1 h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                            <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $paidPct }}%"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs {{ $notBilled > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400' }}">
                            Belum ditagih <span class="tabular-nums">{{ $notBilled > 0 ? $rp($notBilled) : '—' }}</span>
                        </span>
                        <span data-row-actions>
                            @can('invoices.manage')
                                @if ($notBilled > 0)
                                    <a href="{{ route('proposals.show', $p) }}#section-tagihan"
                                       class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">+ Buat Invoice</a>
                                @else
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Menunggu bayar</span>
                                @endif
                            @endcan
                        </span>
                    </div>
                </div>
            @empty
                <p class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">🎉 Tidak ada proyek dengan sisa pelunasan — semua sudah lunas.</p>
            @endforelse
        </div>
    </div>

    {{-- ===================== FILTER + DAFTAR INVOICE =====================
         Filter menempel langsung di atas tabel yang disaringnya. --}}
    <div>
    <form id="searchForm" method="GET" action="{{ route('dashboard.pembayaran') }}" class="bg-white rounded-t-lg border border-b-0 border-gray-200 shadow-sm p-4 flex flex-wrap gap-3 items-end dark:bg-gray-800 dark:border-gray-700">
        <input type="hidden" name="from" value="{{ request('from') }}">
        <input type="hidden" name="to" value="{{ request('to') }}">
        <input type="hidden" name="sort" value="{{ request('sort') }}">
        <input type="hidden" name="dir" value="{{ request('dir') }}">
        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
        <div class="flex-1 min-w-[220px]">
            <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-500">Cari</label>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="No. invoice, kwitansi, proposal, atau nama klien..."
                   class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
        </div>
        <div class="min-w-[180px]">
            <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-500">Status</label>
            <select name="status" class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
                <option value="">Semua Status</option>
                <option value="{{ \App\Models\Invoice::STATUS_UNPAID }}" @selected(request('status') === \App\Models\Invoice::STATUS_UNPAID)>Belum Dibayar</option>
                <option value="overdue" @selected(request('status') === 'overdue')>Tertunggak &gt; {{ $overdueDays }} hari</option>
                <option value="{{ \App\Models\Invoice::STATUS_PAID }}" @selected(request('status') === \App\Models\Invoice::STATUS_PAID)>Dibayar</option>
            </select>
        </div>
        @if (request('q') || request('status'))
            <a href="{{ route('dashboard.pembayaran', array_filter(['from' => request('from'), 'to' => request('to')])) }}" class="inline-flex h-[38px] items-center justify-center gap-1.5 rounded-md border border-gray-300 px-4 text-sm font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">
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
            <button type="button" onclick="closeMarkPaidModal()" class="text-gray-500 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-200">&times;</button>
        </div>
        <p id="markPaidInfo" class="mb-3 text-sm text-gray-600 dark:text-gray-300"></p>
        <form id="markPaidForm" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Pembayaran Diterima</label>
                <input type="date" name="payment_date" id="mark_paid_date" value="{{ now()->toDateString() }}" required lang="id"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kwitansi otomatis diterbitkan dengan tanggal ini.</p>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeMarkPaidModal()"
                        class="inline-flex h-[38px] items-center justify-center gap-1.5 rounded-md border border-gray-300 px-4 text-sm font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">Batal</button>
                <button type="submit" class="px-4 py-2 text-sm rounded-md bg-green-600 text-white hover:bg-green-700">Konfirmasi Dibayar</button>
            </div>
        </form>
    </div>
</div>
@endcan

{{-- ===================== MODAL PENOMORAN ===================== --}}
@can('invoices.manage')
<div id="numberingModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 dark:bg-gray-800">
        <div class="flex justify-between items-center mb-2">
            <h2 class="text-lg font-semibold">Penomoran Invoice &amp; Kwitansi</h2>
            <button type="button" onclick="closeNumberingModal()" class="text-gray-500 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-200" aria-label="Tutup">&times;</button>
        </div>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-500">
            Isi nomor urut <b>terakhir</b> yang sudah terbit tahun {{ now()->year }}, termasuk yang dibuat di luar aplikasi.
            Nomor berikutnya melanjutkan dari angka ini.
        </p>
        <form method="POST" action="{{ route('dashboard.pembayaran.numbering') }}" class="space-y-4">
            @csrf
            @method('PUT')
            @foreach (['invoice' => 'invoice_last', 'kwitansi' => 'kwitansi_last'] as $type => $field)
                @php $n = $numbering[$type]; @endphp
                <div>
                    <label for="{{ $field }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor terakhir {{ $n['label'] }}</label>
                    <input type="number" id="{{ $field }}" name="{{ $field }}" min="0" max="999999" step="1" required
                           value="{{ old($field, $n['last']) }}"
                           data-numbering-input data-issued="{{ $n['issued'] }}" data-tail="{{ $n['tail'] }}" data-preview="{{ $field }}_preview"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm tabular-nums dark:border-gray-600">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                        Berikutnya: <span id="{{ $field }}_preview" class="font-semibold text-gray-800 tabular-nums dark:text-gray-200">{{ $n['next'] }}</span>
                    </p>
                    @if ($n['issued'] > 0)
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Nomor tertinggi yang sudah terbit di aplikasi tahun ini: {{ $n['issued'] }}. Angka di bawahnya tidak dipakai supaya nomor tidak dobel.</p>
                    @endif
                </div>
            @endforeach
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeNumberingModal()"
                        class="inline-flex h-[38px] items-center justify-center gap-1.5 rounded-md border border-gray-300 px-4 text-sm font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">Batal</button>
                <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Simpan</button>
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

        // ---------- Modal penomoran ----------
        function openNumberingModal() {
            document.getElementById('numberingModal').classList.remove('hidden');
            document.getElementById('numberingModal').classList.add('flex');
        }
        function closeNumberingModal() {
            document.getElementById('numberingModal').classList.add('hidden');
            document.getElementById('numberingModal').classList.remove('flex');
        }
        // Pratinjau nomor berikutnya = max(nomor terbit di aplikasi, isian) + 1.
        document.querySelectorAll('[data-numbering-input]').forEach(function (input) {
            input.addEventListener('input', function () {
                const typed = parseInt(input.value, 10);
                const last = Math.max(parseInt(input.dataset.issued, 10) || 0, isNaN(typed) ? 0 : typed);
                document.getElementById(input.dataset.preview).textContent =
                    String(last + 1).padStart(3, '0') + '/' + input.dataset.tail;
            });
        });
        @if ($errors->has('invoice_last') || $errors->has('kwitansi_last'))
            openNumberingModal();
        @endif
    </script>
@endpush
@endsection
