{{-- Kartu header halaman (2026-09-20, feedback user): judul + caption di kartu
     putih dengan aksen indigo setinggi kartu. Dipakai semua halaman yang punya
     menu di sidebar supaya seragam.

     Pemakaian:
       <x-page-header title="List Project" subtitle="…">
           …isi kanan (tombol/segmented/angka), opsional…
       </x-page-header>

     $subtitle boleh berisi HTML sederhana (angka tebal, titik warna). --}}
@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-lg border border-gray-200 bg-white py-4 pl-6 pr-5 shadow-sm dark:border-gray-700 dark:bg-gray-800']) }}>
    <span class="absolute inset-y-0 left-0 w-1.5 bg-indigo-500" aria-hidden="true"></span>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">{!! $subtitle !!}</p>
            @endif
            {{-- Slot "meta": baris tambahan di bawah judul (tanggal, catatan). --}}
            @isset($meta)
                <div class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">{{ $meta }}</div>
            @endisset
        </div>

        @if (trim($slot) !== '')
            <div class="flex flex-wrap items-center gap-3">{{ $slot }}</div>
        @endif
    </div>
</div>
