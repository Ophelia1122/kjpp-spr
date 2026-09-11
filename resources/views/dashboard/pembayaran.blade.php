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
            <h1 class="text-2xl font-bold text-gray-900">Dashboard Pembayaran</h1>
            <p class="text-sm text-gray-500">Rekap Invoice/DP &amp; Kwitansi Pelunasan lintas proyek.</p>
        </div>
        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
            📈 Buka Dashboard
        </a>
    </div>

    {{-- ===================== KARTU ANGKA ===================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Ditagihkan</span>
            <div class="mt-1 text-2xl font-bold text-gray-900 tabular-nums break-words">{{ $rp($totalBilled) }}</div>
            <p class="mt-1 text-xs text-gray-400">Seluruh invoice yang pernah diterbitkan.</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Sudah Diterima (Paid)</span>
            <div class="mt-1 text-2xl font-bold text-emerald-700 tabular-nums break-words">{{ $rp($totalPaid) }}</div>
            <p class="mt-1 text-xs text-gray-400">Sudah terbit Kwitansi Pelunasan.</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Belum Dibayar</span>
            <div class="mt-1 text-2xl font-bold text-amber-600 tabular-nums break-words">{{ $rp($totalUnpaid) }}</div>
            <p class="mt-1 text-xs text-gray-400">Invoice terbit, menunggu verifikasi Keuangan.</p>
        </div>
    </div>

    {{-- ===================== PROYEK DENGAN SISA TAGIHAN ===================== --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto lift">
        <div class="px-6 pt-6 pb-1">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Proyek dengan Sisa Tagihan</h2>
            <p class="mt-0.5 text-xs text-gray-400">Sistem tidak peduli sudah berapa termin — ini murni total_fee dikurangi yang sudah Paid.</p>
        </div>
        <table class="min-w-[640px] w-full text-sm mt-3">
            <thead class="bg-gray-50 border-y border-gray-200">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    <th class="px-6 py-3">No. Proposal</th>
                    <th class="px-6 py-3">Pemberi Tugas</th>
                    <th class="px-6 py-3 text-right">Nilai Kontrak</th>
                    <th class="px-6 py-3 text-right">Sudah Dibayar</th>
                    <th class="px-6 py-3 text-right">Sisa Tagihan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($outstandingProjects as $p)
                    <tr class="hover:bg-blue-50/40">
                        <td class="px-6 py-3 font-medium text-gray-900">
                            <a href="{{ route('proposals.show', $p) }}" class="hover:text-blue-700">{{ $p->proposal_number }}</a>
                        </td>
                        <td class="px-6 py-3 text-gray-600">{{ $p->instructingClient->client_name ?? '-' }}</td>
                        <td class="px-6 py-3 text-right tabular-nums whitespace-nowrap">{{ $rp($p->total_fee) }}</td>
                        <td class="px-6 py-3 text-right tabular-nums whitespace-nowrap text-emerald-700">{{ $rp($p->total_paid) }}</td>
                        <td class="px-6 py-3 text-right tabular-nums whitespace-nowrap font-semibold text-amber-600">{{ $rp($p->remaining_balance) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-400">
                            🎉 Tidak ada proyek dengan sisa tagihan — semua sudah lunas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ===================== FILTER & PENCARIAN ===================== --}}
    <form method="GET" action="{{ route('dashboard.pembayaran') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[220px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Cari</label>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="No. invoice, kwitansi, proposal, atau nama klien..."
                   class="w-full rounded-md border-gray-300 shadow-sm text-sm">
        </div>
        <div class="min-w-[180px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
            <select name="status" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
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
            <a href="{{ route('dashboard.pembayaran') }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50">
                Reset
            </a>
        @endif
    </form>

    {{-- ===================== DAFTAR INVOICE / KWITANSI ===================== --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto lift">
        <table class="min-w-[900px] w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    <th class="px-4 py-3">No. Invoice</th>
                    <th class="px-4 py-3">No. Kwitansi</th>
                    <th class="px-4 py-3">Proyek / Klien</th>
                    <th class="px-4 py-3">Keterangan</th>
                    <th class="px-4 py-3 text-right">Nominal</th>
                    <th class="px-4 py-3 whitespace-nowrap">Status</th>
                    <th class="px-4 py-3 text-center w-24">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($invoices as $inv)
                    <tr class="hover:bg-blue-50/40">
                        <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">{{ $inv->invoice_number }}</td>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $inv->kwitansi_number ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            <a href="{{ route('proposals.show', $inv->project) }}" class="font-medium text-gray-900 hover:text-blue-700">
                                {{ $inv->project->proposal_number }}
                            </a>
                            <div class="text-xs text-gray-400">{{ $inv->project->instructingClient->client_name ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $inv->term_description ?? $inv->invoice_type }}</td>
                        <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap">{{ $rp($inv->amount) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold {{ $inv->status === 'Paid' ? 'bg-emerald-100 text-emerald-700 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300' }}">
                                {{ $inv->status === 'Paid' ? 'Lunas' : 'Belum Dibayar' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-center items-center gap-1.5">
                                @can('invoices.view')
                                    <a href="{{ route('invoices.exportInvoice', $inv) }}" title="Cetak Invoice"
                                       class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900">
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                        </svg>
                                    </a>
                                    @if ($inv->status === 'Paid')
                                        <a href="{{ route('invoices.exportKwitansi', $inv) }}" title="Cetak Kwitansi"
                                           class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-teal-100 hover:text-teal-700">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                            </svg>
                                        </a>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-400">Belum ada invoice yang cocok dengan filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $invoices->links() }}</div>
</div>
@endsection
