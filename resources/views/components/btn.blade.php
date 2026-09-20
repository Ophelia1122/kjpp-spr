{{-- Tombol seragam (2026-09-20, hasil audit UI). Menggantikan kelas panjang
     yang ditulis ulang di tiap halaman, sehingga ukuran & warna tombol sama
     di seluruh aplikasi.

     Props:
       variant : primary (bawaan) | outline | ghost | danger | success
       size    : sm (tinggi 34px, bawaan) | md (38px)
       icon    : true = lebar dikunci sama dengan tinggi, untuk tombol ikon
       href    : bila diisi, dirender sebagai tautan, bukan button
       type    : submit (bawaan) | button

     JANGAN menulis contoh pemakaian komponen ini di dalam komentar Blade.
     Komentar Blade tidak bisa bersarang, jadi contohnya ikut dirender dan
     komponen memanggil dirinya sendiri sampai memori habis (kejadian nyata
     20 Sep 2026). Contoh pemakaian ada di docs/ui-komponen.md. --}}
@props([
    'variant' => 'primary',
    'size'    => 'sm',
    'icon'    => false,
    'href'    => null,
    'type'    => 'submit',
])

@php
    $base = 'inline-flex items-center justify-center gap-1.5 rounded-md border font-medium transition disabled:cursor-not-allowed disabled:opacity-60';

    $sizes = [
        'sm' => $icon ? 'h-[34px] w-[34px] text-xs' : 'h-[34px] px-3 text-xs',
        'md' => $icon ? 'h-[38px] w-[38px] text-sm' : 'h-[38px] px-4 text-sm',
    ];

    $variants = [
        'primary' => 'border-blue-600 bg-blue-600 text-white hover:border-blue-700 hover:bg-blue-700',
        'outline' => 'border-gray-300 bg-transparent text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60',
        'ghost'   => 'border-transparent bg-transparent text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700/60',
        'danger'  => 'border-red-600 bg-red-600 text-white hover:border-red-700 hover:bg-red-700',
        'success' => 'border-emerald-600 bg-transparent text-emerald-700 hover:bg-emerald-50 dark:border-emerald-500 dark:text-emerald-300 dark:hover:bg-emerald-900/30',
    ];

    $classes = trim($base . ' ' . ($sizes[$size] ?? $sizes['sm']) . ' ' . ($variants[$variant] ?? $variants['primary']));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
