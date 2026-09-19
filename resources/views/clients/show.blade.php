@extends('layouts.app')

@section('title', $client->client_name)

@section('content')
@php
    $roleTone = [
        'Pemberi Tugas'    => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        'Nama Klien'       => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
        'Pengguna Laporan' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        'Pihak Menyetujui' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        'Surat Tugas'      => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        'Pembayar Invoice' => 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
    ];
@endphp
<div class="max-w-7xl mx-auto py-8 space-y-6">


    {{-- ===================== IDENTITAS KLIEN ===================== --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 break-words dark:text-gray-100">{{ $client->client_name }}</h1>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $client->client_type ?: 'Klien' }}</p>
            </div>
            @can('clients.manage')
                <a href="{{ route('clients.edit', $client) }}"
                   class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">
                    @include('partials.icon-pencil') Edit
                </a>
            @endcan
        </div>
        <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
            <div class="sm:col-span-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Alamat</dt>
                <dd class="mt-0.5 whitespace-pre-line text-gray-900 dark:text-gray-100">{{ $client->address ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Kontak</dt>
                <dd class="mt-0.5 text-gray-900 dark:text-gray-100">{{ $client->contact_person ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Telepon</dt>
                <dd class="mt-0.5 text-gray-900 dark:text-gray-100">{{ $client->phone ?: '—' }}</dd>
            </div>
            <div class="min-w-0">
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Email</dt>
                <dd class="mt-0.5 break-words text-gray-900 dark:text-gray-100">{{ $client->email ?: '—' }}</dd>
            </div>
        </dl>
    </div>

    {{-- ===================== PROYEK YANG MELIBATKAN KLIEN ===================== --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
        <div class="px-5 pt-5 pb-3">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Proyek terkait ({{ $projects->count() }})</h2>
            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Semua proyek yang mencantumkan klien ini, beserta perannya.</p>
        </div>

        @if ($projects->isEmpty())
            <div class="border-t border-gray-100 px-5 py-10 text-center text-sm text-gray-400 dark:border-gray-700 dark:text-gray-500">Klien ini belum dipakai di proyek mana pun.</div>
        @else
            {{-- Desktop: tabel --}}
            <div class="hidden md:block">
                <table class="w-full text-sm">
                    <thead class="border-y border-gray-100 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="px-5 py-2.5">No. Proposal</th>
                            <th class="px-4 py-2.5">Nama Klien</th>
                            <th class="px-4 py-2.5">Peran</th>
                            <th class="px-4 py-2.5">Tanggal</th>
                            <th class="px-5 py-2.5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($projects as $row)
                            @php $p = $row['project']; @endphp
                            <tr class="cursor-pointer align-top hover:bg-blue-50/40 dark:hover:bg-blue-900/20" onclick="location.href='{{ route('proposals.show', $p) }}'">
                                <td class="px-5 py-3 font-medium text-gray-900 dark:text-gray-100" title="{{ $p->proposal_number }}">{{ $p->proposal_number_short }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $p->effective_client_name ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($row['roles'] as $role)
                                            <span class="rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap {{ $roleTone[$role] ?? '' }}">{{ $role }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-700 whitespace-nowrap dark:text-gray-300">{{ $p->effective_proposal_date->translatedFormat('d M Y') }}</td>
                                <td class="px-5 py-3"><span class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap {{ $p->status_badge_classes }}">{{ $p->status_short }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Ponsel: kartu --}}
            <div class="divide-y divide-gray-100 border-t border-gray-100 md:hidden dark:divide-gray-700 dark:border-gray-700">
                @foreach ($projects as $row)
                    @php $p = $row['project']; @endphp
                    <a href="{{ route('proposals.show', $p) }}" class="block space-y-2 px-4 py-3 hover:bg-blue-50/40 dark:hover:bg-blue-900/20">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $p->proposal_number_short }}</p>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $p->effective_client_name ?: '-' }} &middot; {{ $p->effective_proposal_date->translatedFormat('d M Y') }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap {{ $p->status_badge_classes }}">{{ $p->status_short }}</span>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            @foreach ($row['roles'] as $role)
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $roleTone[$role] ?? '' }}">{{ $role }}</span>
                            @endforeach
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
