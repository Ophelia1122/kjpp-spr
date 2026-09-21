{{-- Pilihan tanda tangan proposal (2026-09-21, feedback user).
     Barcode tanda tangan: default Ya untuk proposal baru; bila Tidak, blok
     tanda tangan tidak menyisakan ruang kosong. Stempel kantor (gambar tetap
     public/images/stempel-spr.png) menimpa barcode. Dipakai create & edit. --}}
@php
    $sigProject = $project ?? null;
    $useBarcode = (bool) old('use_signature_barcode', $sigProject ? $sigProject->use_signature_barcode : true);
    $useStamp   = (bool) old('use_stamp', $sigProject ? $sigProject->use_stamp : false);
    $hasBarcode = $sigProject && $sigProject->signature_barcode;
    $repLimited = (bool) old('representative_limited', $sigProject ? $sigProject->representative_limited : false);
@endphp
<div class="md:col-span-2 rounded-md border border-gray-200 p-4 space-y-4 dark:border-gray-700">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Barcode Tanda Tangan</p>
            <div class="mt-2 flex gap-4 text-sm">
                <label class="inline-flex items-center gap-2"><input type="radio" name="use_signature_barcode" value="1" @checked($useBarcode) onchange="toggleSignatureBarcode()"> Pakai barcode</label>
                <label class="inline-flex items-center gap-2"><input type="radio" name="use_signature_barcode" value="0" @checked(! $useBarcode) onchange="toggleSignatureBarcode()"> Tidak</label>
            </div>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Stempel Kantor
                @include('partials.icon-info', ['tip' => 'Stempel resmi kantor dibubuhkan di atas barcode tanda tangan pada blok "Hormat kami".'])
            </p>
            <div class="mt-2 flex gap-4 text-sm">
                <label class="inline-flex items-center gap-2"><input type="radio" name="use_stamp" value="1" @checked($useStamp)> Pakai stempel</label>
                <label class="inline-flex items-center gap-2"><input type="radio" name="use_stamp" value="0" @checked(! $useStamp)> Tidak</label>
            </div>
        </div>
    </div>

    <div id="signature_barcode_field" @class(['hidden' => ! $useBarcode])>
        <label for="signature_barcode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">File Barcode</label>
        <input type="file" id="signature_barcode" name="signature_barcode" accept="image/png,image/jpeg"
               class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 dark:text-gray-300 dark:file:bg-blue-900/40 dark:file:text-blue-300">
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            PNG/JPG, tepat 370&times;370 piksel, maksimal 100 KB.
            @if ($hasBarcode) Kosongkan bila tidak ingin mengganti barcode yang sudah ada. @endif
        </p>
        @if ($hasBarcode)
            <img src="{{ Storage::disk('public')->url($sigProject->signature_barcode) }}" alt="Barcode tanda tangan saat ini"
                 class="mt-2 h-20 w-20 rounded border border-gray-200 bg-white object-contain dark:border-gray-700">
        @endif
        @error('signature_barcode')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>
    <div class="border-t border-gray-100 pt-4 dark:border-gray-700">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
            Surat Representasi: inspeksi terbatas?
            @include('partials.icon-info', ['tip' => 'Ya = Surat Representasi memuat poin kewenangan inspeksi terbatas (luas bangunan boleh mengacu PBB/IMB, dokumentasi dari luar).'])
        </p>
        <div class="mt-2 flex gap-4 text-sm">
            <label class="inline-flex items-center gap-2"><input type="radio" name="representative_limited" value="1" @checked($repLimited)> Ya, terbatas</label>
            <label class="inline-flex items-center gap-2"><input type="radio" name="representative_limited" value="0" @checked(! $repLimited)> Tidak</label>
        </div>
    </div>
</div>

<script>
    function toggleSignatureBarcode() {
        const on = document.querySelector('input[name="use_signature_barcode"]:checked')?.value === '1';
        document.getElementById('signature_barcode_field').classList.toggle('hidden', ! on);
    }
</script>
