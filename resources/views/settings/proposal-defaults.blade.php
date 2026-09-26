@extends('layouts.app')

@section('title', 'Teks Baku Proposal')

@php
    $editable = collect($sections)->where('editable', true);
    $terisi   = $editable->where('overridden', true);
    $labelTab = $daftarTujuan[$tujuanAktif];
@endphp

@section('content')
<div class="max-w-5xl mx-auto py-8 space-y-6">

    {{-- ===================== HEADER ===================== --}}
    <div>
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Teks Baku Proposal</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Teks default tiap bab proposal, diatur terpisah untuk masing-masing tujuan penilaian.
            Kalau klausul berubah, ubah di sini — tidak perlu mengubah kode.
        </p>
    </div>

    {{-- ===================== TAB TUJUAN PENILAIAN ===================== --}}
    <div class="flex flex-wrap gap-2 border-b border-gray-200 pb-3 dark:border-gray-700">
        @foreach ($daftarTujuan as $nilai => $label)
            <a href="{{ route('settings.proposalDefaults.index', ['tujuan' => $nilai]) }}"
               class="rounded-md px-3 py-1.5 text-sm font-medium
                      {{ $nilai === $tujuanAktif
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- ===================== CARA KERJA ===================== --}}
    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 space-y-2 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
        <p class="font-semibold">Cara kerja</p>
        <ul class="list-disc list-inside space-y-1 text-blue-800 dark:text-blue-300">
            <li>Urutan teks yang dipakai saat proposal dicetak: <span class="font-medium">teks khusus proyek</span> (Editor Teks Proposal di halaman proyek) &rsaquo; <span class="font-medium">teks baku tujuan ini</span> &rsaquo; <span class="font-medium">teks baku "Semua Tujuan"</span> &rsaquo; teks bawaan sistem.</li>
            <li>Bab yang dibiarkan kosong di sini tetap memakai teks bawaan sistem. Tabel, penomoran bab, kop &amp; footer proposal <span class="font-medium">tidak berubah</span>.</li>
            <li>Perubahan berlaku untuk <span class="font-medium">proposal yang dicetak setelah ini</span>, termasuk proyek lama — kecuali proyek yang teksnya sudah ditimpa manual per proyek.</li>
            <li><span class="font-medium">Huruf tebal:</span> apit teks dengan dua bintang, contoh <code>**PT BANK DANAMON**</code> tercetak <b>PT BANK DANAMON</b>.</li>
            <li><span class="font-medium">Satu poin / paragraf per baris</span> (tekan Enter biasa). Baris berawalan <code>a.</code> <code>b.</code> <code>c.</code> otomatis dirapikan jadi daftar bernomor huruf. Baris kosong memisahkan kelompok.</li>
        </ul>

        <div class="pt-1">
            <p class="font-semibold">Kode isian otomatis</p>
            <p class="text-blue-800 dark:text-blue-300">Tulis kode berikut di dalam teks, nanti diganti data proyek saat dicetak:</p>
            <ul class="mt-1 grid gap-x-6 gap-y-0.5 sm:grid-cols-2">
                @foreach ($placeholder as $kode => $arti)
                    <li><code class="rounded bg-white px-1 dark:bg-gray-900">{{ $kode }}</code> — {{ $arti }}</li>
                @endforeach
            </ul>
        </div>

        <div class="pt-1 flex flex-wrap items-center gap-3">
            <span class="text-blue-700 dark:text-blue-400">
                {{ $terisi->count() }} dari {{ $editable->count() }} bab sudah diisi untuk tujuan "{{ $labelTab }}".
            </span>
            @if ($terisi->isNotEmpty())
                <form action="{{ route('settings.proposalDefaults.resetAll') }}" method="POST"
                      data-confirm="Hapus SEMUA {{ $terisi->count() }} teks baku untuk tujuan &quot;{{ $labelTab }}&quot;? Bab-bab itu kembali memakai teks bawaan sistem.">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="tujuan" value="{{ $tujuanAktif }}">
                    <button type="submit" class="font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                        Kembalikan semua ke bawaan sistem
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if ($jumlahTimpa > 0)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
            {{ $jumlahTimpa }} proyek punya teks yang ditimpa manual per proyek. Proyek tersebut tetap memakai teksnya sendiri,
            tidak terpengaruh perubahan di halaman ini.
        </div>
    @endif

    {{-- ===================== DAFTAR BAB ===================== --}}
    <div class="space-y-4">
        @foreach ($sections as $i => $section)
            <div id="bab-{{ $section['key'] }}"
                 class="scroll-mt-6 rounded-lg border bg-white p-5 shadow-sm
                        {{ $section['overridden'] ? 'border-amber-300' : 'border-gray-200' }} dark:bg-gray-800 dark:border-gray-700">

                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        <span class="font-normal text-gray-400 dark:text-gray-400">{{ $i + 1 }}.</span>
                        {{ $section['title'] }}
                    </h2>
                    @if (! $section['editable'])
                        <span class="shrink-0 rounded-full border border-gray-200 bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                            Otomatis
                        </span>
                    @elseif ($section['overridden'])
                        <span class="shrink-0 rounded-full border border-amber-300 bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                            Teks sendiri
                        </span>
                    @else
                        <span class="shrink-0 rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                            Bawaan sistem
                        </span>
                    @endif
                </div>

                @if ($section['note'])
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $section['note'] }}</p>
                @endif

                @if (! $section['editable'])
                    <p class="mt-3 text-sm italic text-gray-400 dark:text-gray-400">Bab ini dibuat otomatis dari data proyek — tidak ada teks untuk diatur.</p>
                @else
                    @php $rows = max(4, min(26, substr_count($section['text'], "\n") + 2)); @endphp
                    <form action="{{ route('settings.proposalDefaults.update', $section['key']) }}" method="POST" class="mt-3">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="tujuan" value="{{ $tujuanAktif }}">
                        <textarea name="body" rows="{{ $rows }}"
                                  class="w-full rounded-md border-gray-300 font-mono text-sm leading-relaxed shadow-sm dark:border-gray-600 dark:bg-gray-900"
                                  spellcheck="false">{{ $section['text'] }}</textarea>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <button type="submit" class="rounded-md bg-blue-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-blue-700">
                                Simpan bab ini
                            </button>
                            @unless ($section['overridden'])
                                <span class="text-xs text-gray-500 dark:text-gray-400">Kotak ini masih berisi teks bawaan sistem — simpan untuk menjadikannya teks baku.</span>
                            @endunless
                        </div>
                    </form>

                    @if ($section['overridden'])
                        <div class="mt-3 flex flex-wrap items-center gap-4 border-t border-gray-100 pt-3 dark:border-gray-800">
                            <form action="{{ route('settings.proposalDefaults.reset', $section['key']) }}" method="POST"
                                  data-confirm="Hapus teks baku bab &quot;{{ $section['title'] }}&quot;? Bab ini kembali memakai teks bawaan sistem.">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="tujuan" value="{{ $tujuanAktif }}">
                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                    Kembalikan ke bawaan sistem
                                </button>
                            </form>
                            <details class="text-xs text-gray-500 dark:text-gray-400">
                                <summary class="cursor-pointer hover:text-gray-700 dark:hover:text-gray-200">Lihat teks bawaan sistem</summary>
                                <pre class="mt-2 whitespace-pre-wrap rounded border border-gray-200 bg-gray-50 p-3 font-mono text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">{{ $section['baku'] }}</pre>
                            </details>
                        </div>
                    @endif
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
