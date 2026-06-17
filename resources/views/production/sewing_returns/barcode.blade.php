{{-- resources/views/production/sewing_returns/barcode.blade.php --}}
@extends('layouts.print')

@section('title', 'Barcode • ' . $return->code)

@push('head')
<style>
    body { background: #e5e7eb; margin: 0; }

    .no-print {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 999;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .5rem 1rem;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        box-sizing: border-box;
    }

    .no-print .btn-group-right {
        display: flex;
        gap: .5rem;
    }

    /* Kompensasi fixed toolbar */
    body { padding-top: 48px; }

    /* Satu sheet 100×150mm */
    .sheet {
        width: 100mm;
        background: #fff;
        margin: 8mm auto;
        padding: 2mm;
        box-sizing: border-box;
        box-shadow: 0 2px 8px rgba(0,0,0,.15);
    }

    /* Grid 3 kolom label kecil */
    .label-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.2mm;
    }

    /* Satu label — tinggi 14.9mm × 9 baris = pas 150mm (margin 3mm) */
    .label {
        display: flex;
        flex-direction: column;
        align-items: center;
        box-sizing: border-box;
        height: 14.9mm;
        border: 0.3mm solid #555;
    }

    /* Body label */
    .label-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        padding: 0.3mm 1mm 0;
        width: 100%;
    }

    .label-barcode {
        width: 100%;
    }

    .label-barcode svg {
        width: 100%;
        height: 7mm;
        display: block;
    }

    /* Baris item code */
    .label-text-row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        margin-top: 0.3mm;
        width: 100%;
    }

    .label-itemcode {
        font-size: 8px;
        font-family: Arial, Helvetica, sans-serif;
        font-weight: 900;
        letter-spacing: 0.8px;
        color: #111827;
        line-height: 1.2;
    }

    .label-bundlecode {
        font-size: 5px;
        font-family: 'Courier New', Courier, monospace;
        color: #9ca3af;
        line-height: 1.2;
        white-space: nowrap;
    }

    .label-sr-code {
        font-size: 4px;
        font-family: 'Courier New', Courier, monospace;
        color: #9ca3af;
        line-height: 1;
        margin-top: 0.3mm;
    }

    /* Print — thermal 100mm, page break tiap 150mm */
    @media print {
        @page {
            size: 100mm 150mm;
            margin: 3mm 4mm;
        }

        body { background: #fff; padding-top: 0 !important; }
        .no-print { display: none !important; }

        .sheet {
            width: 100%;
            margin: 0;
            padding: 0;
            box-shadow: none;
            page-break-after: always;
            break-after: page;
        }

        .sheet:last-child {
            page-break-after: avoid;
            break-after: avoid;
        }

        /* Jangan potong label di tengah */
        .label {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        /* Paksa border & background ikut tercetak */
        .label {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>
@endpush

@section('content')
@php
    $totalLabels = $return->lines->sum(fn($l) => max(1, (int)($l->qty_ok ?? 1)));
    $returnDate  = \Carbon\Carbon::parse($return->date)->format('d/m/y');

    $labels = [];
    foreach ($return->lines as $line) {
        $pl         = $line->sewingPickupLine;
        $bundle     = $pl?->bundle;
        $bundleCode = $bundle?->bundle_code ?? '-';
        $itemCode   = $bundle?->finishedItem?->code ?? $pl?->finishedItem?->code ?? '';
        $qty        = max(1, (int)($line->qty_ok ?? 1));
        for ($i = 0; $i < $qty; $i++) {
            $labels[] = ['itemCode' => $itemCode, 'bundleCode' => $bundleCode];
        }
    }
    $total  = count($labels);
    $perPage = 27; // 9 baris × 3 kolom = ~150mm
    $pages  = array_chunk($labels, $perPage);
@endphp

<div class="no-print">
    <div>
        <strong style="font-size:.9rem;">{{ $return->code }}</strong>
        <span class="text-muted ms-2" style="font-size:.75rem;">{{ $total }} label · 100mm thermal</span>
    </div>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-printer"></i> Cetak
    </button>
</div>

@php $globalIdx = 0; @endphp
@foreach($pages as $page)
<div class="sheet">
    <div class="label-grid">
        @foreach($page as $lbl)
        @php $globalIdx++; @endphp
        <div class="label">
            <div class="label-body">
                <div class="label-barcode">
                    <svg class="barcode-svg"
                         data-code="{{ $lbl['itemCode'] ?: $lbl['bundleCode'] }}"
                         xmlns="http://www.w3.org/2000/svg"></svg>
                </div>
                <div class="label-text-row">
                    <span class="label-itemcode">{{ $lbl['itemCode'] ?: $lbl['bundleCode'] }}</span>
                    <span class="label-itemcode" style="font-weight:400;color:#555;font-size:7px;white-space:nowrap;">{{ $return->code }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endforeach

<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"
        crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.barcode-svg').forEach(function (svg) {
        var code = svg.getAttribute('data-code');
        if (!code || code === '-') return;
        try {
            JsBarcode(svg, code, {
                format: 'CODE128',
                displayValue: false,
                margin: 0,
                width: 1.5,
                height: 30,
                background: '#ffffff',
                lineColor: '#000000',
            });
        } catch (e) {
            console.warn('JsBarcode error:', code, e);
        }
    });
});


</script>
@endsection
