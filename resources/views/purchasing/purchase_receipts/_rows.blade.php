@php
    use Illuminate\Support\Carbon;

    $startIndex = $startIndex ?? (method_exists($receipts, 'firstItem') ? $receipts->firstItem() : 1);
@endphp

@forelse ($receipts as $receipt)
    @php
        $date = $receipt->date ? Carbon::parse($receipt->date)->format('d-m-Y') : '-';

        $statusClass = match ($receipt->status) {
            'draft' => 'status-badge status-badge-draft',
            'approved' => 'status-badge status-badge-approved',
            'closed' => 'status-badge status-badge-closed',
            default => 'status-badge status-badge-draft',
        };

        $rowNumber = $startIndex + $loop->index;
    @endphp

    <tr class="index-table-row" data-href="{{ route('purchasing.purchase_receipts.show', $receipt->id) }}">
        {{-- NO --}}
        <td class="mono col-number">
            {{ $rowNumber }}
        </td>

        {{-- TANGGAL --}}
        <td class="mono">
            {{ $date }}
        </td>

        {{-- KODE (soft badge) --}}
        <td class="mono">
            <span class="index-code-badge">
                {{ $receipt->code }}
            </span>
        </td>

        {{-- SUPPLIER --}}
        <td>
            <div class="fw-semibold">
                {{ optional($receipt->supplier)->name ?? '—' }}
            </div>
            <div class="index-row-subtext mono">
                {{ optional($receipt->supplier)->code ?? '-' }}
            </div>
        </td>

        {{-- GUDANG --}}
        <td>
            <div class="fw-semibold">
                {{ optional($receipt->warehouse)->name ?? '—' }}
            </div>
            <div class="index-row-subtext mono">
                {{ optional($receipt->warehouse)->code ?? '-' }}
            </div>
        </td>

        {{-- STATUS --}}
        <td>
            <span class="{{ $statusClass }}">
                {{ ucfirst($receipt->status) }}
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
