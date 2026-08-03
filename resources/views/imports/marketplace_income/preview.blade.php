{{-- resources/views/imports/marketplace_income/preview.blade.php --}}
@extends('layouts.app')
@section('title','Preview • Marketplace Income')

@php
  $stats = $stats ?? [];
  $sample = $sample ?? [];
  $draftId = (string)($draft_id ?? '');
  $channelValue = (string)($channel ?? '');
  $storeValue = (int)($store_id ?? 0);
  $sourceFile = (string)($source_file ?? '-');
  $pageError = $error ?? session('error');
  $ordersParsed = (int)($stats['orders_parsed'] ?? 0);
  $matched = (int)($stats['orders_matched_shipments'] ?? 0);
  $unmatched = (int)($stats['orders_unmatched_shipments'] ?? max(0, $ordersParsed - $matched));
  $matchRate = $ordersParsed > 0 ? round(($matched / $ordersParsed) * 100, 1) : 0;
  $canCommit = $ordersParsed > 0 && empty($draft_file_missing);

  $money = function ($n) {
      return 'Rp ' . number_format((int) round((float)($n ?? 0)), 0, ',', '.');
  };
@endphp

@push('head')
<style>
  .preview-page {
    max-width: 1440px;
    margin: 0 auto;
    padding: 1rem .9rem 5rem;
    color: var(--preview-ink);
  }

  :root {
    --preview-ink: #0f172a;
    --preview-muted: #64748b;
    --preview-line: rgba(148,163,184,.18);
    --preview-card: #fff;
    --preview-shadow: 0 14px 34px rgba(15,23,42,.06);
  }

  body[data-theme="dark"] {
    --preview-ink: #e2e8f0;
    --preview-muted: #94a3b8;
    --preview-line: rgba(148,163,184,.16);
    --preview-card: rgba(15,23,42,.92);
    --preview-shadow: 0 14px 34px rgba(0,0,0,.24);
  }

  .preview-hero {
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:1rem;
    flex-wrap:wrap;
    overflow:hidden;
    margin-bottom:.9rem;
    padding:1.15rem 1.2rem;
    border:1px solid #e5e7eb;
    border-radius:18px;
    background:#fff;
    box-shadow:0 14px 34px rgba(15,23,42,.06);
  }

  .preview-hero > * { position:relative; z-index:1; }

  .preview-eyebrow {
    display:inline-flex;
    align-items:center;
    gap:.35rem;
    margin-bottom:.35rem;
    color:#475569;
    font-size:.65rem;
    font-weight:900;
    letter-spacing:.1em;
    text-transform:uppercase;
  }

  .preview-title {
    margin:0;
    color:#1f2937;
    font-size:1.35rem;
    font-weight:900;
    letter-spacing:-.04em;
  }

  .preview-sub {
    max-width:52rem;
    margin-top:.3rem;
    color:#64748b;
    font-size:.8rem;
  }

  .preview-badges,
  .preview-actions {
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:.45rem;
  }

  .preview-badges { margin-top:.8rem; }

  .preview-chip {
    display:inline-flex;
    align-items:center;
    gap:.35rem;
    padding:.33rem .62rem;
    border:1px solid #e2e8f0;
    border-radius:999px;
    background:#f8fafc;
    color:#475569;
    font-size:.69rem;
    font-weight:800;
    white-space:nowrap;
  }

  .preview-hero .btn { border-radius:999px; font-weight:800; }
  .preview-hero .btn-outline-light {
    background:#fff;
    border-color:#cbd5e1;
    color:#334155;
  }

  .preview-card {
    margin-bottom:.9rem;
    border:1px solid var(--preview-line);
    border-radius:18px;
    background:var(--preview-card);
    box-shadow:var(--preview-shadow);
  }

  .preview-card-head {
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:.75rem;
    flex-wrap:wrap;
    padding:1rem 1rem .75rem;
  }

  .preview-card-title {
    margin:0;
    color:var(--preview-ink);
    font-size:.9rem;
    font-weight:900;
  }

  .preview-note {
    color:var(--preview-muted);
    font-size:.72rem;
  }

  .preview-kpi-grid {
    display:grid;
    grid-template-columns:repeat(5,minmax(0,1fr));
    gap:.7rem;
    margin-bottom:.9rem;
  }

  .preview-kpi {
    position:relative;
    min-height:128px;
    overflow:hidden;
    padding:.95rem 1rem;
    border:1px solid var(--preview-line);
    border-radius:16px;
    background:var(--preview-card);
    box-shadow:var(--preview-shadow);
  }

  .preview-kpi::before {
    position:absolute;
    top:0;
    left:0;
    right:0;
    height:3px;
    content:'';
    background:var(--preview-kpi-color,#2563eb);
  }

  .preview-kpi-label {
    color:var(--preview-muted);
    font-size:.65rem;
    font-weight:900;
    letter-spacing:.08em;
    text-transform:uppercase;
  }

  .preview-kpi-value {
    margin-top:.45rem;
    color:var(--preview-ink);
    font-size:1.25rem;
    font-weight:950;
    letter-spacing:-.04em;
  }

  .preview-kpi-sub {
    margin-top:.35rem;
    color:var(--preview-muted);
    font-size:.7rem;
    font-weight:750;
  }

  .preview-kpi.rows { --preview-kpi-color:#334155; }
  .preview-kpi.orders { --preview-kpi-color:#2563eb; }
  .preview-kpi.matched { --preview-kpi-color:#16a34a; }
  .preview-kpi.unmatched { --preview-kpi-color:#f59e0b; }
  .preview-kpi.updated { --preview-kpi-color:#8b5cf6; }

  .preview-meta-grid {
    display:grid;
    grid-template-columns:1.3fr 1fr 1fr 1fr;
    gap:.8rem;
    padding:0 1rem 1rem;
  }

  .preview-meta-item {
    min-width:0;
    padding:.72rem .78rem;
    border:1px solid var(--preview-line);
    border-radius:12px;
    background:rgba(148,163,184,.05);
  }

  .preview-meta-label {
    color:var(--preview-muted);
    font-size:.64rem;
    font-weight:900;
    letter-spacing:.07em;
    text-transform:uppercase;
  }

  .preview-meta-value {
    margin-top:.28rem;
    overflow:hidden;
    color:var(--preview-ink);
    font-size:.75rem;
    font-weight:800;
    text-overflow:ellipsis;
    white-space:nowrap;
  }

  .preview-table-wrap {
    overflow-x:auto;
    border-top:1px solid var(--preview-line);
  }

  .preview-table {
    width:100%;
    min-width:820px;
    border-collapse:separate;
    border-spacing:0;
  }

  .preview-table th,
  .preview-table td {
    padding:.78rem .85rem;
    border-bottom:1px solid var(--preview-line);
    vertical-align:top;
  }

  .preview-table th {
    color:var(--preview-muted);
    font-size:.65rem;
    font-weight:900;
    letter-spacing:.07em;
    text-transform:uppercase;
    white-space:nowrap;
  }

  .preview-table td {
    color:var(--preview-ink);
    font-size:.77rem;
  }

  .preview-table tbody tr:hover td { background:rgba(148,163,184,.06); }

  .preview-order {
    color:var(--preview-ink);
    font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;
    font-weight:900;
  }

  .preview-muted { color:var(--preview-muted); }
  .preview-number {
    text-align:right;
    white-space:nowrap;
    font-variant-numeric:tabular-nums;
  }
  .preview-net { color:#16a34a; font-weight:950; }
  .preview-net.negative { color:#dc2626; }

  .preview-mobile-list { display:none; }

  .preview-empty {
    padding:2.5rem 1rem;
    color:var(--preview-muted);
    text-align:center;
  }

  .preview-warning {
    margin:0 1rem 1rem;
    padding:.75rem .85rem;
    border:1px solid rgba(245,158,11,.22);
    border-radius:12px;
    background:rgba(245,158,11,.08);
    color:#b45309;
    font-size:.75rem;
  }

  @media(max-width:1180px) {
    .preview-kpi-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
    .preview-meta-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
  }

  @media(max-width:820px) {
    .preview-page { padding-inline:.65rem; }
    .preview-hero { padding:1rem; }
    .preview-kpi-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .preview-table-wrap { display:none; }
    .preview-mobile-list { display:block; }
  }

  @media(max-width:520px) {
    .preview-kpi-grid,
    .preview-meta-grid { grid-template-columns:1fr; }
    .preview-actions { width:100%; }
    .preview-actions .btn { flex:1 1 auto; }
  }
</style>
@endpush

@section('content')
<div class="preview-page">
  @if($pageError)
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ $pageError }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <section class="preview-hero">
    <div>
      <div class="preview-eyebrow"><i class="bi bi-eye"></i> Import preview</div>
      <h1 class="preview-title">Review Income sebelum Commit</h1>
      <div class="preview-sub">
        Pastikan parser membaca order, nilai payout, dan konteks file sebelum data ditulis ke mp_incomes.
      </div>
      <div class="preview-badges">
        <span class="preview-chip"><i class="bi bi-file-earmark-spreadsheet"></i> {{ $sourceFile }}</span>
        <span class="preview-chip">{{ strtoupper($channelValue ?: '-') }}</span>
        <span class="preview-chip"><i class="bi bi-shop"></i> Store #{{ $storeValue ?: '-' }}</span>
        <span class="preview-chip"><i class="bi bi-shield-check"></i> Dry run</span>
      </div>
    </div>

    <div class="preview-actions">
      <a class="btn btn-sm btn-outline-light px-3"
         href="{{ route('imports.marketplace_income.create', $draftId !== '' ? ['draft_id' => $draftId] : []) }}">
        <i class="bi bi-arrow-left"></i> Kembali
      </a>

      <form method="POST" action="{{ route('imports.marketplace_income.cancel') }}"
            onsubmit="return confirm('Batalkan draft dan hapus file upload?')">
        @csrf
        <input type="hidden" name="draft_id" value="{{ $draftId }}">
        <button class="btn btn-sm btn-outline-danger px-3" type="submit">
          <i class="bi bi-trash3"></i> Batal
        </button>
      </form>

      <form method="POST" action="{{ route('imports.marketplace_income.commit') }}"
            onsubmit="return confirm('Commit import income? Data akan ditulis ke database.')">
        @csrf
        <input type="hidden" name="draft_id" value="{{ $draftId }}">
        <button class="btn btn-sm btn-primary px-3" type="submit" @disabled(!$canCommit)>
          <i class="bi bi-check2-circle"></i> Commit
        </button>
      </form>
    </div>
  </section>

  <section class="preview-kpi-grid">
    <div class="preview-kpi rows">
      <div class="preview-kpi-label">Rows parsed</div>
      <div class="preview-kpi-value">{{ number_format((int)($stats['rows_parsed'] ?? 0)) }}</div>
      <div class="preview-kpi-sub">Baris income yang dinormalisasi</div>
    </div>
    <div class="preview-kpi orders">
      <div class="preview-kpi-label">Orders parsed</div>
      <div class="preview-kpi-value">{{ number_format($ordersParsed) }}</div>
      <div class="preview-kpi-sub">Order unik berdasarkan platform ID</div>
    </div>
    <div class="preview-kpi matched">
      <div class="preview-kpi-label">Matched shipments</div>
      <div class="preview-kpi-value">{{ number_format($matched) }}</div>
      <div class="preview-kpi-sub">{{ $matchRate }}% dapat langsung di-apply</div>
    </div>
    <div class="preview-kpi unmatched">
      <div class="preview-kpi-label">Unmatched</div>
      <div class="preview-kpi-value">{{ number_format($unmatched) }}</div>
      <div class="preview-kpi-sub">Akan dicocokkan saat shipment tersedia</div>
    </div>
    <div class="preview-kpi updated">
      <div class="preview-kpi-label">Shipments updated</div>
      <div class="preview-kpi-value">{{ number_format((int)($stats['shipments_updated'] ?? 0)) }}</div>
      <div class="preview-kpi-sub">Perkiraan update pada commit</div>
    </div>
  </section>

  <section class="preview-card">
    <div class="preview-card-head">
      <div>
        <h2 class="preview-card-title"><i class="bi bi-clipboard-data"></i> Metadata import</h2>
        <div class="preview-note">Audit sumber dan hasil parser sebelum commit.</div>
      </div>
      <span class="badge rounded-pill text-bg-success">DRY RUN</span>
    </div>

    <div class="preview-meta-grid">
      <div class="preview-meta-item">
        <div class="preview-meta-label">Draft</div>
        <div class="preview-meta-value font-monospace" title="{{ $draftId }}">{{ $draftId ?: '-' }}</div>
      </div>
      <div class="preview-meta-item">
        <div class="preview-meta-label">Batch</div>
        <div class="preview-meta-value font-monospace" title="{{ $stats['batch'] ?? '-' }}">{{ $stats['batch'] ?? '-' }}</div>
      </div>
      <div class="preview-meta-item">
        <div class="preview-meta-label">Sheet</div>
        <div class="preview-meta-value">{{ $stats['sheet_name'] ?? '—' }} · header {{ $stats['header_row'] ?? '—' }}</div>
      </div>
      <div class="preview-meta-item">
        <div class="preview-meta-label">Rows skipped</div>
        <div class="preview-meta-value">{{ number_format((int)($stats['rows_skipped'] ?? 0)) }}</div>
      </div>
      <div class="preview-meta-item">
        <div class="preview-meta-label">Stored path</div>
        <div class="preview-meta-value font-monospace" title="{{ $stored_path ?? '-' }}">{{ $stored_path ?? '-' }}</div>
      </div>
      <div class="preview-meta-item">
        <div class="preview-meta-label">File</div>
        <div class="preview-meta-value" title="{{ $sourceFile }}">{{ $sourceFile }}</div>
      </div>
      <div class="preview-meta-item">
        <div class="preview-meta-label">Channel / Store</div>
        <div class="preview-meta-value">{{ strtoupper($channelValue ?: '-') }} · #{{ $storeValue ?: '-' }}</div>
      </div>
      <div class="preview-meta-item">
        <div class="preview-meta-label">Parser status</div>
        <div class="preview-meta-value">{{ !empty($stats['error']) ? 'Perlu upload ulang' : ($ordersParsed > 0 ? 'Siap direview' : 'Tidak ada order valid') }}</div>
      </div>
    </div>

    @if(!empty($stats['warnings']) || !empty($stats['error']))
      <div class="preview-warning">
        <strong><i class="bi bi-exclamation-triangle"></i> Perhatian:</strong>
        @if(!empty($stats['error'])) {{ $stats['error'] }} @endif
        @if(!empty($stats['warnings'])) {{ implode(' ', array_map('strval', (array)$stats['warnings'])) }} @endif
      </div>
    @elseif($unmatched > 0)
      <div class="preview-warning">
        <strong><i class="bi bi-info-circle"></i> Shipment belum match:</strong>
        {{ number_format($unmatched) }} income tetap bisa di-commit. Nilai akan otomatis diterapkan ketika shipment dengan order ID yang sama tersedia.
      </div>
    @endif
  </section>

  <section class="preview-card">
    <div class="preview-card-head">
      <div>
        <h2 class="preview-card-title"><i class="bi bi-list-columns-reverse"></i> Sample income per order</h2>
        <div class="preview-note">Menampilkan maksimal 5 order pertama dari hasil parser.</div>
      </div>
      <span class="preview-note">{{ count($sample) }} / 5 order</span>
    </div>

    <div class="preview-table-wrap">
      <table class="preview-table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Released</th>
            <th class="text-end">Fee</th>
            <th class="text-end">Refund</th>
            <th class="text-end">Net payout</th>
          </tr>
        </thead>
        <tbody>
        @forelse($sample as $row)
          @php
            $rowNet = (float)($row['net_payout_actual'] ?? 0);
            $rowFee = (float)($row['platform_fee_total'] ?? 0);
            $rowRefund = (float)($row['refund_total'] ?? 0);
          @endphp
          <tr>
            <td>
              <div class="preview-order">{{ $row['platform_order_id'] ?? '-' }}</div>
              <div class="preview-muted small">{{ data_get($row, 'hint.transaction_types.0', 'Income') }}</div>
            </td>
            <td>{{ $row['released_at'] ?? '-' }}</td>
            <td class="preview-number">{{ $money($rowFee) }}</td>
            <td class="preview-number {{ $rowRefund != 0 ? 'text-warning fw-bold' : 'preview-muted' }}">{{ $money($rowRefund) }}</td>
            <td class="preview-number {{ $rowNet < 0 ? 'preview-net negative' : 'preview-net' }}">{{ $money($rowNet) }}</td>
          </tr>
        @empty
          <tr><td colspan="5" class="preview-empty">Tidak ada sample income yang valid.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="preview-mobile-list">
      @forelse($sample as $row)
        @php
          $rowNet = (float)($row['net_payout_actual'] ?? 0);
          $rowFee = (float)($row['platform_fee_total'] ?? 0);
          $rowRefund = (float)($row['refund_total'] ?? 0);
        @endphp
        <div style="padding:1rem;border-bottom:1px solid var(--preview-line);">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div class="min-w-0">
              <div class="preview-order">{{ $row['platform_order_id'] ?? '-' }}</div>
              <div class="preview-muted small mt-1">{{ $row['released_at'] ?? '-' }}</div>
              <div class="preview-muted small mt-1">{{ data_get($row, 'hint.transaction_types.0', 'Income') }}</div>
            </div>
            <div class="text-end">
              <div class="preview-net {{ $rowNet < 0 ? 'negative' : '' }}">{{ $money($rowNet) }}</div>
              <div class="preview-muted small">Fee {{ $money($rowFee) }}</div>
              <div class="preview-muted small">Refund {{ $money($rowRefund) }}</div>
            </div>
          </div>
        </div>
      @empty
        <div class="preview-empty">Tidak ada sample income yang valid.</div>
      @endforelse
    </div>
  </section>
</div>
@endsection
