@extends('layouts.app')

@section('title', 'Kelola Rekening Bank')

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    <x-page-header title="Kelola Rekening Bank"
        subtitle='{{ $banks->count() }} rekening terdaftar — dipilih per proposal, dipakai di Invoice &amp; blok "Rekening Bank" proposal.'>
        <a href="{{ route('banks.create') }}"
           class="inline-flex h-[34px] items-center justify-center gap-1.5 rounded-md border border-blue-600 bg-blue-600 px-3 text-xs font-medium text-white transition hover:border-blue-700 hover:bg-blue-700">
            <svg aria-hidden="true" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Tambah Rekening
        </a>
    </x-page-header>

    {{-- Tabel untuk layar lebar; layar sempit memakai kartu (2026-09-20). --}}
    <div class="hidden overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm md:block dark:border-gray-700 dark:bg-gray-800">
        <table class="min-w-[640px] w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">
                    <th class="px-4 py-3">Bank</th>
                    <th class="px-4 py-3">Cabang</th>
                    <th class="px-4 py-3">No. Rekening</th>
                    <th class="px-4 py-3">Atas Nama</th>
                    <th class="px-4 py-3 text-center">Dipakai</th>
                    <th class="px-4 py-3 text-center w-24">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($banks as $bank)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/60">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                            {{ $bank->bank_name }}
                            @if ($bank->is_default)
                                <span class="ml-1 px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800">Default</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $bank->branch ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 font-mono dark:text-gray-400">{{ $bank->account_number }}</td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $bank->account_name }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($bank->projects_count > 0)
                                <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">{{ $bank->projects_count }} proposal</span>
                            @else
                                <span class="text-xs text-gray-500 dark:text-gray-400">Belum dipakai</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-center items-center gap-1.5">
                                <a href="{{ route('banks.edit', $bank) }}" title="Edit rekening" aria-label="Edit rekening"
                                   class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100">
                                    <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                    </svg>
                                </a>
                                @if ($bank->projects_count === 0)
                                    <form action="{{ route('banks.destroy', $bank) }}" method="POST"
                                          data-confirm="Hapus rekening &quot;{{ $bank->bank_name }} - {{ $bank->account_number }}&quot;?">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Hapus rekening" aria-label="Hapus rekening"
                                                class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-red-100 hover:text-red-700 dark:text-gray-400 dark:hover:bg-red-900/30 dark:hover:text-red-300">
                                            <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <span title="Masih dipakai di proposal — tidak dapat dihapus"
                                          class="grid h-8 w-8 place-items-center rounded-md text-gray-300 cursor-not-allowed dark:text-gray-400">
                                        <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                        </svg>
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center">
                            <svg aria-hidden="true" class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                            <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300">Belum ada rekening bank</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Rekening dipakai di Invoice &amp; blok "Rekening Bank" proposal.</p>
                            <x-btn :href="route('banks.create')" class="mt-4">Tambah Rekening</x-btn>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ---------- KARTU (HP) ---------- --}}
    <div class="space-y-3 md:hidden">
        @forelse ($banks as $bank)
            <div class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">
                            {{ $bank->bank_name }}
                            @if ($bank->is_default)
                                <span class="ml-1 rounded-full border border-emerald-200 bg-emerald-100 px-2 py-0.5 text-[11px] text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">Default</span>
                            @endif
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $bank->branch ?: 'Tanpa cabang' }}</p>
                    </div>
                    <span class="flex shrink-0 items-center gap-1">
                        <a href="{{ route('banks.edit', $bank) }}" title="Edit rekening" aria-label="Edit rekening"
                           class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-700 dark:hover:text-gray-100">
                            @include('partials.icon-pencil')
                        </a>
                        @if ($bank->projects_count === 0)
                            <form action="{{ route('banks.destroy', $bank) }}" method="POST"
                                  data-confirm="Hapus rekening &quot;{{ $bank->bank_name }} - {{ $bank->account_number }}&quot;?">
                                @csrf @method('DELETE')
                                <button type="submit" title="Hapus rekening" aria-label="Hapus rekening"
                                        class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-red-100 hover:text-red-700 dark:hover:bg-red-900/30 dark:hover:text-red-300">
                                    @include('partials.icon-trash')
                                </button>
                            </form>
                        @endif
                    </span>
                </div>

                <p class="mt-2 font-mono text-sm tabular-nums text-gray-800 dark:text-gray-200">{{ $bank->account_number }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">a.n. {{ $bank->account_name }}</p>

                <p class="mt-2 text-[11px]">
                    @if ($bank->projects_count > 0)
                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">{{ $bank->projects_count }} proposal</span>
                    @else
                        <span class="text-gray-500 dark:text-gray-400">Belum dipakai</span>
                    @endif
                </p>
            </div>
        @empty
            <div class="rounded-lg border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <svg aria-hidden="true" class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300">Belum ada rekening bank</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Rekening dipakai di Invoice &amp; blok "Rekening Bank" proposal.</p>
                <x-btn :href="route('banks.create')" class="mt-4">Tambah Rekening</x-btn>
            </div>
        @endforelse
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400">
        Rekening <strong>Default</strong> dipakai oleh proposal yang tidak memilih rekening secara eksplisit.
        Bila belum ada rekening sama sekali, dokumen memakai data lama dari <code>config/kjpp.php</code>.
    </p>
</div>
@endsection
