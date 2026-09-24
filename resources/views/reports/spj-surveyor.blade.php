@extends('layouts.app')

@section('title', 'SPJ Surveyor')

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    <x-page-header title="SPJ Surveyor"
        subtitle="Proyek dengan tanggal survei {{ $from->translatedFormat('d M Y') }} &ndash; {{ $to->translatedFormat('d M Y') }}">
        <div class="flex items-center gap-4">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-500">Proyek</p>
                <p class="text-xl font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ $totalProjects }}</p>
            </div>
            <div class="border-l border-gray-200 pl-4 dark:border-gray-700">
                <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-500">Objek</p>
                <p class="text-xl font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ $totalObjects }}</p>
            </div>
        </div>
    </x-page-header>

    {{-- Kartu filter selebar isinya saja & rata kanan (2026-09-20, feedback user). --}}
    <form method="GET" action="{{ route('spj.index') }}"
          class="flex w-full flex-wrap items-end gap-2 rounded-lg border border-gray-200 bg-white p-3 shadow-sm sm:ml-auto sm:w-fit sm:max-w-full dark:border-gray-700 dark:bg-gray-800">
        <div class="min-w-[150px] flex-1 sm:flex-none">
            <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-500">Dari tanggal</label>
            <input type="date" name="from" lang="id" value="{{ $from->toDateString() }}"
                   class="w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600">
        </div>
        <div class="min-w-[150px] flex-1 sm:flex-none">
            <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-500">Sampai tanggal</label>
            <input type="date" name="to" lang="id" value="{{ $to->toDateString() }}"
                   class="w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600">
        </div>
        <div class="w-full min-w-[200px] sm:w-auto">
            <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-500">Penilai</label>
            <select name="appraiser" class="w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600">
                <option value="">Semua penilai</option>
                @foreach ($appraiserOptions as $opt)
                    <option value="{{ $opt->id }}" @selected((int) request('appraiser') === $opt->id)>{{ $opt->name }}</option>
                @endforeach
            </select>
        </div>
        {{-- Contoh komponen tombol (A2, 2026-09-20) — menunggu persetujuan
             sebelum dipakai di seluruh aplikasi. --}}
        <x-btn>Tampilkan</x-btn>
        <x-btn variant="outline" :href="route('spj.index')">Bulan ini</x-btn>
    </form>

    @forelse ($rows as $row)
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    @include('partials.user-avatar', ['avatarUser' => $row['user'], 'avatarClass' => 'h-9 w-9 bg-blue-600 text-sm'])
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $row['user']->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">{{ $row['user']->jabatan ?: 'Jabatan belum diisi' }}</p>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-500">
                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $row['projects']->count() }}</span> proyek &middot;
                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $row['objects'] }}</span> objek
                </p>
            </div>

            {{-- Tabel untuk layar lebar; layar sempit memakai kartu di bawah
                 (2026-09-20, feedback user) supaya tak perlu digeser samping. --}}
            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full min-w-[720px] text-[13px]">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-500">
                        <tr>
                            <th class="px-4 py-2 w-[110px]">Tgl. Survei</th>
                            <th class="px-4 py-2 w-[150px]">No. Proposal</th>
                            <th class="px-4 py-2 min-w-[170px]">Nama Klien</th>
                            <th class="px-4 py-2">Objek yang Disurvei</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($row['projects'] as $p)
                            <tr class="align-top hover:bg-blue-50/40 dark:hover:bg-blue-900/20">
                                <td class="whitespace-nowrap px-4 py-2 text-gray-600 dark:text-gray-500">{{ $p->survey_date->translatedFormat('d M Y') }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('proposals.show', $p) }}" title="{{ $p->proposal_number }}" aria-label="{{ $p->proposal_number }}"
                                       class="font-medium text-blue-600 hover:underline dark:text-blue-400">{{ $p->proposal_number_short }}</a>
                                </td>
                                <td class="px-4 py-2 font-semibold text-gray-900 dark:text-gray-100">{{ $p->effective_client_name ?: '-' }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-500">
                                    @forelse ($row['objectsPer'][$p->id] ?? collect() as $obj)
                                        <div class="{{ $loop->first ? '' : 'mt-1' }}">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $loop->iteration }}. {{ $obj->short_label }}</span>
                                            <span class="text-gray-500 dark:text-gray-500">&mdash; {{ $obj->location ?: '(lokasi belum diisi)' }}</span>
                                        </div>
                                    @empty
                                        <span class="text-gray-500 dark:text-gray-400">(belum ada objek)</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ---------- KARTU (HP & TABLET) ---------- --}}
            <div class="divide-y divide-gray-100 lg:hidden dark:divide-gray-700">
                @foreach ($row['projects'] as $p)
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <a href="{{ route('proposals.show', $p) }}" title="{{ $p->proposal_number }}" aria-label="{{ $p->proposal_number }}"
                                   class="text-sm font-semibold text-blue-600 hover:underline dark:text-blue-400">{{ $p->proposal_number_short }}</a>
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $p->effective_client_name ?: '-' }}</p>
                            </div>
                            <span class="shrink-0 whitespace-nowrap rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                {{ $p->survey_date->translatedFormat('d M Y') }}
                            </span>
                        </div>

                        <p class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Objek disurvei ({{ ($row['objectsPer'][$p->id] ?? collect())->count() }})
                        </p>
                        <div class="mt-1 space-y-1 text-xs text-gray-600 dark:text-gray-500">
                            @forelse ($row['objectsPer'][$p->id] ?? collect() as $obj)
                                <div class="rounded-md bg-gray-50 px-2 py-1.5 dark:bg-gray-900">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $loop->iteration }}. {{ $obj->short_label }}</span>
                                    <span class="block text-gray-500 dark:text-gray-500">{{ $obj->location ?: '(lokasi belum diisi)' }}</span>
                                </div>
                            @empty
                                <span class="text-gray-500 dark:text-gray-400">(belum ada objek)</span>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="rounded-lg border border-gray-200 bg-white p-10 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <svg aria-hidden="true" class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
            </svg>
            <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300">Tidak ada survei pada rentang ini</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ubah rentang tanggal, atau kembali ke bulan berjalan.</p>
            <x-btn variant="outline" :href="route('spj.index')" class="mt-4">Bulan ini</x-btn>
        </div>
    @endforelse

    @if ($paginator->hasPages())
        <div>{{ $paginator->links() }}</div>
    @endif
</div>
@endsection
