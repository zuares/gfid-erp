@php
    // startIndex bisa dikirim dari controller, kalau tidak ada fallback ke firstItem()
    $startIndex = $startIndex ?? (method_exists($receipts, 'firstItem') ? ($receipts->firstItem() ?? 1) : 1);
@endphp

@forelse ($receipts as $receipt)
    @php
        $rowNumber = $startIndex + $loop->index;

        // RETURN (dari controller: withCount + withSum)
        $returnCount = (int) ($receipt->return_count ?? 0);
        $returnTotal = (float) ($receipt->return_total_sum ?? 0);
        $hasReturn = $returnCount > 0 || $returnTotal > 0;

        // STATUS CLASS (selaras index page)
        $statusClass = match ((string) $receipt->status) {
            'draft' => 'status-badge status-badge-draft',
            'posted' => 'status-badge status-badge-posted',
            'closed' => 'status-badge status-badge-closed',
            default => 'status-badge status-badge-draft',
        };

        // kalau ada return, kasih penanda class tambahan
        $statusClassFinal = $hasReturn ? ($statusClass . ' status-badge-return') : $statusClass;

        $supplierName = optional($receipt->supplier)->name ?? '—';
        $supplierCode = optional($receipt->supplier)->code ?? '-';

        $warehouseName = optional($receipt->warehouse)->name ?? '—';
        $warehouseCode = optional($receipt->warehouse)->code ?? '-';
    @endphp

    <tr class="index-table-row" data-href="{{ route('purchasing.purchase_receipts.show', $receipt->id) }}">
        {{-- NO --}}
        <td class="mono col-number text-center">
            {{ $rowNumber }}
        </td>

        {{-- TANGGAL --}}
        <td class="mono text-nowrap">
            {{ $receipt->date ? id_date($receipt->date) : '-' }}
        </td>

        {{-- KODE --}}
        <td class="mono">
            <span class="index-code-badge">
                {{ $receipt->code ?? ('GRN#'.$receipt->id) }}
            </span>
        </td>

        {{-- SUPPLIER --}}
        <td>
            <div class="fw-semibold">{{ $supplierName }}</div>
            <div class="index-row-subtext mono">{{ $supplierCode }}</div>
        </td>

        {{-- GUDANG --}}
        <td>
            <div class="fw-semibold">{{ $warehouseName }}</div>
            <div class="index-row-subtext mono">{{ $warehouseCode }}</div>
        </td>

        {{-- STATUS (+ RET) --}}
        <td class="text-nowrap">
            <span class="{{ $statusClassFinal }}">
                {{ ucfirst((string) $receipt->status) }}
                @if($hasReturn)
                    <span class="ms-1">• RET</span>
                @endif
            </span>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="text-center index-row-subtext py-3">
            Belum ada data Goods Receipt.
        </td>
    </tr>
@endforelse
