@extends('layouts.app')

@section('title', 'Ringkasan Project')

@php
    use App\Models\Project;

    $statusHex = [
        Project::STATUS_DRAFT            => '#9ca3af',
        Project::STATUS_WAITING_APPROVAL => '#3b82f6',
        Project::STATUS_DP_INVOICING     => '#eab308',
        Project::STATUS_IN_PROGRESS      => '#22c55e',
        Project::STATUS_SELESAI          => '#059669',
        Project::STATUS_BATAL            => '#f43f5e',
    ];

    $maxStatus  = max(1, $statusCounts->max() ?: 0);
    $maxMonthly = max(1, $monthly->max('count') ?: 0);
    $maxPurpose = max(1, $purposeCounts->max() ?: 0);

    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
@endphp

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    {{-- ===================== HEADER ===================== --}}
    <x-page-header title="Ringkasan Project"
        subtitle="Ringkasan monitoring proyek &middot; {{ now()->translatedFormat('d F Y') }}" />

    @if ($totalProposals === 0)
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-10 text-center text-gray-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-500">
            Belum ada proposal. Widget monitoring akan muncul setelah proposal pertama dibuat.
        </div>
    @else

    {{-- ===================== KARTU ANGKA ===================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Proposal --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-500">Total Proposal</span>
                <span class="grid h-8 w-8 place-items-center rounded-md bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                    <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-3xl font-bold text-gray-900 tabular-nums dark:text-gray-100">{{ number_format($totalProposals, 0, ',', '.') }}</div>
            <p class="text-xs text-gray-500 mt-1 dark:text-gray-500">1 proyek = 1 proposal</p>
        </div>

        {{-- Pending / Belum Deal --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-500">Belum Ada Pembayaran</span>
                <span class="grid h-8 w-8 place-items-center rounded-md bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                    <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-3xl font-bold text-gray-900 tabular-nums dark:text-gray-100">{{ number_format($pendingCount, 0, ',', '.') }}</div>
            <p class="text-xs text-gray-500 mt-1 dark:text-gray-500">Proposal aktif, klien belum membayar</p>
        </div>

        {{-- Deal --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-500">Sudah Ada Pembayaran</span>
                <span class="grid h-8 w-8 place-items-center rounded-md bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                    <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-3xl font-bold text-gray-900 tabular-nums dark:text-gray-100">{{ number_format($dealCount, 0, ',', '.') }}</div>
            <p class="text-xs text-gray-500 mt-1 dark:text-gray-500">Minimal satu invoice sudah dibayar</p>
        </div>

        {{-- Batal --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-500">Batal</span>
                <span class="grid h-8 w-8 place-items-center rounded-md bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400">
                    <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-3xl font-bold text-gray-900 tabular-nums dark:text-gray-100">{{ number_format($cancelledCount, 0, ',', '.') }}</div>
            <p class="text-xs text-gray-500 mt-1 dark:text-gray-500">Data tetap tersimpan</p>
        </div>
    </div>

    {{-- Kartu "Nilai Kontrak" dihapus dari sini (2026-09-14, feedback user) —
         angka uang cukup di Dashboard Pembayaran supaya tidak dobel. --}}

    {{-- ===================== STATUS BARS + DONUT PIPELINE ===================== --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 *:min-w-0">

        {{-- Proyek per Status --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 lift dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4 dark:text-gray-500">Proyek per Status</h2>
            <div class="space-y-3">
                @foreach ($statusCounts as $label => $count)
                    <div class="flex items-center gap-3 text-sm">
                        {{-- Label status singkat, sama dengan List Project (2026-09-14). --}}
                        <span class="w-32 shrink-0 text-gray-600 truncate dark:text-gray-500" title="{{ $label }}">{{ Project::STATUS_SHORT_LABELS[$label] ?? $label }}</span>
                        <span class="flex-1 h-2.5 rounded-full bg-gray-100 overflow-hidden dark:bg-gray-800">
                            <span class="block h-full rounded-full"
                                  style="width: {{ $count ? max(4, round($count / $maxStatus * 100)) : 0 }}%; background: {{ $statusHex[$label] ?? '#9ca3af' }};"></span>
                        </span>
                        <span class="w-8 shrink-0 text-right font-semibold text-gray-800 tabular-nums dark:text-gray-200">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Top 5 Bank Pemberi Tugas — pie 3D, menggantikan Komposisi Pipeline
             (2026-09-21, feedback user). --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 lift dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4 dark:text-gray-500"
                title="Jumlah proyek per bank (tanpa proyek batal). Nama bank yang sama digabung walau alamatnya beda.">Top 5 Bank Pemberi Tugas</h2>
            @if (empty($topBanks['slices']))
                <p class="py-10 text-center text-sm text-gray-400 dark:text-gray-500">Belum ada proyek dari klien bank.</p>
            @else
                {{-- Tinggi pie 160px, setara kartu Proyek per Status; legenda di samping
                     (turun ke bawah di HP). --}}
                <div class="flex flex-wrap items-center gap-6 sm:flex-nowrap">
                    <div class="shrink-0">@include('dashboard._pie3d', ['slices' => $topBanks['slices']])</div>
                    <div class="min-w-0 flex-1">
                        <ul class="space-y-2 text-sm">
                            @foreach ($topBanks['slices'] as $s)
                                <li class="flex items-start gap-2">
                                    <span class="mt-1 h-3 w-3 shrink-0 rounded-sm" style="background: {{ $s['color'] }}"></span>
                                    <span class="min-w-0 leading-tight text-gray-600 dark:text-gray-400">{{ $s['label'] }}</span>
                                    <span class="ml-auto font-semibold text-gray-800 tabular-nums dark:text-gray-200">{{ $s['value'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                        @if ($topBanks['nonBank'] > 0)
                            <p class="mt-3 text-[11px] text-gray-400 dark:text-gray-500">Di luar grafik: {{ $topBanks['nonBank'] }} proyek dari Pemberi Tugas non-bank.</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ===================== PROGRESS STATUS PENILAI (2026-09-21, feedback user) =====================
         Proyek In-Progress yang sedang dipegang tiap penilai, supaya terlihat
         siapa yang penuh dan siapa yang kosong. --}}
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm lift dark:border-gray-700 dark:bg-gray-800">
        <div class="px-6 pt-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-500">Progress Status Penilai</h2>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Proyek In-Progress yang sedang dipegang, urut dari yang paling banyak. Satu proyek bisa dipegang beberapa penilai.</p>
        </div>
        @if ($workload->isEmpty())
            <p class="px-6 py-10 text-center text-sm text-gray-400 dark:text-gray-500">Belum ada akun penilai aktif.</p>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="mt-3 w-full min-w-[720px] text-sm">
                    <thead class="border-y border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-500">
                            <th class="px-6 py-3">Penilai</th>
                            <th class="px-4 py-3 text-right" title="Tanggal survei di masa depan">Akan Survei</th>
                            <th class="px-4 py-3 text-right" title="Sudah/sedang survei, nilai belum diajukan">Survei &amp; Penilaian</th>
                            <th class="px-4 py-3 text-right" title="Nilai diajukan / Draft Resume dirilis">Review Nilai</th>
                            <th class="px-4 py-3 text-right" title="Draft laporan sampai proses cetak buku">Draft Laporan</th>
                            <th class="px-4 py-3 text-right">Total Aktif</th>
                            <th class="px-6 py-3 text-right">Selesai Bulan Ini</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($workload as $w)
                            <tr class="{{ $w['total'] === 0 ? 'bg-gray-50/60 dark:bg-gray-900/30' : '' }}">
                                <td class="px-6 py-3">
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $w['user']->name }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $w['user']->jabatan ?: '-' }}</p>
                                </td>
                                @foreach (['upcoming', 'surveying', 'review', 'draft'] as $k)
                                    <td class="px-4 py-3 text-right tabular-nums {{ $w[$k] ? 'text-gray-800 dark:text-gray-200' : 'text-gray-300 dark:text-gray-600' }}">{{ $w[$k] }}</td>
                                @endforeach
                                <td class="px-4 py-3 text-right">
                                    @if ($w['total'] === 0)
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-400">Kosong</span>
                                    @else
                                        <span class="text-base font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ $w['total'] }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right tabular-nums text-emerald-600 dark:text-emerald-400">{{ $w['doneMonth'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- Ponsel: kartu per penilai. --}}
            <div class="mt-3 divide-y divide-gray-100 border-t border-gray-100 md:hidden dark:divide-gray-700 dark:border-gray-700">
                @foreach ($workload as $w)
                    <div class="px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-gray-900 dark:text-gray-100">{{ $w['user']->name }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $w['user']->jabatan ?: '-' }}</p>
                            </div>
                            @if ($w['total'] === 0)
                                <span class="shrink-0 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-400">Kosong</span>
                            @else
                                <span class="shrink-0 text-right"><span class="text-lg font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ $w['total'] }}</span> <span class="text-xs text-gray-400">aktif</span></span>
                            @endif
                        </div>
                        <div class="mt-2 grid grid-cols-5 gap-1 text-center text-[10px] text-gray-400 dark:text-gray-500">
                            @foreach (['upcoming' => 'Akan survei', 'surveying' => 'Survei', 'review' => 'Review', 'draft' => 'Draft', 'doneMonth' => 'Selesai'] as $k => $lbl)
                                <div><p class="text-sm font-semibold tabular-nums {{ $w[$k] ? 'text-gray-800 dark:text-gray-200' : 'text-gray-300 dark:text-gray-600' }}">{{ $w[$k] }}</p>{{ $lbl }}</div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ===================== MONTHLY + PURPOSE ===================== --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 *:min-w-0">

        {{-- Proposal masuk per bulan --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 lift dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4 dark:text-gray-500">Proposal Masuk &mdash; 6 Bulan Terakhir</h2>
            <div class="flex items-end justify-between gap-2 h-40">
                @foreach ($monthly as $m)
                    <div class="flex-1 flex flex-col items-center justify-end h-full">
                        <span class="text-xs font-semibold text-gray-700 tabular-nums mb-1 dark:text-gray-300">{{ $m['count'] }}</span>
                        <div class="w-full max-w-[42px] rounded-t bg-blue-500/90"
                             style="height: {{ $m['count'] ? max(4, round($m['count'] / $maxMonthly * 100)) : 1 }}%;"
                             title="{{ $m['label'] }}: {{ $m['count'] }} proposal"></div>
                        <span class="mt-2 text-[11px] text-gray-500 text-center dark:text-gray-500">{{ $m['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Proyek per jenis proposal --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 lift dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4 dark:text-gray-500">Proyek per Tujuan Penilaian</h2>
            <div class="space-y-3">
                @foreach ($purposeCounts as $label => $count)
                    <div class="flex items-center gap-3 text-sm">
                        <span class="w-40 shrink-0 text-gray-600 truncate dark:text-gray-500" title="{{ $label }}">{{ $label }}</span>
                        <span class="flex-1 h-2.5 rounded-full bg-gray-100 overflow-hidden dark:bg-gray-800">
                            <span class="block h-full rounded-full bg-indigo-500"
                                  style="width: {{ $count ? max(4, round($count / $maxPurpose * 100)) : 0 }}%;"></span>
                        </span>
                        <span class="w-8 shrink-0 text-right font-semibold text-gray-800 tabular-nums dark:text-gray-200">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Tidak termasuk proyek berstatus Batal.</p>
        </div>
    </div>

    {{-- ===================== PROYEK TERBARU ===================== --}}
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm lift dark:border-gray-700 dark:bg-gray-800">
        <div class="px-6 pt-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Proyek Terbaru</h2>
        </div>
        {{-- Tabel di layar lebar, kartu di HP (2026-09-20, hasil audit UI). --}}
        <div class="hidden overflow-x-auto md:block">
        <table class="min-w-[640px] w-full text-sm mt-3">
            <thead class="bg-gray-50 border-y border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-500">
                    <th class="px-6 py-3">No. Proposal</th>
                    <th class="px-6 py-3">Nama Klien</th>
                    <th class="px-6 py-3">Jenis</th>
                    <th class="px-6 py-3 w-52">Status</th>
                    <th class="px-6 py-3 whitespace-nowrap">Dibuat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($recent as $p)
                    <tr class="hover:bg-blue-50/40 dark:hover:bg-blue-900/20 {{ $p->status === Project::STATUS_BATAL ? 'opacity-60' : '' }}">
                        <td class="px-6 py-3 font-medium text-gray-900 dark:text-gray-100">
                            <a href="{{ route('proposals.show', $p) }}" class="whitespace-nowrap hover:text-blue-700 dark:hover:text-blue-300" title="{{ $p->proposal_number }}" aria-label="{{ $p->proposal_number }}">{{ $p->proposal_number_short }}</a>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-500">{{ $p->effective_client_name ?: '-' }}</td>
                        <td class="px-6 py-3 text-gray-500 dark:text-gray-500">{{ $p->proposal_purpose }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap {{ $p->status_badge_classes }}" title="{{ $p->status }}">
                                {{ $p->status_short }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-gray-500 whitespace-nowrap dark:text-gray-500">{{ $p->created_at->translatedFormat('d M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        <div class="divide-y divide-gray-100 md:hidden dark:divide-gray-700">
            @foreach ($recent as $p)
                <a href="{{ route('proposals.show', $p) }}"
                   class="flex items-start justify-between gap-3 px-4 py-3 {{ $p->status === Project::STATUS_BATAL ? 'opacity-60' : '' }}">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $p->proposal_number_short }}</p>
                        <p class="truncate text-xs text-gray-600 dark:text-gray-400">{{ $p->effective_client_name ?: '-' }}</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $p->proposal_purpose }} &middot; {{ $p->created_at->translatedFormat('d M Y') }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $p->status_badge_classes }}">{{ $p->status_short }}</span>
                </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
