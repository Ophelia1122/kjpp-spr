@extends('layouts.app')

@section('title', 'Sampah')

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    <x-page-header title="Sampah"
        subtitle="Proyek &amp; klien yang dihapus disimpan di sini {{ $retention }} hari, lalu dibuang permanen.">
        @if (auth()->user()->isAdministrator() && ($projects->isNotEmpty() || $clients->isNotEmpty()))
            <button type="button" onclick="openPurgeTrashModal()"
                    class="inline-flex h-[38px] items-center gap-1.5 rounded-md border border-rose-300 px-4 text-sm font-medium text-rose-600 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-400 dark:hover:bg-rose-900/30">
                <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                </svg>
                Kosongkan Sampah
            </button>
        @endif
    </x-page-header>

    {{-- Kosongkan seluruh Sampah (2026-09-25, permintaan user). Wajib kata
         sandi karena tidak bisa dibatalkan — sama seperti Kosongkan Log. --}}
    @if (auth()->user()->isAdministrator())
        <div id="purgeTrashModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-gray-900/60 p-4" role="dialog" aria-modal="true"
             onclick="if (event.target === this) closePurgeTrashModal()">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-800">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Kosongkan Sampah</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    <b>{{ $projects->count() }}</b> proyek dan <b>{{ $clients->count() }}</b> klien akan dihapus
                    <b>permanen</b> beserta seluruh invoice proyeknya. Tindakan ini <b>tidak bisa dibatalkan</b>.
                </p>
                <form method="POST" action="{{ route('trash.purgeAll') }}" class="mt-4 space-y-3">
                    @csrf
                    @method('DELETE')
                    <div>
                        <label for="purge_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kata sandi Anda</label>
                        <input type="password" name="password" id="purge_password" required autocomplete="current-password"
                               class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900">
                        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" onclick="closePurgeTrashModal()"
                                class="inline-flex h-[38px] items-center rounded-md border border-gray-300 px-4 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">Batal</button>
                        <button type="submit"
                                class="inline-flex h-[38px] items-center rounded-md bg-rose-600 px-4 text-sm font-medium text-white hover:bg-rose-700">Hapus Semua Permanen</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            const purgeTrashModal = document.getElementById('purgeTrashModal');
            document.body.appendChild(purgeTrashModal);

            function openPurgeTrashModal() {
                purgeTrashModal.classList.remove('hidden');
                purgeTrashModal.classList.add('flex');
                document.getElementById('purge_password').focus();
            }

            function closePurgeTrashModal() {
                purgeTrashModal.classList.add('hidden');
                purgeTrashModal.classList.remove('flex');
            }

            @if ($errors->has('password'))
                openPurgeTrashModal();
            @endif
        </script>
    @endif

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
