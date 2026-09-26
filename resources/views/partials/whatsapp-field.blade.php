{{-- Nomor WhatsApp — untuk di-mention bot notifikasi di grup kantor.
     Diletakkan tepat di bawah Password (2026-09-15, feedback user). Param: $waValue. --}}
<div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor WhatsApp</label>
    <input type="tel" name="whatsapp_number" value="{{ old('whatsapp_number', $waValue ?? null) }}"
           placeholder="Contoh: 081234567890" inputmode="tel"
           class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
    <p class="mt-1 text-xs text-gray-400 dark:text-gray-400">Dipakai bot untuk me-mention di grup WhatsApp kantor.</p>
    @error('whatsapp_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
</div>
