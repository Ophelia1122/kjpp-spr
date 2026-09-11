@extends('layouts.app')

@section('title', 'Timeline Project')

@php
    // Warna batang per kondisi SLA: track (latar + garis), fill (porsi
    // waktu yang sudah berjalan), dan warna teks keterangan.
    $tone = [
        'overdue'  => ['track' => 'border-rose-300 bg-rose-50',       'fill' => 'bg-rose-500',    'text' => 'text-rose-600'],
        'due-soon' => ['track' => 'border-amber-300 bg-amber-50',     'fill' => 'bg-amber-500',   'text' => 'text-amber-600'],
        'on-track' => ['track' => 'border-emerald-300 bg-emerald-50', 'fill' => 'bg-emerald-500', 'text' => 'text-emerald-700'],
        'done'     => ['track' => 'border-gray-300 bg-gray-100',      'fill' => 'bg-gray-400',    'text' => 'text-gray-500'],
        'none'     => ['track' => 'border-gray-300 bg-gray-100',      'fill' => 'bg-gray-300',    'text' => 'text-gray-400'],
    ];
@endphp

@section('content')
<div class="max-w-6xl mx-auto py-8 space-y-6">

    {{-- ===================== HEADER ===================== --}}
    <div class="flex items-end justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Timeline Project</h1>
            <p class="text-sm text-gray-500">
                Batang = rentang SLA draf laporan (tanggal survei &rarr; target selesai).
                Bagian pekat = waktu berjalan, sisanya = sisa hari SLA.
            </p>
            {{-- Filter cakupan — pola yang sama dengan Dashboard Project. --}}
            <div class="flex gap-2 mt-2">
                <a href="{{ route('timeline') }}"
                   class="px-3 py-1 text-xs rounded-full font-medium {{ ! $mine ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    🗂 Semua Proyek
                </a>
                <a href="{{ route('timeline', ['mine' => 1]) }}"
                   class="px-3 py-1 text-xs rounded-full font-medium {{ $mine ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    👤 Proyek Saya
                </a>
            </div>
        </div>
        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
            📊 Dashboard Project
        </a>
    </div>

    {{-- ===================== RINGKASAN + LEGENDA ===================== --}}
    <div class="flex flex-wrap items-center gap-2 text-xs">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200">
            <span class="h-2 w-2 rounded-full bg-rose-500"></span> Lewat deadline: <strong>{{ $summary['overdue'] }}</strong>
        </span>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
            <span class="h-2 w-2 rounded-full bg-amber-500"></span> Mendekati (&le;2 hari): <strong>{{ $summary['dueSoon'] }}</strong>
        </span>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span> On-track: <strong>{{ $summary['onTrack'] }}</strong>
        </span>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 border border-gray-300">
            <span class="h-2 w-2 rounded-full bg-gray-400"></span> Selesai: <strong>{{ $summary['done'] }}</strong>
        </span>
    </div>

    {{-- ===================== GANTT ===================== --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 overflow-x-auto lift">
        @if ($bars->isEmpty())
            <p class="py-10 text-center text-sm text-gray-400">
                @if ($mine)
                    Belum ada proyek Anda yang terjadwal. Coba lihat <a href="{{ route('timeline') }}" class="text-blue-600 hover:text-blue-800">Semua Proyek</a>.
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
                            <span class="absolute -translate-x-1/2 text-[11px] text-gray-400 whitespace-nowrap"
                                  style="left: {{ $tick['pct'] }}%">{{ $tick['label'] }}</span>
                        @endforeach
                    </div>
                    <div class="w-32 shrink-0"></div>
                </div>

                {{-- Garis sumbu --}}
                <div class="flex">
                    <div class="w-48 shrink-0"></div>
                    <div class="flex-1 border-t border-gray-200"></div>
                    <div class="w-32 shrink-0"></div>
                </div>

                {{-- Baris proyek --}}
                <div class="divide-y divide-gray-50">
                    @foreach ($bars as $bar)
                        @php $t = $tone[$bar['state']] ?? $tone['none']; @endphp
                        <div class="flex items-center py-2.5">

                            {{-- Identitas proyek --}}
                            <div class="w-48 shrink-0 pr-3">
                                <a href="{{ route('proposals.show', $bar['project']) }}"
                                   class="block text-xs font-medium text-gray-900 truncate hover:text-blue-700"
                                   title="{{ $bar['project']->proposal_number }}">
                                    {{ $bar['project']->proposal_number }}
                                </a>
                                <span class="block text-[11px] text-gray-500 truncate">
                                    {{ $bar['project']->instructingClient->client_name ?? '-' }}
                                </span>
                            </div>

                            {{-- Track + batang --}}
                            <div class="relative flex-1 h-7">
                                {{-- Penanda hari ini --}}
                                <div class="absolute inset-y-0 w-0 border-l border-dashed border-blue-400/70"
                                     style="left: {{ $todayPct }}%"></div>

                                <div class="absolute top-1/2 -translate-y-1/2 h-5 rounded-md border overflow-hidden {{ $t['track'] }}"
                                     style="left: {{ $bar['left'] }}%; width: {{ $bar['width'] }}%"
                                     title="{{ $bar['startText'] }} → {{ $bar['endText'] }} ({{ $bar['spanDays'] }} hari) — {{ $bar['label'] }}">
                                    <div class="h-full {{ $t['fill'] }}" style="width: {{ $bar['fill'] }}%"></div>
                                </div>
                            </div>

                            {{-- Keterangan sisa SLA --}}
                            <div class="w-32 shrink-0 pl-3 text-right">
                                <span class="text-xs font-medium {{ $t['text'] }}">{{ $bar['label'] }}</span>
                                <span class="block text-[11px] text-gray-400">{{ $bar['endText'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Keterangan penanda hari ini --}}
                <div class="flex">
                    <div class="w-48 shrink-0"></div>
                    <div class="relative flex-1 h-5">
                        <span class="absolute -translate-x-1/2 text-[11px] text-blue-500 whitespace-nowrap"
                              style="left: {{ $todayPct }}%">hari ini</span>
                    </div>
                    <div class="w-32 shrink-0"></div>
                </div>
            </div>
        @endif
    </div>

    {{-- ===================== BELUM TERJADWAL ===================== --}}
    @if ($unscheduled->isNotEmpty())
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden lift">
            <div class="px-5 pt-5 pb-3">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Belum Terjadwal</h2>
                <p class="mt-0.5 text-xs text-gray-400">
                    Sudah In-Progress / Scheduled tapi tanggal survei belum diisi, sehingga belum bisa dipetakan di timeline.
                </p>
            </div>
            @foreach ($unscheduled as $p)
                <a href="{{ route('proposals.show', $p) }}"
                   class="flex items-center justify-between gap-4 px-5 py-3 border-t border-gray-100 hover:bg-blue-50/40">
                    <span class="min-w-0">
                        <span class="block text-sm font-medium text-gray-900 truncate">{{ $p->proposal_number }}</span>
                        <span class="block text-xs text-gray-500 truncate">{{ $p->instructingClient->client_name ?? '-' }}</span>
                    </span>
                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap {{ $p->status_badge_classes }}">
                        {{ $p->status }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
