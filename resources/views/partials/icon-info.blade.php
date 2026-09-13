{{-- Ikon ⓘ dengan tooltip hover.
     Dipakai untuk memindahkan caption panjang keluar dari alur baca form.
     Pemakaian: @include('partials.icon-info', ['tip' => 'penjelasan...']) --}}
<span title="{{ $tip }}" tabindex="0"
      class="ml-1 inline-flex cursor-help align-middle text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
    <svg class="h-[15px] w-[15px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
    </svg>
</span>
