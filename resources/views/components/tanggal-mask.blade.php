{{-- Field tanggal bermasker (2026-10-02, permintaan user: format tanggal
     beda-beda antar perangkat karena <input type="date"> ikut locale
     browser/OS). Tampilan dipaksa "dd/mm/yyyy" dari kode sendiri; ikon
     kalender tetap membuka date picker native lewat input tersembunyi.
     Yang disubmit ke server: hidden input "name" berisi ISO (yyyy-mm-dd),
     format & validasi backend TIDAK berubah.

     Pola ini sebelumnya ditulis manual dua kali (Tanggal Proposal di
     proposals/create.blade.php & proposals/edit.blade.php) — sekarang satu
     komponen dipakai bersama lewat <x-tanggal-mask>.

     Props:
       name     : nama field yang disubmit (wajib), boleh notasi array mis. "surveys[3][start]"
       value    : tanggal ISO (yyyy-mm-dd) atau null
       id       : prefix id elemen, bawaan diturunkan dari name
       required : true/false (bawaan false) — diterapkan ke input TEKS (tampilan),
                  bukan ke hidden, karena browser mengabaikan "required" pada type=hidden
       disabled : true/false (bawaan false) — kondisi awal terkunci (mis. data sudah final)
       placeholder : bawaan "dd/mm/yyyy"
       inputClass  : KELAS BORDER input teksnya, termasuk default & warna error
                     (mis. dari helper $errCls()) — beda dengan atribut biasa/class
                     di tag komponen yang jatuh ke div pembungkus. Bawaan border abu
                     normal; JANGAN tambah border-gray-300 lagi di pemanggil kalau
                     sudah pakai helper error, nanti rebutan sama warna merahnya. --}}
@props([
    'name',
    'value'       => null,
    'id'          => null,
    'required'    => false,
    'disabled'    => false,
    'placeholder' => 'dd/mm/yyyy',
    'inputClass'  => 'border-gray-300 dark:border-gray-600',
])

@php
    $idBase = $id ?: 'tm_' . preg_replace('/[^a-zA-Z0-9]+/', '_', $name);
    $isoValue = $value instanceof \Carbon\Carbon ? $value->toDateString() : (string) $value;
@endphp

<div {{ $attributes->merge(['class' => 'relative']) }} data-tanggal-mask>
    <input type="text" id="{{ $idBase }}_display" data-tm-display
           value="{{ $isoValue ? \Carbon\Carbon::parse($isoValue)->format('d/m/Y') : '' }}"
           @if ($required) required @endif
           @disabled($disabled)
           placeholder="{{ $placeholder }}" inputmode="numeric" autocomplete="off" maxlength="10"
           class="w-full rounded-md shadow-sm pr-10 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed {{ $inputClass }}">
    <button type="button" id="{{ $idBase }}_pick" data-tm-pick tabindex="-1" aria-label="Pilih dari kalender"
            @disabled($disabled)
            class="absolute inset-y-0 right-0 grid w-10 place-items-center text-gray-400 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40 dark:text-gray-400 dark:hover:text-gray-200">
        <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0V11.25A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
        </svg>
    </button>
    <input type="date" id="{{ $idBase }}_picker" data-tm-picker tabindex="-1" aria-hidden="true" lang="id"
           class="pointer-events-none absolute bottom-0 left-0 h-px w-px opacity-0">
    <input type="hidden" name="{{ $name }}" id="{{ $idBase }}_iso" data-tm-iso value="{{ $isoValue }}">
</div>
