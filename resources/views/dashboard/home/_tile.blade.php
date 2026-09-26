{{-- Kartu angka Beranda. Params: label, value, hint, sub?, tone?, href? --}}
@php
    $valueTone = [
        'rose'    => 'text-rose-600 dark:text-rose-400',
        'amber'   => 'text-amber-600 dark:text-amber-400',
        'emerald' => 'text-emerald-600 dark:text-emerald-400',
        'indigo'  => 'text-indigo-600 dark:text-indigo-400',
    ][$tone ?? ''] ?? 'text-gray-900 dark:text-gray-100';
    $tileClass = 'block bg-white rounded-lg border border-gray-200 shadow-sm p-5 dark:bg-gray-800 dark:border-gray-700';
@endphp
@if (! empty($href))
<a href="{{ $href }}" class="{{ $tileClass }} lift hover:border-blue-300 dark:hover:border-blue-700">
@else
<div class="{{ $tileClass }}">
@endif
    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">{{ $label }}</div>
    <div class="mt-1 text-3xl font-bold tabular-nums {{ $valueTone }}" data-angka>{{ $value }}</div>
    @if (! empty($sub))
        <div class="text-sm font-semibold text-gray-700 tabular-nums dark:text-gray-300" data-angka>{{ $sub }}</div>
    @endif
    <p class="mt-1 text-xs text-gray-400 dark:text-gray-400">{{ $hint }}</p>
@if (! empty($href))
</a>
@else
</div>
@endif
