@extends('layouts.app')

@section('title', 'Buat Proposal')

@section('content')
@php
    // ---------- Helper kelas border untuk field yang error ----------
    $errCls = fn ($f) => $errors->has($f)
        ? 'border-red-400 ring-1 ring-red-300 dark:border-red-500'
        : 'border-gray-300 dark:border-gray-600';

    // ---------- Repopulasi setelah validasi gagal ----------
    // Klien & objek penilaian dibangun lewat JS, jadi tidak ikut terbawa
    // old() secara otomatis. Di sini datanya diambil ulang dari DB lalu
    // dititipkan ke JS lewat window.__BOOT__ (2026-09-14, feedback user —
    // sebelumnya semua objek yang sudah diketik hilang begitu validasi gagal).
    $oldInstructing = old('instructing_client_id')
        ? \App\Models\Client::find(old('instructing_client_id'))
        : null;
    $oldIntended = \App\Models\Client::whereIn('id', (array) old('intended_user_ids', []))->get();
    $oldApprover = old('approver_client_id')
        ? \App\Models\Client::find(old('approver_client_id'))
        : null;
    $oldNamed = old('client_id')
        ? \App\Models\Client::find(old('client_id'))
        : null;
    $clientBoot = fn ($c) => $c ? ['id' => $c->id, 'client_name' => $c->client_name, 'address' => (string) $c->address] : null;
    $oldObjects = array_values((array) old('objects', []));
    $oldPsak = (array) old('psak_classification', []);
@endphp

<div class="max-w-5xl mx-auto py-8 space-y-6">

    {{-- Jenis layanan dipilih di modal sebelum halaman ini (2026-09-25).
         $konsultasi menentukan blok mana yang tampil. --}}
    @php
        $konsultasi = ($serviceType ?? \App\Models\Project::SERVICE_PENILAIAN) === \App\Models\Project::SERVICE_KONSULTASI;
        $labelLayanan = \App\Models\Project::SERVICE_LABELS[$serviceType] ?? $serviceType;
        $labelObjek = $konsultasi ? 'Objek Pekerjaan' : 'Objek Penilaian';
    @endphp

    {{-- ===================== HEADER ===================== --}}
    <x-page-header title="Buat Proposal Penawaran Baru"
        subtitle="Lengkapi seluruh bagian di bawah, lalu simpan untuk men-generate dokumen proposal.">
        <span class="shrink-0 rounded-full border px-3 py-1 text-xs font-semibold
                     {{ $konsultasi ? 'border-violet-300 bg-violet-50 text-violet-700 dark:border-violet-800 dark:bg-violet-900/30 dark:text-violet-300'
                                    : 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-300' }}">
            {{ $labelLayanan }}
        </span>
        <a href="{{ route('dashboard') }}"
           class="shrink-0 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            &larr; Kembali ke List Project
        </a>
    </x-page-header>

    {{-- ===================== NAVIGASI CEPAT ===================== --}}
    <div class="sticky top-14 lg:top-3 z-20 flex items-center gap-2 bg-white border border-gray-200 rounded-lg p-1.5 shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <div id="quickNav" class="flex min-w-0 flex-1 gap-1.5 overflow-x-auto">
            <a href="#section-identitas" data-target="section-identitas" class="quicknav-pill shrink-0 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">Identitas</a>
            <a href="#section-pihak" data-target="section-pihak" class="quicknav-pill shrink-0 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">Pihak Terkait</a>
            <a href="#section-lingkup" data-target="section-lingkup" class="quicknav-pill shrink-0 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">Lingkup &amp; SLA</a>
            <a href="#section-biaya" data-target="section-biaya" class="quicknav-pill shrink-0 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">Biaya</a>
            <a href="#section-objek" data-target="section-objek" class="quicknav-pill shrink-0 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">{{ $labelObjek }}</a>
        </div>
    </div>

    <form id="proposalForm" action="{{ route('proposals.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        <input type="hidden" name="service_type" value="{{ $serviceType }}">

        {{-- ============================================================
             KARTU 1 — IDENTITAS PROPOSAL
             ============================================================ --}}
        <div id="section-identitas" class="scroll-mt-24 bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3 dark:text-gray-400">Identitas Proposal</h2>

            <div class="space-y-4">
                {{-- Nomor (70%) + Tanggal (30%) — 1 baris di layar lebar --}}
                <div class="grid grid-cols-1 sm:grid-cols-10 gap-4">
                    <div class="sm:col-span-7">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Nomor Proposal
                            @include('partials.icon-info', ['tip' => 'Diinput manual sesuai nomor resmi yang diterbitkan sistem terintegrasi Kantor Pusat.'])
                        </label>
                        <input type="text" name="proposal_number" required value="{{ old('proposal_number') }}"
                               autocomplete="off" spellcheck="false"
                               placeholder="00000/2.0131-00/KJPPSPR-PRO/APP/_/2026"
                               class="mt-1 w-full rounded-md shadow-sm font-mono {{ $errCls('proposal_number') }}">
                        @error('proposal_number')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Tanggal Proposal
                            @include('partials.icon-info', ['tip' => 'Tanggal ini tercetak di kop dokumen. Boleh diisi mundur.'])
                        </label>
                        {{-- Diketik/tampil dd/mm/yyyy; ikon kalender membuka date picker native.
                             Yang disubmit = hidden #proposal_date_iso (yyyy-mm-dd). --}}
                        <div class="relative mt-1">
                            <input type="text" id="proposal_date_display" required
                                   placeholder="dd/mm/yyyy" inputmode="numeric" autocomplete="off" maxlength="10"
                                   class="w-full rounded-md shadow-sm pr-10 {{ $errCls('proposal_date') }}">
                            <button type="button" id="proposal_date_pick" tabindex="-1" aria-label="Pilih dari kalender"
                                    class="absolute inset-y-0 right-0 grid w-10 place-items-center text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-200">
                                <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0V11.25A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                                </svg>
                            </button>
                            <input type="date" id="proposal_date_picker" tabindex="-1" aria-hidden="true" lang="id"
                                   class="pointer-events-none absolute bottom-0 left-0 h-px w-px opacity-0">
                        </div>
                        <input type="hidden" name="proposal_date" id="proposal_date_iso"
                               value="{{ old('proposal_date', now()->toDateString()) }}">
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Format dd/mm/yyyy</p>
                        @error('proposal_date')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Dasar permintaan penilaian --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Dasar Permintaan Penilaian
                        @include('partials.icon-info', ['tip' => 'Mengisi bagian kosong pada kalimat pembuka proposal: "Sesuai dengan informasi permintaan penilaian [teks ini], mengenai permohonan jasa Penilai…". Boleh dikosongkan — nanti tampil titik-titik untuk diisi manual di dokumen.'])
                    </label>
                    <textarea name="request_basis" rows="2"
                              placeholder="Contoh: yang kami terima melalui Pesan WhatsApp permintaan penilaian tanggal 07 September 2026"
                              class="mt-1 w-full rounded-md shadow-sm {{ $errCls('request_basis') }}">{{ old('request_basis') }}</textarea>
                    @error('request_basis')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ============================================================
             KARTU 2 — PIHAK TERKAIT
             ============================================================ --}}
        <div id="section-pihak" class="scroll-mt-24 bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3 dark:text-gray-400">Pihak Terkait</h2>

            <div class="space-y-4">
                {{-- Nama Klien (debitur/pemilik aset) — dipilih dari Database Klien,
                     sama seperti Pemberi Tugas & Pengguna Laporan (2026-09-14, feedback user). --}}
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nama Klien
                        @include('partials.icon-info', ['tip' => 'Pihak yang asetnya dinilai / debitur (mis. PT pemilik aset), bisa berbeda dari Pemberi Tugas — contoh: Pemberi Tugas bank untuk penjaminan utang atau lelang. Dipakai di baris "Hal" proposal & List Project. Kosongkan = otomatis pakai Pemberi Tugas.'])
                    </label>
                    <div class="flex gap-2 mt-1">
                        <div class="relative flex-1">
                            <input type="text" id="named_client_search" autocomplete="off"
                                   placeholder="Kosongkan = otomatis Pemberi Tugas. Ketik untuk mencari..."
                                   class="w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 {{ $errCls('client_id') }}">
                            <div id="named_client_results"
                                 class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto dark:bg-gray-800 dark:border-gray-700"></div>
                        </div>
                        <button type="button" onclick="openClientModal('named')" title="Tambah klien baru" aria-label="Tambah klien baru"
                                class="inline-flex items-center gap-1 px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 whitespace-nowrap">
                            <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Klien Baru
                        </button>
                    </div>
                    <div id="named_client_chip" style="display:none" class="mt-2 items-start justify-between gap-3 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800 dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-300">
                        <div class="min-w-0"><div id="named_client_chip_text" class="font-medium"></div><div id="named_client_chip_address" class="text-xs opacity-80 whitespace-pre-line"></div></div>
                        <button type="button" onclick="clearNamedClient()" class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">&times;</button>
                    </div>
                    <input type="hidden" name="client_id" id="named_client_id" value="">
                    @error('client_id')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                {{-- Pemberi Tugas (AJAX combobox) --}}
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pemberi Tugas</label>
                    <div class="flex gap-2 mt-1">
                        <div class="relative flex-1">
                            <input type="text" id="instructing_client_search" autocomplete="off"
                                   placeholder="Ketik nama klien untuk mencari..."
                                   class="w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 {{ $errCls('instructing_client_id') }}">
                            <div id="instructing_client_results"
                                 class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto dark:bg-gray-800 dark:border-gray-700"></div>
                        </div>
                        <button type="button" onclick="openClientModal('instructing')" title="Tambah klien baru" aria-label="Tambah klien baru"
                                class="inline-flex items-center gap-1 px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 whitespace-nowrap">
                            <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Klien Baru
                        </button>
                    </div>
                    {{-- display diatur lewat style inline, bukan class "hidden":
                         di build Tailwind CDN yang dipakai, .inline-flex menang
                         atas .hidden sehingga chip kosong sempat ikut terlihat. --}}
                    <div id="instructing_client_chip" style="display:none" class="mt-2 items-start justify-between gap-3 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800 dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-300">
                        <div class="min-w-0"><div id="instructing_client_chip_text" class="font-medium"></div><div id="instructing_client_chip_address" class="text-xs opacity-80 whitespace-pre-line"></div></div>
                        <button type="button" onclick="clearInstructingClient()" class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">&times;</button>
                    </div>
                    <input type="hidden" name="instructing_client_id" id="instructing_client_id" required>
                    @error('instructing_client_id')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                {{-- Pengguna Laporan (AJAX combobox, multi) --}}
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pengguna Laporan (bisa lebih dari satu)</label>
                    <div class="flex gap-2 mt-1">
                        <div class="relative flex-1">
                            <input type="text" id="intended_user_search" autocomplete="off"
                                   placeholder="Ketik nama klien untuk mencari..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:border-gray-600">
                            <div id="intended_user_results"
                                 class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto dark:bg-gray-800 dark:border-gray-700"></div>
                        </div>
                        <button type="button" onclick="openClientModal('intended')" title="Tambah klien baru" aria-label="Tambah klien baru"
                                class="inline-flex items-center gap-1 px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 whitespace-nowrap">
                            <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Klien Baru
                        </button>
                    </div>
                    <div id="intended_user_chips" class="space-y-2 mt-2"></div>
                    <div id="intended_user_hidden_inputs"></div>
                    @error('intended_user_ids')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                {{-- Penanggung Jawab (penandatangan proposal) — label diganti
                     dari "Penandatangan Proposal" (2026-09-15, feedback user). --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Penanggung Jawab
                        @include('partials.icon-info', ['tip' => 'Daftar diambil dari pengguna aktif berjabatan "Penanggung Jawab". Nama & nomor izin (MAPPI, RMK, Izin Menkeu, STTD OJK, Klasifikasi) pada blok tanda tangan mengikuti biodata pengguna yang dipilih.'])
                    </label>
                    <select name="signed_by_user_id" class="mt-1 w-full rounded-md shadow-sm {{ $errCls('signed_by_user_id') }}">
                        {{-- Pilihan "Penanggung Jawab baku kantor" dihapus (2026-09-15, feedback
                             user) — data bakunya kini ada di akun user Penanggung Jawab. --}}
                        @foreach ($signers as $signer)
                            <option value="{{ $signer->id }}" @selected(old('signed_by_user_id') == $signer->id)>{{ $signer->name }}</option>
                        @endforeach
                    </select>
                    @error('signed_by_user_id')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    @if ($signers->isEmpty())
                        <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                            Belum ada pengguna aktif berjabatan &ldquo;Penanggung Jawab&rdquo;. Lengkapi lewat menu Kelola Pengguna.
                        </p>
                    @endif
                </div>

                @include('proposals._signature_options')

                {{-- Pihak yang Menyetujui --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Pihak yang Menyetujui
                        @include('partials.icon-info', ['tip' => 'Nama pihak pada kolom "Menyetujui," di blok tanda tangan. Isi bila yang menyetujui berbeda dari Pemberi Tugas (mis. bank, sementara Pemberi Tugas-nya PT — atau sebaliknya).'])
                    </label>
                    {{-- Dipilih dari Database Klien, sama seperti Pemberi Tugas
                         (2026-09-15, feedback user). --}}
                    <div class="flex gap-2 mt-1">
                        <div class="relative flex-1">
                            <input type="text" id="approver_client_search" autocomplete="off"
                                   placeholder="Kosongkan = otomatis Pemberi Tugas. Ketik untuk mencari..."
                                   class="w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 {{ $errCls('approver_client_id') }}">
                            <div id="approver_client_results"
                                 class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto dark:bg-gray-800 dark:border-gray-700"></div>
                        </div>
                        <button type="button" onclick="openClientModal('approver')" title="Tambah klien baru" aria-label="Tambah klien baru"
                                class="inline-flex items-center gap-1 px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 whitespace-nowrap">
                            <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Klien Baru
                        </button>
                    </div>
                    <div id="approver_client_chip" style="display:none" class="mt-2 items-start justify-between gap-3 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800 dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-300">
                        <div class="min-w-0"><div id="approver_client_chip_text" class="font-medium"></div><div id="approver_client_chip_address" class="text-xs opacity-80 whitespace-pre-line"></div></div>
                        <button type="button" onclick="clearApproverClient()" class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">&times;</button>
                    </div>
                    <input type="hidden" name="approver_client_id" id="approver_client_id" value="">
                    @error('approver_client_id')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                {{-- Marketing — field proposal, tidak terikat ke data klien. Pilih
                     "Lainnya" untuk mengetik nama manual; nama ketikan itulah yang
                     disimpan (2026-09-15, feedback user). --}}
                @php
                    $mkChoice = old('marketing_name');
                    $mkOther  = old('marketing_name_other');
                @endphp
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Marketing</label>
                    <select name="marketing_name" id="marketing_name" onchange="toggleMarketingOther(this)"
                            class="mt-1 w-full rounded-md shadow-sm {{ $errCls('marketing_name') }}">
                        <option value="">-- Pilih Marketing --</option>
                        @foreach (config('kjpp.marketing_names', []) as $marketing)
                            <option value="{{ $marketing }}" @selected($mkChoice === $marketing)>{{ $marketing }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="marketing_name_other" id="marketing_name_other" value="{{ $mkOther }}"
                           placeholder="Ketik nama marketing"
                           @if ($mkChoice === 'Lainnya') required @else style="display:none" @endif
                           class="mt-2 w-full rounded-md shadow-sm {{ $errCls('marketing_name_other') }}">
                    @error('marketing_name')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    @error('marketing_name_other')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
                <script>
                    function toggleMarketingOther(select) {
                        const other = document.getElementById('marketing_name_other');
                        const isOther = select.value === 'Lainnya';
                        other.style.display = isOther ? '' : 'none';
                        other.required = isOther;
                        if (isOther) other.focus();
                    }
                </script>
            </div>
        </div>

        {{-- ============================================================
             KARTU 3 — LINGKUP PEKERJAAN & SLA
             ============================================================ --}}
        <div id="section-lingkup" class="scroll-mt-24 bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3 dark:text-gray-400">Lingkup Pekerjaan &amp; SLA</h2>

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @unless ($konsultasi)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tujuan Penilaian</label>
                            <select name="proposal_purpose" id="proposal_purpose" onchange="toggleLkFields()"
                                    class="mt-1 w-full rounded-md shadow-sm {{ $errCls('proposal_purpose') }}">
                                @foreach (['Penjaminan Utang', 'Jual Beli', 'Lelang', 'Pelaporan Keuangan'] as $purpose)
                                    <option value="{{ $purpose }}" @selected(old('proposal_purpose') === $purpose)>
                                        {{ $purpose === 'Pelaporan Keuangan' ? 'Pelaporan Keuangan (LK Properti)' : $purpose }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500" id="purpose_hint"></p>
                            @error('proposal_purpose')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Laporan</label>
                            <select name="report_style" class="mt-1 w-full rounded-md shadow-sm {{ $errCls('report_style') }}">
                                <option value="Long Report" @selected(old('report_style') === 'Long Report')>Long Report — Laporan Terinci (Comprehensive Style)</option>
                                <option value="Short Report" @selected(old('report_style') === 'Short Report')>Short Report — Laporan Ringkas (Short Form)</option>
                            </select>
                            @error('report_style')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                    @else
                        {{-- Konsultasi: tidak ada tujuan penilaian maupun Long/Short
                             Report. Jenis pekerjaannya sudah dipilih di modal. --}}
                        {{-- Jenis pekerjaan Non-Penilaian dipilih di sini, sejajar
                             dengan Tujuan Penilaian pada proposal penilaian
                             (2026-09-26, permintaan user). --}}
                        <div>
                            <label for="consulting_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Pekerjaan</label>
                            <select name="consulting_type" id="consulting_type" required
                                    class="mt-1 w-full rounded-md shadow-sm {{ $errCls('consulting_type') }}">
                                @foreach (\App\Models\Project::CONSULTING_TYPES as $jenis)
                                    <option value="{{ $jenis }}" @selected(old('consulting_type', $consultingType) === $jenis)>{{ $jenis }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Jasa Konsultasi (SPI 350). Tidak bisa diubah setelah proposal disimpan.</p>
                            @error('consulting_type')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                    <div>
                        <label for="letter_attn" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Up. <span class="font-normal text-gray-400">(opsional)</span>
                        </label>
                        <input type="text" name="letter_attn" id="letter_attn" maxlength="150"
                               value="{{ old('letter_attn') }}" placeholder="Contoh: Bapak Sinyo, Kepala Divisi Kredit"
                               class="mt-1 w-full rounded-md text-sm shadow-sm {{ $errCls('letter_attn') }}">
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Tercetak di kop surat, di bawah alamat klien.</p>
                        @error('letter_attn')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                        <input type="hidden" name="report_style" value="{{ \App\Models\Project::REPORT_LONG }}">
                    @endunless
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            SLA Laporan Draft/Resume (hari kerja)
                            @include('partials.icon-info', ['tip' => 'Dihitung sejak inspeksi lapangan & penerimaan data terakhir.'])
                        </label>
                        <input type="number" name="sla_draft_days" min="1" max="365" required
                               value="{{ old('sla_draft_days') }}" placeholder="Contoh: 3"
                               class="mt-1 w-full rounded-md shadow-sm {{ $errCls('sla_draft_days') }}">
                        @error('sla_draft_days')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            SLA Laporan Final (hari kerja)
                            @include('partials.icon-info', ['tip' => 'Dihitung sejak Laporan Draft/Resume disetujui Pemberi Tugas.'])
                        </label>
                        <input type="number" name="sla_final_days" min="1" max="365" required
                               value="{{ old('sla_final_days') }}" placeholder="Contoh: 5"
                               class="mt-1 w-full rounded-md shadow-sm {{ $errCls('sla_final_days') }}">
                        @error('sla_final_days')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Blok khusus Pelaporan Keuangan --}}
                <div id="lk_fields" class="hidden space-y-4 rounded-md border border-dashed border-blue-300 bg-blue-50 p-4 dark:border-blue-700 dark:bg-blue-900/30">
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-300">Detail Khusus Pelaporan Keuangan</p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Klasifikasi PSAK
                            @include('partials.icon-info', ['tip' => 'Bisa pilih lebih dari satu (Ctrl/Cmd + klik).'])
                        </label>
                        <select name="psak_classification[]" multiple class="mt-1 w-full rounded-md border-gray-300 shadow-sm h-24 dark:border-gray-600">
                            @foreach (['Aset Tetap (PSAK 16)', 'Properti Investasi (PSAK 13)', 'Persediaan (PSAK 14)', 'Aset Tidak Berwujud (PSAK 19)'] as $psak)
                                <option value="{{ $psak }}" @selected(in_array($psak, $oldPsak, true))>{{ $psak }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Pelaporan Keuangan (Cut-off)</label>
                        <input type="date" name="financial_reporting_date" lang="id" value="{{ old('financial_reporting_date') }}"
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="is_public_company" value="1" @checked(old('is_public_company'))
                               class="rounded border-gray-300 dark:border-gray-600">
                        Klien adalah Perusahaan Terbuka (menampilkan klausul POJK 28/POJK.04/2021)
                    </label>
                </div>
            </div>
        </div>

        {{-- ============================================================
             KARTU 4 — BIAYA & PEMBAYARAN
             ============================================================ --}}
        <div id="section-biaya" class="scroll-mt-24 bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3 dark:text-gray-400">Biaya &amp; Pembayaran</h2>

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status PPN</label>
                        <select name="fee_ppn_included" id="fee_ppn_included" class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                            <option value="1" @selected(old('fee_ppn_included', '1') == '1')>Sudah termasuk PPN</option>
                            <option value="0" @selected(old('fee_ppn_included') === '0')>Belum termasuk PPN (11%)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Format Biaya</label>
                        <select name="fee_breakdown" id="fee_breakdown" onchange="toggleFeeBreakdown()" class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                            <option value="0" @selected(old('fee_breakdown', '0') == '0')>All-in (langsung)</option>
                            <option value="1" @selected(old('fee_breakdown') === '1')>Rincian (breakdown)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Transport &amp; Akomodasi
                            @include('partials.icon-info', ['tip' => 'Termasuk = biaya transport & akomodasi ikut ditagih dan dikenai PPN. Ditanggung klien = tidak masuk total biaya; proposal mencantumkan bahwa biaya belum termasuk transport & akomodasi.'])
                        </label>
                        <select name="transport_reimbursed" id="transport_reimbursed" onchange="toggleFeeBreakdown()" class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                            <option value="0" @selected(old('transport_reimbursed', '0') == '0')>Termasuk dalam biaya</option>
                            <option value="1" @selected(old('transport_reimbursed') === '1')>Ditanggung klien (reimburse)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" id="service_fee_label">Nilai Dasar Biaya (Rp)</label>
                    <input type="text" id="service_fee_display" inputmode="numeric" autocomplete="off" required
                           value="{{ old('service_fee') ? number_format((float) old('service_fee'), 0, ',', '.') : '' }}"
                           placeholder="Contoh: 10.000.000"
                           class="mt-1 w-full rounded-md shadow-sm {{ $errCls('service_fee') }}">
                    <input type="hidden" name="service_fee" id="service_fee_raw" value="{{ old('service_fee') }}">
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500" id="service_fee_hint"></p>
                    @error('service_fee')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div id="transport_cost_wrap" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Biaya Transport &amp; Akomodasi (Rp)</label>
                    <input type="text" id="transport_cost_display" inputmode="numeric" autocomplete="off"
                           value="{{ old('transport_cost') ? number_format((float) old('transport_cost'), 0, ',', '.') : '' }}"
                           placeholder="Contoh: 6.000.000"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                    <input type="hidden" name="transport_cost" id="transport_cost_raw" value="{{ old('transport_cost') }}">
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400" id="fee_total_preview"></p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-gray-100 pt-4 dark:border-gray-700">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Skema Pembayaran
                            @include('partials.icon-info', ['tip' => '"Bayar Nanti" untuk klien yang baru bayar di tengah atau di akhir pengerjaan, tanpa DP lebih dulu — proyek bisa langsung mulai kerja lapangan tanpa menunggu invoice dibayar. Pilihan ini terkunci begitu proyek mulai diproses.'])
                        </label>
                        <select name="payment_scheme" class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                            <option value="DP di Awal" @selected(old('payment_scheme', 'DP di Awal') === 'DP di Awal')>DP di Awal (standar)</option>
                            <option value="Bayar Nanti" @selected(old('payment_scheme') === 'Bayar Nanti')>Bayar Nanti (tanpa DP di awal)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Rekening Bank Pembayaran
                            @include('partials.icon-info', ['tip' => 'Dipakai di blok "Rekening Bank" proposal & PDF Invoice. Daftar dikelola di menu Kelola Rekening Bank.'])
                        </label>
                        <select name="bank_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                            {{-- Pilihan "Rekening baku kantor" dihapus (2026-09-15, feedback
                                 user); bawaannya langsung rekening ber-is_default. --}}
                            @php $defaultBankId = optional($banks->firstWhere('is_default', true))->id; @endphp
                            @foreach ($banks as $bank)
                                <option value="{{ $bank->id }}" @selected((string) old('bank_id', $defaultBankId) === (string) $bank->id)>
                                    {{ $bank->bank_name }}{{ $bank->branch ? ' (' . $bank->branch . ')' : '' }} — {{ $bank->account_number }}{{ $bank->is_default ? ' · default' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @include('partials.payment-terms', [
                        'termPercents' => array_map('floatval', explode(',', old('payment_terms', '50,50'))),
                        'locked'       => false,
                    ])
                </div>
            </div>
        </div>

        {{-- ============================================================
             KARTU 5 — OBJEK PENILAIAN
             ============================================================ --}}
        <div id="section-objek" class="scroll-mt-24 bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between gap-2 mb-2">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">
                    @if ($konsultasi)
                        Objek Pekerjaan
                        @include('partials.icon-info', ['tip' => 'Cukup isi lokasi tiap objek. Kategori aset, bentuk hak, dan atas nama hanya dipakai pada proposal penilaian.'])
                    @else
                        Identifikasi Objek Penilaian &amp; Kepemilikan
                        @include('partials.icon-info', ['tip' => 'Setiap objek wajib diisi lokasi, bentuk/jenis hak, dan atas nama secara manual — sesuai format tabel "Identifikasi Obyek Penilaian" pada dokumen resmi KJPP.'])
                    @endif
                </h2>
                <button type="button" onclick="addObjectRow()"
                        class="inline-flex shrink-0 items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                    <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Tambah Objek
                </button>
            </div>

            @if ($konsultasi)
                {{-- Kalimat objek pekerjaan (2026-09-25, permintaan user): dicetak
                     apa adanya di bab Objek Pekerjaan pada proposal DAN sebagai
                     paragraf uraian objek di Surat Tugas. --}}
                <div class="mb-3">
                    <label for="work_object_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Uraian Objek Pekerjaan
                    </label>
                    <textarea name="work_object_description" id="work_object_description" rows="3" maxlength="2000"
                              placeholder="Contoh : Rencana pengembangan ____ yang dikembangkan oleh ______ yang terletak di ____"
                              class="mt-1 w-full rounded-md text-sm shadow-sm {{ $errCls('work_object_description') }}">{{ old('work_object_description') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <span class="font-medium">Tulis satu kalimat utuh</span> — sebutkan apa yang dikerjakan, siapa yang mengembangkan, dan di mana lokasinya.
                        Dicetak apa adanya sebagai paragraf di <span class="font-medium">Surat Tugas</span> dan di bab
                        <span class="font-medium">Objek Pekerjaan</span> pada proposal.
                    </p>
                    @error('work_object_description')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
            @endif

            <div id="objects_container" class="space-y-3" @if ($konsultasi) data-konsultasi="1" @endif></div>
        </div>
    </form>
</div>

@include('proposals._form_errors')

@include('proposals._form_actions', [
    'cancelUrl'     => route('dashboard'),
    'cancelForm'    => null,
    'cancelConfirm' => null,
    'cancelTip' => 'Batalkan dan kembali ke List Project',
    'saveLabel' => 'Simpan',
    'saveTip'   => 'Simpan & Generate Proposal',
    'saveTone'  => 'emerald',
])

{{-- =============== TEMPLATE 1 BARIS OBJEK PENILAIAN (di-clone via JS) =============== --}}
<template id="object_row_template">
    <div class="object-row border border-gray-200 rounded-md bg-gray-50 relative dark:border-gray-700 dark:bg-gray-900">
        {{-- Header kartu objek: bisa diklik untuk melipat (2026-09-14, feedback
             user — dengan 5 objek form jadi sangat panjang). --}}
        <div class="flex items-center gap-2 px-3 py-2">
            <button type="button" class="object-toggle flex min-w-0 flex-1 items-center gap-2 text-left">
                <span class="object-row-number shrink-0 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-full w-6 h-6 flex items-center justify-center dark:text-gray-300 dark:bg-gray-800 dark:border-gray-600">1</span>
                <span class="object-summary min-w-0 flex-1 truncate text-sm text-gray-500 dark:text-gray-400">Objek baru — belum diisi</span>
                <svg aria-hidden="true" class="object-chevron h-4 w-4 shrink-0 text-gray-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5"/>
                </svg>
            </button>
            <button type="button" title="Hapus Objek" aria-label="Hapus Objek"
                    class="remove-object-btn grid h-7 w-7 shrink-0 place-items-center rounded-md text-gray-400 hover:bg-rose-100 hover:text-rose-700 dark:hover:bg-rose-900/30 dark:hover:text-rose-300">
                <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                </svg>
            </button>
        </div>

        <div class="object-body grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-gray-200 p-3 dark:border-gray-700">
            <div class="khusus-penilaian sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Kategori Aset/Properti</label>
                <select class="object-category mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option value="Real Properti - Tanah">Real Properti - Tanah</option>
                    <option value="Real Properti - Tanah dan Bangunan">Real Properti - Tanah dan Bangunan</option>
                    <option value="Real Properti - Tanah, Bangunan dan Sarana Pelengkap">Real Properti - Tanah, Bangunan dan Sarana Pelengkap</option>
                    <option value="Real Properti - Ruko">Real Properti - Ruko</option>
                    <option value="Real Properti - Office Space">Real Properti - Office Space</option>
                    <option value="Real Properti - Unit Apartemen">Real Properti - Unit Apartemen</option>
                    <option value="Personal Properti - Mesin dan Peralatan">Personal Properti - Mesin dan Peralatan</option>
                    <option value="Personal Properti - Kendaraan">Personal Properti - Kendaraan</option>
                    <option value="Personal Properti - Alat Berat">Personal Properti - Alat Berat</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>

            {{-- Field khusus kategori "Lainnya" --}}
            <div class="other-category-fields khusus-penilaian hidden sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Sebutkan Jenis Aset/Properti</label>
                <input type="text" class="object-custom-category mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600"
                       placeholder="Contoh: Kapal / Pesawat / Hak Sewa / Tanaman Keras">
            </div>

            {{-- Field khusus Real Properti --}}
            <div class="real-property-fields khusus-penilaian hidden">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Luas Tanah (m²)</label>
                <input type="number" step="0.01" min="0" class="object-land-area mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
            </div>
            <div class="real-property-fields khusus-penilaian hidden">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Luas Bangunan (m²)</label>
                <input type="number" step="0.01" min="0" class="object-building-area mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
            </div>

            {{-- Field khusus Personal Properti --}}
            <div class="personal-property-fields khusus-penilaian hidden sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Jumlah Unit</label>
                <input type="number" min="0" class="object-unit-quantity mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Lokasi Objek</label>
                <textarea rows="2" class="object-location mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600" required
                          placeholder="Masukkan alamat lengkap beserta kelurahan, kecamatan, kota/kabupaten dan Provinsi"></textarea>
            </div>

            <div class="khusus-penilaian">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Bentuk/Jenis Hak Atas Tanah</label>
                <input type="text" class="object-ownership-form mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600" required
                       placeholder="Contoh: Tunggal - SHGB No. 11948">
            </div>
            <div class="khusus-penilaian">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Atas Nama</label>
                <input type="text" class="object-owner-name mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600" required
                       placeholder="Contoh: PT. Kembang Griya Cahaya">
            </div>

            <div class="sm:col-span-2">
                @if ($konsultasi)
                    {{-- Pada proposal Non-Penilaian kolom ini menampung NAMA SINGKAT
                         proyek, bukan catatan bebas (2026-09-26, permintaan user).
                         Frasanya disisipkan ke kalimat Invoice, Kwitansi, dan
                         beberapa bab proposal lewat kode :proyek. --}}
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">
                        Nama Singkat Proyek <span class="font-normal text-red-500">*</span>
                    </label>
                    <input type="text" class="object-notes mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600"
                           placeholder="Tulis rencana pengembangan/objek pekerjaan disini">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <span class="font-medium">Tulis frasa pendek saja, tanpa nama klien dan tanpa alamat.</span>
                        Contoh: <span class="font-medium">Pabrik Pengolahan Tembakau</span> &middot;
                        <span class="font-medium">Resort Tahap II</span> &middot;
                        <span class="font-medium">Ruko/Rukan pada Project Pantai Indah Mutiara</span>.
                        Dipakai di kalimat Invoice, Kwitansi, dan bab proposal — nama klien &amp; lokasi ditambahkan otomatis.
                    </p>
                @else
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Catatan Tambahan (opsional)</label>
                    <input type="text" class="object-notes mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600"
                           placeholder="Contoh: Rumah Tinggal 2 Lantai / sesuai list yang diterima">
                @endif
            </div>
        </div>
    </div>
</template>

{{-- =============== MODAL POPUP KLIEN BARU (Tailwind) =============== --}}
<div id="clientModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Tambah Klien Baru</h2>
            <button type="button" onclick="closeClientModal()" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-200">&times;</button>
        </div>
        <div id="clientModalErrors" class="hidden mb-3 text-sm text-red-600 dark:text-red-400"></div>
        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Klien</label>
                <input type="text" id="modal_client_name" class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Klien</label>
                <select id="modal_client_type" class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                    <option value="Perbankan">Perbankan</option>
                    <option value="Korporat">Korporat</option>
                    <option value="Perorangan">Perorangan</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alamat</label>
                <textarea id="modal_client_address" rows="2" class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600"></textarea>
            </div>
            @include('partials.client-duplicate-check')
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" onclick="closeClientModal()"
                    class="inline-flex h-[38px] items-center justify-center gap-1.5 rounded-md border border-gray-300 px-4 text-sm font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">Batal</button>
            <button type="button" onclick="submitNewClient()"
                    class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Simpan</button>
        </div>
    </div>
</div>

{{-- Data repopulasi setelah validasi gagal (klien & objek dibangun via JS). --}}
<script>
    window.__BOOT__ = {
        objects: @json($oldObjects),
        instructing: @json($clientBoot($oldInstructing)),
        intended: @json($oldIntended->map($clientBoot)->values()),
        approver: @json($clientBoot($oldApprover)),
        named: @json($clientBoot($oldNamed)),
    };
</script>

<script>
    // ============================================================
    // OBJEK PENILAIAN — dynamic add/remove rows
    // ============================================================
    let objectCounter = 0; // index unik, TIDAK di-reset saat hapus baris
                            // (array PHP tetap valid walau index tidak berurutan)

    function addObjectRow(data) {
        const template = document.getElementById('object_row_template');
        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('.object-row');
        const index = objectCounter++;

        row.dataset.index = index;

        // Set name="objects[index][field]" untuk tiap input di baris ini
        row.querySelector('.object-category').name = `objects[${index}][asset_category]`;
        row.querySelector('.object-custom-category').name = `objects[${index}][custom_category]`;
        row.querySelector('.object-land-area').name = `objects[${index}][land_area]`;
        row.querySelector('.object-building-area').name = `objects[${index}][building_area]`;
        row.querySelector('.object-unit-quantity').name = `objects[${index}][unit_quantity]`;
        row.querySelector('.object-location').name = `objects[${index}][location]`;
        row.querySelector('.object-ownership-form').name = `objects[${index}][ownership_form]`;
        row.querySelector('.object-owner-name').name = `objects[${index}][owner_name]`;
        row.querySelector('.object-notes').name = `objects[${index}][notes]`;

        // Proposal konsultasi: objek cukup lokasi saja (2026-09-25).
        if (document.getElementById('objects_container').dataset.konsultasi) {
            row.querySelectorAll('.khusus-penilaian').forEach((blok) => {
                blok.classList.add('hidden');
                blok.querySelectorAll('[required]').forEach((el) => el.removeAttribute('required'));
            });
        }

        // Isi ulang nilai lama (dipakai saat validasi gagal)
        if (data) {
            const setVal = (sel, key) => {
                const el = row.querySelector(sel);
                if (el && data[key] != null) el.value = data[key];
            };
            setVal('.object-category', 'asset_category');
            setVal('.object-custom-category', 'custom_category');
            setVal('.object-land-area', 'land_area');
            setVal('.object-building-area', 'building_area');
            setVal('.object-unit-quantity', 'unit_quantity');
            setVal('.object-location', 'location');
            setVal('.object-ownership-form', 'ownership_form');
            setVal('.object-owner-name', 'owner_name');
            setVal('.object-notes', 'notes');
        }

        // Toggle field Real Properti vs Personal Properti sesuai kategori
        const categorySelect = row.querySelector('.object-category');
        categorySelect.addEventListener('change', () => {
            toggleObjectFields(row);
            updateObjectSummary(row);
        });
        row.querySelector('.object-location').addEventListener('input', () => updateObjectSummary(row));

        // Lipat/buka kartu objek
        row.querySelector('.object-toggle').addEventListener('click', () => setObjectCollapsed(row, !isObjectCollapsed(row)));

        // Tombol hapus baris ini
        row.querySelector('.remove-object-btn').addEventListener('click', () => {
            row.remove();
            renumberObjectRows();
        });

        document.getElementById('objects_container').appendChild(row);
        toggleObjectFields(row);
        updateObjectSummary(row);
        renumberObjectRows();
        return row;
    }

    function isObjectCollapsed(row) {
        return row.querySelector('.object-body').style.display === 'none';
    }

    function setObjectCollapsed(row, collapsed) {
        // Pakai style inline, bukan class "hidden" — .grid menang atas .hidden
        // di build Tailwind CDN yang dipakai aplikasi ini.
        row.querySelector('.object-body').style.display = collapsed ? 'none' : '';
        row.querySelector('.object-chevron').style.transform = collapsed ? 'rotate(180deg)' : '';
    }

    function updateObjectSummary(row) {
        const category = row.querySelector('.object-category').value;
        const location = (row.querySelector('.object-location').value || '').trim();
        const summary = row.querySelector('.object-summary');
        if (!category && !location) {
            summary.textContent = 'Objek baru — belum diisi';
            return;
        }
        summary.textContent = [category, location].filter(Boolean).join(' — ');
    }

    function toggleObjectFields(row) {
        // Proposal konsultasi tidak memakai field khusus penilaian sama sekali.
        if (document.getElementById('objects_container').dataset.konsultasi) return;

        const category = row.querySelector('.object-category').value;
        const isReal = category.startsWith('Real Properti');
        const isPersonal = category.startsWith('Personal Properti');
        const isOther = category === 'Lainnya';

        row.querySelectorAll('.real-property-fields').forEach(el => el.classList.toggle('hidden', !isReal));
        row.querySelectorAll('.personal-property-fields').forEach(el => el.classList.toggle('hidden', !isPersonal));
        row.querySelectorAll('.other-category-fields').forEach(el => el.classList.toggle('hidden', !isOther));

        // 'required' hanya aktif saat field-nya tampil, supaya tidak
        // memblokir submit secara tak terlihat.
        const customInput = row.querySelector('.object-custom-category');
        customInput.required = isOther;
        if (!isOther) customInput.value = '';
    }

    function renumberObjectRows() {
        const rows = document.querySelectorAll('#objects_container .object-row');
        rows.forEach((row, i) => {
            row.querySelector('.object-row-number').textContent = i + 1;
        });
        // Kalau semua baris terhapus, tambahkan 1 baris kosong lagi
        // supaya form tidak pernah kosong melompong.
        if (rows.length === 0) addObjectRow();
    }

    // Baris objek yang terlipat berisi field 'required' yang tidak terlihat —
    // browser akan memblokir submit tanpa pesan yang jelas. Jadi buka semua
    // sebelum validasi berjalan (click) dan saat ada field invalid (capture).
    function expandAllObjectRows() {
        document.querySelectorAll('#objects_container .object-row').forEach(row => setObjectCollapsed(row, false));
    }
    document.getElementById('submitProposalBtn').addEventListener('click', expandAllObjectRows);
    document.getElementById('proposalForm').addEventListener('invalid', (e) => {
        const row = e.target.closest('.object-row');
        if (row) setObjectCollapsed(row, false);
    }, true);

    // ============================================================
    // STATE: klien yang sedang dipilih
    // ============================================================
    let instructingClient = null;
    let intendedUsers = [];
    let activeModalTarget = null;

    const searchUrl = "{{ route('clients.search') }}";
    const storeAjaxUrl = "{{ route('clients.storeAjax') }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    function debounce(fn, delay = 300) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), delay);
        };
    }

    async function searchClients(keyword) {
        const res = await fetch(`${searchUrl}?q=${encodeURIComponent(keyword)}`, {
            headers: { 'Accept': 'application/json' },
        });
        return res.json();
    }

    // Teks dari database di-escape sebelum disisipkan ke innerHTML.
    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
    }

    function renderResults(container, clients, onPick) {
        if (clients.length === 0) {
            container.innerHTML = `<div class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500">Tidak ditemukan. Coba "+ Klien Baru".</div>`;
        } else {
            // Yang ditampilkan alamat, bukan jenis klien (2026-09-15, feedback
            // user) — satu nama seperti Bank Mandiri bisa punya banyak cabang.
            container.innerHTML = clients.map((c, i) => `
                <button type="button" data-index="${i}"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-blue-50 border-b border-gray-100 last:border-0 dark:hover:bg-blue-900/30 dark:border-gray-800">
                    <div class="font-medium text-gray-800 dark:text-gray-200">${escapeHtml(c.client_name)}</div>
                    <div class="text-xs text-gray-400 whitespace-pre-line dark:text-gray-500">${escapeHtml(c.address) || '<span class="italic">Alamat belum diisi</span>'}</div>
                </button>
            `).join('');
            container.querySelectorAll('button[data-index]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const c = clients[+btn.dataset.index];
                    onPick({ id: c.id, client_name: c.client_name, address: c.address || '' });
                    container.classList.add('hidden');
                });
            });
        }
        container.classList.remove('hidden');
    }

    // Chip klien terpilih: nama + alamat.
    function showClientChip(prefix, client) {
        document.getElementById(prefix + '_chip_text').textContent = client.client_name;
        document.getElementById(prefix + '_chip_address').textContent = client.address || 'Alamat belum diisi';
        document.getElementById(prefix + '_chip').style.display = 'flex';
    }

    const instructingInput = document.getElementById('instructing_client_search');
    const instructingResults = document.getElementById('instructing_client_results');

    instructingInput.addEventListener('input', debounce(async (e) => {
        const keyword = e.target.value.trim();
        if (keyword.length < 2) { instructingResults.classList.add('hidden'); return; }
        const clients = await searchClients(keyword);
        renderResults(instructingResults, clients, setInstructingClient);
    }));

    function setInstructingClient(client) {
        instructingClient = client;
        document.getElementById('instructing_client_id').value = client.id;
        showClientChip('instructing_client', client);
        instructingInput.value = '';
        instructingInput.classList.add('hidden');
    }

    function clearInstructingClient() {
        instructingClient = null;
        document.getElementById('instructing_client_id').value = '';
        document.getElementById('instructing_client_chip').style.display = 'none';
        instructingInput.classList.remove('hidden');
    }

    const intendedInput = document.getElementById('intended_user_search');
    const intendedResults = document.getElementById('intended_user_results');

    intendedInput.addEventListener('input', debounce(async (e) => {
        const keyword = e.target.value.trim();
        if (keyword.length < 2) { intendedResults.classList.add('hidden'); return; }
        const clients = await searchClients(keyword);
        const filtered = clients.filter(c => !intendedUsers.some(u => u.id == c.id));
        renderResults(intendedResults, filtered, addIntendedUser);
    }));

    function addIntendedUser(client) {
        if (intendedUsers.some(u => u.id == client.id)) return;
        intendedUsers.push(client);
        renderIntendedChips();
        intendedInput.value = '';
    }

    function removeIntendedUser(id) {
        intendedUsers = intendedUsers.filter(u => u.id != id);
        renderIntendedChips();
    }

    function renderIntendedChips() {
        const chipBox = document.getElementById('intended_user_chips');
        const hiddenBox = document.getElementById('intended_user_hidden_inputs');

        chipBox.innerHTML = intendedUsers.map(u => `
            <div class="flex items-start justify-between gap-3 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300">
                <div class="min-w-0">
                    <div class="font-medium">${escapeHtml(u.client_name)}</div>
                    <div class="text-xs text-gray-500 whitespace-pre-line dark:text-gray-400">${escapeHtml(u.address) || 'Alamat belum diisi'}</div>
                </div>
                <button type="button" onclick="removeIntendedUser(${u.id})" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-200">&times;</button>
            </div>
        `).join('');

        hiddenBox.innerHTML = intendedUsers.map(u =>
            `<input type="hidden" name="intended_user_ids[]" value="${u.id}">`
        ).join('');
    }

    // ---------- Nama Klien (opsional, satu klien dari Database Klien) ----------
    const namedInput = document.getElementById('named_client_search');
    const namedResults = document.getElementById('named_client_results');

    namedInput.addEventListener('input', debounce(async (e) => {
        const keyword = e.target.value.trim();
        if (keyword.length < 2) { namedResults.classList.add('hidden'); return; }
        renderResults(namedResults, await searchClients(keyword), setNamedClient);
    }));

    function setNamedClient(client) {
        document.getElementById('named_client_id').value = client.id;
        showClientChip('named_client', client);
        namedInput.value = '';
        namedInput.classList.add('hidden');
    }

    function clearNamedClient() {
        document.getElementById('named_client_id').value = '';
        document.getElementById('named_client_chip').style.display = 'none';
        namedInput.classList.remove('hidden');
    }

    document.addEventListener('click', (e) => {
        if (!namedInput.contains(e.target) && !namedResults.contains(e.target)) namedResults.classList.add('hidden');
    });

    // ---------- Pihak yang Menyetujui (opsional, satu klien) ----------
    const approverInput = document.getElementById('approver_client_search');
    const approverResults = document.getElementById('approver_client_results');

    approverInput.addEventListener('input', debounce(async (e) => {
        const keyword = e.target.value.trim();
        if (keyword.length < 2) { approverResults.classList.add('hidden'); return; }
        renderResults(approverResults, await searchClients(keyword), setApproverClient);
    }));

    function setApproverClient(client) {
        document.getElementById('approver_client_id').value = client.id;
        showClientChip('approver_client', client);
        approverInput.value = '';
        approverInput.classList.add('hidden');
    }

    function clearApproverClient() {
        document.getElementById('approver_client_id').value = '';
        document.getElementById('approver_client_chip').style.display = 'none';
        approverInput.classList.remove('hidden');
    }

    document.addEventListener('click', (e) => {
        if (!approverInput.contains(e.target) && !approverResults.contains(e.target)) {
            approverResults.classList.add('hidden');
        }
    });

    document.addEventListener('click', (e) => {
        if (!instructingInput.contains(e.target) && !instructingResults.contains(e.target)) {
            instructingResults.classList.add('hidden');
        }
        if (!intendedInput.contains(e.target) && !intendedResults.contains(e.target)) {
            intendedResults.classList.add('hidden');
        }
    });

    // ============================================================
    // MODAL TAMBAH KLIEN BARU
    // ============================================================
    function openClientModal(target) {
        activeModalTarget = target;
        document.getElementById('clientModalErrors').classList.add('hidden');
        if (window.clientDupReset) clientDupReset();
        document.getElementById('clientModal').classList.remove('hidden');
        document.getElementById('clientModal').classList.add('flex');
    }

    function closeClientModal() {
        document.getElementById('clientModal').classList.add('hidden');
        document.getElementById('clientModal').classList.remove('flex');
    }

    // Pasang klien (baru dibuat ATAU dipilih dari peringatan klien kembar) ke kolom asal modal.
    function applyModalClient(client) {
        if (activeModalTarget === 'instructing') setInstructingClient(client);
        else if (activeModalTarget === 'approver') setApproverClient(client);
        else if (activeModalTarget === 'named') setNamedClient(client);
        else addIntendedUser(client);
    }

    async function submitNewClient() {
        const payload = {
            client_name: document.getElementById('modal_client_name').value,
            client_type: document.getElementById('modal_client_type').value,
            address: document.getElementById('modal_client_address').value,
        };
        if ((window.clientDupMatches || []).some((c) => c.same_address) && !window.clientDupDismissed
            && !confirm('Klien dengan nama & alamat yang sama sudah ada. Tetap buat klien baru?')) {
            return;
        }

        try {
            const response = await fetch(storeAjaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!data.success) {
                const errBox = document.getElementById('clientModalErrors');
                errBox.innerHTML = Object.values(data.errors).flat().join('<br>');
                errBox.classList.remove('hidden');
                return;
            }

            const client = { id: data.client.id, client_name: data.client.client_name, address: data.client.address || '' };

            applyModalClient(client);

            closeClientModal();
        } catch (err) {
            alert('Gagal menyimpan klien. Silakan coba lagi.');
            console.error(err);
        }
    }

    // ============================================================
    // INPUT MASKING — Biaya (separator ribuan) + rincian PPN/transport
    // ============================================================
    const PPN_RATE = {{ (float) config('kjpp.ppn_rate', 0.11) }};

    function rupiahFmt(n) { return new Intl.NumberFormat('id-ID').format(Math.round(n)); }
    function digits(v) { return (v || '').replace(/\D/g, ''); }

    function maskMoney(displayId, rawId, onChange) {
        const d = document.getElementById(displayId);
        const r = document.getElementById(rawId);
        if (!d) return;
        d.addEventListener('input', () => {
            const only = digits(d.value);
            d.value = only ? rupiahFmt(parseInt(only, 10)) : '';
            r.value = only;
            if (onChange) onChange();
        });
    }

    function toggleFeeBreakdown() {
        const isBreakdown = document.getElementById('fee_breakdown').value === '1';
        const reimbursed = document.getElementById('transport_reimbursed').value === '1';
        // Isian nominal TA hanya relevan di format Rincian & bila TA ikut ditagih.
        document.getElementById('transport_cost_wrap').classList.toggle('hidden', !(isBreakdown && !reimbursed));
        document.getElementById('service_fee_label').textContent =
            isBreakdown ? 'Fee Jasa Profesional (Rp)' : 'Nilai Biaya Jasa (Rp)';
        recalcFeeTotal();
    }

    /**
     * Baca nilai rupiah dari input tersembunyi. Bagian desimal dibuang lebih
     * dulu supaya '5550000.00' tidak terbaca 555000000 (2026-09-24).
     */
    function rawRupiah(id) {
        const v = String(document.getElementById(id).value || '').trim();
        return parseInt(digits(v.split(/[.,]\d{1,2}$/)[0]) || '0', 10);
    }

    function recalcFeeTotal() {
        // PPN atas Fee + Transport & Akomodasi yang ditagih — rumus sama dengan
        // accessor total_fee di model Project. TA reimburse tidak dihitung.
        const PPN_PCT = +(PPN_RATE * 100).toFixed(2);
        const base = rawRupiah('service_fee_raw');                 // Fee
        const transportRaw = rawRupiah('transport_cost_raw');
        const ppnIncluded = document.getElementById('fee_ppn_included').value === '1';
        const isBreakdown = document.getElementById('fee_breakdown').value === '1';
        const reimbursed = document.getElementById('transport_reimbursed').value === '1';
        const transport = (isBreakdown && !reimbursed) ? transportRaw : 0;

        let feeNet, transportNet, ppn, total;
        if (ppnIncluded) {
            total = base + transport;             // input sudah gross → tidak digross-up lagi
            feeNet = base / (1 + PPN_RATE);
            transportNet = transport / (1 + PPN_RATE);
            ppn = total - total / (1 + PPN_RATE);
        } else {
            feeNet = base;
            transportNet = transport;
            ppn = (base + transport) * PPN_RATE;
            total = base + transport + ppn;
        }

        // Nominal per termin ikut berubah begitu biaya jasa diubah.
        if (window.ptSetTotal) window.ptSetTotal(total);

        document.getElementById('service_fee_hint').textContent = (ppnIncluded
            ? 'Fee & transport sudah termasuk PPN — di rincian tampil net (dikurangi PPN).'
            : 'PPN ' + PPN_PCT + '% ditambahkan di atas Fee & transport.')
            + (reimbursed ? ' Transport & akomodasi ditanggung klien — tidak masuk total.' : '');

        const preview = document.getElementById('fee_total_preview');
        if (!base && !transport) { preview.textContent = ''; return; }
        preview.textContent = isBreakdown
            ? 'Perkiraan: Fee Rp ' + rupiahFmt(feeNet)
              + (transport ? ' + Transport Rp ' + rupiahFmt(transportNet) : '')
              + ' + PPN ' + PPN_PCT + '% Rp ' + rupiahFmt(ppn)
              + ' = Total Rp ' + rupiahFmt(total)
            : 'Perkiraan total (ditagihkan): Rp ' + rupiahFmt(total) + '  ·  PPN Rp ' + rupiahFmt(ppn);
    }

    maskMoney('service_fee_display', 'service_fee_raw', recalcFeeTotal);
    maskMoney('transport_cost_display', 'transport_cost_raw', recalcFeeTotal);
    document.getElementById('fee_ppn_included').addEventListener('change', recalcFeeTotal);

    document.getElementById('proposalForm').addEventListener('submit', (e) => {
        const only = digits(document.getElementById('service_fee_display').value);
        document.getElementById('service_fee_raw').value = only;
        document.getElementById('transport_cost_raw').value = digits(document.getElementById('transport_cost_display').value);
        if (!only) {
            e.preventDefault();
            document.getElementById('service_fee_display').classList.add('border-red-400');
            document.getElementById('service_fee_display').focus();
            return;
        }
        formSubmitting = true; // matikan peringatan "keluar halaman"
    });

    toggleFeeBreakdown();

    // ============================================================
    // TOGGLE FIELD LK PROPERTI + HINT DASAR NILAI
    // ============================================================
    const purposeHints = {
        'Jual Beli': 'Dasar Nilai: Nilai Pasar.',
        'Penjaminan Utang': 'Dasar Nilai: Nilai Pasar (Indikasi Nilai Likuidasi dapat diminta bank).',
        'Lelang': 'Dasar Nilai: Nilai Pasar & Nilai Likuidasi (wajib), termasuk klausul Waktu Ekspos.',
        'Pelaporan Keuangan': 'Dasar Nilai: Nilai Wajar (Fair Value), mengacu PSAK 113.',
    };

    function toggleLkFields() {
        // Proposal konsultasi tidak merender dropdown tujuannya.
        const select = document.getElementById('proposal_purpose');
        if (! select) return;

        const purpose = select.value;
        document.getElementById('lk_fields').classList.toggle('hidden', purpose !== 'Pelaporan Keuangan');
        document.getElementById('purpose_hint').textContent = purposeHints[purpose] ?? '';
    }

    // ============================================================
    // PERINGATAN KELUAR HALAMAN — form ini panjang, salah tekan
    // back/tutup tab berarti mengetik ulang semuanya.
    // ============================================================
    let formDirty = false;
    let formSubmitting = false;
    document.getElementById('proposalForm').addEventListener('input', () => { formDirty = true; });
    document.getElementById('proposalForm').addEventListener('change', () => { formDirty = true; });
    window.addEventListener('beforeunload', (e) => {
        if (!formDirty || formSubmitting) return;
        e.preventDefault();
        e.returnValue = '';
    });
    document.getElementById('cancelProposalLink').addEventListener('click', (e) => {
        if (formDirty && !confirm('Keluar dari halaman ini? Data proposal yang sudah diisi akan hilang.')) {
            e.preventDefault();
        } else {
            formSubmitting = true; // sudah dikonfirmasi, jangan tanya dua kali
        }
    });

    // ============================================================
    // INISIALISASI
    // ============================================================
    function initProposalForm() {
        const boot = window.__BOOT__ || {};

        if (Array.isArray(boot.objects) && boot.objects.length) {
            boot.objects.forEach(o => addObjectRow(o));
        } else {
            addObjectRow();
        }

        if (boot.instructing) setInstructingClient(boot.instructing);
        (boot.intended || []).forEach(addIntendedUser);
        if (boot.approver) setApproverClient(boot.approver);
        if (boot.named) setNamedClient(boot.named);

        toggleLkFields();
        recalcFeeTotal();
        initQuickNav();
    }

    // ============================================================
    // NAVIGASI CEPAT — scrollspy sederhana (pola sama dgn halaman detail)
    // ============================================================
    function initQuickNav() {
        const nav = document.getElementById('quickNav');
        if (!nav) return;
        const links = Array.from(nav.querySelectorAll('[data-target]'));
        const sections = [];
        links.forEach(link => {
            const el = document.getElementById(link.dataset.target);
            if (el) sections.push(el);
        });
        if (sections.length === 0) return;

        function setActive(id) {
            links.forEach(link => {
                const active = link.dataset.target === id;
                link.classList.toggle('bg-blue-600', active);
                link.classList.toggle('text-white', active);
                link.classList.toggle('text-gray-500', !active);
                link.classList.toggle('dark:text-gray-400', !active);
            });
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) setActive(entry.target.id);
            });
        }, { rootMargin: '-96px 0px -70% 0px' });

        sections.forEach(el => observer.observe(el));
        setActive(sections[0].id);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProposalForm);
    } else {
        initProposalForm();
    }

    /* ===== Tanggal Proposal — tampil/ketik dd/mm/yyyy, submit ISO (yyyy-mm-dd) ===== */
    (function () {
        var disp = document.getElementById('proposal_date_display');
        var iso  = document.getElementById('proposal_date_iso');
        if (!disp || !iso) return;

        function isoToDisplay(v) {
            var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v || '');
            return m ? m[3] + '/' + m[2] + '/' + m[1] : '';
        }
        function displayToIso(v) {
            var m = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec((v || '').trim());
            if (!m) return '';
            var d = +m[1], mo = +m[2], y = +m[3];
            var dt = new Date(y, mo - 1, d);
            if (dt.getFullYear() !== y || dt.getMonth() !== mo - 1 || dt.getDate() !== d) return '';
            return y + '-' + String(mo).padStart(2, '0') + '-' + String(d).padStart(2, '0');
        }

        disp.value = isoToDisplay(iso.value);

        disp.addEventListener('input', function () {
            var digits = disp.value.replace(/\D/g, '').slice(0, 8);
            if (digits.length > 4)      disp.value = digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4);
            else if (digits.length > 2) disp.value = digits.slice(0, 2) + '/' + digits.slice(2);
            else                        disp.value = digits;
            var parsed = displayToIso(disp.value);
            if (parsed) iso.value = parsed;
        });

        disp.addEventListener('blur', function () {
            var parsed = displayToIso(disp.value);
            if (parsed) { iso.value = parsed; disp.value = isoToDisplay(parsed); }
            else        { disp.value = isoToDisplay(iso.value); }
        });

        // Ikon kalender -> date picker native; hasilnya sinkron ke text + hidden.
        var pick    = document.getElementById('proposal_date_picker');
        var pickBtn = document.getElementById('proposal_date_pick');
        if (pick && pickBtn) {
            pickBtn.addEventListener('click', function () {
                pick.value = iso.value || '';
                if (typeof pick.showPicker === 'function') { try { pick.showPicker(); return; } catch (e) {} }
                pick.focus(); pick.click();
            });
            pick.addEventListener('change', function () {
                if (pick.value) { iso.value = pick.value; disp.value = isoToDisplay(pick.value); }
            });
        }
    })();
</script>
@endsection
