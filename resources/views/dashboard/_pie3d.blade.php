{{-- Pie 3D (SVG murni, tanpa library) — 2026-09-21, feedback user.
     Butuh $slices = [['label' => ..., 'value' => int, 'color' => '#hex'], ...].
     Irisan dimulai dari arah jam 12 searah jarum jam. Dinding samping hanya
     digambar untuk bagian depan (sudut 0..180 derajat di layar). --}}
@php
    $cx = 200; $cy = 120; $rx = 180; $ry = 92; $depth = 32;
    $total = max(1, array_sum(array_column($slices, 'value')));
    $pt = fn ($a, $dy = 0) => sprintf('%.2f %.2f', $cx + $rx * cos($a), $cy + $ry * sin($a) + $dy);
    $shade = function (string $hex, float $f) {
        $hex = ltrim($hex, '#');
        return sprintf('#%02x%02x%02x', ...array_map(fn ($i) => (int) (hexdec(substr($hex, $i, 2)) * $f), [0, 2, 4]));
    };

    $parts = [];
    $a = -M_PI / 2;
    foreach ($slices as $s) {
        if ($s['value'] <= 0) {
            continue;
        }
        $sweep = $s['value'] / $total * 2 * M_PI;
        $parts[] = $s + ['a0' => $a, 'a1' => $a + $sweep];
        $a += $sweep;
    }
    $single = count($parts) === 1;
@endphp

<svg viewBox="0 {{ $cy - $ry - 6 }} 400 {{ 2 * $ry + $depth + 12 }}" class="h-56 w-auto max-w-full" role="img"
     aria-label="{{ collect($parts)->map(fn ($p) => $p['label'] . ': ' . $p['value'])->implode(', ') }}">
    <g stroke="#ffffff" stroke-width="1" stroke-linejoin="round">
        {{-- Dinding samping (bagian depan saja). --}}
        @foreach ($parts as $p)
            @php
                $t0 = max($p['a0'], 0);
                $t1 = min($p['a1'], M_PI);
            @endphp
            @if ($t1 > $t0)
                <path fill="{{ $shade($p['color'], 0.62) }}"
                      d="M {{ $pt($t0) }} A {{ $rx }} {{ $ry }} 0 0 1 {{ $pt($t1) }} L {{ $pt($t1, $depth) }} A {{ $rx }} {{ $ry }} 0 0 0 {{ $pt($t0, $depth) }} Z"/>
            @endif
        @endforeach

        {{-- Permukaan atas. --}}
        @foreach ($parts as $p)
            @if ($single)
                <ellipse cx="{{ $cx }}" cy="{{ $cy }}" rx="{{ $rx }}" ry="{{ $ry }}" fill="{{ $p['color'] }}"/>
            @else
                <path fill="{{ $p['color'] }}"
                      d="M {{ $cx }} {{ $cy }} L {{ $pt($p['a0']) }} A {{ $rx }} {{ $ry }} 0 {{ ($p['a1'] - $p['a0']) > M_PI ? 1 : 0 }} 1 {{ $pt($p['a1']) }} Z">
                    <title>{{ $p['label'] }}: {{ $p['value'] }} proyek</title>
                </path>
            @endif
        @endforeach
    </g>

    {{-- Angka di tengah tiap irisan. --}}
    @foreach ($parts as $p)
        @php
            $mid = ($p['a0'] + $p['a1']) / 2;
            $lx = $single ? $cx : $cx + $rx * 0.62 * cos($mid);
            $ly = $single ? $cy : $cy + $ry * 0.62 * sin($mid);
        @endphp
        <text x="{{ round($lx, 1) }}" y="{{ round($ly + 8, 1) }}" text-anchor="middle"
              class="fill-white text-[24px] font-bold" style="paint-order: stroke; stroke: rgba(0,0,0,.25); stroke-width: 2px;">{{ $p['value'] }}</text>
    @endforeach
</svg>
