@php
    /** Var: title, rows (array), link (url|null) */
    $statusMap = [
        'PROCESSED' => ['Diproses', 'blue'],
        'READY_TO_SHIP' => ['Siap kirim', 'amber'],
        'TO_CONFIRM_RECEIVE' => ['Konfirmasi', 'green'],
        'SHIPPED' => ['Dikirim', 'green'],
        'COMPLETED' => ['Selesai', 'green'],
    ];
@endphp
<div class="dpanel">
    <div class="dpanel-head">
        <div class="t"><i class="bi bi-arrow-repeat"></i> {{ $title }}</div>
        @if(!empty($link))<a href="{{ $link }}">Lihat semua <i class="bi bi-arrow-right-short"></i></a>@endif
    </div>
    <div class="dpanel-body">
        @if(!empty($rows))
            <div class="drepeat-table-wrap">
                <table class="drepeat-table">
                    <thead>
                        <tr>
                            <th class="numeric">No</th>
                            <th>Username</th>
                            <th>Order hari ini</th>
                            <th>No pesanan terakhir beli</th>
                            <th>SKU marketplace</th>
                            <th class="numeric">Qty</th>
                            <th>Terakhir beli</th>
                            <th class="numeric">Order sebelumnya</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            @php($status = $statusMap[$row['order_status'] ?? ''] ?? [$row['order_status'] ?? '—', 'slate'])
                            <tr>
                                <td class="numeric ditem-number">{{ $loop->iteration }}</td>
                                <td class="drepeat-buyer">{{ $row['buyer_label'] ?: 'Tanpa username' }}</td>
                                <td class="drepeat-order">{{ $row['order_number'] ?: '—' }}</td>
                                <td class="drepeat-order">{{ $row['last_order_number'] ?: '—' }}</td>
                                <td class="drepeat-items">
                                    @forelse($row['items'] ?? [] as $item)
                                        <div class="drepeat-item-line"><code>{{ $item['sku'] ?: '—' }}</code></div>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td class="numeric drepeat-items">
                                    @forelse($row['items'] ?? [] as $item)
                                        <div class="drepeat-item-line">{{ number_format((float) ($item['qty'] ?? 0), 0, ',', '.') }}</div>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td class="drepeat-date">
                                    {{ !empty($row['last_purchased_at']) ? \Carbon\Carbon::parse($row['last_purchased_at'])->translatedFormat('d M Y') : '—' }}
                                </td>
                                <td class="numeric">{{ number_format((int) ($row['previous_order_count'] ?? 0), 0, ',', '.') }}</td>
                                <td><span class="pill {{ $status[1] }}">{{ $status[0] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="dash-empty"><i class="bi bi-arrow-repeat"></i> Belum ada repeat order.</div>
        @endif
    </div>
</div>
