<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Verifikasi Penerimaan Barang</title>
    <style>
        @page { margin: 20px 22px 26px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; }
        .document-header { border-bottom: 2px solid #1e3a5f; padding-bottom: 10px; margin-bottom: 10px; }
        .header-grid { width: 100%; }
        .header-grid td { vertical-align: top; }
        .brand-cell { width: 50%; }
        .brand-mark { display: inline-block; background: #1e3a5f; color: #fff; font-size: 16px; font-weight: 700; letter-spacing: 1px; padding: 6px 8px; border-radius: 4px; }
        .brand-name { color: #1e3a5f; font-size: 9px; font-weight: 700; margin-top: 4px; }
        .brand-caption { color: #64748b; font-size: 7px; margin-top: 2px; }
        .document-heading { width: 50%; text-align: right; }
        .eyebrow { color: #64748b; font-size: 7px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
        .title { color: #1e3a5f; font-size: 17px; font-weight: 700; line-height: 1.15; margin-top: 3px; }
        .subtitle { color: #64748b; font-size: 8px; line-height: 1.3; margin-top: 4px; }
        .doc-meta { width: 100%; border-collapse: separate; border-spacing: 5px 0; margin: 0 -5px 10px; }
        .doc-meta td { width: 24%; background: #f8fafc; border: 1px solid #dbe4ee; border-radius: 4px; padding: 6px 8px; vertical-align: top; }
        .doc-meta td.document-number { width: 28%; background: #eef4fb; border-color: #b9cbe0; }
        .meta-label { color: #64748b; font-size: 7px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; }
        .meta-value { color: #172033; font-size: 8px; font-weight: 700; line-height: 1.25; margin-top: 3px; }
        .document-number .meta-value { color: #1e3a5f; font-size: 10px; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 4px 0; margin: 0 -4px 10px; }
        .summary td { width: 25%; background: #f1f5f9; border: 1px solid #dbe4ee; border-radius: 4px; padding: 6px 8px; }
        .summary-label { color: #64748b; font-size: 7px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; }
        .summary-value { color: #1e3a5f; font-size: 11px; font-weight: 700; line-height: 1.25; margin-top: 3px; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .data thead { display: table-header-group; }
        .data th { background: #1e3a5f; color: #fff; font-size: 7px; font-weight: 700; padding: 5px 4px; text-align: left; }
        .data td { border-bottom: 1px solid #dbe4ee; padding: 3px 4px; vertical-align: top; word-wrap: break-word; line-height: 1.22; }
        .data tr:nth-child(even) td { background: #f8fafc; }
        .right { text-align: right !important; }
        .center { text-align: center !important; }
        .date-primary { color: #1e3a5f; font-size: 9px; font-weight: 700; white-space: nowrap; }
        .po-secondary { color: #64748b; font-size: 7px; margin-top: 2px; white-space: nowrap; }
        .muted { color: #64748b; font-size: 7px; margin-top: 2px; }
        .strong { font-weight: 700; }
        .nowrap { white-space: nowrap; }
        .verification { page-break-inside: avoid; margin-top: 11px; }
        .verification-title { color: #1e3a5f; font-size: 9px; font-weight: 700; margin-bottom: 4px; }
        .verification-copy { color: #475569; font-size: 8px; line-height: 1.35; }
        .note-line { border-bottom: 1px solid #cbd5e1; height: 16px; margin-top: 7px; }
        .signatures { width: 100%; margin-top: 12px; page-break-inside: avoid; }
        .signatures td { width: 50%; color: #475569; font-size: 8px; padding: 0 14px 0 0; vertical-align: top; }
        .signatures td + td { padding: 0 0 0 14px; }
        .signature-space { height: 34px; border-bottom: 1px solid #94a3b8; margin-top: 3px; }
        .signature-caption { color: #64748b; font-size: 7px; margin-top: 3px; }
        .footer { color: #64748b; border-top: 1px solid #cbd5e1; margin-top: 10px; padding-top: 5px; font-size: 7px; }
        .empty { color: #64748b; text-align: center; padding: 24px; }
    </style>
</head>
<body>
@php
    $rows = collect($rows ?? []);
    $printedAt = now();
    $reportNumber = 'GRN-VER-' . $printedAt->format('Ymd-His');
    $receivedByUnit = $rows->groupBy(fn ($line) => $line->effectiveStockUnit())
        ->map(fn ($items) => $items->sum(fn ($line) => $line->stockQtyReceived()));
    $rejectByUnit = $rows->groupBy(fn ($line) => $line->effectiveStockUnit())
        ->map(fn ($items) => $items->sum(fn ($line) => $line->stockQtyReject()));
    $formatQty = function ($qty, $unit = null) {
        $value = number_format((float) $qty, 2, ',', '.');
        $value = rtrim(rtrim($value, '0'), ',');
        return $value . ($unit ? ' ' . $unit : '');
    };
    $formatUnits = function ($totals) use ($formatQty) {
        return $totals->filter(fn ($qty) => (float) $qty > 0.000001)
            ->map(fn ($qty, $unit) => $formatQty($qty, $unit))->implode(' - ') ?: '0';
    };
    $dayNames = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];
    $formatDate = function ($date) use ($dayNames) {
        if (!$date) {
            return '-';
        }

        return ($dayNames[$date->format('l')] ?? $date->format('l')) . ', ' . $date->format('d/m/Y');
    };
    $formatFilterDate = function ($value) use ($formatDate) {
        if (!$value) {
            return null;
        }

        try {
            return $formatDate(\Carbon\Carbon::parse($value));
        } catch (\Throwable $e) {
            return $value;
        }
    };
    $fromLabel = $formatFilterDate($filters['from_date'] ?? null);
    $toLabel = $formatFilterDate($filters['to_date'] ?? null);
    $period = $fromLabel && $toLabel ? $fromLabel . ' s/d ' . $toLabel : ($fromLabel ?: ($toLabel ?: 'Semua tanggal'));
@endphp

<div class="document-header">
    <table class="header-grid">
        <tr>
            <td class="brand-cell">
                <div class="brand-mark">GFID</div>
                <div class="brand-name">GFID ERP</div>
                <div class="brand-caption">Dokumen verifikasi penerimaan supplier</div>
            </td>
            <td class="document-heading">
                <div class="eyebrow">Dokumen Verifikasi</div>
                <div class="title">Laporan Verifikasi<br>Penerimaan Barang</div>
                <div class="subtitle">Pencocokan barang diterima dengan purchase order.</div>
            </td>
        </tr>
    </table>
</div>

<table class="doc-meta">
    <tr>
        <td>
            <div class="meta-label">Supplier</div>
            <div class="meta-value">{{ $supplier ? $supplier->code . ' - ' . $supplier->name : 'Semua supplier' }}</div>
        </td>
        <td>
            <div class="meta-label">Gudang penerima</div>
            <div class="meta-value">{{ $warehouse ? $warehouse->code . ' - ' . $warehouse->name : 'Semua gudang' }}</div>
        </td>
        <td>
            <div class="meta-label">Periode penerimaan</div>
            <div class="meta-value">{{ $period }}</div>
        </td>
        <td class="document-number">
            <div class="meta-label">Nomor dokumen</div>
            <div class="meta-value">{{ $reportNumber }}</div>
            <div class="muted">Tanggal cetak: {{ $formatDate($printedAt) }} {{ $printedAt->format('H:i') }}</div>
        </td>
    </tr>
</table>

<table class="summary">
    <tr>
        <td><div class="summary-label">Baris detail</div><div class="summary-value">{{ $rows->count() }}</div></td>
        <td><div class="summary-label">Total diterima</div><div class="summary-value">{{ $formatUnits($receivedByUnit) }}</div></td>
        <td><div class="summary-label">Total reject</div><div class="summary-value">{{ $formatUnits($rejectByUnit) }}</div></td>
        <td><div class="summary-label">Nilai penerimaan</div><div class="summary-value">Rp {{ number_format((float) $rows->sum(fn ($line) => (float) $line->line_total), 0, ',', '.') }}</div></td>
    </tr>
</table>

<table class="data">
    <thead>
        <tr>
            <th style="width: 4%;" class="center">No</th>
            <th style="width: 18%;">Tanggal / PO</th>
            <th style="width: 25%;">Item</th>
            <th style="width: 10%;" class="right">Qty PO</th>
            <th style="width: 14%;" class="right">Diterima</th>
            <th style="width: 11%;" class="right">Reject</th>
            <th style="width: 9%;" class="right">Harga</th>
            <th style="width: 9%;" class="right">Total</th>
        </tr>
    </thead>
    <tbody>
    @forelse ($rows as $line)
        @php
            $receipt = $line->receipt;
            $poLine = $line->purchaseOrderLine;
        @endphp
        <tr>
            <td class="center">{{ $loop->iteration }}</td>
            <td>
                <div class="date-primary">{{ $formatDate($receipt?->date) }}</div>
                <div class="po-secondary">PO: {{ $receipt?->order?->code ?: '-' }}</div>
            </td>
            <td>
                <div class="strong">{{ $line->item?->name ?: '-' }}</div>
                <div class="muted">{{ $line->item?->code ? 'Kode: ' . $line->item->code : '-' }}</div>
            </td>
            <td class="right">{{ $poLine ? $formatQty($poLine->qty, $line->effectivePurchaseUnit()) : '-' }}</td>
            <td class="right">
                <div class="strong">{{ $formatQty($line->stockQtyReceived(), $line->effectiveStockUnit()) }}</div>
                <div class="muted">{{ $formatQty($line->qty_received, $line->effectivePurchaseUnit()) }}</div>
            </td>
            <td class="right">
                <div class="strong">{{ $formatQty($line->stockQtyReject(), $line->effectiveStockUnit()) }}</div>
                <div class="muted">{{ $formatQty($line->qty_reject, $line->effectivePurchaseUnit()) }}</div>
            </td>
            <td class="right nowrap">Rp {{ number_format((float) $line->unit_price, 0, ',', '.') }}</td>
            <td class="right nowrap">Rp {{ number_format((float) $line->line_total, 0, ',', '.') }}</td>
        </tr>
    @empty
        <tr><td colspan="8" class="empty">Tidak ada data penerimaan sesuai filter.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="verification">
    <div class="verification-title">Catatan verifikasi</div>
    <div class="verification-copy">Mohon periksa kesesuaian tanggal, PO, nama item, jumlah diterima, dan reject. Jika terdapat perbedaan, tuliskan pada ruang catatan sebelum dokumen dikonfirmasi.</div>
    <div class="note-line"></div>
</div>

<table class="signatures">
    <tr>
        <td>
            <div class="strong">Pihak GFID</div>
            <div class="signature-space"></div>
            <div class="signature-caption">Nama / tanda tangan / tanggal</div>
        </td>
        <td>
            <div class="strong">Pihak supplier</div>
            <div class="signature-space"></div>
            <div class="signature-caption">Nama / tanda tangan / tanggal</div>
        </td>
    </tr>
</table>

<div class="footer">Dokumen ini dibuat untuk kebutuhan verifikasi penerimaan barang dan bukan merupakan invoice atau tagihan. Maksimal 2.000 baris detail per download.</div>
</body>
</html>
