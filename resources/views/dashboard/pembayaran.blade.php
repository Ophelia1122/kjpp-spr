@extends('layouts.app')

@section('title', 'Dashboard Pembayaran')

@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
@endphp

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    {{-- ===================== HEADER ===================== --}}
    <div class="flex items-end justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard Pembayaran</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Rekap Invoice/DP &amp; Kwitansi Pelunasan lintas proyek.</p>
        </div>
    </div>

    {{-- ===================== KARTU ANGKA ===================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Total Ditagihkan</span>
            <div class="mt-1 text-2xl font-bold text-gray-900 tabular-nums break-words dark:text-gray-100">{{ $rp($totalBilled) }}</div>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Seluruh invoice yang pernah diterbitkan.</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Sudah Diterima (Paid)</span>
            <div class="mt-1 text-2xl font-bold text-emerald-700 tabular-nums break-words dark:text-emerald-400">{{ $rp($totalPaid) }}</div>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Sudah terbit Kwitansi Pelunasan.</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Belum Dibayar</span>
            <div class="mt-1 text-2xl font-bold text-amber-600 tabular-nums break-words dark:text-amber-400">{{ $rp($totalUnpaid) }}</div>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Invoice terbit, menunggu verifikasi Keuangan.</p>
        </div>
    </div>

    {{-- ===================== PROYEK DENGAN SISA TAGIHAN ===================== --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto lift dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 pt-6 pb-1">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Proyek dengan Sisa Tagihan</h2>
            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Sistem tidak peduli sudah berapa termin — ini murni total_fee dikurangi yang sudah Paid.</p>
        </div>
        <table class="min-w-[640px] w-full text-sm mt-3">
            <thead class="bg-gray-50 border-y border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">
                    <th class="px-6 py-3">No. Proposal</th>
                    <th class="px-6 py-3">Pemberi Tugas</th>
                    <th class="px-6 py-3 text-right">Nilai Kontrak</th>
                    <th class="px-6 py-3 text-right">Sudah Dibayar</th>
                    <th class="px-6 py-3 text-right">Sisa Tagihan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($outstandingProjects as $p)
                    <tr class="hover:bg-blue-50/40 dark:hover:bg-blue-900/20">
                        <td class="px-6 py-3 font-medium text-gray-900 dark:text-gray-100">
                            <a href="{{ route('proposals.show', $p) }}" class="hover:text-blue-700 dark:hover:text-blue-300">{{ $p->proposal_number }}</a>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $p->instructingClient->client_name ?? '-' }}</td>
                        <td class="px-6 py-3 text-right tabular-nums whitespace-nowrap">{{ $rp($p->total_fee) }}</td>
                        <td class="px-6 py-3 text-right tabular-nums whitespace-nowrap text-emerald-700 dark:text-emerald-400">{{ $rp($p->total_paid) }}</td>
                        <td class="px-6 py-3 text-right tabular-nums whitespace-nowrap font-semibold text-amber-600 dark:text-amber-400">{{ $rp($p->remaining_balance) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-400 dark:text-gray-500">
                            🎉 Tidak ada proyek dengan sisa tagihan — semua sudah lunas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ===================== FILTER & PENCARIAN ===================== --}}
    <form id="searchForm" method="GET" action="{{ route('dashboard.pembayaran') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 flex flex-wrap gap-3 items-end dark:bg-gray-800 dark:border-gray-700">
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
                @foreach ($statusOptions as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 text-sm rounded-md bg-gray-800 text-white hover:bg-gray-900">
            Terapkan Filter
        </button>
        @if (request('q') || request('status'))
            <a href="{{ route('dashboard.pembayaran') }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700/60">
                Reset
            </a>
        @endif
    </form>

    {{-- ===================== DAFTAR INVOICE / KWITANSI ===================== --}}
    <div id="searchResults" class="space-y-6">
        @include('dashboard._invoice_results')
    </div>
</div>

@push('scripts')
    <script>
        initLiveSearch({ form: '#searchForm', results: '#searchResults' });
    </script>
@endpush
@endsection
