@php
    /** Var: title, rows (array), link (url|null) */
    $qtyFmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $typeLabel = ['finished_good' => 'Barang jadi', 'material' => 'Bahan baku'];
@endphp
<div class="dpanel">
    <div class="dpanel-head">
        <div class="t"><i class="bi bi-exclamation-triangle"></i> {{ $title }}</div>
        @if(!empty($link))<a href="{{ $link }}">Lihat stok <i class="bi bi-arrow-right-short"></i></a>@endif
    </div>
    <div class="dpanel-body">
        @forelse($rows as $row)
            <div class="drow">
                <div class="main">
                    <div class="name">{{ $row['item_name'] ?? '-' }}</div>
                    <div class="meta">
                        {{ $row['item_code'] ?? '' }}
                        @if(!empty($row['type'])) · {{ $typeLabel[$row['type']] ?? $row['type'] }}@endif
                    </div>
                </div>
                <div class="val {{ ($row['qty'] ?? 0) <= 0 ? 'red' : '' }}">
                    {{ $qtyFmt($row['qty'] ?? 0) }} pcs
                </div>
            </div>
        @empty
            <div class="dash-empty"><i class="bi bi-check2-circle"></i> Semua stok aman. Tidak ada yang habis.</div>
        @endforelse
    </div>
</div>
