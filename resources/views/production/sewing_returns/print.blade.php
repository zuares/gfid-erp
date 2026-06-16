{{-- resources/views/production/sewing_returns/print.blade.php --}}
@extends('layouts.print')

@section('title', 'Cetak Setor Jahit • ' . $return->code)

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
        margin-bottom: 1.25rem;
    }

    /* ── SLIP (ukuran asli = 50mm equivalent) ── */
    .slip {
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: .9rem .75rem;
        font-size: 8.5px;
        color: #000;
        font-family: 'Courier New', Courier, monospace;
        line-height: 1.4;
        width: 100%;
        box-sizing: border-box;
    }

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

    .section-label {
        font-size: 7px;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #777;
        margin: 5px 0 2px;
    }

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

    .col-ok { color: #166534; }
    .col-rj { color: #991b1b; }

    tfoot td {
        border-top: 1px solid #999;
        border-bottom: none;
        font-weight: 900;
        padding-top: 3px;
        font-size: 9px;
    }

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
        <a href="{{ route('production.sewing.returns.show', $return) }}"
           class="btn btn-sm btn-outline-secondary">← Kembali</a>
        <div class="d-flex align-items-center gap-2">
            <label for="paperWidth" class="mb-0" style="font-size:.8rem;color:#64748b;white-space:nowrap">Lebar kertas</label>
            <select id="paperWidth" class="form-select form-select-sm" style="width:90px">
                <option value="50mm" selected>50 mm</option>
                <option value="58mm">58 mm</option>
                <option value="80mm">80 mm</option>
                <option value="100mm">100 mm</option>
            </select>
            <button onclick="doPrint()" class="btn btn-sm btn-primary">
                <i class="bi bi-printer me-1"></i> Cetak
            </button>
        </div>
    </div>

    <style id="pageStyle">@media print { @page { size: 50mm auto; margin: 2mm 3mm; } }</style>

    <script>
        document.getElementById('paperWidth').addEventListener('change', function () {
            document.getElementById('pageStyle').textContent =
                `@media print { @page { size: ${this.value} auto; margin: 2mm 3mm; } }`;
        });

        function doPrint() { window.print(); }
    </script>

    <div class="slip">

            {{-- Header minimalis --}}
            <div class="slip-header">
                <p class="slip-title">Setor Hasil Jahit</p>
                <p class="slip-meta-inline">
                    {{ $return->code }} · {{ id_date($return->date) }} · {{ $return->operator?->name ?? '-' }}
                    @if($return->pickup) · {{ $return->pickup?->code }} @endif
                </p>
            </div>

            <hr class="divider">

            {{-- Tabel item --}}
            <p class="section-label">Item</p>

            @php $totalOk = 0; $totalRj = 0; @endphp

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Kode</th>
                        <th class="text-end col-ok">OK</th>
                        <th class="text-end col-rj">RJ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($return->lines as $i => $line)
                        @php
                            $pl       = $line->sewingPickupLine;
                            $qtyOk    = (float) ($line->qty_ok ?? 0);
                            $qtyRj    = (float) ($line->qty_reject ?? 0);
                            $totalOk += $qtyOk;
                            $totalRj += $qtyRj;
                            $itemCode = $pl?->bundle?->finishedItem?->code ?? '-';
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $itemCode }}</td>
                            <td class="text-end col-ok">{{ number_format($qtyOk, 0, ',', '.') }}</td>
                            <td class="text-end col-rj">{{ number_format($qtyRj, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center" style="color:#aaa;padding:4px 0;font-weight:400">
                                Kosong
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end">Total</td>
                        <td class="text-end col-ok">{{ number_format($totalOk, 0, ',', '.') }}</td>
                        <td class="text-end col-rj">{{ number_format($totalRj, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>

            <hr class="divider">

            <div class="ttd-section">
                <div class="ttd-box">
                    <p class="ttd-label">Diserahkan oleh</p>
                    <div class="ttd-line"></div>
                    <p class="ttd-name">{{ $return->operator?->name ?? '( _____________ )' }}</p>
                </div>
                <div class="ttd-box">
                    <p class="ttd-label">Diterima oleh</p>
                    <div class="ttd-line"></div>
                    <p class="ttd-name">( _____________ )</p>
                </div>
            </div>

            <p class="slip-footer">{{ $return->code }} · {{ id_datetime(now()) }}</p>

        </div>
    </div>

</div>
@endsection
