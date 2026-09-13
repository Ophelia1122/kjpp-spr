{{-- ===================== FLOATING ACTION BAR FORM PROPOSAL =====================
     Tombol Simpan & Batal form create/edit proposal (2026-09-14, feedback
     user) — pola sama persis dengan floating action bar di halaman detail:
     VERTIKAL melayang di kanan layar pada desktop (ikon saja + tooltip saat
     hover), HORIZONTAL menempel di bawah layar pada mobile dengan label teks
     (layar sentuh tidak punya hover).

     Tombol Simpan terhubung ke form lewat atribut form="proposalForm", jadi
     partial ini harus dipanggil DI LUAR container ".max-w-4xl" — container
     itu punya transform yang membuatnya jadi containing block untuk
     position:fixed sehingga bar akan ikut ter-offset ke tengah dokumen.

     Variabel: $cancelUrl, $cancelTip, $saveLabel, $saveTip, $saveTone --}}
@php
    $formActionTones = [
        'emerald' => 'text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 dark:text-emerald-400 dark:hover:bg-emerald-900/30',
        'blue'    => 'text-blue-600 hover:bg-blue-50 hover:text-blue-700 dark:text-blue-400 dark:hover:bg-blue-900/30',
    ];
    $formActionBtn = 'flex w-full flex-col items-center justify-center gap-0.5 rounded-lg px-2 py-1.5 text-[10px] font-medium leading-tight lg:h-10 lg:w-10 lg:gap-0 lg:px-0 lg:py-0';
    $formActionTip = 'pointer-events-none absolute right-full top-1/2 z-10 mr-2 hidden -translate-y-1/2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-[11px] font-medium text-white shadow-lg lg:group-hover:block dark:bg-gray-900';
@endphp

{{-- Spacer: mencegah konten terakhir halaman tertutup bar bawah di mobile. --}}
<div class="h-24 lg:hidden" aria-hidden="true"></div>

<div class="fixed z-40 inset-x-0 bottom-0 lg:inset-x-auto lg:bottom-auto lg:right-4 lg:top-1/2 lg:-translate-y-1/2">
    <div class="flex flex-row items-stretch justify-center gap-1 border-t border-gray-200 bg-white/95 p-2 shadow-lg backdrop-blur
                lg:flex-col lg:gap-1.5 lg:rounded-xl lg:border lg:p-1.5
                dark:border-gray-700 dark:bg-gray-800/95">

        {{-- Simpan --}}
        <div class="group relative flex-1 lg:flex-none">
            <span class="{{ $formActionTip }}">{{ $saveTip }}</span>
            <button type="submit" form="proposalForm" id="submitProposalBtn"
                    class="{{ $formActionBtn }} {{ $formActionTones[$saveTone] ?? $formActionTones['emerald'] }}">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <span class="lg:hidden">{{ $saveLabel }}</span>
            </button>
        </div>

        {{-- Batal --}}
        <div class="group relative flex-1 lg:flex-none">
            <span class="{{ $formActionTip }}">{{ $cancelTip }}</span>
            <a href="{{ $cancelUrl }}" id="cancelProposalLink"
               class="{{ $formActionBtn }} text-gray-500 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
                <span class="lg:hidden">Batal</span>
            </a>
        </div>
    </div>
</div>
