@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Buat Proposal Penawaran Baru</h1>
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali ke Dashboard</a>
    </div>

    <form action="{{ route('proposals.store') }}" method="POST" class="space-y-6 bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        @csrf

        {{-- ===================== PEMBERI TUGAS (AJAX COMBOBOX) ===================== --}}
        <div class="relative">
            <label class="block text-sm font-medium text-gray-700">Nama Klien (Pemberi Tugas)</label>
            <div class="flex gap-2 mt-1">
                <div class="relative flex-1">
                    <input type="text" id="instructing_client_search" autocomplete="off"
                           placeholder="Ketik nama klien untuk mencari..."
                           class="w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    {{-- Dropdown hasil pencarian --}}
                    <div id="instructing_client_results"
                         class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto"></div>
                </div>
                <button type="button" onclick="openClientModal('instructing')"
                        class="px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 whitespace-nowrap">
                    + Klien Baru
                </button>
            </div>
            {{-- Chip klien terpilih --}}
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
            {{-- Chip multi klien terpilih --}}
            <div id="intended_user_chips" class="flex flex-wrap gap-2 mt-2"></div>
            {{-- Hidden inputs di-generate JS setiap ada perubahan pilihan --}}
            <div id="intended_user_hidden_inputs"></div>
        </div>

        <hr>

        {{-- ===================== JENIS PROPOSAL & LAPORAN ===================== --}}
        <div class="grid grid-cols-2 gap-4">
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
                    <option value="Terinci">Terinci (Comprehensive) — SLA 7 hari kerja</option>
                    <option value="Ringkas">Ringkas (Short Form) — SLA 3 hari kerja</option>
                </select>
            </div>
        </div>

        {{-- ===== Field khusus Pelaporan Keuangan (LK Properti) — disembunyikan default ===== --}}
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

        {{-- ===================== DATA OBJEK PENILAIAN ===================== --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Nama Pemilik Aset</label>
            <input type="text" name="property_owner_name" required
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Jenis Objek Penilaian</label>
                <select name="asset_type" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">-- Pilih Jenis Objek --</option>
                    <option value="Tanah">Tanah</option>
                    <option value="Tanah dan Bangunan">Tanah dan Bangunan</option>
                    <option value="Bangunan">Bangunan</option>
                    <option value="Mesin & Peralatan">Mesin & Peralatan</option>
                    <option value="Kendaraan">Kendaraan</option>
                    <option value="Alat Berat">Alat Berat</option>
                    <option value="Bisnis / Perusahaan">Bisnis / Perusahaan</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Nilai Penawaran / Fee Appraisal (Rp)</label>
                <input type="text" id="service_fee_display" inputmode="numeric" autocomplete="off" required
                       placeholder="Contoh: 10.000.000"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                {{-- Nilai murni angka (tanpa titik) yang sesungguhnya dikirim ke controller --}}
                <input type="hidden" name="service_fee" id="service_fee_raw">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Alamat Objek Penilaian</label>
            <textarea name="asset_address" rows="3" required
                      class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></textarea>
        </div>

        <div class="pt-2">
            <button type="submit" class="px-5 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 font-medium">
                Simpan & Generate Proposal
            </button>
        </div>
    </form>
</div>

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
    // STATE: klien yang sedang dipilih
    // ============================================================
    let instructingClient = null;                 // { id, client_name }
    let intendedUsers = [];                        // [{ id, client_name }, ...]
    let activeModalTarget = null;                   // 'instructing' | 'intended'

    const searchUrl = "{{ route('clients.search') }}";
    const storeAjaxUrl = "{{ route('clients.storeAjax') }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // ============================================================
    // AJAX SEARCH — dipakai untuk kedua combobox (instructing & intended)
    // ============================================================
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

    // ---------- Combobox: Pemberi Tugas (single) ----------
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

    // ---------- Combobox: Pengguna Laporan (multi) ----------
    const intendedInput = document.getElementById('intended_user_search');
    const intendedResults = document.getElementById('intended_user_results');

    intendedInput.addEventListener('input', debounce(async (e) => {
        const keyword = e.target.value.trim();
        if (keyword.length < 2) { intendedResults.classList.add('hidden'); return; }
        const clients = await searchClients(keyword);
        // Sembunyikan yang sudah terpilih supaya tidak dobel
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

    // ---------- Tutup dropdown kalau klik di luar ----------
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
        activeModalTarget = target; // 'instructing' atau 'intended'
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
    // INPUT MASKING — Fee Appraisal (separator ribuan otomatis)
    // Pengguna lihat "10.000.000", tapi yang dikirim ke server
    // tetap angka murni "10000000" lewat input hidden.
    // ============================================================
    (function () {
        const displayInput = document.getElementById('service_fee_display');
        const rawInput = document.getElementById('service_fee_raw');

        function formatRupiah(value) {
            const digitsOnly = value.replace(/\D/g, ''); // buang semua kecuali angka
            if (!digitsOnly) return '';
            return new Intl.NumberFormat('id-ID').format(parseInt(digitsOnly, 10));
        }

        displayInput.addEventListener('input', (e) => {
            const digitsOnly = e.target.value.replace(/\D/g, '');
            e.target.value = formatRupiah(e.target.value);
            rawInput.value = digitsOnly;
        });

        // Jaga-jaga kalau form di-submit tanpa sempat trigger 'input'
        // (misal browser autofill), sinkronkan sekali lagi sebelum submit.
        displayInput.closest('form').addEventListener('submit', (e) => {
            const digitsOnly = displayInput.value.replace(/\D/g, '');
            rawInput.value = digitsOnly;
            if (!digitsOnly) {
                e.preventDefault();
                displayInput.classList.add('border-red-400');
                displayInput.focus();
            }
        });
    })();

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
