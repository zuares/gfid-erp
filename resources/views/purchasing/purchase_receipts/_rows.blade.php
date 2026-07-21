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
        $dateLabel = $receipt->date ? id_date($receipt->date) . ' ' . $receipt->created_at->format('H:i') : '—';
    @endphp

    <tr class="grn-row" data-href="{{ $showRoute }}">
        <td class="text-muted small mobile-hide mono">{{ $rowNumber }}</td>

        <td>
            <div class="ship-row-main">
                <div style="min-width:0;">
                    <a class="mono text-muted d-block" style="font-size: 0.72rem; text-decoration: none;" href="{{ $showRoute }}">{{ $receipt->code ?? ('GRN#'.$receipt->id) }}</a>
                    <div class="small mono text-nowrap mt-1" style="color:var(--shp-text); font-weight: 500;">{{ $dateLabel }}</div>
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
                <span class="mono">{{ $dateLabel }}</span>
                <span>{{ $warehouseName }}</span>
                @if ($hasReturn)<span class="ret-flag">Retur {{ $returnCount ?: 1 }}x</span>@endif
            </div>
        </td>
        
        <td class="text-end mobile-hide">
            <div class="fw-semibold mono" style="color:#16a34a;">Rp {{ number_format($receipt->grand_total ?? 0, 0, ',', '.') }}</div>
            <div class="muted mono mt-1">
                {{ rtrim(rtrim(number_format($receipt->total_qty ?? 0, 2, ',', '.'), '0'), ',') }} Qty
            </div>
        </td>

        <td class="text-end mobile-hide">
            @if(($receipt->total_reject ?? 0) > 0)
                <span class="mono fw-semibold" style="color:#dc2626;">{{ rtrim(rtrim(number_format($receipt->total_reject, 2, ',', '.'), '0'), ',') }}</span>
            @else
                <span class="text-muted">-</span>
            @endif
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
