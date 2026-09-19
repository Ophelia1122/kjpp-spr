{{-- Foto profil bulat; tanpa foto tampil inisial nama (2026-09-15).
     Param: $avatarUser (User), $avatarClass (ukuran & warna latar inisial). --}}
@if ($avatarUser->avatar_url)
    <img src="{{ $avatarUser->avatar_url }}" alt="Foto {{ $avatarUser->name }}"
         class="{{ $avatarClass }} shrink-0 rounded-full object-cover">
@else
    <span class="{{ $avatarClass }} grid shrink-0 place-items-center rounded-full font-semibold text-white uppercase">{{ $avatarUser->initials }}</span>
@endif
