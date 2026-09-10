@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Buat Proposal Penawaran Baru</h1>
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali ke Dashboard</a>
    </div>

    <form action="{{ route('proposals.store') }}" method="POST" class="space-y-6 bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        @csrf

        {{-- ===================== NOMOR PROPOSAL (INPUT MANUAL) ===================== --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Nomor Proposal</label>
            <input type="text" name="proposal_number" required value="{{ old('proposal_number') }}"
                   autocomplete="off" spellcheck="false"
                   placeholder="00000/2.0131-00/KJPPSPR-PRO/APP/_/2026"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm font-mono">
            <p class="mt-1 text-xs text-gray-400">
                Diinput manual sesuai nomor resmi yang diterbitkan sistem terintegrasi Kantor Pusat.
            </p>
        </div>

        {{-- ===================== DASAR PERMINTAAN PENILAIAN (MANUAL) ===================== --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Dasar Permintaan Penilaian</label>
            <textarea name="request_basis" rows="2"
                      placeholder="Contoh: yang kami terima melalui Pesan WhatsApp permintaan penilaian tanggal 07 September 2026"
                      class="mt-1 w-full rounded-md border-gray-300 shadow-sm">{{ old('request_basis') }}</textarea>
            <p class="mt-1 text-xs text-gray-400">
                Mengisi bagian kosong pada kalimat pembuka proposal:
                &ldquo;Sesuai dengan informasi permintaan penilaian <span class="italic">[teks ini]</span>, mengenai permohonan jasa Penilai&hellip;&rdquo;.
                Boleh dikosongkan (nanti tampil titik-titik untuk diisi manual di dokumen).
            </p>
        </div>

        {{-- ===================== PENANDATANGAN PROPOSAL ===================== --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Penandatangan Proposal</label>
            <select name="signed_by_user_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                <option value="">-- Penandatangan baku kantor ({{ config('kjpp.signatory.name') }}) --</option>
                @foreach ($signers as $signer)
                    <option value="{{ $signer->id }}" @selected(old('signed_by_user_id') == $signer->id)>{{ $signer->name }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-400">
                Daftar diambil dari pengguna aktif berjabatan <span class="font-medium">Penanggung Jawab</span>.
                Nama &amp; nomor izin (MAPPI, RMK, Izin Menkeu, STTD OJK, Klasifikasi) pada blok tanda tangan
                proposal mengikuti biodata pengguna yang dipilih. Kosongkan untuk memakai penandatangan baku.
            </p>
            @if ($signers->isEmpty())
                <p class="mt-1 text-xs text-amber-600">
                    Belum ada pengguna aktif berjabatan &ldquo;Penanggung Jawab&rdquo;. Lengkapi lewat menu Kelola Pengguna.
                </p>
            @endif
        </div>

        {{-- ===================== PIHAK YANG MENYETUJUI ===================== --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Pihak yang Menyetujui</label>
            <input type="text" name="approver_name" value="{{ old('approver_name') }}"
                   placeholder="Kosongkan = otomatis pakai nama Pemberi Tugas"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            <p class="mt-1 text-xs text-gray-400">
                Nama pihak pada kolom &ldquo;Menyetujui,&rdquo; di blok tanda tangan. Isi bila yang menyetujui
                berbeda dari Pemberi Tugas (mis. bank, sementara Pemberi Tugas-nya PT — atau sebaliknya).
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

        {{-- ===================== JENIS PROPOSAL & LAPORAN ===================== --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Jenis Proposal</label>
                <select name="proposal_purpose" id="proposal_purpose" onchange="toggleLkFields()"
                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    <option value="Jual Beli">Jual Beli</option>
                    <option value="Penjaminan Utang">Penjaminan Utang</option>
                    <option value="Lelang">Lelang</option>
                    <option value="Pelaporan Keuangan">Pelaporan Keuangan (LK Properti)</option>
                </select>
                <p class="mt-1 text-xs text-gray-400" id="purpose_hint"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Jenis Laporan</label>
                <select name="report_style" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    <option value="Long Report" @selected(old('report_style') === 'Long Report')>Long Report — Laporan Terinci (Comprehensive Style)</option>
                    <option value="Short Report" @selected(old('report_style') === 'Short Report')>Short Report — Laporan Ringkas (Short Form)</option>
                </select>
            </div>
        </div>

        {{-- ===================== SLA (HARI KERJA, INPUT MANUAL) ===================== --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">SLA Laporan Draft/Resume (hari kerja)</label>
                <input type="number" name="sla_draft_days" min="1" max="365" required
                       value="{{ old('sla_draft_days') }}" placeholder="Contoh: 3"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                <p class="mt-1 text-xs text-gray-400">Dihitung sejak inspeksi lapangan & penerimaan data terakhir.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">SLA Laporan Final (hari kerja)</label>
                <input type="number" name="sla_final_days" min="1" max="365" required
                       value="{{ old('sla_final_days') }}" placeholder="Contoh: 5"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                <p class="mt-1 text-xs text-gray-400">Dihitung sejak Laporan Draft/Resume disetujui Pemberi Tugas.</p>
            </div>
        </div>

        <div id="lk_fields" class="hidden space-y-4 rounded-md border border-dashed border-blue-300 bg-blue-50 p-4">
            <p class="text-sm font-medium text-blue-800">Detail Khusus Pelaporan Keuangan</p>
            <div>
                <label class="block text-sm font-medium text-gray-700">Klasifikasi PSAK</label>
                <select name="psak_classification[]" multiple class="mt-1 w-full rounded-md border-gray-300 shadow-sm h-24">
                    <option value="Aset Tetap (PSAK 16)">Aset Tetap (PSAK 16)</option>
                    <option value="Properti Investasi (PSAK 13)">Properti Investasi (PSAK 13)</option>
                    <option value="Persediaan (PSAK 14)">Persediaan (PSAK 14)</option>
                    <option value="Aset Tidak Berwujud (PSAK 19)">Aset Tidak Berwujud (PSAK 19)</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Bisa pilih lebih dari satu (Ctrl/Cmd + klik).</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Tanggal Pelaporan Keuangan (Cut-off)</label>
                <input type="date" name="financial_reporting_date" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_public_company" value="1" class="rounded border-gray-300">
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
                        <option value="1" @selected(old('fee_ppn_included', '1') == '1')>Sudah termasuk PPN</option>
                        <option value="0" @selected(old('fee_ppn_included') === '0')>Belum termasuk PPN (11%)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Format Biaya</label>
                    <select name="fee_breakdown" id="fee_breakdown" onchange="toggleFeeBreakdown()" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="0" @selected(old('fee_breakdown', '0') == '0')>All-in (langsung)</option>
                        <option value="1" @selected(old('fee_breakdown') === '1')>Rincian (breakdown)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700" id="service_fee_label">Nilai Dasar Biaya (Rp)</label>
                <input type="text" id="service_fee_display" inputmode="numeric" autocomplete="off" required
                       placeholder="Contoh: 10.000.000"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                <input type="hidden" name="service_fee" id="service_fee_raw">
                <p class="mt-1 text-xs text-gray-400" id="service_fee_hint"></p>
            </div>

            <div id="transport_cost_wrap" class="hidden">
                <label class="block text-sm font-medium text-gray-700">Biaya Transport &amp; Akomodasi (Rp)</label>
                <input type="text" id="transport_cost_display" inputmode="numeric" autocomplete="off"
                       placeholder="Contoh: 6.000.000"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                <input type="hidden" name="transport_cost" id="transport_cost_raw">
            </div>

            <p class="text-sm text-gray-600" id="fee_total_preview"></p>
        </div>

        <hr>

        {{-- ===================== OBJEK PENILAIAN (DINAMIS, BISA LEBIH DARI 1) ===================== --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700">
                    Identifikasi Objek Penilaian dan Kepemilikan
                </label>
                <button type="button" onclick="addObjectRow()"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                    + Tambah Objek
                </button>
            </div>
            <p class="text-xs text-gray-400 mb-3">
                Setiap objek wajib diisi lokasi, bentuk/jenis hak, dan atas nama secara manual —
                sesuai format tabel "Identifikasi Obyek Penilaian" pada dokumen resmi KJPP.
            </p>

            <div id="objects_container" class="space-y-4"></div>
        </div>

        <div class="pt-2">
            <button type="submit" class="px-5 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 font-medium">
                Simpan & Generate Proposal
            </button>
        </div>
    </form>
</div>

{{-- =============== TEMPLATE 1 BARIS OBJEK PENILAIAN (di-clone via JS) =============== --}}
<template id="object_row_template">
    <div class="object-row border border-gray-200 rounded-md p-4 bg-gray-50 relative">
        <div class="flex items-center justify-between mb-3">
            <span class="object-row-number text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-full w-6 h-6 flex items-center justify-center">1</span>
            <button type="button" class="remove-object-btn text-red-500 hover:text-red-700 text-sm font-medium">
                🗑 Hapus Objek
            </button>
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

            {{-- Field khusus kategori "Lainnya" --}}
            <div class="other-category-fields hidden col-span-2">
                <label class="block text-xs font-medium text-gray-600">Sebutkan Jenis Aset/Properti</label>
                <input type="text" class="object-custom-category mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm"
                       placeholder="Contoh: Kapal / Pesawat / Hak Sewa / Tanaman Keras">
            </div>

            {{-- Field khusus Real Properti --}}
            <div class="real-property-fields hidden">
                <label class="block text-xs font-medium text-gray-600">Luas Tanah (m²)</label>
                <input type="number" step="0.01" min="0" class="object-land-area mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>
            <div class="real-property-fields hidden">
                <label class="block text-xs font-medium text-gray-600">Luas Bangunan (m²)</label>
                <input type="number" step="0.01" min="0" class="object-building-area mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>

            {{-- Field khusus Personal Properti --}}
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
                <input type="text" class="object-ownership-form mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm" required
                       placeholder="Contoh: Tunggal - SHGB No. 11948">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600">Atas Nama</label>
                <input type="text" class="object-owner-name mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm" required
                       placeholder="Contoh: PT. Kembang Griya Cahaya">
            </div>

            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600">Catatan Tambahan (opsional)</label>
                <input type="text" class="object-notes mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm"
                       placeholder="Contoh: Rumah Tinggal 2 Lantai / sesuai list yang diterima">
            </div>
        </div>
    </div>
</template>

{{-- =============== MODAL POPUP KLIEN BARU (Tailwind) =============== --}}
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
            <button type="button" onclick="closeClientModal()"
                    class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50">Batal</button>
            <button type="button" onclick="submitNewClient()"
                    class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Simpan</button>
        </div>
    </div>
</div>

<script>
    // ============================================================
    // OBJEK PENILAIAN — dynamic add/remove rows
    // ============================================================
    let objectCounter = 0; // index unik, TIDAK di-reset saat hapus baris
                            // (array PHP tetap valid walau index tidak berurutan)

    function addObjectRow() {
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

        // Toggle field Real Properti vs Personal Properti sesuai kategori
        const categorySelect = row.querySelector('.object-category');
        categorySelect.addEventListener('change', () => toggleObjectFields(row));

        // Tombol hapus baris ini
        row.querySelector('.remove-object-btn').addEventListener('click', () => {
            row.remove();
            renumberObjectRows();
        });

        document.getElementById('objects_container').appendChild(row);
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

    // Mulai dengan 1 baris kosong saat halaman dimuat
    document.addEventListener('DOMContentLoaded', addObjectRow);

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
        const clients = await searchClients(keyword);
        renderResults(instructingResults, clients, setInstructingClient);
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
            <span class="inline-flex items-center gap-2 bg-gray-100 border border-gray-200 text-gray-700 text-sm px-3 py-1.5 rounded-full">
                ${u.client_name}
                <button type="button" onclick="removeIntendedUser(${u.id})" class="text-gray-400 hover:text-gray-600">&times;</button>
            </span>
        `).join('');

        hiddenBox.innerHTML = intendedUsers.map(u =>
            `<input type="hidden" name="intended_user_ids[]" value="${u.id}">`
        ).join('');
    }

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

            const client = { id: data.client.id, client_name: data.client.client_name };

            if (activeModalTarget === 'instructing') {
                setInstructingClient(client);
            } else {
                addIntendedUser(client);
            }

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
        document.getElementById('transport_cost_wrap').classList.toggle('hidden', !isBreakdown);
        document.getElementById('service_fee_label').textContent =
            isBreakdown ? 'Fee Jasa Profesional (Rp) — tanpa transport & PPN' : 'Nilai Dasar Biaya (Rp)';
        recalcFeeTotal();
    }

    function recalcFeeTotal() {
        const PPN_PCT = +(PPN_RATE * 100).toFixed(2);
        const base = parseInt(digits(document.getElementById('service_fee_raw').value) || '0', 10);
        const transport = parseInt(digits(document.getElementById('transport_cost_raw').value) || '0', 10);
        const ppnIncluded = document.getElementById('fee_ppn_included').value === '1';
        const isBreakdown = document.getElementById('fee_breakdown').value === '1';

        let net, ppn, total;
        if (isBreakdown) {
            // Rincian: base = Fee saja. Total = (Fee + Transport) + PPN.
            net = base + transport;
            ppn = net * PPN_RATE;
            total = net + ppn;
        } else if (ppnIncluded) {
            total = base;                              // sudah gross
            net = base / (1 + PPN_RATE);
            ppn = base - net;
        } else {
            net = base;
            ppn = base * PPN_RATE;
            total = base + ppn;
        }

        document.getElementById('service_fee_hint').textContent = isBreakdown
            ? 'Total = Fee + Transport + PPN ' + PPN_PCT + '%.'
            : (ppnIncluded
                ? 'Angka ini sudah dianggap termasuk PPN — angka final = angka ini.'
                : 'Angka final = angka ini + PPN ' + PPN_PCT + '%.');

        const preview = document.getElementById('fee_total_preview');
        if (!base && !transport) { preview.textContent = ''; return; }
        preview.textContent = isBreakdown
            ? 'Perkiraan: Fee Rp ' + rupiahFmt(base) + ' + Transport Rp ' + rupiahFmt(transport)
              + ' + PPN ' + PPN_PCT + '% Rp ' + rupiahFmt(ppn) + ' = Total Rp ' + rupiahFmt(total)
            : 'Perkiraan total (ditagihkan): Rp ' + rupiahFmt(total) + '  ·  PPN Rp ' + rupiahFmt(ppn);
    }

    maskMoney('service_fee_display', 'service_fee_raw', recalcFeeTotal);
    maskMoney('transport_cost_display', 'transport_cost_raw', recalcFeeTotal);
    document.getElementById('fee_ppn_included').addEventListener('change', recalcFeeTotal);

    document.getElementById('service_fee_display').closest('form').addEventListener('submit', (e) => {
        const only = digits(document.getElementById('service_fee_display').value);
        document.getElementById('service_fee_raw').value = only;
        document.getElementById('transport_cost_raw').value = digits(document.getElementById('transport_cost_display').value);
        if (!only) {
            e.preventDefault();
            document.getElementById('service_fee_display').classList.add('border-red-400');
            document.getElementById('service_fee_display').focus();
        }
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
        const purpose = document.getElementById('proposal_purpose').value;
        document.getElementById('lk_fields').classList.toggle('hidden', purpose !== 'Pelaporan Keuangan');
        document.getElementById('purpose_hint').textContent = purposeHints[purpose] ?? '';
    }
    document.addEventListener('DOMContentLoaded', toggleLkFields);
</script>
@endsection