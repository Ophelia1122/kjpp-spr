{{-- Partial form rekening bank. $bank null saat create. --}}
@php $bank = $bank ?? null; @endphp

<div>
    <label class="block text-sm font-medium text-gray-700">Nama Bank</label>
    <input type="text" name="bank_name" required autocomplete="off"
           value="{{ old('bank_name', $bank->bank_name ?? '') }}"
           placeholder="Contoh: Bank Mandiri"
           class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
</div>

<div>
    <label class="block text-sm font-medium text-gray-700">Nama Cabang <span class="text-gray-400 font-normal">(opsional)</span></label>
    <input type="text" name="branch" autocomplete="off"
           value="{{ old('branch', $bank->branch ?? '') }}"
           placeholder="Contoh: Wisma Danantara Indonesia"
           class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
</div>

<div>
    <label class="block text-sm font-medium text-gray-700">Nomor Rekening</label>
    <input type="text" name="account_number" required autocomplete="off" inputmode="numeric"
           value="{{ old('account_number', $bank->account_number ?? '') }}"
           placeholder="Contoh: 7301121479"
           class="mt-1 w-full rounded-md border-gray-300 shadow-sm font-mono">
</div>

<div>
    <label class="block text-sm font-medium text-gray-700">Atas Nama</label>
    <input type="text" name="account_name" required autocomplete="off"
           value="{{ old('account_name', $bank->account_name ?? config('kjpp.company_name')) }}"
           class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
</div>

<label class="flex items-start gap-2 text-sm text-gray-700">
    <input type="checkbox" name="is_default" value="1" class="mt-0.5 rounded border-gray-300"
           @checked(old('is_default', $bank->is_default ?? false))>
    <span>
        Jadikan rekening <span class="font-medium">default</span>
        <span class="block text-xs text-gray-400">Dipakai proposal yang tidak memilih rekening. Menyalakan ini otomatis mematikan default rekening lain.</span>
    </span>
</label>
