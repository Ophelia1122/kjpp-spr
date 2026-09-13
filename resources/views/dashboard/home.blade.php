@extends('layouts.app')

@section('title', 'Beranda')

@php
    // Nada warna per jenis item pada daftar "butuh perhatian".
    $toneText = [
        'red'   => 'text-rose-600 dark:text-rose-400',
        'amber' => 'text-amber-600 dark:text-amber-400',
        'slate' => 'text-gray-500 dark:text-gray-400',
    ];
@endphp

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    {{-- ===================== HEADER =====================
         Tombol "Buat Proposal Baru" dipindah ke kanan atas (2026-09-13,
         feedback user) — sebelumnya melayang sendirian di bawah halaman. --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Halo, {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }} 👋</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Ringkasan pekerjaan &middot; {{ now()->locale('id')->translatedFormat('l, d F Y') }}</p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                @if ($officeWide)
                    🏢 Angka seluruh kantor (semua proyek aktif).
                @else
                    👤 Hanya proyek yang ditugaskan kepada Anda sebagai penilai lapangan.
                @endif
                Semua proyek ada di <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">List Project</a>
                &amp; <a href="{{ route('timeline') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Timeline Project</a>.
            </p>
        </div>
        @can('proposals.manage')
            <a href="{{ route('proposals.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white shadow-sm hover:bg-blue-700">
                + Buat Proposal Baru
            </a>
        @endcan
    </div>

    {{-- ===================== KARTU ANGKA ===================== --}}
    @php
        $extraTiles = ($canSeeInvoices ? 1 : 0) + ($completedCount !== null ? 1 : 0);
        $gridColsClass = match (3 + $extraTiles) {
            5 => 'lg:grid-cols-5',
            4 => 'lg:grid-cols-4',
            default => 'lg:grid-cols-3',
        };
    @endphp
    <div class="grid grid-cols-2 {{ $gridColsClass }} gap-4">

        {{-- Kartu bisa diklik (2026-09-13, feedback user) — membuka List
             Project yang sudah difilter sesuai isi kartunya. --}}
        @php
            $cardLink = 'block bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift hover:border-blue-300 dark:bg-gray-800 dark:border-gray-700 dark:hover:border-blue-700';
            $listBase = ['mine' => $officeWide ? 0 : 1];
        @endphp
        <a href="{{ route('dashboard', $listBase + ['focus' => 'active']) }}" class="{{ $cardLink }}">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Proyek aktif</div>
            <div class="mt-1 text-3xl font-bold text-gray-900 tabular-nums dark:text-gray-100">{{ $activeCount }}</div>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Belum selesai &amp; tidak dibatalkan</p>
        </a>

        <a href="{{ route('dashboard', $listBase + ['focus' => 'overdue']) }}" class="{{ $cardLink }}">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Lewat deadline</div>
            <div class="mt-1 text-3xl font-bold tabular-nums {{ $overdueCount > 0 ? 'text-rose-600' : 'text-gray-900 dark:text-gray-100' }}">{{ $overdueCount }}</div>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Melewati target draf laporan</p>
        </a>

        <a href="{{ route('dashboard', $listBase + ['focus' => 'survey_week']) }}" class="{{ $cardLink }}">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Survei minggu ini</div>
            <div class="mt-1 text-3xl font-bold text-gray-900 tabular-nums dark:text-gray-100">{{ $surveyWeekCount }}</div>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $weekRange }}</p>
        </a>

        @if ($canSeeInvoices)
            <a href="{{ route('dashboard.pembayaran', ['status' => \App\Models\Invoice::STATUS_UNPAID]) }}" class="{{ $cardLink }}">
                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Invoice belum lunas</div>
                <div class="mt-1 text-3xl font-bold tabular-nums {{ $unpaidCount > 0 ? 'text-amber-500' : 'text-gray-900 dark:text-gray-100' }}">{{ $unpaidCount }}</div>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Menunggu pembayaran klien</p>
            </a>
        @endif

        {{-- Khusus jabatan Penilai/Pelaksana Inspeksi (dihitung dari penugasan
             lapangan) & Reviewer (dihitung dari proyek yang pernah direview). --}}
        @if ($completedCount !== null)
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Proyek selesai</div>
                <div class="mt-1 text-3xl font-bold text-emerald-600 tabular-nums dark:text-emerald-400">{{ $completedCount }}</div>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                    {{ auth()->user()->jabatan === \App\Models\User::JABATAN_REVIEWER ? 'Yang pernah Anda review' : 'Yang Anda tangani sebagai penilai lapangan' }}
                </p>
            </div>
        @endif
    </div>

    {{-- ===================== MENUNGGU REVIEW ANDA (khusus Reviewer & Administrator) ===================== --}}
    @if (auth()->user()->jabatan === \App\Models\User::JABATAN_REVIEWER || auth()->user()->isAdministrator())
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden lift dark:bg-gray-800 dark:border-gray-700">
            <div class="px-5 pt-5 pb-3">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Menunggu Review Anda</h2>
                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                    Draf yang sudah diajukan Surveyor, menunggu ditandai &quot;Sudah Direview&quot;.
                </p>
            </div>

            @forelse ($pendingReview as $p)
                <a href="{{ route('proposals.show', $p) }}"
                   class="flex items-center justify-between gap-4 px-5 py-3 border-t border-gray-100 hover:bg-blue-50/40 dark:hover:bg-blue-900/20 dark:border-gray-800">
                    <span class="min-w-0">
                        <span class="block text-sm font-medium text-gray-900 truncate dark:text-gray-100" title="{{ $p->proposal_number }}">{{ $p->proposal_number_short }}</span>
                        <span class="block text-xs text-gray-500 truncate dark:text-gray-400">{{ $p->instructingClient->client_name ?? '-' }}</span>
                    </span>
                    <span class="text-right">
                        <span class="block text-xs font-medium text-indigo-600 whitespace-nowrap dark:text-indigo-400">⏳ Menunggu Review</span>
                        <span class="block text-[11px] text-gray-400 whitespace-nowrap dark:text-gray-500">
                            Diajukan {{ $p->review_submitted_at?->translatedFormat('d M Y') ?? '-' }} oleh {{ $p->reviewSubmittedBy->name ?? '-' }}
                        </span>
                    </span>
                </a>
            @empty
                <div class="px-5 py-8 text-center text-sm text-gray-400 border-t border-gray-100 dark:text-gray-500 dark:border-gray-800">
                    🎉 Tidak ada draf yang menunggu direview saat ini.
                </div>
            @endforelse
        </div>
    @endif

    {{-- ===================== BUTUH PERHATIAN HARI INI ===================== --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden lift dark:bg-gray-800 dark:border-gray-700">
        <div class="px-5 pt-5 pb-3 flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Butuh perhatian hari ini</h2>
            <a href="{{ route('timeline') }}" class="text-xs font-medium text-blue-600 hover:text-blue-800 whitespace-nowrap dark:text-blue-400 dark:hover:text-blue-300">
                📅 Lihat Timeline
            </a>
        </div>

        @forelse ($attention as $item)
            <a href="{{ route('proposals.show', $item['project']) }}"
               class="flex items-center justify-between gap-4 px-5 py-3 border-t border-gray-100 hover:bg-blue-50/40 dark:hover:bg-blue-900/20 dark:border-gray-800">
                <span class="min-w-0">
                    <span class="block text-sm font-medium text-gray-900 truncate dark:text-gray-100" title="{{ $item['project']->proposal_number }}">{{ $item['project']->proposal_number_short }}</span>
                    <span class="block text-xs text-gray-500 truncate dark:text-gray-400">{{ $item['project']->instructingClient->client_name ?? '-' }}</span>
                </span>
                <span class="text-xs font-medium whitespace-nowrap {{ $toneText[$item['tone']] ?? 'text-gray-500' }}">
                    {{ $item['note'] }}
                </span>
            </a>
        @empty
            <div class="px-5 py-10 text-center text-sm text-gray-400 border-t border-gray-100 dark:text-gray-500 dark:border-gray-800">
                @if ($activeCount === 0)
                    {{ $officeWide ? 'Belum ada proyek aktif.' : 'Belum ada proyek yang ditugaskan kepada Anda.' }}
                @else
                    🎉 Tidak ada yang mendesak. {{ $officeWide ? 'Semua proyek' : 'Proyek Anda' }} masih dalam jadwal.
                @endif
            </div>
        @endforelse
    </div>
</div>
@endsection
