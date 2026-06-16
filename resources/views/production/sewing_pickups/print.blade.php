{{-- resources/views/production/sewing_pickups/print.blade.php --}}
@extends('layouts.print')

@section('title', 'Cetak Serah Terima Jahit • ' . $pickup->code)

@push('head')
<style>
    .page-wrap {
        max-width: 420px;
        margin-inline: auto;
        padding: 1rem;
    }

    @media (max-width: 480px) {
        .page-wrap { max-width: 100%; padding: .75rem; }
    }

    .no-print {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .slip {
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: .9rem .75rem;
        font-size: 8.5px;
        color: #000;
        font-family: 'Courier New', Courier, monospace;
        line-height: 1.4;
    }

    /* ── HEADER ── */
    .slip-header {
        text-align: center;
        margin-bottom: 4px;
    }

    .slip-title {
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin: 0;
    }

    .slip-meta-inline {
        font-size: 7.5px;
        color: #555;
        margin-top: 2px;
    }

    .divider {
        border: none;
        border-top: 1px dashed #999;
        margin: 4px 0;
    }

    /* ── SECTION ── */
    .section-label {
        font-size: 7px;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #777;
        margin: 5px 0 2px;
    }

    /* ── TABLE ── */
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9px;
    }

    table th {
        font-size: 7.5px;
        text-transform: uppercase;
        letter-spacing: .03em;
        border-bottom: 1px solid #999;
        padding: 1px 2px 3px;
        font-weight: 700;
    }

    table td {
        padding: 3px 2px;
        vertical-align: middle;
        border-bottom: 1px dotted #ddd;
        font-weight: 700;
    }

    .text-end   { text-align: right; }
    .text-center { text-align: center; }

    tfoot td {
        border-top: 1px solid #999;
        border-bottom: none;
        font-weight: 900;
        padding-top: 3px;
        font-size: 9px;
    }

    /* ── TTD ── */
    .ttd-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        margin-top: 8px;
    }

    .ttd-box { text-align: center; }

    .ttd-label {
        font-size: 7px;
        color: #555;
        margin-bottom: 2px;
    }

    .ttd-line {
        border-bottom: 1px solid #000;
        height: 18px;
        margin-bottom: 2px;
    }

    .ttd-name {
        font-size: 7px;
        font-weight: 700;
        word-break: break-word;
    }

    .slip-footer {
        margin-top: 5px;
        font-size: 6.5px;
        color: #aaa;
        text-align: center;
    }

    @media print {
        .no-print { display: none !important; }
        body { background: white !important; margin: 0; padding: 0; }
        .page-wrap { max-width: none; padding: 0; margin: 0; }
        .slip { border: none; border-radius: 0; padding: 0; box-shadow: none; }
    }
</style>
@endpush

@section('content')
<div class="page-wrap">

    <div class="no-print">
        <a href="{{ route('production.sewing.pickups.show', $pickup) }}"
           class="btn btn-sm btn-outline-secondary">← Kembali</a>
        <div class="d-flex align-items-center gap-2">
            <label for="paperWidth" class="mb-0" style="font-size:.8rem;color:#64748b;white-space:nowrap">Lebar kertas</label>
            @php $defaultPaperWidth = session('paper_width', '50mm'); @endphp
            <select id="paperWidth" class="form-select form-select-sm" style="width:90px">
                <option value="50mm" @selected($defaultPaperWidth === '50mm')>50 mm</option>
                <option value="58mm" @selected($defaultPaperWidth === '58mm')>58 mm</option>
                <option value="80mm" @selected($defaultPaperWidth === '80mm')>80 mm</option>
                <option value="100mm" @selected($defaultPaperWidth === '100mm')>100 mm</option>
            </select>
            <button onclick="doPrint()" class="btn btn-sm btn-primary">
                <i class="bi bi-printer me-1"></i> Cetak
            </button>
        </div>
    </div>

    <style id="pageStyle">@media print { @page { size: {{ $defaultPaperWidth }} auto; margin: 2mm 3mm; } }</style>

    <script>
        document.getElementById('paperWidth').addEventListener('change', function () {
            document.getElementById('pageStyle').textContent =
                `@media print { @page { size: ${this.value} auto; margin: 2mm 3mm; } }`;
        });

        function doPrint() { window.print(); }

        @if(session('auto_print'))
        window.addEventListener('load', () => window.print());
        @endif
    </script>

    <div class="slip">

        {{-- Header minimalis --}}
        <div class="slip-header">
            <p class="slip-title">Serah Terima Jahit</p>
            <p class="slip-meta-inline">
                {{ $pickup->code }} · {{ id_date($pickup->date) }} · {{ $pickup->operator?->name ?? '-' }}
            </p>
        </div>

        <hr class="divider">

        {{-- Bundle --}}
        <p class="section-label">Item</p>

        @php $totalQty = 0; @endphp

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kode</th>
                    <th class="text-end">Qty</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pickup->lines as $i => $line)
                    @php
                        $qty      = (float) ($line->qty_bundle ?? 0);
                        $totalQty += $qty;
                        $itemCode = $line->bundle?->finishedItem?->code ?? ($line->finishedItem?->code ?? '-');
                        $isVoid   = ($line->status ?? null) === 'void';
                    @endphp
                    <tr @if($isVoid) style="opacity:.4;text-decoration:line-through" @endif>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $itemCode }}</td>
                        <td class="text-end">{{ number_format($qty, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center" style="color:#aaa;padding:4px 0;font-weight:400">
                            Kosong
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="text-end">Total</td>
                    <td class="text-end">{{ number_format($totalQty, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- Kelengkapan --}}
        @if($pickup->supplyLines->isNotEmpty())
            <hr class="divider">
            <p class="section-label">Kelengkapan</p>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Kode</th>
                        <th class="text-end">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pickup->supplyLines as $j => $sup)
                        @php
                            $pcsQty = (float) ($sup->issued_pcs ?? 0);
                            if ($pcsQty <= 0) $pcsQty = (float) ($sup->required_pcs ?? 0);
                        @endphp
                        <tr>
                            <td>{{ $j + 1 }}</td>
                            <td>{{ $sup->material?->code ?? '-' }}</td>
                            <td class="text-end">{{ number_format($pcsQty, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <hr class="divider">

        {{-- TTD --}}
        <div class="ttd-section">
            <div class="ttd-box">
                <p class="ttd-label">Diserahkan oleh</p>
                <div class="ttd-line"></div>
                <p class="ttd-name">( _____________ )</p>
            </div>
            <div class="ttd-box">
                <p class="ttd-label">Diterima oleh</p>
                <div class="ttd-line"></div>
                <p class="ttd-name">{{ $pickup->operator?->name ?? '( _____________ )' }}</p>
            </div>
        </div>

        <p class="slip-footer">{{ $pickup->code }} · {{ id_datetime(now()) }}</p>

    </div>
</div>
@endsection
