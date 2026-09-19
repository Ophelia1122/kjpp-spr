{{-- Sakelar mode terang/gelap (2026-09-15, feedback user) — pentol matahari/bulan
     yang bergeser. Dipakai di sidebar & header HP; semua instance disinkronkan
     oleh toggleTheme() di layouts/app. Gaya: .theme-switch (layouts/app). --}}
@auth
    @php $isDark = (bool) auth()->user()->dark_mode; @endphp
    <button type="button" data-theme-toggle onclick="toggleTheme()" role="switch"
            aria-checked="{{ $isDark ? 'true' : 'false' }}"
            aria-label="{{ $isDark ? 'Ganti ke mode terang' : 'Ganti ke mode gelap' }}"
            title="Mode terang / gelap"
            class="theme-switch {{ $class ?? '' }}">
        <span class="knob" aria-hidden="true">
            <svg class="icon-sun" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/></svg>
            <svg class="icon-moon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/></svg>
        </span>
    </button>
@endauth
