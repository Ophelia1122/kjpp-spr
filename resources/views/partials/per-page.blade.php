{{-- Pilihan jumlah baris per halaman 15/25, seragam untuk semua daftar (2026-09-15). Param: $paginator --}}
<div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
    <span>Tampilkan</span>
    @foreach ([15, 25] as $size)
        <a href="{{ request()->fullUrlWithQuery(['per_page' => $size, 'page' => null]) }}"
           class="px-2 py-1 rounded-md font-medium {{ $paginator->perPage() === $size ? 'bg-blue-600 text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}">{{ $size }}</a>
    @endforeach
</div>
