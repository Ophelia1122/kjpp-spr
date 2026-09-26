{{-- Tahap alur produksi saat ini + pemegang giliran. Param: $project --}}
<span class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 whitespace-nowrap dark:bg-indigo-900/30 dark:text-indigo-300">{{ $project->stage['label'] }}</span>
@if ($project->stage['actor'])
    <span class="mt-0.5 block text-[11px] text-gray-400 dark:text-gray-400">giliran {{ $project->stage['actor'] }}</span>
@endif
