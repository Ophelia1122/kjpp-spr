@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Edit Pengguna</h1>
        <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali</a>
    </div>

    <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-4 bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Password Baru (opsional)</label>
            <input type="password" name="password" minlength="8"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            <p class="mt-1 text-xs text-gray-400">Kosongkan kalau tidak ingin mengubah password.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Role</label>
            <select name="role_id" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- ===================== BIODATA PROFESI ===================== --}}
        <fieldset class="rounded-md border border-gray-200 p-4 space-y-4">
            <legend class="px-1 text-sm font-medium text-gray-700">Biodata Profesi</legend>

            <div>
                <label class="block text-sm font-medium text-gray-700">Jabatan</label>
                <select name="jabatan" id="jabatan_select" onchange="toggleBiodataFields()"
                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">-- Pilih Jabatan --</option>
                    @foreach (\App\Models\User::JABATAN_OPTIONS as $j)
                        <option value="{{ $j }}" @selected(old('jabatan', $user->jabatan) === $j)>{{ $j }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-400">
                    Field biodata di bawah menyesuaikan Jabatan. Boleh dikosongkan; yang kosong memakai data baku kantor saat mengisi proposal.
                </p>
            </div>

            {{-- Pelaksana Inspeksi & Admin --}}
            <div data-bio="mappi" class="hidden">
                <label class="block text-sm font-medium text-gray-700">Nomor MAPPI</label>
                <input type="text" name="mappi_no" value="{{ old('mappi_no', $user->mappi_no) }}"
                       placeholder="Contoh: 13-S-04682"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>

            {{-- Penilai --}}
            <div data-bio="rmk" class="hidden">
                <label class="block text-sm font-medium text-gray-700">Nomor RMK</label>
                <input type="text" name="rmk_no" value="{{ old('rmk_no', $user->rmk_no) }}"
                       placeholder="Contoh: RMK-2017.01230"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>

            {{-- Penanggung Jawab (penandatangan proposal) --}}
            <div data-bio="pj" class="hidden space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status Partner</label>
                    <input type="text" name="partner_status" value="{{ old('partner_status', $user->partner_status) }}"
                           placeholder="Partner / Managing Partner"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    <p class="mt-1 text-xs text-gray-400">Status di perusahaan — tercetak sebagai jabatan pada blok tanda tangan proposal.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nomor Izin Penilai Publik (Menkeu)</label>
                        <input type="text" name="izin_menkeu_no" value="{{ old('izin_menkeu_no', $user->izin_menkeu_no) }}"
                               placeholder="Contoh: P-1.25.00690"
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nomor SK Menkeu</label>
                        <input type="text" name="sk_menkeu_no" value="{{ old('sk_menkeu_no', $user->sk_menkeu_no) }}"
                               placeholder="Contoh: 185/MK/SJ/2025 tanggal 23 April 2025"
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nomor Surat Tanda Terdaftar OJK</label>
                        <input type="text" name="sttd_ojk_no" value="{{ old('sttd_ojk_no', $user->sttd_ojk_no) }}"
                               placeholder="Contoh: KEP-324/KS.13/2026"
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nomor KEP OJK</label>
                        <input type="text" name="ojk_kep_no" value="{{ old('ojk_kep_no', $user->ojk_kep_no) }}"
                               placeholder="Contoh: KEP-324/KS.13/2026 tanggal 22 Mei 2026"
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Klasifikasi</label>
                    <input type="text" name="klasifikasi" value="{{ old('klasifikasi', $user->klasifikasi) }}"
                           placeholder="Klasifikasi Bidang Jasa Properti"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                </div>
            </div>
        </fieldset>

        @if ($user->id !== auth()->id())
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300" @checked(old('is_active', $user->is_active))>
                Akun Aktif (nonaktifkan kalau karyawan resign / cuti panjang)
            </label>
        @else
            <input type="hidden" name="is_active" value="1">
            <p class="text-xs text-gray-400">Anda tidak dapat menonaktifkan akun sendiri.</p>
        @endif

        <div class="pt-2 flex gap-3">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">
                Simpan Perubahan
            </button>
            <a href="{{ route('users.index') }}" class="px-5 py-2 border border-gray-300 rounded-md hover:bg-gray-50 font-medium text-gray-700">
                Batal
            </a>
        </div>
    </form>
</div>

<script>
    // Field biodata yang tampil menyesuaikan Jabatan:
    //   Pelaksana Inspeksi / Admin -> Nomor MAPPI
    //   Penilai                    -> Nomor RMK
    //   Penanggung Jawab           -> Status Partner + nomor izin Penilai Publik
    // Input yang tersembunyi tetap ikut ter-submit sehingga nilainya tidak
    // hilang saat Jabatan diganti.
    function toggleBiodataFields() {
        var j = document.getElementById('jabatan_select').value;
        var groups = {
            mappi: (j === 'Pelaksana Inspeksi' || j === 'Admin'),
            rmk:   (j === 'Penilai'),
            pj:    (j === 'Penanggung Jawab'),
        };
        Object.keys(groups).forEach(function (key) {
            document.querySelectorAll('[data-bio="' + key + '"]').forEach(function (el) {
                el.classList.toggle('hidden', !groups[key]);
            });
        });
    }
    document.addEventListener('DOMContentLoaded', toggleBiodataFields);
</script>
@endsection
