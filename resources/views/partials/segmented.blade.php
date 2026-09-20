{{-- Segmented control (2026-09-19, feedback user): dua pilihan dalam satu
     wadah abu, yang aktif jadi putih ber-shadow. Menggantikan dua pill lepas
     yang membuat pilihan tidak aktif nyaris tak terbaca.
     Param: $items = [['url' =>, 'label' =>, 'active' => bool, 'icon' => path-d]] --}}
<div class="inline-flex rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900/60">
    @foreach ($items as $item)
        <a href="{{ $item['url'] }}"
           class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition
                  {{ $item['active']
                        ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-gray-100'
                        : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200' }}">
            @isset($item['icon'])
                <svg aria-hidden="true" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                </svg>
            @endisset
            {{ $item['label'] }}
        </a>
    @endforeach
</div>
