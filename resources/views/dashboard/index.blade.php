@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">
    
    {{-- ===================== HEADER + AKSI UTAMA ===================== --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard Proyek</h1>
            <p class="text-sm text-gray-500">{{ $projects->total() }} proyek ditemukan</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('dashboard.exportExcel', request()->query()) }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                📊 Export ke Excel
            </a>
            <a href="{{ route('proposals.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                + Buat Proposal Baru
            </a>
        </div>
    </div>

    {{-- ===================== FILTER & PENCARIAN ===================== --}}
    <form method="GET" action="{{ route('dashboard') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[220px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Cari</label>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="No. proposal, pemilik aset, atau nama klien..."
                   class="w-full rounded-md border-gray-300 shadow-sm text-sm">
        </div>
        <div class="min-w-[200px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
            <select name="status" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">Semua Status</option>
                @foreach ($statusOptions as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 text-sm rounded-md bg-gray-800 text-white hover:bg-gray-900">
            Terapkan Filter
        </button>
        @if (request('q') || request('status'))
            <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50">
                Reset
            </a>
        @endif
    </form>

    {{-- ===================== TABEL PROYEK ===================== --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    <th class="px-4 py-3">No. Proposal</th>
                    <th class="px-4 py-3">Pemberi Tugas</th>
                    <th class="px-4 py-3">Jenis Proposal</th>
                    <th class="px-4 py-3">Objek</th>
                    <th class="px-4 py-3 text-right">Fee Jasa</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($projects as $project)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $project->proposal_number }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $project->instructingClient->client_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $project->proposal_purpose }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $project->asset_type }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">
                            Rp {{ number_format($project->service_fee, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $project->status_badge_classes }}">
                                {{ $project->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('proposals.show', $project) }}"
                               class="text-blue-600 hover:text-blue-800 font-medium">
                                Kelola &rarr;
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                            Belum ada proyek. Klik "+ Buat Proposal Baru" untuk memulai.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ===================== PAGINASI ===================== --}}
    <div>
        {{ $projects->links() }}
    </div>

</div>
@endsection
