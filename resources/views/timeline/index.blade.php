@extends('layouts.app')

@section('title', 'Timeline Project')

@php
    // Warna batang per kondisi SLA: track (latar + garis), fill (porsi
    // waktu yang sudah berjalan), dan warna teks keterangan.
    $tone = [
        'overdue'  => ['track' => 'border-rose-300 bg-rose-50 dark:border-rose-800 dark:bg-rose-900/30',             'fill' => 'bg-rose-500',    'text' => 'text-rose-600 dark:text-rose-400'],
        'due-soon' => ['track' => 'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/30',         'fill' => 'bg-amber-500',   'text' => 'text-amber-600 dark:text-amber-400'],
        'on-track' => ['track' => 'border-emerald-300 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/30', 'fill' => 'bg-emerald-500', 'text' => 'text-emerald-700 dark:text-emerald-400'],
        'none'     => ['track' => 'border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-700',               'fill' => 'bg-gray-300',    'text' => 'text-gray-400 dark:text-gray-500'],
    ];
@endphp

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    {{-- ===================== HEADER ===================== --}}
    <div class="flex items-end justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Timeline Project</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Batang = rentang SLA draf laporan (tanggal survei &rarr; target selesai).
                Bagian pekat = waktu berjalan, sisanya = sisa hari SLA.
            </p>
            {{-- Filter cakupan — pola yang sama dengan List Project, termasuk
                 disembunyikan untuk role admin (2026-09-14, lihat User::seesOfficeWide). --}}
            @unless (auth()->user()->seesOfficeWide())
            <div class="flex gap-2 mt-2">
                <a href="{{ route('timeline', ['mine' => 0]) }}"
                   class="px-3 py-1 text-xs rounded-full font-medium {{ ! $mine ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }} dark:text-gray-400">
                    🗂 Semua Proyek
                </a>
                <a href="{{ route('timeline', ['mine' => 1]) }}"
                   class="px-3 py-1 text-xs rounded-full font-medium {{ $mine ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }} dark:text-gray-400">
                    👤 Proyek Saya
                </a>
            </div>
            @endunless
        </div>
        {{-- Tombol "Dashboard Project" dihapus (2026-09-15, feedback user) —
             List Project sudah selalu ada di sidebar. --}}
    </div>

    {{-- ===================== RINGKASAN + LEGENDA ===================== --}}
    <div class="flex flex-wrap items-center gap-2 text-xs">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800">
            <span class="h-2 w-2 rounded-full bg-rose-500"></span> Lewat deadline: <strong>{{ $summary['overdue'] }}</strong>
        </span>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800">
            <span class="h-2 w-2 rounded-full bg-amber-500"></span> Mendekati (&le;2 hari): <strong>{{ $summary['dueSoon'] }}</strong>
        </span>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span> On-track: <strong>{{ $summary['onTrack'] }}</strong>
        </span>
    </div>

    {{-- ===================== GANTT ===================== --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 overflow-x-auto lift dark:bg-gray-800 dark:border-gray-700">
        <p class="-mt-2 mb-2 flex items-center justify-end gap-1 text-xs text-gray-400 md:hidden dark:text-gray-500">
            Geser ke samping
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
        </p>
        @if ($bars->isEmpty())
            <p class="py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                @if ($mine)
                    Belum ada proyek Anda yang terjadwal. Coba lihat <a href="{{ route('timeline') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Semua Proyek</a>.
                @else
                    Belum ada proyek yang terjadwal. Isi tanggal survei &amp; SLA pada proyek untuk melihatnya di sini.
                @endif
            </p>
        @else
            <div class="min-w-[720px]">

                {{-- Sumbu tanggal --}}
                <div class="flex items-end">
                    <div class="w-48 shrink-0"></div>
                    <div class="relative flex-1 h-5">
                        @foreach ($ticks as $tick)
                            <span class="absolute -translate-x-1/2 text-[11px] text-gray-400 whitespace-nowrap dark:text-gray-500"
                                  style="left: {{ $tick['pct'] }}%">{{ $tick['label'] }}</span>
                        @endforeach
                    </div>
                    <div class="w-32 shrink-0"></div>
                </div>

                {{-- Garis sumbu --}}
                <div class="flex">
                    <div class="w-48 shrink-0"></div>
                    <div class="flex-1 border-t border-gray-200 dark:border-gray-700"></div>
                    <div class="w-32 shrink-0"></div>
                </div>

                {{-- Baris proyek --}}
                <div class="divide-y divide-gray-50 dark:divide-gray-800">
                    @foreach ($bars as $bar)
                        @php $t = $tone[$bar['state']] ?? $tone['none']; @endphp
                        <div class="flex items-center py-2.5">

                            {{-- Identitas proyek --}}
                            <div class="w-48 shrink-0 pr-3">
                                <a href="{{ route('proposals.show', $bar['project']) }}"
                                   class="block text-xs font-medium text-gray-900 truncate hover:text-blue-700 dark:text-gray-100 dark:hover:text-blue-300"
                                   title="{{ $bar['project']->proposal_number }}">
                                    {{ $bar['project']->proposal_number_short }}
                                </a>
                                <span class="block text-[11px] text-gray-500 truncate dark:text-gray-400">
                                    {{ $bar['project']->effective_client_name ?: '-' }}<br>
                                    <b>{{ $bar['project']->assigned_appraiser ?: '-' }}</b>
                                </span>
                            </div>

                            {{-- Track + batang (sampai 3 segmen: Draft, Gap proses review, Final) --}}
                            <div class="relative flex-1 h-10">
                                {{-- Penanda hari ini --}}
                                <div class="absolute inset-y-0 w-0 border-l border-dashed border-blue-400/70"
                                     style="left: {{ $todayPct }}%"></div>

                                {{-- Tanggal mulai (survei), nempel di ujung kiri batang — supaya
                                     rentang tanggal langsung kelihatan tanpa harus hover. --}}
                                <span class="absolute top-0 -translate-x-1/2 text-[10px] text-gray-400 whitespace-nowrap dark:text-gray-500"
                                      style="left: {{ $bar['left'] }}%">{{ $bar['startShort'] }}</span>

                                @if ($bar['draftDone'])
                                    {{-- Draft sudah disubmit -> dikunci jadi riwayat (abu-abu solid),
                                         tak perlu terus mengejar target lagi. --}}
                                    <div class="absolute bottom-0 h-5 rounded-md border border-gray-300 bg-gray-200 dark:border-gray-600 dark:bg-gray-700"
                                         style="left: {{ $bar['left'] }}%; width: {{ $bar['width'] }}%"
                                         title="Draft: {{ $bar['startText'] }} → {{ $bar['draftEndText'] }} (sudah disubmit)"></div>
                                @else
                                    {{-- Draft masih berjalan: dua-tona elapsed/sisa seperti biasa. --}}
                                    <div class="absolute bottom-0 h-5 rounded-md border overflow-hidden {{ $t['track'] }}"
                                         style="left: {{ $bar['left'] }}%; width: {{ $bar['width'] }}%"
                                         title="{{ $bar['startText'] }} → {{ $bar['draftEndText'] }} ({{ $bar['spanDays'] }} hari) — {{ $bar['label'] }}">
                                        <div class="h-full {{ $t['fill'] }}" style="width: {{ $bar['fill'] }}%"></div>
                                    </div>

                                    {{-- Lewat deadline: batang "menjorok" putus-putus sampai hari ini,
                                         supaya keterlambatan kelihatan dari BENTUKnya, bukan cuma teks. --}}
                                    @if ($bar['overdueWidth'] > 0)
                                        <div class="absolute bottom-0 h-5 rounded-r-md border border-l-0 border-dashed border-rose-400 dark:border-rose-600"
                                             style="left: calc({{ $bar['left'] + $bar['width'] }}% - 1px); width: {{ $bar['overdueWidth'] }}%"
                                             title="{{ $bar['label'] }} — target semula {{ $bar['draftEndText'] }}"></div>
                                        <span class="absolute bottom-0 translate-x-1.5 text-[10px] font-medium text-rose-600 dark:text-rose-400 whitespace-nowrap"
                                              style="left: {{ $bar['left'] + $bar['width'] + $bar['overdueWidth'] }}%">{{ $bar['label'] }}</span>
                                    @endif
                                @endif

                                {{-- Gap: waktu proses review (Reviewer + Admin Produksi), tak
                                     terhitung SLA manapun — netral putus-putus supaya jelas itu
                                     bukan lubang/bug, memang proses berjalan. --}}
                                @if ($bar['gap'])
                                    <div class="absolute bottom-0 h-5 border-y border-dashed border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-800/60"
                                         style="left: {{ $bar['gap']['left'] }}%; width: {{ $bar['gap']['width'] }}%"
                                         title="Proses review: {{ $bar['gap']['startText'] }} → {{ $bar['gap']['endText'] }}"></div>
                                @endif

                                @if ($bar['final'])
                                    @php $tf = $tone[$bar['final']['state']] ?? $tone['none']; @endphp
                                    {{-- Segmen Final: sama persis logikanya dg Draft di atas. --}}
                                    <div class="absolute bottom-0 h-5 rounded-md border overflow-hidden {{ $tf['track'] }}"
                                         style="left: {{ $bar['final']['left'] }}%; width: {{ $bar['final']['width'] }}%"
                                         title="Final: {{ $bar['final']['startText'] }} → {{ $bar['final']['endText'] }} ({{ $bar['final']['spanDays'] }} hari) — {{ $bar['final']['label'] }}">
                                        <div class="h-full {{ $tf['fill'] }}" style="width: {{ $bar['final']['fill'] }}%"></div>
                                    </div>

                                    @if ($bar['final']['overdueWidth'] > 0)
                                        <div class="absolute bottom-0 h-5 rounded-r-md border border-l-0 border-dashed border-rose-400 dark:border-rose-600"
                                             style="left: calc({{ $bar['final']['left'] + $bar['final']['width'] }}% - 1px); width: {{ $bar['final']['overdueWidth'] }}%"
                                             title="{{ $bar['final']['label'] }} — target semula {{ $bar['final']['endText'] }}"></div>
                                        <span class="absolute bottom-0 translate-x-1.5 text-[10px] font-medium text-rose-600 dark:text-rose-400 whitespace-nowrap"
                                              style="left: {{ $bar['final']['left'] + $bar['final']['width'] + $bar['final']['overdueWidth'] }}%">{{ $bar['final']['label'] }}</span>
                                    @endif
                                @endif
                            </div>

                            {{-- Keterangan sisa SLA --}}
                            <div class="w-32 shrink-0 pl-3 text-right">
                                <span class="text-xs font-medium {{ $t['text'] }}">{{ $bar['label'] }}</span>
                                <span class="block text-[11px] text-gray-400 dark:text-gray-500">{{ $bar['endText'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Keterangan penanda hari ini --}}
                <div class="flex">
                    <div class="w-48 shrink-0"></div>
                    <div class="relative flex-1 h-5">
                        <span class="absolute -translate-x-1/2 text-[11px] text-blue-500 whitespace-nowrap dark:text-blue-400"
                              style="left: {{ $todayPct }}%">hari ini</span>
                    </div>
                    <div class="w-32 shrink-0"></div>
                </div>
            </div>
        @endif
    </div>

    {{-- ===================== BELUM TERJADWAL ===================== --}}
    @if ($unscheduled->isNotEmpty())
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden lift dark:bg-gray-800 dark:border-gray-700">
            <div class="px-5 pt-5 pb-3">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Belum Terjadwal</h2>
                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                    Sudah In-Progress / Scheduled tapi tanggal survei belum diisi, sehingga belum bisa dipetakan di timeline.
                </p>
            </div>
            @foreach ($unscheduled as $p)
                <a href="{{ route('proposals.show', $p) }}"
                   class="flex items-center justify-between gap-4 px-5 py-3 border-t border-gray-100 hover:bg-blue-50/40 dark:hover:bg-blue-900/20 dark:border-gray-800">
                    <span class="min-w-0">
                        <span class="block text-sm font-medium text-gray-900 truncate dark:text-gray-100" title="{{ $p->proposal_number }}">{{ $p->proposal_number_short }}</span>
                        <span class="block text-xs text-gray-500 truncate dark:text-gray-400">{{ $p->effective_client_name ?: '-' }}</span>
                    </span>
                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap {{ $p->status_badge_classes }}" title="{{ $p->status }}">
                        {{ $p->status_short }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
