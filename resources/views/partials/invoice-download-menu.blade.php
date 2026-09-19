{{-- Satu tombol "Unduh" berisi Invoice & Kwitansi (PDF/Word), menggantikan 4 ikon
     terpisah (2026-09-14, feedback user). Dipakai halaman proyek & Dashboard
     Pembayaran. Buka/tutup + posisi menu diatur skrip [data-dropdown] di layout.

     Parameter: $inv (Invoice), $kwitansiAvailable (bool). --}}
@php
    $menuItem = 'flex items-center justify-between gap-4 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700/60';
    $fmtBadge = 'rounded border px-1.5 text-[10px] font-semibold leading-4';
@endphp
<div class="relative" data-dropdown>
    <button type="button" data-dropdown-toggle aria-haspopup="menu" aria-expanded="false" title="Unduh invoice / kwitansi"
            class="inline-flex h-8 items-center gap-0.5 rounded-md px-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100">
        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
        </svg>
        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
        </svg>
    </button>

    <div data-dropdown-menu role="menu" style="display: none;"
         class="z-50 w-56 rounded-lg border border-gray-200 bg-white py-1 text-left shadow-lg dark:border-gray-700 dark:bg-gray-800">
        <p class="px-3 pt-1.5 pb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Invoice</p>
        <a href="{{ route('invoices.exportInvoice', $inv) }}" role="menuitem" class="{{ $menuItem }}">
            Unduh Invoice <span class="{{ $fmtBadge }} border-rose-300 text-rose-600 dark:border-rose-800 dark:text-rose-400">PDF</span>
        </a>
        <a href="{{ route('invoices.exportInvoiceWord', $inv) }}" role="menuitem" class="{{ $menuItem }}">
            Unduh Invoice <span class="{{ $fmtBadge }} border-blue-300 text-blue-600 dark:border-blue-800 dark:text-blue-400">Word</span>
        </a>

        <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
        <p class="px-3 pt-1 pb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Kwitansi</p>
        {{-- Kwitansi: setelah Dibayar, atau kapan saja untuk skema Bayar Nanti. --}}
        @if ($kwitansiAvailable)
            <a href="{{ route('invoices.exportKwitansi', $inv) }}" role="menuitem" class="{{ $menuItem }}">
                Unduh Kwitansi <span class="{{ $fmtBadge }} border-rose-300 text-rose-600 dark:border-rose-800 dark:text-rose-400">PDF</span>
            </a>
            <a href="{{ route('invoices.exportKwitansiWord', $inv) }}" role="menuitem" class="{{ $menuItem }}">
                Unduh Kwitansi <span class="{{ $fmtBadge }} border-blue-300 text-blue-600 dark:border-blue-800 dark:text-blue-400">Word</span>
            </a>
        @else
            <p class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500" aria-disabled="true">
                Kwitansi PDF &amp; Word
                <span class="block text-[11px]">Tersedia setelah invoice ditandai dibayar</span>
            </p>
        @endif
    </div>
</div>
