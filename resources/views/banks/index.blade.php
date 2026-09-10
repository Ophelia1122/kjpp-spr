@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-8 space-y-6">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Master Rekening Bank</h1>
            <p class="text-sm text-gray-500">{{ $banks->count() }} rekening terdaftar — dipilih per proposal, dipakai di Invoice &amp; blok "Rekening Bank" proposal.</p>
        </div>
        <a href="{{ route('banks.create') }}"
           class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
            + Tambah Rekening
        </a>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-[640px] w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    <th class="px-4 py-3">Bank</th>
                    <th class="px-4 py-3">Cabang</th>
                    <th class="px-4 py-3">No. Rekening</th>
                    <th class="px-4 py-3">Atas Nama</th>
                    <th class="px-4 py-3 text-center">Dipakai</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($banks as $bank)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">
                            {{ $bank->bank_name }}
                            @if ($bank->is_default)
                                <span class="ml-1 px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700 border border-emerald-200">Default</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $bank->branch ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 font-mono">{{ $bank->account_number }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $bank->account_name }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($bank->projects_count > 0)
                                <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">{{ $bank->projects_count }} proposal</span>
                            @else
                                <span class="text-xs text-gray-400">Belum dipakai</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-3">
                                <a href="{{ route('banks.edit', $bank) }}" class="text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                                @if ($bank->projects_count === 0)
                                    <form action="{{ route('banks.destroy', $bank) }}" method="POST"
                                          onsubmit="return confirm('Hapus rekening &quot;{{ $bank->bank_name }} - {{ $bank->account_number }}&quot;?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Hapus</button>
                                    </form>
                                @else
                                    <span class="text-gray-300 cursor-not-allowed" title="Masih dipakai di proposal">Hapus</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-400">Belum ada rekening bank.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-400">
        Rekening <strong>Default</strong> dipakai oleh proposal yang tidak memilih rekening secara eksplisit.
        Bila belum ada rekening sama sekali, dokumen memakai data lama dari <code>config/kjpp.php</code>.
    </p>
</div>
@endsection
