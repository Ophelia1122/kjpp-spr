@extends('layouts.app')

@section('title', 'Kelola Rekening Bank')

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Kelola Rekening Bank</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $banks->count() }} rekening terdaftar — dipilih per proposal, dipakai di Invoice &amp; blok "Rekening Bank" proposal.</p>
        </div>
        <a href="{{ route('banks.create') }}"
           class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
            + Tambah Rekening
        </a>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto dark:bg-gray-800 dark:border-gray-700">
        @include('partials.scroll-hint')
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
                                <span class="text-xs text-gray-400 dark:text-gray-500">Belum dipakai</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-center items-center gap-1.5">
                                <a href="{{ route('banks.edit', $bank) }}" title="Edit rekening"
                                   class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100">
                                    <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                    </svg>
                                </a>
                                @if ($bank->projects_count === 0)
                                    <form action="{{ route('banks.destroy', $bank) }}" method="POST"
                                          data-confirm="Hapus rekening &quot;{{ $bank->bank_name }} - {{ $bank->account_number }}&quot;?">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Hapus rekening"
                                                class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-red-100 hover:text-red-700 dark:text-gray-400 dark:hover:bg-red-900/30 dark:hover:text-red-300">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <span title="Masih dipakai di proposal — tidak dapat dihapus"
                                          class="grid h-8 w-8 place-items-center rounded-md text-gray-300 cursor-not-allowed dark:text-gray-400">
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                        </svg>
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">Belum ada rekening bank.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-400 dark:text-gray-500">
        Rekening <strong>Default</strong> dipakai oleh proposal yang tidak memilih rekening secara eksplisit.
        Bila belum ada rekening sama sekali, dokumen memakai data lama dari <code>config/kjpp.php</code>.
    </p>
</div>
@endsection
