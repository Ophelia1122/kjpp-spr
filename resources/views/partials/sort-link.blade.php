{{-- Header kolom yang bisa diurutkan, seragam dengan List Project (2026-09-15).
     Params: key, label. Butuh $currentSort & $currentDir dari controller. --}}
@php
    $isSorted = ($currentSort ?? null) === $key;
    $nextDir  = ($isSorted && ($currentDir ?? 'asc') === 'asc') ? 'desc' : 'asc';
@endphp
<a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'dir' => $nextDir, 'page' => null]) }}"
   class="inline-flex items-center gap-1 hover:text-gray-800 dark:hover:text-gray-200">
    {{ $label }}
    @if ($isSorted)
        <svg class="h-3 w-3 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ ($currentDir ?? 'asc') === 'asc' ? 'm4.5 15.75 7.5-7.5 7.5 7.5' : 'm19.5 8.25-7.5 7.5-7.5-7.5' }}"/>
        </svg>
    @else
        <svg class="h-3 w-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/>
        </svg>
    @endif
</a>
