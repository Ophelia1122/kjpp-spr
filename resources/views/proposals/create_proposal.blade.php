@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto py-8">
    <h1 class="text-xl font-semibold mb-6">Buat Proposal Penawaran</h1>

    <form action="{{ route('proposals.store') }}" method="POST" class="space-y-5">
        @csrf

        {{-- Pemberi Tugas (instructing_client) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Pemberi Tugas</label>
            <div class="flex gap-2 mt-1">
                <select name="instructing_client_id" id="instructing_client_id"
                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Pilih Klien --</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->client_name }} ({{ $client->client_type }})</option>
                    @endforeach
                </select>
                <button type="button" onclick="openClientModal('instructing_client_id')"
                        class="px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    + Klien Baru
                </button>
            </div>
        </div>

        {{-- Pengguna Laporan (bisa multi) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Pengguna Laporan (bisa lebih dari satu)</label>
            <div class="flex gap-2 mt-1">
                <select name="intended_user_ids[]" id="intended_user_ids" multiple
                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 h-28">
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->client_name }} ({{ $client->client_type }})</option>
                    @endforeach
                </select>
                <button type="button" onclick="openClientModal('intended_user_ids')"
                        class="px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 h-fit">
                    + Klien Baru
                </button>
            </div>
        </div>

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
                <select name="psak_classification[]" multiple
                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm h-24">
                    <option value="Aset Tetap (PSAK 16)">Aset Tetap (PSAK 16)</option>
                    <option value="Properti Investasi (PSAK 13)">Properti Investasi (PSAK 13)</option>
                    <option value="Persediaan (PSAK 14)">Persediaan (PSAK 14)</option>
                    <option value="Aset Tidak Berwujud (PSAK 19)">Aset Tidak Berwujud (PSAK 19)</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Bisa pilih lebih dari satu (Ctrl/Cmd + klik).</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Tanggal Pelaporan Keuangan (Cut-off)</label>
                <input type="date" name="financial_reporting_date"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                <p class="text-xs text-gray-400 mt-1">Ini yang jadi tanggal penilaian di laporan, bukan tanggal survei.</p>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_public_company" value="1" class="rounded border-gray-300">
                Klien adalah Perusahaan Terbuka (menampilkan klausul POJK 28/POJK.04/2021)
            </label>
        </div>


        <div>
            <label class="block text-sm font-medium text-gray-700">Fee Jasa (Rp)</label>
            <input type="number" name="service_fee" step="0.01"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Nama Pemilik Aset</label>
            <input type="text" name="property_owner_name" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Jenis Aset</label>
            <input type="text" name="asset_type" placeholder="Tanah, Bangunan, Mesin, dll"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Alamat Aset</label>
            <textarea name="asset_address" rows="3" class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></textarea>
        </div>

        <button type="submit" class="px-5 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
            Simpan & Generate Proposal
        </button>
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
    // Menyimpan target <select> mana yang sedang minta klien baru
    // (instructing_client_id atau intended_user_ids)
    let activeTargetSelect = null;

    const purposeHints = {
        'Jual Beli': 'Dasar Nilai: Nilai Pasar.',
        'Penjaminan Utang': 'Dasar Nilai: Nilai Pasar (Indikasi Nilai Likuidasi dapat diminta bank).',
        'Lelang': 'Dasar Nilai: Nilai Pasar & Nilai Likuidasi (wajib), termasuk klausul Waktu Ekspos.',
        'Pelaporan Keuangan': 'Dasar Nilai: Nilai Wajar (Fair Value), mengacu PSAK 113.',
    };

    function toggleLkFields() {
        const purpose = document.getElementById('proposal_purpose').value;
        const lkFields = document.getElementById('lk_fields');
        const hint = document.getElementById('purpose_hint');

        lkFields.classList.toggle('hidden', purpose !== 'Pelaporan Keuangan');
        hint.textContent = purposeHints[purpose] ?? '';
    }

    // Jalankan sekali saat halaman dimuat supaya hint langsung tampil
    document.addEventListener('DOMContentLoaded', toggleLkFields);

    function openClientModal(targetSelectId) {
        activeTargetSelect = targetSelectId;
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
            const response = await fetch("{{ route('clients.storeAjax') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
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

            // Sisipkan <option> baru ke <select> target TANPA reload halaman
            const select = document.getElementById(activeTargetSelect);
            const option = document.createElement('option');
            option.value = data.client.id;
            option.text = `${data.client.client_name} (${data.client.client_type})`;
            option.selected = true;
            select.appendChild(option);

            closeClientModal();
        } catch (err) {
            alert('Gagal menyimpan klien. Silakan coba lagi.');
            console.error(err);
        }
    }
</script>
@endsection
