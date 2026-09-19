@extends('layouts.app')

@section('title', 'SPJ Surveyor')

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    <div class="rounded-lg border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex min-w-0 items-start gap-3">
                <span class="mt-1 h-9 w-1 shrink-0 rounded-full bg-indigo-500" aria-hidden="true"></span>
                <div class="min-w-0">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">SPJ Surveyor</h1>
                <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">
                    Proyek dengan tanggal survei {{ $from->translatedFormat('d M Y') }} &ndash; {{ $to->translatedFormat('d M Y') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500">Proyek</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $totalProjects }}</p>
                </div>
                <div class="border-l border-gray-200 pl-4 dark:border-gray-700">
                    <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500">Objek</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $totalObjects }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Kartu filter selebar isinya saja & rata kanan (2026-09-20, feedback user). --}}
    <form method="GET" action="{{ route('spj.index') }}"
          class="flex w-full flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:ml-auto sm:w-fit sm:max-w-full dark:border-gray-700 dark:bg-gray-800">
        <div class="min-w-[150px] flex-1 sm:flex-none">
            <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Dari tanggal</label>
            <input type="date" name="from" lang="id" value="{{ $from->toDateString() }}"
                   class="w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600">
        </div>
        <div class="min-w-[150px] flex-1 sm:flex-none">
            <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Sampai tanggal</label>
            <input type="date" name="to" lang="id" value="{{ $to->toDateString() }}"
                   class="w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600">
        </div>
        <div class="w-full min-w-[200px] sm:w-auto">
            <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Penilai</label>
            <select name="appraiser" class="w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600">
                <option value="">Semua penilai</option>
                @foreach ($appraiserOptions as $opt)
                    <option value="{{ $opt->id }}" @selected((int) request('appraiser') === $opt->id)>{{ $opt->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-md border border-blue-600 bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700">
            Tampilkan
        </button>
        <a href="{{ route('spj.index') }}" class="rounded-md border border-gray-300 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">
            Bulan ini
        </a>
    </form>

    @forelse ($rows as $row)
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    @include('partials.user-avatar', ['avatarUser' => $row['user'], 'avatarClass' => 'h-9 w-9 bg-blue-600 text-sm'])
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $row['user']->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['user']->jabatan ?: 'Jabatan belum diisi' }}</p>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $row['projects']->count() }}</span> proyek &middot;
                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $row['objects'] }}</span> objek
                </p>
            </div>

            {{-- Tabel untuk layar lebar; layar sempit memakai kartu di bawah
                 (2026-09-20, feedback user) supaya tak perlu digeser samping. --}}
            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full min-w-[720px] text-[12px]">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
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
                                <td class="whitespace-nowrap px-4 py-2 text-gray-600 dark:text-gray-400">{{ $p->survey_date->translatedFormat('d M Y') }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('proposals.show', $p) }}" title="{{ $p->proposal_number }}"
                                       class="font-medium text-blue-600 hover:underline dark:text-blue-400">{{ $p->proposal_number_short }}</a>
                                </td>
                                <td class="px-4 py-2 font-semibold text-gray-900 dark:text-gray-100">{{ $p->effective_client_name ?: '-' }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                                    @forelse ($p->valuationObjects as $obj)
                                        <div class="{{ $loop->first ? '' : 'mt-1' }}">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $loop->iteration }}. {{ $obj->short_label }}</span>
                                            <span class="text-gray-500 dark:text-gray-400">&mdash; {{ $obj->location ?: '(lokasi belum diisi)' }}</span>
                                        </div>
                                    @empty
                                        <span class="text-gray-400 dark:text-gray-500">(belum ada objek)</span>
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
                                <a href="{{ route('proposals.show', $p) }}" title="{{ $p->proposal_number }}"
                                   class="text-sm font-semibold text-blue-600 hover:underline dark:text-blue-400">{{ $p->proposal_number_short }}</a>
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $p->effective_client_name ?: '-' }}</p>
                            </div>
                            <span class="shrink-0 whitespace-nowrap rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                {{ $p->survey_date->translatedFormat('d M Y') }}
                            </span>
                        </div>

                        <p class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                            Objek disurvei ({{ $p->valuationObjects->count() }})
                        </p>
                        <div class="mt-1 space-y-1 text-xs text-gray-600 dark:text-gray-400">
                            @forelse ($p->valuationObjects as $obj)
                                <div class="rounded-md bg-gray-50 px-2 py-1.5 dark:bg-gray-900">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $loop->iteration }}. {{ $obj->short_label }}</span>
                                    <span class="block text-gray-500 dark:text-gray-400">{{ $obj->location ?: '(lokasi belum diisi)' }}</span>
                                </div>
                            @empty
                                <span class="text-gray-400 dark:text-gray-500">(belum ada objek)</span>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="rounded-lg border border-gray-200 bg-white p-10 text-center text-sm text-gray-400 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
            Tidak ada survei pada rentang tanggal ini.
        </div>
    @endforelse
</div>
@endsection
