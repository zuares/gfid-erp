{{-- resources/views/inventory/barcodes/print.blade.php --}}
@extends('layouts.print')

@section('title', 'Cetak Barcode • ' . $total . ' label')

@push('head')
<style>
    body { background: #e5e7eb; margin: 0; padding-top: 48px; }

    .no-print {
        position: fixed;
        top: 0; left: 0; right: 0;
        z-index: 999;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .5rem 1rem;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        box-sizing: border-box;
    }

    /* Satu sheet 100x150mm */
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

    /* Satu label — tinggi 14.9mm x 9 baris = pas 150mm */
    .label {
        display: flex;
        flex-direction: column;
        align-items: center;
        box-sizing: border-box;
        height: 14.9mm;
        border: 0.3mm solid #555;
    }

    .label-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        padding: 0.3mm 1mm 0;
        width: 100%;
    }

    .label-barcode { width: 100%; }
    .label-barcode svg { width: 100%; height: 7mm; display: block; }

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
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    .label-name {
        font-size: 5px;
        font-family: Arial, Helvetica, sans-serif;
        color: #6b7280;
        line-height: 1.15;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    /* Print — thermal 100mm, page break tiap 150mm */
    @media print {
        @page { size: 100mm 150mm; margin: 3mm 4mm; }

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
        .sheet:last-child { page-break-after: avoid; break-after: avoid; }

        .label {
            break-inside: avoid;
            page-break-inside: avoid;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>
@endpush

@section('content')
<div class="no-print">
    <div>
        <strong style="font-size:.9rem;">Cetak Barcode</strong>
        <span class="text-muted ms-2" style="font-size:.75rem;">{{ $total }} label · {{ $itemCount }} barang · 100mm thermal</span>
    </div>
    <div style="display:flex; gap:.5rem;">
        <a href="{{ ($backUrl ?? route('inventory.barcodes.create')) }}" class="btn btn-sm btn-outline-secondary">← Ubah</a>
        <button onclick="window.print()" class="btn btn-sm btn-dark">
            <i class="bi bi-printer"></i> Cetak
        </button>
    </div>
</div>

@if ($total === 0)
    <div class="sheet" style="padding:8mm; text-align:center; color:#6b7280;">
        Tidak ada label untuk dicetak. <a href="{{ ($backUrl ?? route('inventory.barcodes.create')) }}">Kembali ke form</a>.
    </div>
@endif

@foreach ($pages as $page)
<div class="sheet">
    <div class="label-grid">
        @foreach ($page as $lbl)
        <div class="label">
            <div class="label-body">
                <div class="label-barcode">
                    <svg class="barcode-svg" data-code="{{ $lbl['code'] }}"
                         xmlns="http://www.w3.org/2000/svg"></svg>
                </div>
                <div class="label-text-row">
                    <span class="label-itemcode">{{ $lbl['code'] }}</span>
                </div>
                @if (!empty($lbl['name']))
                    <span class="label-name">{{ $lbl['name'] }}</span>
                @endif
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
