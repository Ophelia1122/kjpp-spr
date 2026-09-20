@extends('layouts.app')

@section('title', 'Sampah')

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    <x-page-header title="Sampah"
        subtitle="Proyek &amp; klien yang dihapus disimpan di sini {{ $retention }} hari, lalu dibuang permanen." />

    {{-- ===================== PROYEK ===================== --}}
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Proyek <span class="font-normal normal-case">({{ $projects->count() }})</span>
            </h2>
        </div>

        @forelse ($projects as $p)
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-4 py-3 last:border-b-0 dark:border-gray-700">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $p->proposal_number }}</p>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                        {{ $p->effective_client_name ?: '-' }} &middot; status terakhir {{ $p->status }}
                    </p>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        Dibuang {{ $p->deleted_at->translatedFormat('d M Y, H:i') }} &middot;
                        dibersihkan {{ $p->deleted_at->copy()->addDays($retention)->translatedFormat('d M Y') }}
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <form action="{{ route('trash.projects.restore', $p->id) }}" method="POST">
                        @csrf
                        <x-btn variant="outline">Pulihkan</x-btn>
                    </form>
                    <form action="{{ route('trash.projects.forceDelete', $p->id) }}" method="POST"
                          data-confirm="Hapus permanen proposal {{ $p->proposal_number }} beserta seluruh invoice-nya? Aksi ini tidak bisa dibatalkan.">
                        @csrf
                        @method('DELETE')
                        <x-btn variant="danger">Hapus permanen</x-btn>
                    </form>
                </div>
            </div>
        @empty
            <p class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada proyek di Sampah.</p>
        @endforelse
    </div>

    {{-- ===================== KLIEN ===================== --}}
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Klien <span class="font-normal normal-case">({{ $clients->count() }})</span>
            </h2>
        </div>

        @forelse ($clients as $c)
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-4 py-3 last:border-b-0 dark:border-gray-700">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $c->client_name }}</p>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $c->client_type }} &middot; {{ $c->address ?: 'alamat belum diisi' }}</p>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        Dibuang {{ $c->deleted_at->translatedFormat('d M Y, H:i') }} &middot;
                        dibersihkan {{ $c->deleted_at->copy()->addDays($retention)->translatedFormat('d M Y') }}
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <form action="{{ route('trash.clients.restore', $c->id) }}" method="POST">
                        @csrf
                        <x-btn variant="outline">Pulihkan</x-btn>
                    </form>
                    <form action="{{ route('trash.clients.forceDelete', $c->id) }}" method="POST"
                          data-confirm="Hapus permanen klien &quot;{{ $c->client_name }}&quot;? Aksi ini tidak bisa dibatalkan.">
                        @csrf
                        @method('DELETE')
                        <x-btn variant="danger">Hapus permanen</x-btn>
                    </form>
                </div>
            </div>
        @empty
            <p class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada klien di Sampah.</p>
        @endforelse
    </div>
</div>
@endsection
