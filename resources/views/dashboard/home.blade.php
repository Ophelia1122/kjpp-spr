@extends('layouts.app')

@section('title', 'Beranda')

@php
    // Nada warna per jenis item pada daftar "butuh perhatian".
    $toneText = [
        'red'   => 'text-rose-600',
        'amber' => 'text-amber-600',
        'slate' => 'text-gray-500',
    ];
@endphp

@section('content')
<div class="max-w-5xl mx-auto py-8 space-y-6">

    {{-- ===================== HEADER ===================== --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Halo, {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }} 👋</h1>
        <p class="text-sm text-gray-500">Ringkasan pekerjaan &middot; {{ now()->locale('id')->translatedFormat('l, d F Y') }}</p>
        <p class="mt-1 text-xs text-gray-400">
            👤 Hanya proyek yang ditugaskan kepada Anda sebagai penilai lapangan.
            Semua proyek ada di <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800">Dashboard Project</a>
            &amp; <a href="{{ route('timeline') }}" class="text-blue-600 hover:text-blue-800">Timeline Project</a>.
        </p>
    </div>

    {{-- ===================== KARTU ANGKA ===================== --}}
    <div class="grid grid-cols-2 {{ $canSeeInvoices ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }} gap-4">

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Proyek aktif</div>
            <div class="mt-1 text-3xl font-bold text-gray-900 tabular-nums">{{ $activeCount }}</div>
            <p class="mt-1 text-xs text-gray-400">Belum selesai &amp; tidak dibatalkan</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Lewat deadline</div>
            <div class="mt-1 text-3xl font-bold tabular-nums {{ $overdueCount > 0 ? 'text-rose-600' : 'text-gray-900' }}">{{ $overdueCount }}</div>
            <p class="mt-1 text-xs text-gray-400">Melewati target draf laporan</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Survei minggu ini</div>
            <div class="mt-1 text-3xl font-bold text-gray-900 tabular-nums">{{ $surveyWeekCount }}</div>
            <p class="mt-1 text-xs text-gray-400">{{ $weekRange }}</p>
        </div>

        @if ($canSeeInvoices)
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift">
                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Invoice belum lunas</div>
                <div class="mt-1 text-3xl font-bold tabular-nums {{ $unpaidCount > 0 ? 'text-amber-500' : 'text-gray-900' }}">{{ $unpaidCount }}</div>
                <p class="mt-1 text-xs text-gray-400">Menunggu pembayaran klien</p>
            </div>
        @endif
    </div>

    {{-- ===================== BUTUH PERHATIAN HARI INI ===================== --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden lift">
        <div class="px-5 pt-5 pb-3 flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Butuh perhatian hari ini</h2>
            <a href="{{ route('timeline') }}" class="text-xs font-medium text-blue-600 hover:text-blue-800 whitespace-nowrap">
                📅 Lihat Timeline
            </a>
        </div>

        @forelse ($attention as $item)
            <a href="{{ route('proposals.show', $item['project']) }}"
               class="flex items-center justify-between gap-4 px-5 py-3 border-t border-gray-100 hover:bg-blue-50/40">
                <span class="min-w-0">
                    <span class="block text-sm font-medium text-gray-900 truncate">{{ $item['project']->proposal_number }}</span>
                    <span class="block text-xs text-gray-500 truncate">{{ $item['project']->instructingClient->client_name ?? '-' }}</span>
                </span>
                <span class="text-xs font-medium whitespace-nowrap {{ $toneText[$item['tone']] ?? 'text-gray-500' }}">
                    {{ $item['note'] }}
                </span>
            </a>
        @empty
            <div class="px-5 py-10 text-center text-sm text-gray-400 border-t border-gray-100">
                @if ($activeCount === 0)
                    Belum ada proyek yang ditugaskan kepada Anda.
                @else
                    🎉 Tidak ada yang mendesak. Proyek Anda masih dalam jadwal.
                @endif
            </div>
        @endforelse
    </div>

    {{-- ===================== PINTASAN ===================== --}}
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
            📊 Dashboard Project
        </a>
        <a href="{{ route('timeline') }}"
           class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
            📅 Timeline Project
        </a>
        @can('proposals.manage')
            <a href="{{ route('proposals.create') }}"
               class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                + Buat Proposal Baru
            </a>
        @endcan
    </div>
</div>
@endsection
