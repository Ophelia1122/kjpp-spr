{{-- SLA yang sedang berlaku (Draft / Final). Param: $project --}}
@php $sla = $project->active_sla; @endphp
@if ($sla)
    <p class="text-xs font-medium whitespace-nowrap {{ $slaTone[$sla['state']] ?? '' }}">{{ $sla['text'] }}</p>
    <p class="text-[11px] text-gray-400 whitespace-nowrap dark:text-gray-500">SLA {{ $sla['phase'] }} &middot; {{ $sla['date']->translatedFormat('d M Y') }}</p>
@else
    <span class="text-xs text-gray-400 dark:text-gray-500">Belum terjadwal</span>
@endif
