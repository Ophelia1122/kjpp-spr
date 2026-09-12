{{-- Tombol "Batalkan Project" — di-@include di baris tombol aksi tiap
     status pada "Aksi Tersedia" (bukan blok tersendiri) supaya semua
     tombol yang tampil untuk status yang sama selalu sejajar segaris.
     Pemanggil WAJIB sudah membungkus dengan @can('proposals.manage'). --}}
<form action="{{ route('proposals.cancel', $project) }}" method="POST"
      onsubmit="return confirm('Batalkan proyek {{ $project->proposal_number }}? Data TIDAK dihapus — status menjadi Batal dan bisa diaktifkan kembali kapan saja.')">
    @csrf
    <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border border-rose-300 text-rose-600 hover:bg-rose-50 dark:border-rose-700 dark:text-rose-400 dark:hover:bg-rose-900/30">
        ⛔ Batalkan Project
    </button>
</form>
