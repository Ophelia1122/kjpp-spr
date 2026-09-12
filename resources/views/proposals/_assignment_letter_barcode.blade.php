{{-- Barcode Surat Tugas — TERSIMPAN OTOMATIS begitu file dipilih (AJAX,
     tanpa reload) dan bisa dihapus lewat tombol di bawah. Partial isi saja
     (wrapper #assignmentLetterBarcodeContainer ada di proposals/show.blade.php
     supaya event listener AJAX tidak lepas tiap kali kontennya diganti). --}}
@if ($project->assignment_letter_barcode)
    <div class="mt-1 flex items-center gap-3">
        <img src="{{ \Illuminate\Support\Facades\Storage::url($project->assignment_letter_barcode) }}" alt="Barcode"
             class="h-16 w-16 border border-gray-200 rounded object-contain dark:border-gray-600">
        <form action="{{ route('projects.assignmentLetterBarcode.destroy', $project) }}" method="POST"
              class="assignment-letter-barcode-delete-form"
              onsubmit="return confirm('Hapus barcode ini?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs text-red-500 hover:text-red-700">🗑️ Hapus Barcode</button>
        </form>
    </div>
@else
    <input type="file" name="assignment_letter_barcode" id="assignmentLetterBarcodeInput" accept="image/png"
           class="mt-1 w-full text-sm text-gray-700 dark:text-gray-300
                  file:mr-3 file:px-3 file:py-1.5 file:rounded-md file:border-0 file:text-sm file:font-medium
                  file:bg-gray-100 file:text-gray-700 file:hover:bg-gray-200
                  dark:file:bg-gray-700 dark:file:text-gray-200 dark:file:hover:bg-gray-600">
    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">PNG, maksimal 5 KB, dimensi tepat 370x370 piksel. Tersimpan otomatis begitu dipilih.</p>
    <p class="assignment-letter-barcode-error mt-1 text-xs text-red-500" hidden></p>
@endif
