@extends('layouts.app')

@section('title', 'Beranda')

@php
    // Beranda = PR utama hari ini per peran (2026-09-15, feedback user). Data
    // disiapkan DashboardController@home; tiap peran punya partial sendiri.
    $rp    = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $panel = 'bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700';
    $slaTone = [
        'overdue'  => 'text-rose-600 dark:text-rose-400',
        'due-soon' => 'text-amber-600 dark:text-amber-400',
        'on-track' => 'text-emerald-600 dark:text-emerald-400',
        'done'     => 'text-gray-500 dark:text-gray-400',
        'none'     => 'text-gray-400 dark:text-gray-500',
    ];
    $clientOf = fn ($p) => $p->effective_client_name ?: '-';
    // "menunggu N hari" dihitung dari tanggal kalender.
    $waitDays = fn ($since) => $since ? (int) $since->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null;
@endphp

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    @include('dashboard.home._profile')

    {{-- Tab mode: Reviewer bisa juga bertugas sebagai penilai; Administrator bisa me-review. --}}
    @if (count($modes) > 1)
        <div class="flex flex-wrap gap-2">
            @foreach ($modes as $key => $label)
                <a href="{{ route('home', ['mode' => $key]) }}"
                   class="inline-flex items-center px-3 py-1.5 text-sm rounded-full font-medium {{ $mode === $key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    @endif

    @if ($mode === 'penilai')
        @include('dashboard.home._penilai')
    @elseif ($mode === 'reviewer')
        @include('dashboard.home._reviewer')
    @else
        {{-- General Admin memegang keuangan lebih dulu, jadi kartu keuangan naik
             ke atas dan panel produksi turun (2026-09-24, feedback user).
             Administrator tetap melihat produksi dulu. --}}
        @php $keuanganDuluan = $canFinance && ! auth()->user()->isAdministrator(); @endphp

        @if ($keuanganDuluan)
            @include('dashboard.home._keuangan')
            @include('dashboard.home._produksi')
        @else
            @include('dashboard.home._produksi')
            @if ($canFinance)
                @include('dashboard.home._keuangan')
            @endif
        @endif
    @endif
</div>
@endsection
