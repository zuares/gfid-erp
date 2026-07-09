@php
    /** Var: title, rows (array), link (url|null), showAmount (bool, default true) */
    $showAmount = $showAmount ?? true;
    $statusMap = [
        'PROCESSED' => ['Diproses', 'blue'],
        'READY_TO_SHIP' => ['Siap kirim', 'amber'],
        'TO_CONFIRM_RECEIVE' => ['Konfirmasi', 'green'],
        'SHIPPED' => ['Dikirim', 'green'],
    ];
@endphp
<div class="dpanel">
    <div class="dpanel-head">
        <div class="t"><i class="bi bi-hourglass-split"></i> {{ $title }}</div>
        @if(!empty($link))<a href="{{ $link }}">Lihat semua <i class="bi bi-arrow-right-short"></i></a>@endif
    </div>
    <div class="dpanel-body">
        @forelse($rows as $row)
            @php
                $st = $statusMap[$row['order_status'] ?? ''] ?? [($row['order_status'] ?? '-'), 'slate'];
                $inv = $row['external_invoice_no'] ?: ($row['external_order_id'] ?? '-');
            @endphp
            <div class="drow">
                <div class="main">
                    <div class="name">{{ $row['buyer_name'] ?: 'Tanpa nama' }}</div>
                    <div class="meta">
                        {{ $inv }}
                        @if(!empty($row['shipping_city'])) · {{ $row['shipping_city'] }}@endif
                        @if(!empty($row['shipping_courier_code'])) · {{ $row['shipping_courier_code'] }}@endif
                    </div>
                </div>
                <div style="text-align:right;">
                    @if($showAmount)
                        <div class="val">{{ rupiah($row['amount'] ?? 0) }}</div>
                    @endif
                    <span class="pill {{ $st[1] }}">{{ $st[0] }}</span>
                </div>
            </div>
        @empty
            <div class="dash-empty"><i class="bi bi-check2-circle"></i> Tidak ada pesanan yang menunggu. Aman!</div>
        @endforelse
    </div>
</div>
