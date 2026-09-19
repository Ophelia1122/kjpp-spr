{{-- Catatan terakhir dari Riwayat Proyek. Param: $project --}}
@php $lastNote = $notes[$project->id] ?? null; @endphp
@if ($lastNote)
    <p class="text-xs text-gray-700 line-clamp-2 dark:text-gray-300" title="{{ $lastNote->note }}">{{ $lastNote->note }}</p>
    <p class="text-[11px] text-gray-400 dark:text-gray-500">{{ $lastNote->user->name ?? 'Sistem' }} &middot; {{ $lastNote->created_at->translatedFormat('d M, H:i') }}</p>
@else
    <span class="text-xs text-gray-300 dark:text-gray-600">—</span>
@endif
