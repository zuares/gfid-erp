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
        $dateLabel = $receipt->date ? id_date($receipt->date) : '—';
    @endphp

    <tr class="grn-row" data-href="{{ $showRoute }}">
        <td class="text-muted small mobile-hide mono">{{ $rowNumber }}</td>

        <td class="small mobile-hide mono text-nowrap">{{ $dateLabel }}</td>

        <td>
            <div class="ship-row-main">
                <div style="min-width:0;">
                    <a class="code-link mono" href="{{ $showRoute }}">{{ $receipt->code ?? ('GRN#'.$receipt->id) }}</a>
                    <div class="muted mt-1">
                        @if ($hasReturn)
                            <span class="ret-flag">Retur {{ $returnCount ?: 1 }}x</span>
                        @else
                            {{ $statusLabel }}
                        @endif
                    </div>
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

        <td class="mobile-hide">
            <span class="badge-status {{ $statusClass }}">{{ $statusLabel }}</span>
        </td>

        <td class="text-end ship-row-action">
            <a href="{{ $actionRoute }}" class="btn btn-sm btn-ship-outline btn-pill">{{ $actionLabel }}</a>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="empty">Belum ada Goods Receipt.</td>
    </tr>
@endforelse
