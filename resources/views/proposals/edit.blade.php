@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Edit Proposal — {{ $project->proposal_number }}</h1>
            <p class="text-xs text-amber-600 mt-1">⚠️ Perubahan di sini akan menimpa data objek penilaian sebelumnya.</p>
        </div>
        <a href="{{ route('proposals.show', $project) }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali ke Detail Proyek</a>
    </div>

    <form action="{{ route('proposals.update', $project) }}" method="POST" class="space-y-6 bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        @csrf
        @method('PUT')

        {{-- ============ NOMOR PROPOSAL (70%) + TANGGAL PROPOSAL (30%) — 1 BARIS ============ --}}
        <div class="grid grid-cols-1 sm:grid-cols-10 gap-4">
            <div class="sm:col-span-7">
                <label class="block text-sm font-medium text-gray-700">Nomor Proposal</label>
                <input type="text" name="proposal_number" required
                       value="{{ old('proposal_number', $project->proposal_number) }}"
                       autocomplete="off" spellcheck="false"
                       placeholder="00000/2.0131-00/KJPPSPR-PRO/APP/_/2026"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm font-mono">
                <p class="mt-1 text-xs text-gray-400">
                    Diinput manual sesuai nomor resmi yang diterbitkan sistem terintegrasi Kantor Pusat.
                </p>
            </div>
            <div class="sm:col-span-3">
                <label class="block text-sm font-medium text-gray-700">Tanggal Proposal</label>
                {{-- Diketik/tampil dd/mm/yyyy; ikon kalender membuka date picker native.
                     Yang disubmit = hidden #proposal_date_iso (yyyy-mm-dd). --}}
                <div class="relative mt-1">
                    <input type="text" id="proposal_date_display" required
                           placeholder="dd/mm/yyyy" inputmode="numeric" autocomplete="off" maxlength="10"
                           class="w-full rounded-md border-gray-300 shadow-sm pr-10">
                    <button type="button" id="proposal_date_pick" tabindex="-1" aria-label="Pilih dari kalender"
                            class="absolute inset-y-0 right-0 grid w-10 place-items-center text-gray-400 hover:text-gray-600">
                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0V11.25A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                        </svg>
                    </button>
                    <input type="date" id="proposal_date_picker" tabindex="-1" aria-hidden="true"
                           class="pointer-events-none absolute bottom-0 left-0 h-px w-px opacity-0">
                </div>
                <input type="hidden" name="proposal_date" id="proposal_date_iso"
                       value="{{ old('proposal_date', ($project->proposal_date ?? $project->created_at)->toDateString()) }}">
                <p class="mt-1 text-xs text-gray-400">
                    Format dd/mm/yyyy — atau klik ikon kalender. Tercetak di kop dokumen; boleh mundur.
                </p>
            </div>
        </div>

        {{-- ===================== DASAR PERMINTAAN PENILAIAN (MANUAL) ===================== --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Dasar Permintaan Penilaian</label>
            <textarea name="request_basis" rows="2"
                      placeholder="Contoh: yang kami terima melalui Pesan WhatsApp permintaan penilaian tanggal 07 September 2026"
                      class="mt-1 w-full rounded-md border-gray-300 shadow-sm">{{ old('request_basis', $project->request_basis) }}</textarea>
            <p class="mt-1 text-xs text-gray-400">
                Mengisi bagian kosong pada kalimat pembuka proposal:
                &ldquo;Sesuai dengan informasi permintaan penilaian <span class="italic">[teks ini]</span>, mengenai permohonan jasa Penilai&hellip;&rdquo;.
            </p>
        </div>

        {{-- ===================== PENANDATANGAN PROPOSAL ===================== --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Penandatangan Proposal</label>
            <select name="signed_by_user_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                <option value="">-- Penandatangan baku kantor ({{ config('kjpp.signatory.name') }}) --</option>
                @foreach ($signers as $signer)
                    <option value="{{ $signer->id }}" @selected(old('signed_by_user_id', $project->signed_by_user_id) == $signer->id)>{{ $signer->name }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-400">
                Daftar diambil dari pengguna aktif berjabatan <span class="font-medium">Penanggung Jawab</span>.
                Nama &amp; nomor izin (MAPPI, RMK, Izin Menkeu, STTD OJK, Klasifikasi) pada blok tanda tangan
                proposal mengikuti biodata pengguna yang dipilih. Kosongkan untuk memakai penandatangan baku.
            </p>
            @if ($signers->isEmpty() && ! $project->signed_by_user_id)
                <p class="mt-1 text-xs text-amber-600">
                    Belum ada pengguna aktif berjabatan &ldquo;Penanggung Jawab&rdquo;. Lengkapi lewat menu Kelola Pengguna.
                </p>
            @endif
        </div>

        {{-- ===================== PIHAK YANG MENYETUJUI ===================== --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Pihak yang Menyetujui</label>
            <input type="text" name="approver_name" value="{{ old('approver_name', $project->approver_name) }}"
                   placeholder="Kosongkan = otomatis pakai nama Pemberi Tugas"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            <p class="mt-1 text-xs text-gray-400">
                Nama pihak pada kolom &ldquo;Menyetujui,&rdquo; di blok tanda tangan. Isi bila yang menyetujui
                berbeda dari Pemberi Tugas (mis. bank, sementara Pemberi Tugas-nya PT — atau sebaliknya).
            </p>
        </div>

        {{-- ===================== REKENING BANK ===================== --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Rekening Bank Pembayaran</label>
            <select name="bank_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                <option value="">-- Rekening baku kantor (default) --</option>
                @foreach ($banks as $bank)
                    <option value="{{ $bank->id }}" @selected(old('bank_id', $project->bank_id) == $bank->id)>
                        {{ $bank->bank_name }}{{ $bank->branch ? ' (' . $bank->branch . ')' : '' }} — {{ $bank->account_number }}{{ $bank->is_default ? ' · default' : '' }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-400">
                Dipakai di blok &ldquo;Rekening Bank&rdquo; proposal &amp; PDF Invoice. Kosongkan untuk memakai
                rekening default. Daftar dikelola di menu <span class="font-medium">Kelola Rekening Bank</span>.
            </p>
        </div>

        {{-- ===================== PEMBERI TUGAS (AJAX COMBOBOX) ===================== --}}
        <div class="relative">
            <label class="block text-sm font-medium text-gray-700">Nama Klien (Pemberi Tugas)</label>
            <div class="flex gap-2 mt-1">
                <div class="relative flex-1">
                    <input type="text" id="instructing_client_search" autocomplete="off"
                           placeholder="Ketik nama klien untuk mencari..."
                           class="w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <div id="instructing_client_results"
                         class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto"></div>
                </div>
                <button type="button" onclick="openClientModal('instructing')"
                        class="px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 whitespace-nowrap">
                    + Klien Baru
                </button>
            </div>
            <div id="instructing_client_chip" class="hidden mt-2 inline-flex items-center gap-2 bg-blue-50 border border-blue-200 text-blue-800 text-sm px-3 py-1.5 rounded-full">
                <span id="instructing_client_chip_text"></span>
                <button type="button" onclick="clearInstructingClient()" class="text-blue-500 hover:text-blue-700">&times;</button>
            </div>
            <input type="hidden" name="instructing_client_id" id="instructing_client_id" required>
        </div>

        {{-- ===================== PENGGUNA LAPORAN (AJAX COMBOBOX, MULTI) ===================== --}}
        <div class="relative">
            <label class="block text-sm font-medium text-gray-700">Pengguna Laporan (bisa lebih dari satu)</label>
            <div class="flex gap-2 mt-1">
                <div class="relative flex-1">
                    <input type="text" id="intended_user_search" autocomplete="off"
                           placeholder="Ketik nama klien untuk mencari..."
                           class="w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <div id="intended_user_results"
                         class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto"></div>
                </div>
                <button type="button" onclick="openClientModal('intended')"
                        class="px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 whitespace-nowrap">
                    + Klien Baru
                </button>
            </div>
            <div id="intended_user_chips" class="flex flex-wrap gap-2 mt-2"></div>
            <div id="intended_user_hidden_inputs"></div>
        </div>

        <hr>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Jenis Proposal</label>
                <select name="proposal_purpose" id="proposal_purpose" onchange="toggleLkFields()"
                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    <option value="Jual Beli" @selected($project->proposal_purpose === 'Jual Beli')>Jual Beli</option>
                    <option value="Penjaminan Utang" @selected($project->proposal_purpose === 'Penjaminan Utang')>Penjaminan Utang</option>
                    <option value="Lelang" @selected($project->proposal_purpose === 'Lelang')>Lelang</option>
                    <option value="Pelaporan Keuangan" @selected($project->proposal_purpose === 'Pelaporan Keuangan')>Pelaporan Keuangan (LK Properti)</option>
                </select>
                <p class="mt-1 text-xs text-gray-400" id="purpose_hint"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Jenis Laporan</label>
                <select name="report_style" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    <option value="Long Report" @selected(old('report_style', $project->report_style) === 'Long Report')>Long Report — Laporan Terinci (Comprehensive Style)</option>
                    <option value="Short Report" @selected(old('report_style', $project->report_style) === 'Short Report')>Short Report — Laporan Ringkas (Short Form)</option>
                </select>
            </div>
        </div>

        {{-- ===================== SLA (HARI KERJA, INPUT MANUAL) ===================== --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">SLA Laporan Draft/Resume (hari kerja)</label>
                <input type="number" name="sla_draft_days" min="1" max="365" required
                       value="{{ old('sla_draft_days', $project->sla_draft_days) }}" placeholder="Contoh: 3"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                <p class="mt-1 text-xs text-gray-400">Dihitung sejak inspeksi lapangan & penerimaan data terakhir.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">SLA Laporan Final (hari kerja)</label>
                <input type="number" name="sla_final_days" min="1" max="365" required
                       value="{{ old('sla_final_days', $project->sla_final_days) }}" placeholder="Contoh: 5"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                <p class="mt-1 text-xs text-gray-400">Dihitung sejak Laporan Draft/Resume disetujui Pemberi Tugas.</p>
            </div>
        </div>

        <div id="lk_fields" class="hidden space-y-4 rounded-md border border-dashed border-blue-300 bg-blue-50 p-4">
            <p class="text-sm font-medium text-blue-800">Detail Khusus Pelaporan Keuangan</p>
            <div>
                <label class="block text-sm font-medium text-gray-700">Klasifikasi PSAK</label>
                <select name="psak_classification[]" multiple class="mt-1 w-full rounded-md border-gray-300 shadow-sm h-24">
                    @php $selectedPsak = explode(', ', $project->psak_classification ?? ''); @endphp
                    <option value="Aset Tetap (PSAK 16)" @selected(in_array('Aset Tetap (PSAK 16)', $selectedPsak))>Aset Tetap (PSAK 16)</option>
                    <option value="Properti Investasi (PSAK 13)" @selected(in_array('Properti Investasi (PSAK 13)', $selectedPsak))>Properti Investasi (PSAK 13)</option>
                    <option value="Persediaan (PSAK 14)" @selected(in_array('Persediaan (PSAK 14)', $selectedPsak))>Persediaan (PSAK 14)</option>
                    <option value="Aset Tidak Berwujud (PSAK 19)" @selected(in_array('Aset Tidak Berwujud (PSAK 19)', $selectedPsak))>Aset Tidak Berwujud (PSAK 19)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Tanggal Pelaporan Keuangan (Cut-off)</label>
                <input type="date" name="financial_reporting_date"
                       value="{{ $project->financial_reporting_date?->toDateString() }}"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_public_company" value="1" class="rounded border-gray-300" @checked($project->is_public_company)>
                Klien adalah Perusahaan Terbuka (menampilkan klausul POJK 28/POJK.04/2021)
            </label>
        </div>

        <hr>

        {{-- ===================== BIAYA JASA PENILAIAN ===================== --}}
        <div class="space-y-4 rounded-md border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-700">Biaya Jasa Penilaian</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status PPN</label>
                    <select name="fee_ppn_included" id="fee_ppn_included" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="1" @selected(old('fee_ppn_included', $project->fee_ppn_included ? '1' : '0') == '1')>Sudah termasuk PPN</option>
                        <option value="0" @selected(old('fee_ppn_included', $project->fee_ppn_included ? '1' : '0') === '0')>Belum termasuk PPN (11%)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Format Biaya</label>
                    <select name="fee_breakdown" id="fee_breakdown" onchange="toggleFeeBreakdown()" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="0" @selected(old('fee_breakdown', $project->fee_breakdown ? '1' : '0') == '0')>All-in (langsung)</option>
                        <option value="1" @selected(old('fee_breakdown', $project->fee_breakdown ? '1' : '0') === '1')>Rincian (breakdown)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700" id="service_fee_label">Nilai Dasar Biaya (Rp)</label>
                <input type="text" id="service_fee_display" inputmode="numeric" autocomplete="off" required
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                <input type="hidden" name="service_fee" id="service_fee_raw" value="{{ $project->service_fee }}">
                <p class="mt-1 text-xs text-gray-400" id="service_fee_hint"></p>
            </div>

            <div id="transport_cost_wrap" class="hidden">
                <label class="block text-sm font-medium text-gray-700">Biaya Transport &amp; Akomodasi (Rp)</label>
                <input type="text" id="transport_cost_display" inputmode="numeric" autocomplete="off"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                <input type="hidden" name="transport_cost" id="transport_cost_raw" value="{{ $project->transport_cost ? (int) $project->transport_cost : '' }}">
            </div>

            <p class="text-sm text-gray-600" id="fee_total_preview"></p>
        </div>

        <hr>

        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700">Identifikasi Objek Penilaian dan Kepemilikan</label>
                <button type="button" onclick="addObjectRow()"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                    + Tambah Objek
                </button>
            </div>
            <div id="objects_container" class="space-y-4"></div>
        </div>

        <div class="pt-2 flex gap-3">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">
                Simpan Perubahan
            </button>
            <a href="{{ route('proposals.show', $project) }}" class="px-5 py-2 border border-gray-300 rounded-md hover:bg-gray-50 font-medium text-gray-700">
                Batal
            </a>
        </div>
    </form>
</div>

{{-- Template baris objek — SAMA PERSIS dengan create.blade.php --}}
<template id="object_row_template">
    <div class="object-row border border-gray-200 rounded-md p-4 bg-gray-50 relative">
        <div class="flex items-center justify-between mb-3">
            <span class="object-row-number text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-full w-6 h-6 flex items-center justify-center">1</span>
            <button type="button" class="remove-object-btn text-red-500 hover:text-red-700 text-sm font-medium">🗑 Hapus Objek</button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600">Kategori Aset/Properti</label>
                <select class="object-category mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option value="Real Properti - Tanah">Real Properti - Tanah</option>
                    <option value="Real Properti - Bangunan">Real Properti - Bangunan</option>
                    <option value="Real Properti - Tanah dan Bangunan">Real Properti - Tanah dan Bangunan</option>
                    <option value="Personal Properti - Mesin dan Peralatan">Personal Properti - Mesin dan Peralatan</option>
                    <option value="Personal Properti - Kendaraan">Personal Properti - Kendaraan</option>
                    <option value="Personal Properti - Alat Berat">Personal Properti - Alat Berat</option>
                    <option value="Bisnis / Perusahaan">Bisnis / Perusahaan</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>
            <div class="other-category-fields hidden col-span-2">
                <label class="block text-xs font-medium text-gray-600">Sebutkan Jenis Aset/Properti</label>
                <input type="text" class="object-custom-category mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm"
                       placeholder="Contoh: Kapal / Pesawat / Hak Sewa / Tanaman Keras">
            </div>
            <div class="real-property-fields hidden">
                <label class="block text-xs font-medium text-gray-600">Luas Tanah (m²)</label>
                <input type="number" step="0.01" min="0" class="object-land-area mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>
            <div class="real-property-fields hidden">
                <label class="block text-xs font-medium text-gray-600">Luas Bangunan (m²)</label>
                <input type="number" step="0.01" min="0" class="object-building-area mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>
            <div class="personal-property-fields hidden col-span-2">
                <label class="block text-xs font-medium text-gray-600">Jumlah Unit</label>
                <input type="number" min="0" class="object-unit-quantity mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600">Lokasi Objek</label>
                <textarea rows="2" class="object-location mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm" required
                          placeholder="Masukkan alamat lengkap beserta kelurahan, kecamatan, kota/kabupaten dan Provinsi"></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600">Bentuk/Jenis Hak Atas Tanah</label>
                <input type="text" class="object-ownership-form mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600">Atas Nama</label>
                <input type="text" class="object-owner-name mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm" required>
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600">Catatan Tambahan (opsional)</label>
                <input type="text" class="object-notes mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>
        </div>
    </div>
</template>

{{-- Modal tambah klien baru — SAMA PERSIS dengan create.blade.php --}}
<div id="clientModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Tambah Klien Baru</h2>
            <button type="button" onclick="closeClientModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <div id="clientModalErrors" class="hidden mb-3 text-sm text-red-600"></div>
        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700">Nama Klien</label>
                <input type="text" id="modal_client_name" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Jenis Klien</label>
                <select id="modal_client_type" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    <option value="Perbankan">Perbankan</option>
                    <option value="Korporat">Korporat</option>
                    <option value="Perorangan">Perorangan</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Alamat</label>
                <textarea id="modal_client_address" rows="2" class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></textarea>
            </div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" onclick="closeClientModal()" class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50">Batal</button>
            <button type="button" onclick="submitNewClient()" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Simpan</button>
        </div>
    </div>
</div>

<script>
    // ============================================================
    // DATA AWAL DARI SERVER (untuk pre-fill form edit)
    // ============================================================
    @php
        // Siapkan data di PHP dulu (bukan langsung di dalam @json()
        // dengan closure multi-baris) — supaya tidak ada lagi masalah
        // kurung tidak seimbang saat Blade meng-compile.
        $intendedUsersData = $project->intendedUsers->map(function ($u) {
            return ['id' => $u->id, 'client_name' => $u->client_name];
        })->values();
 
        $objectsData = $project->valuationObjects->map(function ($o) {
            return [
                'asset_category' => $o->asset_category,
                'custom_category' => $o->custom_category,
                'land_area' => $o->land_area,
                'building_area' => $o->building_area,
                'unit_quantity' => $o->unit_quantity,
                'location' => $o->location,
                'ownership_form' => $o->ownership_form,
                'owner_name' => $o->owner_name,
                'notes' => $o->notes,
            ];
        })->values();
    @endphp
    const initialInstructingClient = @json([
        'id' => $project->instructingClient->id,
        'client_name' => $project->instructingClient->client_name,
    ]);
    const initialIntendedUsers = @json($intendedUsersData);
    const initialObjects = @json($objectsData);
    const initialServiceFee = {{ $project->service_fee }};

    // ============================================================
    // OBJEK PENILAIAN — dynamic add/remove rows (identik dengan create.blade.php)
    // ============================================================
    let objectCounter = 0;

    function addObjectRow(prefill = null) {
        const template = document.getElementById('object_row_template');
        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('.object-row');
        const index = objectCounter++;

        row.dataset.index = index;
        row.querySelector('.object-category').name = `objects[${index}][asset_category]`;
        row.querySelector('.object-custom-category').name = `objects[${index}][custom_category]`;
        row.querySelector('.object-land-area').name = `objects[${index}][land_area]`;
        row.querySelector('.object-building-area').name = `objects[${index}][building_area]`;
        row.querySelector('.object-unit-quantity').name = `objects[${index}][unit_quantity]`;
        row.querySelector('.object-location').name = `objects[${index}][location]`;
        row.querySelector('.object-ownership-form').name = `objects[${index}][ownership_form]`;
        row.querySelector('.object-owner-name').name = `objects[${index}][owner_name]`;
        row.querySelector('.object-notes').name = `objects[${index}][notes]`;

        if (prefill) {
            row.querySelector('.object-category').value = prefill.asset_category ?? '';
            row.querySelector('.object-custom-category').value = prefill.custom_category ?? '';
            row.querySelector('.object-land-area').value = prefill.land_area ?? '';
            row.querySelector('.object-building-area').value = prefill.building_area ?? '';
            row.querySelector('.object-unit-quantity').value = prefill.unit_quantity ?? '';
            row.querySelector('.object-location').value = prefill.location ?? '';
            row.querySelector('.object-ownership-form').value = prefill.ownership_form ?? '';
            row.querySelector('.object-owner-name').value = prefill.owner_name ?? '';
            row.querySelector('.object-notes').value = prefill.notes ?? '';
        }

        const categorySelect = row.querySelector('.object-category');
        categorySelect.addEventListener('change', () => toggleObjectFields(row));
        row.querySelector('.remove-object-btn').addEventListener('click', () => {
            row.remove();
            renumberObjectRows();
        });

        document.getElementById('objects_container').appendChild(row);
        toggleObjectFields(row); // langsung tampilkan field yang relevan kalau prefill sudah ada kategori
        renumberObjectRows();
    }

    function toggleObjectFields(row) {
        const category = row.querySelector('.object-category').value;
        const isReal = category.startsWith('Real Properti');
        const isPersonal = category.startsWith('Personal Properti');
        const isOther = category === 'Lainnya';
        row.querySelectorAll('.real-property-fields').forEach(el => el.classList.toggle('hidden', !isReal));
        row.querySelectorAll('.personal-property-fields').forEach(el => el.classList.toggle('hidden', !isPersonal));
        row.querySelectorAll('.other-category-fields').forEach(el => el.classList.toggle('hidden', !isOther));

        const customInput = row.querySelector('.object-custom-category');
        customInput.required = isOther;
        if (!isOther) customInput.value = '';
    }

    function renumberObjectRows() {
        const rows = document.querySelectorAll('#objects_container .object-row');
        rows.forEach((row, i) => { row.querySelector('.object-row-number').textContent = i + 1; });
        if (rows.length === 0) addObjectRow();
    }

    // ============================================================
    // PRE-FILL saat halaman dimuat
    // ============================================================
    document.addEventListener('DOMContentLoaded', () => {
        setInstructingClient(initialInstructingClient);
        initialIntendedUsers.forEach(u => addIntendedUser(u));

        if (initialObjects.length > 0) {
            initialObjects.forEach(obj => addObjectRow(obj));
        } else {
            addObjectRow();
        }

        // Pre-fill tampilan biaya (diformat ribuan) + rincian
        document.getElementById('service_fee_display').value =
            initialServiceFee ? new Intl.NumberFormat('id-ID').format(initialServiceFee) : '';
        const tcRaw = document.getElementById('transport_cost_raw').value;
        if (tcRaw) {
            document.getElementById('transport_cost_display').value =
                new Intl.NumberFormat('id-ID').format(parseInt(tcRaw, 10));
        }

        toggleLkFields();
        toggleFeeBreakdown();
    });

    // ============================================================
    // STATE & FUNGSI KLIEN (identik dengan create.blade.php)
    // ============================================================
    let instructingClient = null;
    let intendedUsers = [];
    let activeModalTarget = null;

    const searchUrl = "{{ route('clients.search') }}";
    const storeAjaxUrl = "{{ route('clients.storeAjax') }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    function debounce(fn, delay = 300) {
        let timer;
        return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), delay); };
    }

    async function searchClients(keyword) {
        const res = await fetch(`${searchUrl}?q=${encodeURIComponent(keyword)}`, { headers: { 'Accept': 'application/json' } });
        return res.json();
    }

    function renderResults(container, clients, onPick) {
        if (clients.length === 0) {
            container.innerHTML = `<div class="px-3 py-2 text-sm text-gray-400">Tidak ditemukan. Coba "+ Klien Baru".</div>`;
        } else {
            container.innerHTML = clients.map(c => `
                <button type="button" data-id="${c.id}" data-name="${c.client_name.replace(/"/g, '&quot;')}"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-blue-50 border-b border-gray-100 last:border-0">
                    <div class="font-medium text-gray-800">${c.client_name}</div>
                    <div class="text-xs text-gray-400">${c.client_type}</div>
                </button>
            `).join('');
            container.querySelectorAll('button[data-id]').forEach(btn => {
                btn.addEventListener('click', () => {
                    onPick({ id: btn.dataset.id, client_name: btn.dataset.name });
                    container.classList.add('hidden');
                });
            });
        }
        container.classList.remove('hidden');
    }

    const instructingInput = document.getElementById('instructing_client_search');
    const instructingResults = document.getElementById('instructing_client_results');

    instructingInput.addEventListener('input', debounce(async (e) => {
        const keyword = e.target.value.trim();
        if (keyword.length < 2) { instructingResults.classList.add('hidden'); return; }
        renderResults(instructingResults, await searchClients(keyword), setInstructingClient);
    }));

    function setInstructingClient(client) {
        instructingClient = client;
        document.getElementById('instructing_client_id').value = client.id;
        document.getElementById('instructing_client_chip_text').textContent = client.client_name;
        document.getElementById('instructing_client_chip').classList.remove('hidden');
        instructingInput.value = '';
        instructingInput.classList.add('hidden');
    }

    function clearInstructingClient() {
        instructingClient = null;
        document.getElementById('instructing_client_id').value = '';
        document.getElementById('instructing_client_chip').classList.add('hidden');
        instructingInput.classList.remove('hidden');
    }

    const intendedInput = document.getElementById('intended_user_search');
    const intendedResults = document.getElementById('intended_user_results');

    intendedInput.addEventListener('input', debounce(async (e) => {
        const keyword = e.target.value.trim();
        if (keyword.length < 2) { intendedResults.classList.add('hidden'); return; }
        const clients = await searchClients(keyword);
        renderResults(intendedResults, clients.filter(c => !intendedUsers.some(u => u.id == c.id)), addIntendedUser);
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
        document.getElementById('intended_user_chips').innerHTML = intendedUsers.map(u => `
            <span class="inline-flex items-center gap-2 bg-gray-100 border border-gray-200 text-gray-700 text-sm px-3 py-1.5 rounded-full">
                ${u.client_name}
                <button type="button" onclick="removeIntendedUser(${u.id})" class="text-gray-400 hover:text-gray-600">&times;</button>
            </span>
        `).join('');
        document.getElementById('intended_user_hidden_inputs').innerHTML = intendedUsers.map(u =>
            `<input type="hidden" name="intended_user_ids[]" value="${u.id}">`
        ).join('');
    }

    document.addEventListener('click', (e) => {
        if (!instructingInput.contains(e.target) && !instructingResults.contains(e.target)) instructingResults.classList.add('hidden');
        if (!intendedInput.contains(e.target) && !intendedResults.contains(e.target)) intendedResults.classList.add('hidden');
    });

    function openClientModal(target) {
        activeModalTarget = target;
        document.getElementById('clientModalErrors').classList.add('hidden');
        document.getElementById('clientModal').classList.remove('hidden');
        document.getElementById('clientModal').classList.add('flex');
    }
    function closeClientModal() {
        document.getElementById('clientModal').classList.add('hidden');
        document.getElementById('clientModal').classList.remove('flex');
    }

    async function submitNewClient() {
        const payload = {
            client_name: document.getElementById('modal_client_name').value,
            client_type: document.getElementById('modal_client_type').value,
            address: document.getElementById('modal_client_address').value,
        };
        try {
            const response = await fetch(storeAjaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (!data.success) {
                const errBox = document.getElementById('clientModalErrors');
                errBox.innerHTML = Object.values(data.errors).flat().join('<br>');
                errBox.classList.remove('hidden');
                return;
            }
            const client = { id: data.client.id, client_name: data.client.client_name };
            activeModalTarget === 'instructing' ? setInstructingClient(client) : addIntendedUser(client);
            closeClientModal();
        } catch (err) {
            alert('Gagal menyimpan klien. Silakan coba lagi.');
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
        document.getElementById('transport_cost_wrap').classList.toggle('hidden', !isBreakdown);
        document.getElementById('service_fee_label').textContent =
            isBreakdown ? 'Fee Jasa Profesional (Rp)' : 'Nilai Biaya Jasa (Rp)';
        recalcFeeTotal();
    }

    function recalcFeeTotal() {
        // PPN hanya atas Fee; Transport & Akomodasi = penggantian biaya (tanpa PPN).
        const PPN_PCT = +(PPN_RATE * 100).toFixed(2);
        const base = parseInt(digits(document.getElementById('service_fee_raw').value) || '0', 10);  // Fee
        const transportRaw = parseInt(digits(document.getElementById('transport_cost_raw').value) || '0', 10);
        const ppnIncluded = document.getElementById('fee_ppn_included').value === '1';
        const isBreakdown = document.getElementById('fee_breakdown').value === '1';
        const transport = isBreakdown ? transportRaw : 0;

        let feeNet, ppn, total;
        if (ppnIncluded) {
            feeNet = base / (1 + PPN_RATE);
            ppn = base - feeNet;
            total = base + transport;
        } else {
            feeNet = base;
            ppn = base * PPN_RATE;
            total = base + ppn + transport;
        }

        document.getElementById('service_fee_hint').textContent = ppnIncluded
            ? 'Fee sudah termasuk PPN — di rincian Fee tampil net (dikurangi PPN). Transport tanpa PPN.'
            : 'PPN ' + PPN_PCT + '% ditambahkan di atas Fee. Transport tanpa PPN.';

        const preview = document.getElementById('fee_total_preview');
        if (!base && !transport) { preview.textContent = ''; return; }
        preview.textContent = isBreakdown
            ? 'Perkiraan: Fee Rp ' + rupiahFmt(feeNet)
              + (transport ? ' + Transport Rp ' + rupiahFmt(transport) : '')
              + ' + PPN ' + PPN_PCT + '% Rp ' + rupiahFmt(ppn)
              + ' = Total Rp ' + rupiahFmt(total)
            : 'Perkiraan total (ditagihkan): Rp ' + rupiahFmt(total) + '  ·  PPN Rp ' + rupiahFmt(ppn);
    }

    maskMoney('service_fee_display', 'service_fee_raw', recalcFeeTotal);
    maskMoney('transport_cost_display', 'transport_cost_raw', recalcFeeTotal);
    document.getElementById('fee_ppn_included').addEventListener('change', recalcFeeTotal);

    document.getElementById('service_fee_display').closest('form').addEventListener('submit', () => {
        document.getElementById('service_fee_raw').value = digits(document.getElementById('service_fee_display').value);
        document.getElementById('transport_cost_raw').value = digits(document.getElementById('transport_cost_display').value);
    });

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
        const purpose = document.getElementById('proposal_purpose').value;
        document.getElementById('lk_fields').classList.toggle('hidden', purpose !== 'Pelaporan Keuangan');
        document.getElementById('purpose_hint').textContent = purposeHints[purpose] ?? '';
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
