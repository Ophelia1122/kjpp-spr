@extends('layouts.app')

@section('title', 'Tambah Pengguna')

@section('content')
<div class="max-w-lg mx-auto py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Tambah Pengguna</h1>
        <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Kembali</a>
    </div>

    <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        @csrf

        {{-- Foto (ikon edit/trash) di samping Nama & Email (2026-09-15). --}}
        <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
            @include('partials.avatar-upload', ['avatarUser' => null])
            <div class="w-full min-w-0 flex-1 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                    <input type="text" name="username" value="{{ old('username') }}" required autocapitalize="none" spellcheck="false"
                           placeholder="contoh: budi.p"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Dipakai untuk login. Huruf kecil, angka, titik, garis bawah, strip. Hanya Administrator yang bisa mengubah.</p>
                    @error('username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                </div>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
            <input type="password" name="password" required minlength="8"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Minimal 8 karakter.</p>
        </div>

        @include('partials.whatsapp-field', ['waValue' => null])

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
            <select name="role_id" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                <option value="">-- Pilih Role --</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- ===================== BIODATA PROFESI ===================== --}}
        <fieldset class="rounded-md border border-gray-200 p-4 space-y-4 dark:border-gray-700">
            <legend class="px-1 text-sm font-medium text-gray-700 dark:text-gray-300">Biodata Profesi</legend>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jabatan</label>
                <select name="jabatan" id="jabatan_select" onchange="toggleBiodataFields()"
                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                    <option value="">-- Pilih Jabatan --</option>
                    @foreach (\App\Models\User::JABATAN_OPTIONS as $j)
                        <option value="{{ $j }}" @selected(old('jabatan') === $j)>{{ $j }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                    Field biodata di bawah menyesuaikan Jabatan. Boleh dikosongkan; yang kosong memakai data baku kantor saat mengisi proposal.
                </p>
            </div>

            {{-- Nomor MAPPI — tampil untuk SEMUA jabatan (tiap penilai/staf punya nomor keanggotaan MAPPI). --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor MAPPI</label>
                <input type="text" name="mappi_no" value="{{ old('mappi_no') }}"
                       placeholder="Contoh: 13-S-04682"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>

            {{-- Penilai, Penanggung Jawab & Reviewer (2026-09-15, feedback user) --}}
            <div data-bio="rmk" class="hidden">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor RMK</label>
                <input type="text" name="rmk_no" value="{{ old('rmk_no') }}"
                       placeholder="Contoh: RMK-2017.01230"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>

            {{-- Penanggung Jawab (penandatangan proposal) & Reviewer --}}
            <div data-bio="pj" class="hidden space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status Partner</label>
                    <input type="text" name="partner_status" value="{{ old('partner_status') }}"
                           placeholder="Partner / Managing Partner"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Status di perusahaan — tercetak sebagai jabatan pada blok tanda tangan proposal.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor Izin Penilai Publik (Menkeu)</label>
                        <input type="text" name="izin_menkeu_no" value="{{ old('izin_menkeu_no') }}"
                               placeholder="Contoh: P-1.25.00690"
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                    </div>
                    {{-- Nomor & tanggal surat saling mengikat — diisi berpasangan
                         (2026-09-15, feedback user). --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor SK Menkeu</label>
                        <input type="text" name="sk_menkeu_no" value="{{ old('sk_menkeu_no') }}"
                               placeholder="Contoh: 185/MK/SJ/2025"
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal SK Menkeu</label>
                        <input type="date" name="sk_menkeu_date" value="{{ old('sk_menkeu_date') }}" lang="id"
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor Surat Tanda Terdaftar OJK</label>
                        <input type="text" name="sttd_ojk_no" value="{{ old('sttd_ojk_no') }}"
                               placeholder="Contoh: KEP-324/KS.13/2026"
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Surat Tanda Terdaftar OJK</label>
                        <input type="date" name="sttd_ojk_date" value="{{ old('sttd_ojk_date') }}" lang="id"
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                    </div>
                    {{-- Isian "KEP OJK" dihapus — nomornya sama dengan STTD OJK
                         di atas (2026-09-15, feedback user). --}}
                </div>
            {{-- Bab "Penjelasan Status Penilai" (2026-09-22): sektor OJK & izin
                 Penilai Pertanahan. Izin pertanahan kosong = poinnya tidak dicetak. --}}
            <div>
                <p class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lingkup Sektor Jasa Keuangan (OJK)</p>
                @php $bioSectors = (array) old('ojk_sectors', []); @endphp
                <div class="mt-1.5 space-y-1.5">
                    @foreach (\App\Models\User::OJK_SECTORS as $sector)
                        <label class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <input type="checkbox" name="ojk_sectors[]" value="{{ $sector }}" class="mt-0.5 rounded border-gray-300" @checked(in_array($sector, $bioSectors, true))>
                            {{ $sector }}
                        </label>
                    @endforeach
                </div>
                <label class="mt-3 block text-xs font-medium text-gray-500 dark:text-gray-400" for="ojk_sectors_other">Sektor lain (di luar daftar di atas), satu per baris</label>
                <textarea id="ojk_sectors_other" name="ojk_sectors_other" rows="2" placeholder="Contoh: Pergadaian"
                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600">{{ old('ojk_sectors_other', '') }}</textarea>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Dicetak berurutan di bab Penjelasan Status Penilai.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor Izin Penilai Pertanahan</label>
                    <input type="text" name="pertanahan_izin_no" value="{{ old('pertanahan_izin_no') }}"
                           placeholder="Kosongkan bila tidak ada"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Izin Pertanahan</label>
                    <input type="date" name="pertanahan_izin_date" lang="id"
                           value="{{ old('pertanahan_izin_date') }}"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                </div>
            </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Klasifikasi</label>
                    <input type="text" name="klasifikasi" value="{{ old('klasifikasi') }}"
                           placeholder="Klasifikasi Bidang Jasa Properti"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                </div>
            </div>
        </fieldset>

        <div class="pt-2 flex gap-3">
            <button type="submit" class="inline-flex h-[38px] items-center justify-center gap-1.5 rounded-md border border-blue-600 bg-blue-600 px-4 text-sm font-medium text-white transition hover:border-blue-700 hover:bg-blue-700">
                Simpan Pengguna
            </button>
            <a href="{{ route('users.index') }}" class="inline-flex h-[38px] items-center justify-center gap-1.5 rounded-md border border-gray-300 px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">
                Batal
            </a>
        </div>
    </form>
</div>

<script>
    // Nomor MAPPI selalu tampil (semua jabatan). Field lain menyesuaikan Jabatan:
    //   Penilai                     -> Nomor RMK
    //   Penanggung Jawab / Reviewer -> Status Partner + nomor izin Penilai Publik dll.
    // Input yang tersembunyi tetap ikut ter-submit sehingga nilainya tidak
    // hilang saat Jabatan diganti.
    function toggleBiodataFields() {
        var j = document.getElementById('jabatan_select').value;
        var groups = {
            rmk:   (j === 'Penilai' || j === 'Penanggung Jawab' || j === 'Reviewer'),
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
@endsection
