{{-- Fieldset "Biodata Profesi" — dipakai users/edit & profile/show.
     Butuh $bioUser (App\Models\User). Field yang tampil menyesuaikan Jabatan. --}}
<fieldset class="rounded-md border border-gray-200 p-4 space-y-4">
    <legend class="px-1 text-sm font-medium text-gray-700">Biodata Profesi</legend>

    <div>
        <label class="block text-sm font-medium text-gray-700">Jabatan</label>
        <select name="jabatan" id="jabatan_select" onchange="toggleBiodataFields()"
                class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            <option value="">-- Pilih Jabatan --</option>
            @foreach (\App\Models\User::JABATAN_OPTIONS as $j)
                <option value="{{ $j }}" @selected(old('jabatan', $bioUser->jabatan) === $j)>{{ $j }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-400">
            Field biodata di bawah menyesuaikan Jabatan. Boleh dikosongkan; yang kosong memakai data baku kantor saat mengisi proposal.
        </p>
    </div>

    {{-- Nomor MAPPI — tampil untuk SEMUA jabatan (tiap penilai/staf punya nomor keanggotaan MAPPI). --}}
    <div>
        <label class="block text-sm font-medium text-gray-700">Nomor MAPPI</label>
        <input type="text" name="mappi_no" value="{{ old('mappi_no', $bioUser->mappi_no) }}"
               placeholder="Contoh: 13-S-04682"
               class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
    </div>

    {{-- Penilai --}}
    <div data-bio="rmk" class="hidden">
        <label class="block text-sm font-medium text-gray-700">Nomor RMK</label>
        <input type="text" name="rmk_no" value="{{ old('rmk_no', $bioUser->rmk_no) }}"
               placeholder="Contoh: RMK-2017.01230"
               class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
    </div>

    {{-- Penanggung Jawab (penandatangan proposal) & Reviewer --}}
    <div data-bio="pj" class="hidden space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Status Partner</label>
            <input type="text" name="partner_status" value="{{ old('partner_status', $bioUser->partner_status) }}"
                   placeholder="Partner / Managing Partner"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            <p class="mt-1 text-xs text-gray-400">Status di perusahaan — tercetak sebagai jabatan pada blok tanda tangan proposal.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Nomor Izin Penilai Publik (Menkeu)</label>
                <input type="text" name="izin_menkeu_no" value="{{ old('izin_menkeu_no', $bioUser->izin_menkeu_no) }}"
                       placeholder="Contoh: P-1.25.00690"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Nomor SK Menkeu</label>
                <input type="text" name="sk_menkeu_no" value="{{ old('sk_menkeu_no', $bioUser->sk_menkeu_no) }}"
                       placeholder="Contoh: 185/MK/SJ/2025 tanggal 23 April 2025"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Nomor Surat Tanda Terdaftar OJK</label>
                <input type="text" name="sttd_ojk_no" value="{{ old('sttd_ojk_no', $bioUser->sttd_ojk_no) }}"
                       placeholder="Contoh: KEP-324/KS.13/2026"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Nomor KEP OJK</label>
                <input type="text" name="ojk_kep_no" value="{{ old('ojk_kep_no', $bioUser->ojk_kep_no) }}"
                       placeholder="Contoh: KEP-324/KS.13/2026 tanggal 22 Mei 2026"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Klasifikasi</label>
            <input type="text" name="klasifikasi" value="{{ old('klasifikasi', $bioUser->klasifikasi) }}"
                   placeholder="Klasifikasi Bidang Jasa Properti"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
        </div>
    </div>
</fieldset>

<script>
    // Nomor MAPPI selalu tampil (semua jabatan). Field lain menyesuaikan Jabatan:
    //   Penilai                     -> Nomor RMK
    //   Penanggung Jawab / Reviewer -> Status Partner + nomor izin Penilai Publik dll.
    // Input tersembunyi tetap ikut ter-submit sehingga nilainya tidak hilang
    // saat Jabatan diganti.
    function toggleBiodataFields() {
        var j = document.getElementById('jabatan_select').value;
        var groups = {
            rmk:   (j === 'Penilai'),
            pj:    (j === 'Penanggung Jawab' || j === 'Reviewer'),
        };
        Object.keys(groups).forEach(function (key) {
            document.querySelectorAll('[data-bio="' + key + '"]').forEach(function (el) {
                el.classList.toggle('hidden', !groups[key]);
            });
        });
    }
    document.addEventListener('DOMContentLoaded', toggleBiodataFields);
</script>
