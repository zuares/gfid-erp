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
                    @if(!empty($row['items']))
                        <div class="order-products">
                            @foreach($row['items'] as $item)
                                @php
                                    $orderedQty = (float) ($item['qty'] ?? 0);
                                    $stockQty = $item['stock_qty'] ?? null;
                                    $stockShort = $stockQty !== null && $stockQty < $orderedQty;
                                @endphp
                                <div class="order-product">
                                    <div class="order-product-main">
                                        <div class="order-product-name">
                                            {{ $item['name'] ?: 'Produk belum dipetakan' }}
                                            @if(!empty($item['variant'])) · {{ $item['variant'] }} @endif
                                        </div>
                                        <div class="meta">
                                            @if(!empty($item['sku'])){{ $item['sku'] }} · @endif
                                            Pesan {{ number_format($orderedQty, 0, ',', '.') }} pcs
                                        </div>
                                    </div>
                                    <div class="order-stock {{ $stockShort ? 'short' : '' }}">
                                        <span>Stok FG/RTS</span>
                                        <strong>{{ $stockQty === null ? '—' : number_format($stockQty, 0, ',', '.') }}</strong>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="meta order-items-empty">Detail produk belum tersedia</div>
                    @endif
                </div>
                <div class="drow-actions">
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
