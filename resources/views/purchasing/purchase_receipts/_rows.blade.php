@php
    $startIndex = $startIndex ?? (method_exists($receipts, 'firstItem') ? ($receipts->firstItem() ?? 1) : 1);
@endphp

@forelse ($receipts as $receipt)
    @php
        $rowNumber = $startIndex + $loop->index;

        $returnCount = (int) ($receipt->return_count ?? 0);
        $returnTotal = (float) ($receipt->return_total_sum ?? 0);
        $hasReturn   = $returnCount > 0 || $returnTotal > 0;

        $status = (string) $receipt->status;
        $statusClass = match ($status) {
            'posted' => 'st-posted',
            'closed' => 'st-closed',
            default  => 'st-draft',
        };
        $statusLabel = ucfirst($status);

        $supplierName = optional($receipt->supplier)->name ?? '—';
        $supplierCode = optional($receipt->supplier)->code ?? '';
        $warehouseName = optional($receipt->warehouse)->name ?? '—';
        $warehouseCode = optional($receipt->warehouse)->code ?? '';

        $isDraft = $status === 'draft';
        $canEdit = $isDraft && !((bool) ($receipt->is_replacement ?? false));
        $actionRoute = $canEdit
            ? route('purchasing.purchase_receipts.edit', $receipt->id)
            : route('purchasing.purchase_receipts.show', $receipt->id);
        $actionLabel = $canEdit ? 'Lanjutkan' : 'Detail';
        $showRoute = route('purchasing.purchase_receipts.show', $receipt->id);
        $ps = (string) ($receipt->order->payment_status ?? 'unpaid');
        $payLabel = match($ps) {
            'paid' => 'Lunas',
            'partial' => 'Sebagian',
            default => 'Belum',
        };
        $payBadgeClass = match($ps) {
            'paid' => 'text-success bg-success bg-opacity-10',
            'partial' => 'text-warning bg-warning bg-opacity-10',
            default => 'text-muted bg-secondary bg-opacity-10',
        };
    @endphp

    <tr class="grn-row" data-href="{{ $showRoute }}">
        <td class="text-muted small mobile-hide mono">{{ $rowNumber }}</td>

        <td>
            <div class="ship-row-main">
                <div style="min-width:0;">
                    <div class="date-primary-ui">{{ $receipt->date ? id_day($receipt->date) : 'Tanggal belum diatur' }}</div>
                    <div class="muted mono mt-1">Jam {{ $receipt->created_at?->format('H:i') ?? '-' }}</div>
                    @if ($receipt->order)
                        <a class="po-secondary-ui mono d-block mt-1" href="{{ route('purchasing.purchase_orders.show', $receipt->order->id) }}">
                            PO {{ $receipt->order->code }}
                        </a>
                    @else
                        <span class="po-secondary-ui muted d-block mt-1">Tanpa PO</span>
                    @endif
                    <a class="grn-secondary-ui mono d-block mt-1" href="{{ $showRoute }}">GRN {{ $receipt->code ?? ('#'.$receipt->id) }}</a>
                    @if ($hasReturn)
                        <div class="muted mt-1">
                            <span class="ret-flag">Retur {{ $returnCount ?: 1 }}x</span>
                        </div>
                    @endif
                </div>
                <span class="badge-status {{ $statusClass }} d-md-none">{{ $statusLabel }}</span>
            </div>
        </td>

        <td>
            <div class="store-name">{{ $supplierName }}</div>
            <div class="muted mono">{{ $supplierCode ? $supplierCode.' · ' : '' }}{{ $warehouseName }}</div>
            <div class="ship-row-meta d-md-none">
                @if ($hasReturn)<span class="ret-flag">Retur {{ $returnCount ?: 1 }}x</span>@endif
            </div>
        </td>
        
        <td class="text-end mobile-hide">
            <div class="fw-semibold mono" style="color:#16a34a;">Rp {{ number_format($receipt->grand_total ?? 0, 0, ',', '.') }}</div>
            <div class="muted mono mt-1">
                {{ rtrim(rtrim(number_format($receipt->total_stock_qty ?? 0, 2, ',', '.'), '0'), ',') }} stok
            </div>
        </td>

        <td class="text-end mobile-hide">
            @if(($receipt->total_stock_reject ?? 0) > 0)
                <div class="mono fw-semibold" style="color:#dc2626;">{{ rtrim(rtrim(number_format($receipt->total_stock_reject, 2, ',', '.'), '0'), ',') }} stok</div>
                <div class="muted mono mt-1" style="color:#ef4444;">Rp {{ number_format($receipt->total_reject_rp ?? 0, 0, ',', '.') }}</div>
            @else
                <span class="text-muted">-</span>
            @endif
        </td>

        <td class="mobile-hide">
            <span class="badge py-1 px-2 {{ $payBadgeClass }}" style="font-weight: 600; font-size: .72rem; border-radius: 6px;">{{ $payLabel }}</span>
        </td>

        <td class="mobile-hide">
            <span class="badge-status {{ $statusClass }}">{{ $statusLabel }}</span>
        </td>

        <td class="text-end ship-row-action mobile-hide">
            <div class="d-inline-flex gap-1 justify-content-end">
                <a href="{{ $actionRoute }}" class="btn btn-sm btn-ship-outline btn-pill">{{ $actionLabel }}</a>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="empty">Belum ada Goods Receipt.</td>
    </tr>
@endforelse
