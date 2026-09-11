@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">
    
    {{-- ===================== HEADER + AKSI UTAMA ===================== --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard Project</h1>
            <p class="text-sm text-gray-500">{{ $projects->total() }} proyek ditemukan</p>
            <div class="flex gap-2 mt-2">
                <a href="{{ route('dashboard', array_merge(request()->except('mine'), [])) }}"
                   class="px-3 py-1 text-xs rounded-full font-medium {{ !request()->boolean('mine') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    🗂 Semua Proyek
                </a>
                <a href="{{ route('dashboard', array_merge(request()->all(), ['mine' => 1])) }}"
                   class="px-3 py-1 text-xs rounded-full font-medium {{ request()->boolean('mine') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    👤 Proyek Saya
                </a>
            </div>
        </div>
        <div class="flex gap-2">
        @can('reports.export')
            <a href="{{ route('dashboard.exportExcel', request()->query()) }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                📊 Export ke Excel
            </a>
        @endcan
        @can('proposals.manage')
            <a href="{{ route('proposals.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                + Buat Proposal Baru
            </a>
        @endcan
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
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto lift">
        <table class="min-w-[860px] w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    <th class="px-4 py-3">No. Proposal</th>
                    <th class="px-4 py-3">Pemberi Tugas</th>
                    <th class="px-4 py-3">Jenis Proposal</th>
                    <th class="px-4 py-3">Objek</th>
                    <th class="px-4 py-3 text-right whitespace-nowrap w-40">Fee Jasa</th>
                    <th class="px-4 py-3 whitespace-nowrap w-52">Status</th>
                    <th class="px-4 py-3 text-center w-28">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($projects as $project)
                    <tr class="hover:bg-blue-50/40 {{ $project->status === \App\Models\Project::STATUS_BATAL ? 'opacity-60' : '' }}">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $project->proposal_number }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $project->instructingClient->client_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $project->proposal_purpose }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $project->asset_type }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 whitespace-nowrap tabular-nums">
                            Rp {{ number_format($project->total_fee, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap {{ $project->status_badge_classes }}">
                                {{ $project->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-center items-center gap-1.5">
                                <a href="{{ route('proposals.show', $project) }}" title="Lihat / kelola"
                                   class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-blue-100 hover:text-blue-700">
                                    <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                    </svg>
                                </a>
                                @can('proposals.manage')
                                    @if ($project->status === \App\Models\Project::STATUS_DRAFT)
                                        <a href="{{ route('proposals.edit', $project) }}" title="Edit proposal"
                                           class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                            </svg>
                                        </a>
                                        <form action="{{ route('proposals.destroy', $project) }}" method="POST"
                                              onsubmit="return confirm('Yakin hapus proposal {{ $project->proposal_number }}? Aksi ini tidak bisa dibatalkan.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hapus proposal"
                                                    class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-red-100 hover:text-red-700">
                                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
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
