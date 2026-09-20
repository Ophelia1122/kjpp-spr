@extends('layouts.app')

@section('title', 'Bot WhatsApp')

@section('content')
{{-- Header selebar halaman seperti menu lain (2026-09-20, feedback user);
     isi form tetap sempit supaya enak dibaca. --}}
<div class="max-w-7xl mx-auto py-8 space-y-6">
    <x-page-header title="Bot WhatsApp" subtitle='Mengirim notifikasi ke grup WhatsApp kantor saat proyek <b>diajukan review</b> (mention Reviewer) dan saat
        <b>dikembalikan ke Surveyor</b> (mention Surveyor). Nomor yang di-mention diambil dari Nomor WhatsApp di data pengguna.'>
        @if ($configured)
            <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300">Aktif</span>
        @else
            <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Nonaktif</span>
        @endif
    </x-page-header>


    <form action="{{ route('settings.whatsapp.update') }}" method="POST" class="mx-auto max-w-lg space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        @csrf
        @method('PUT')

        <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="enabled" value="1" class="mt-0.5 rounded border-gray-300"
                   @checked(old('enabled', filter_var($settings['enabled'], FILTER_VALIDATE_BOOLEAN)))>
            <span class="font-medium">Aktifkan notifikasi WhatsApp</span>
        </label>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL Evolution API</label>
            <input type="url" name="base_url" autocomplete="off"
                   value="{{ old('base_url', $settings['base_url']) }}"
                   placeholder="Contoh: http://kjpp-wa:8080"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm font-mono dark:border-gray-600">
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Di NAS: <code>http://kjpp-wa:8080</code>. Di laptop: <code>http://localhost:8081</code>.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Key</label>
            <input type="password" name="api_key" autocomplete="new-password"
                   placeholder="{{ $settings['api_key'] ? '•••••••• (tersimpan — kosongkan bila tidak diubah)' : 'AUTHENTICATION_API_KEY dari .env bot' }}"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Instance</label>
            <input type="text" name="instance" autocomplete="off"
                   value="{{ old('instance', $settings['instance']) }}"
                   placeholder="kjpp"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Nama instance yang dibuat & di-scan QR-nya di dashboard Evolution API.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Grup Tujuan</label>
            <div class="mt-1 flex flex-wrap gap-2">
                <input type="text" name="group_jid" id="wa_group_jid" autocomplete="off"
                       value="{{ old('group_jid', $settings['group_jid']) }}"
                       placeholder="1203630xxxxxxxxx@g.us"
                       class="min-w-0 flex-1 rounded-md border-gray-300 shadow-sm font-mono text-sm dark:border-gray-600">
                <button type="button" id="wa_fetch_groups"
                        class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">
                    Ambil daftar grup
                </button>
            </div>
            <select id="wa_group_select" style="display:none"
                    class="mt-2 w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600"></select>
            <p id="wa_group_msg" class="mt-1 text-xs text-gray-400 dark:text-gray-500">Simpan URL, API key & instance dulu, lalu ambil daftar grup yang diikuti nomor bot.</p>
            @error('group_jid') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="pt-2 flex flex-wrap gap-3">
            <button type="submit" class="inline-flex h-[38px] items-center justify-center gap-1.5 rounded-md border border-blue-600 bg-blue-600 px-4 text-sm font-medium text-white transition hover:border-blue-700 hover:bg-blue-700">Simpan</button>
            <button type="submit" form="wa_test_form" @disabled(! $configured)
                    class="px-5 py-2 border border-gray-300 rounded-md hover:bg-gray-50 font-medium text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-600 dark:hover:bg-gray-700/60 dark:text-gray-300">
                Kirim Pesan Tes
            </button>
        </div>
    </form>

    <form id="wa_test_form" action="{{ route('settings.whatsapp.test') }}" method="POST">@csrf</form>
</div>

<script>
document.getElementById('wa_fetch_groups').addEventListener('click', async function () {
    const msg = document.getElementById('wa_group_msg');
    const select = document.getElementById('wa_group_select');
    const input = document.getElementById('wa_group_jid');
    msg.textContent = 'Mengambil daftar grup…';
    try {
        const res = await fetch(@json(route('settings.whatsapp.groups')), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Gagal mengambil daftar grup.');
        if (!data.groups.length) { msg.textContent = 'Nomor bot belum bergabung di grup mana pun.'; return; }
        select.innerHTML = '<option value="">-- Pilih grup --</option>' + data.groups.map(g =>
            `<option value="${g.id}" ${g.id === input.value ? 'selected' : ''}>${g.subject.replace(/</g, '&lt;')}</option>`).join('');
        select.style.display = '';
        msg.textContent = data.groups.length + ' grup ditemukan. Pilih grup, lalu klik Simpan.';
    } catch (e) {
        msg.textContent = e.message;
    }
});
document.getElementById('wa_group_select').addEventListener('change', function () {
    if (this.value) document.getElementById('wa_group_jid').value = this.value;
});
</script>
@endsection
