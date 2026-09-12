@extends('layouts.app')

@section('title', 'Dashboard')

@php
    use App\Models\Project;

    $statusHex = [
        Project::STATUS_DRAFT            => '#9ca3af',
        Project::STATUS_WAITING_APPROVAL => '#3b82f6',
        Project::STATUS_DP_INVOICING     => '#eab308',
        Project::STATUS_IN_PROGRESS      => '#22c55e',
        Project::STATUS_PELUNASAN        => '#f97316',
        Project::STATUS_SELESAI          => '#059669',
        Project::STATUS_BATAL            => '#f43f5e',
    ];

    $maxStatus  = max(1, $statusCounts->max() ?: 0);
    $maxMonthly = max(1, $monthly->max('count') ?: 0);
    $maxPurpose = max(1, $purposeCounts->max() ?: 0);

    $pipelineTotal = $pendingCount + $dealCount + $cancelledCount;
    $pct = fn ($n) => $pipelineTotal ? round($n / $pipelineTotal * 100, 2) : 0;
    $pPend  = $pct($pendingCount);
    $pDeal  = $pct($dealCount);
    $pBatal = $pct($cancelledCount);

    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
@endphp

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    {{-- ===================== HEADER ===================== --}}
    <div class="flex items-end justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Ringkasan monitoring proyek &middot; {{ now()->translatedFormat('d F Y') }}</p>
        </div>
        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">
            📊 Buka Dashboard Project
        </a>
    </div>

    @if ($totalProposals === 0)
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-10 text-center text-gray-400 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-500">
            Belum ada proposal. Widget monitoring akan muncul setelah proposal pertama dibuat.
        </div>
    @else

    {{-- ===================== KARTU ANGKA ===================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Proposal --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Total Proposal</span>
                <span class="grid h-8 w-8 place-items-center rounded-md bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                    <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-3xl font-bold text-gray-900 tabular-nums dark:text-gray-100">{{ number_format($totalProposals, 0, ',', '.') }}</div>
            <p class="text-xs text-gray-400 mt-1 dark:text-gray-500">1 proyek = 1 proposal</p>
        </div>

        {{-- Pending / Belum Deal --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Pending</span>
                <span class="grid h-8 w-8 place-items-center rounded-md bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                    <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-3xl font-bold text-gray-900 tabular-nums dark:text-gray-100">{{ number_format($pendingCount, 0, ',', '.') }}</div>
            <p class="text-xs text-gray-400 mt-1 dark:text-gray-500">Belum ada pembayaran</p>
        </div>

        {{-- Deal --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Deal</span>
                <span class="grid h-8 w-8 place-items-center rounded-md bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                    <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-3xl font-bold text-gray-900 tabular-nums dark:text-gray-100">{{ number_format($dealCount, 0, ',', '.') }}</div>
            <p class="text-xs text-gray-400 mt-1 dark:text-gray-500">Sudah ada pembayaran</p>
        </div>

        {{-- Batal --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 lift dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Batal</span>
                <span class="grid h-8 w-8 place-items-center rounded-md bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400">
                    <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-3xl font-bold text-gray-900 tabular-nums dark:text-gray-100">{{ number_format($cancelledCount, 0, ',', '.') }}</div>
            <p class="text-xs text-gray-400 mt-1 dark:text-gray-500">Data tetap tersimpan</p>
        </div>
    </div>

    {{-- ===================== NILAI KONTRAK ===================== --}}
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 lift dark:bg-gray-800 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Total Nilai Kontrak Proposal</span>
            <div class="mt-1 text-2xl font-bold text-gray-900 tabular-nums break-words dark:text-gray-100">{{ $rp($totalContractValue) }}</div>
            <p class="text-xs text-gray-400 mt-1 dark:text-gray-500">Akumulasi seluruh proyek aktif (di luar Batal), sudah termasuk PPN &amp; transport.</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 lift dark:bg-gray-800 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Nilai Kontrak Deal</span>
            <div class="mt-1 text-2xl font-bold text-emerald-700 tabular-nums break-words dark:text-emerald-400">{{ $rp($dealContractValue) }}</div>
            <p class="text-xs text-gray-400 mt-1 dark:text-gray-500">Proyek yang sudah menerima pembayaran.</p>
        </div>
    </div>

    {{-- ===================== STATUS BARS + DONUT PIPELINE ===================== --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Proyek per Status --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 lift dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4 dark:text-gray-400">Proyek per Status</h2>
            <div class="space-y-3">
                @foreach ($statusCounts as $label => $count)
                    <div class="flex items-center gap-3 text-sm">
                        <span class="w-44 shrink-0 text-gray-600 truncate dark:text-gray-400" title="{{ $label }}">{{ $label }}</span>
                        <span class="flex-1 h-2.5 rounded-full bg-gray-100 overflow-hidden dark:bg-gray-800">
                            <span class="block h-full rounded-full"
                                  style="width: {{ $count ? max(4, round($count / $maxStatus * 100)) : 0 }}%; background: {{ $statusHex[$label] ?? '#9ca3af' }};"></span>
                        </span>
                        <span class="w-8 shrink-0 text-right font-semibold text-gray-800 tabular-nums dark:text-gray-200">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Komposisi Pipeline (donut) --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 lift dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4 dark:text-gray-400">Komposisi Pipeline</h2>
            <div class="flex items-center gap-6">
                <div class="relative h-40 w-40 shrink-0">
                    <svg viewBox="0 0 36 36" class="h-40 w-40 -rotate-90">
                        <circle cx="18" cy="18" r="15.9155" fill="none" class="stroke-gray-100 dark:stroke-gray-700" stroke-width="3.8"/>
                        @if ($pipelineTotal)
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#f59e0b" stroke-width="3.8"
                                    stroke-dasharray="{{ $pPend }} {{ 100 - $pPend }}" stroke-dashoffset="25"/>
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#10b981" stroke-width="3.8"
                                    stroke-dasharray="{{ $pDeal }} {{ 100 - $pDeal }}" stroke-dashoffset="{{ 25 - $pPend }}"/>
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#f43f5e" stroke-width="3.8"
                                    stroke-dasharray="{{ $pBatal }} {{ 100 - $pBatal }}" stroke-dashoffset="{{ 25 - $pPend - $pDeal }}"/>
                        @endif
                    </svg>
                    <div class="absolute inset-0 grid place-items-center text-center">
                        <div>
                            <div class="text-2xl font-bold text-gray-900 tabular-nums leading-none dark:text-gray-100">{{ $totalProposals }}</div>
                            <div class="text-[11px] text-gray-400 dark:text-gray-500">proyek</div>
                        </div>
                    </div>
                </div>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-sm" style="background:#f59e0b"></span>
                        <span class="text-gray-600 dark:text-gray-400">Pending</span>
                        <span class="ml-auto font-semibold text-gray-800 tabular-nums dark:text-gray-200">{{ $pendingCount }} <span class="text-gray-400 font-normal dark:text-gray-500">({{ $pPend }}%)</span></span>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-sm" style="background:#10b981"></span>
                        <span class="text-gray-600 dark:text-gray-400">Deal</span>
                        <span class="ml-auto font-semibold text-gray-800 tabular-nums dark:text-gray-200">{{ $dealCount }} <span class="text-gray-400 font-normal dark:text-gray-500">({{ $pDeal }}%)</span></span>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-sm" style="background:#f43f5e"></span>
                        <span class="text-gray-600 dark:text-gray-400">Batal</span>
                        <span class="ml-auto font-semibold text-gray-800 tabular-nums dark:text-gray-200">{{ $cancelledCount }} <span class="text-gray-400 font-normal dark:text-gray-500">({{ $pBatal }}%)</span></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ===================== MONTHLY + PURPOSE ===================== --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Proposal masuk per bulan --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 lift dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4 dark:text-gray-400">Proposal Masuk &mdash; 6 Bulan Terakhir</h2>
            <div class="flex items-end justify-between gap-2 h-40">
                @foreach ($monthly as $m)
                    <div class="flex-1 flex flex-col items-center justify-end h-full">
                        <span class="text-xs font-semibold text-gray-700 tabular-nums mb-1 dark:text-gray-300">{{ $m['count'] }}</span>
                        <div class="w-full max-w-[42px] rounded-t bg-blue-500/90"
                             style="height: {{ $m['count'] ? max(4, round($m['count'] / $maxMonthly * 100)) : 1 }}%;"
                             title="{{ $m['label'] }}: {{ $m['count'] }} proposal"></div>
                        <span class="mt-2 text-[11px] text-gray-400 text-center dark:text-gray-500">{{ $m['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Proyek per jenis proposal --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 lift dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4 dark:text-gray-400">Proyek per Jenis Proposal</h2>
            <div class="space-y-3">
                @foreach ($purposeCounts as $label => $count)
                    <div class="flex items-center gap-3 text-sm">
                        <span class="w-40 shrink-0 text-gray-600 truncate dark:text-gray-400" title="{{ $label }}">{{ $label }}</span>
                        <span class="flex-1 h-2.5 rounded-full bg-gray-100 overflow-hidden dark:bg-gray-800">
                            <span class="block h-full rounded-full bg-indigo-500"
                                  style="width: {{ $count ? max(4, round($count / $maxPurpose * 100)) : 0 }}%;"></span>
                        </span>
                        <span class="w-8 shrink-0 text-right font-semibold text-gray-800 tabular-nums dark:text-gray-200">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">Tidak termasuk proyek berstatus Batal.</p>
        </div>
    </div>

    {{-- ===================== PROYEK TERBARU ===================== --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto lift dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 pt-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Proyek Terbaru</h2>
        </div>
        <table class="min-w-[640px] w-full text-sm mt-3">
            <thead class="bg-gray-50 border-y border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">
                    <th class="px-6 py-3">No. Proposal</th>
                    <th class="px-6 py-3">Pemberi Tugas</th>
                    <th class="px-6 py-3">Jenis</th>
                    <th class="px-6 py-3 w-52">Status</th>
                    <th class="px-6 py-3 whitespace-nowrap">Dibuat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($recent as $p)
                    <tr class="hover:bg-blue-50/40 dark:hover:bg-blue-900/20 {{ $p->status === Project::STATUS_BATAL ? 'opacity-60' : '' }}">
                        <td class="px-6 py-3 font-medium text-gray-900 dark:text-gray-100">
                            <a href="{{ route('proposals.show', $p) }}" class="hover:text-blue-700 dark:hover:text-blue-300">{{ $p->proposal_number }}</a>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $p->instructingClient->client_name ?? '-' }}</td>
                        <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $p->proposal_purpose }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap {{ $p->status_badge_classes }}">
                                {{ $p->status }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-gray-500 whitespace-nowrap dark:text-gray-400">{{ $p->created_at->translatedFormat('d M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ===================== PERLU DITINDAKLANJUTI (DRAFT & DP INVOICING) ===================== --}}
    @if ($followUps->isNotEmpty())
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto lift dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 pt-6">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Perlu Ditindaklanjuti</h2>
                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                    Proposal berstatus Draft Proposal / DP Invoicing &mdash; reminder sudah berapa hari sejak dibuat.
                </p>
            </div>
            <table class="min-w-[640px] w-full text-sm mt-3">
                <thead class="bg-gray-50 border-y border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">
                        <th class="px-6 py-3">No. Proposal</th>
                        <th class="px-6 py-3">Pemberi Tugas</th>
                        <th class="px-6 py-3 w-52">Status</th>
                        <th class="px-6 py-3 text-right whitespace-nowrap">Reminder</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($followUps as $p)
                        @php
                            $daysSinceCreated = (int) $p->created_at->diffInDays(now());
                            $reminderTone = match (true) {
                                $daysSinceCreated >= 7 => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
                                $daysSinceCreated >= 3 => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                default                => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                            };
                        @endphp
                        <tr class="hover:bg-blue-50/40 dark:hover:bg-blue-900/20">
                            <td class="px-6 py-3 font-medium text-gray-900 dark:text-gray-100">
                                <a href="{{ route('proposals.show', $p) }}" class="hover:text-blue-700 dark:hover:text-blue-300">{{ $p->proposal_number }}</a>
                            </td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $p->instructingClient->client_name ?? '-' }}</td>
                            <td class="px-6 py-3">
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap {{ $p->status_badge_classes }}">
                                    {{ $p->status }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-right whitespace-nowrap">
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold {{ $reminderTone }}">
                                    {{ $daysSinceCreated }} hari
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @endif
</div>
@endsection
