@extends('layouts.app')

@section('title', 'Editor Teks Proposal — ' . $project->proposal_number)

@php
    $editable       = collect($sections)->where('editable', true);
    $overriddenList = $editable->where('overridden', true);
@endphp

@section('content')
<div class="max-w-5xl mx-auto py-8 space-y-6">

    {{-- ===================== HEADER ===================== --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Editor Teks Proposal per-Bab</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $project->proposal_number }} — {{ $project->instructingClient->client_name }}</p>
        </div>
        <a href="{{ route('proposals.show', $project) }}" class="text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap dark:text-gray-400 dark:hover:text-gray-200">&larr; Kembali ke Detail Proyek</a>
    </div>

    {{-- ===================== CARA KERJA ===================== --}}
    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 space-y-2 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
        <p class="font-semibold">Cara kerja</p>
        <ul class="list-disc list-inside space-y-1 text-blue-800 dark:text-blue-300">
            <li>Bab yang <span class="font-medium">tidak diedit</span> di sini otomatis memakai teks baku. Tampilan, tabel, penomoran bab, kop &amp; footer proposal <span class="font-medium">tidak berubah</span>.</li>
            <li>Bab yang <span class="font-medium">diedit</span> akan memakai teks Anda apa adanya (rata kiri-kanan biasa). Tabel &amp; elemen otomatis bab tsb tetap dibuat sistem.</li>
            <li>Teks di kotak sudah <span class="font-medium">ter-render</span> (nama klien, nominal biaya, tanggal, dll sudah jadi). Karena itu teks hasil edit <span class="font-medium">tidak ikut berubah</span> kalau data proyek diubah — klik <span class="font-medium">"Kembalikan ke teks baku"</span> untuk menyegarkan.</li>
            <li><span class="font-medium">Huruf tebal:</span> apit teks dengan dua bintang, contoh <code>**PT BANK DANAMON**</code> tercetak <b>PT BANK DANAMON</b>. Hapus bintangnya untuk membatalkan tebal.</li>
            <li><span class="font-medium">Satu poin / paragraf per baris</span> (tekan Enter biasa). Baris berawalan <code>a.</code> <code>b.</code> <code>c.</code> otomatis dirapikan jadi daftar bernomor huruf yang sejajar. Baris kosong memisahkan kelompok.</li>
        </ul>
        <div class="pt-1 flex items-center gap-3">
            <span class="text-blue-700 dark:text-blue-400">
                {{ $overriddenList->count() }} dari {{ $editable->count() }} bab sudah diedit.
            </span>
            @if ($overriddenList->isNotEmpty())
                <form action="{{ route('proposals.texts.resetAll', $project) }}" method="POST"
                      data-confirm="Kembalikan SEMUA {{ $overriddenList->count() }} bab yang diedit ke teks baku? Tindakan ini tidak bisa dibatalkan.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium dark:text-red-400 dark:hover:text-red-300">
                        Kembalikan semua ke baku
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- ===================== DAFTAR BAB ===================== --}}
    <div class="space-y-4">
        @foreach ($sections as $i => $section)
            <div id="bab-{{ $section['key'] }}"
                 class="scroll-mt-6 bg-white rounded-lg border shadow-sm p-5
                        {{ $section['overridden'] ? 'border-amber-300' : 'border-gray-200' }} dark:bg-gray-800">

                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        <span class="text-gray-400 font-normal dark:text-gray-500">{{ $i + 1 }}.</span>
                        {{ $section['title'] }}
                    </h2>
                    @if (! $section['editable'])
                        <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700">
                            Otomatis
                        </span>
                    @elseif ($section['overridden'])
                        <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-700">
                            Diedit
                        </span>
                    @endif
                </div>

                @if ($section['note'])
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $section['note'] }}</p>
                @endif

                @if (! $section['editable'])
                    <p class="mt-3 text-sm text-gray-400 italic dark:text-gray-500">Bab ini dibuat otomatis dari data proyek — tidak ada teks untuk diedit.</p>
                @else
                    @php $rows = max(4, min(26, substr_count($section['text'], "\n") + 2)); @endphp
                    <form action="{{ route('proposals.texts.update', [$project, $section['key']]) }}" method="POST" class="mt-3">
                        @csrf
                        @method('PUT')
                        <textarea name="body" rows="{{ $rows }}"
                                  class="w-full rounded-md border-gray-300 shadow-sm text-sm font-mono leading-relaxed dark:border-gray-600"
                                  spellcheck="false">{{ $section['text'] }}</textarea>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <button type="submit"
                                    class="px-4 py-1.5 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                Simpan bab ini
                            </button>
                            @if ($section['overridden'])
                                <span class="text-xs text-amber-700 dark:text-amber-400">Bab ini memakai teks hasil edit.</span>
                            @endif
                        </div>
                    </form>

                    @if ($section['overridden'])
                        <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-center gap-4 dark:border-gray-800">
                            <form action="{{ route('proposals.texts.reset', [$project, $section['key']]) }}" method="POST"
                                  data-confirm="Kembalikan bab &quot;{{ $section['title'] }}&quot; ke teks baku? Teks hasil edit akan hilang.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium dark:text-red-400 dark:hover:text-red-300">
                                    Kembalikan ke teks baku
                                </button>
                            </form>
                            <details class="text-xs text-gray-500 dark:text-gray-400">
                                <summary class="cursor-pointer hover:text-gray-700 dark:hover:text-gray-200">Lihat teks baku terkini</summary>
                                <pre class="mt-2 whitespace-pre-wrap font-mono bg-gray-50 border border-gray-200 rounded p-3 text-gray-600 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-400">{{ $section['baku'] }}</pre>
                            </details>
                        </div>
                    @endif
                @endif
            </div>
        @endforeach
    </div>

    <div class="pt-2">
        <a href="{{ route('proposals.show', $project) }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Kembali ke Detail Proyek</a>
    </div>
</div>
@endsection
