{{-- resources/views/purchasing/purchase_returns/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Return Pembelian ' . ($ret->code ?? ''))

@push('head')
<style>
  .pr-show-page{ min-height:100vh; }
  .pr-show-page .page-wrap{ max-width: 1150px; margin-inline:auto; padding: 1rem 1rem 4.5rem; }

  body[data-theme="light"] .pr-show-page .page-wrap{
    background: radial-gradient(circle at top left,
        rgba(59,130,246,.12) 0,
        rgba(45,212,191,.10) 26%,
        #f9fafb 60%);
  }

  .return-hero{
    background: var(--card);
    border:1px solid var(--line);
    border-radius:16px;
    padding:.9rem;
    box-shadow:0 10px 24px rgba(15,23,42,.06);
  }
  .return-hero-main{ display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; }
  .return-title{ font-size:1.22rem; font-weight:900; margin:0; letter-spacing:-.01em; }
  .return-code{ font-weight:900; color:#2563eb; }
  .return-meta-row{ display:flex; gap:.35rem; flex-wrap:wrap; margin-top:.45rem; }
  .return-chip{
    display:inline-flex;
    align-items:center;
    gap:.3rem;
    border:1px solid var(--line);
    background:rgba(148,163,184,.07);
    border-radius:999px;
    padding:.2rem .6rem;
    font-size:.74rem;
    color:var(--muted);
    min-height:26px;
  }
  .return-chip strong{ color:var(--text); }
  .return-actions{ display:flex; gap:.4rem; flex-wrap:wrap; justify-content:flex-end; }
  .card-main{
    background: var(--card);
    border-radius: 10px;
    border: 1px solid rgba(148,163,184,.35);
    box-shadow: 0 10px 30px rgba(15,23,42,.10), 0 0 0 1px rgba(148,163,184,.08);
  }
  .card-soft{
    background: color-mix(in srgb, var(--card) 94%, var(--bg) 6%);
    border-radius: 10px;
    border: 1px solid var(--line);
  }

  .mono{
    font-variant-numeric: tabular-nums;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
  }

  /* header */
  .page-header-title{ font-size: 1.35rem; font-weight: 650; }
  .page-header-subtitle{ font-size: .82rem; color: var(--muted); }
  .pill{
    font-size: .75rem;
    border-radius: 999px;
    padding: .16rem .7rem;
    border: 1px solid rgba(148,163,184,.55);
    background: color-mix(in srgb, var(--card) 80%, var(--bg) 20%);
  }
  .status-badge{
    font-size: .78rem;
    border-radius: 999px;
    padding: .14rem .7rem;
    border: 1px solid var(--line);
    background: rgba(148,163,184,.10);
  }
  .primary-label,.state-pill{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    padding:.12rem .5rem;
    font-size:.68rem;
    font-weight:850;
    border:1px solid transparent;
    white-space:nowrap;
  }
  .primary-label{ color:#1d4ed8; background:rgba(37,99,235,.08); border-color:rgba(37,99,235,.32); }
  .state-pill.is-muted{ color:#64748b; background:rgba(148,163,184,.1); border-color:rgba(148,163,184,.35); }
  .btn-pill{ border-radius: 999px; padding-inline: 1rem; }
  .btn-soft{
    border-radius: 999px;
    padding-inline: 1rem;
    border: 1px solid var(--line);
    background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
  }

  /* section */
  .section-title{
    font-size: .86rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--muted);
  }

  /* table */
  .table-wrap{ overflow-x:auto; }
  .return-table{ --bs-table-bg: transparent; }
  .return-table thead th{
    background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
    border-bottom-color: var(--line);
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    padding:.58rem .65rem;
    white-space: nowrap;
  }
  .return-table tbody td{
    border-bottom-color: var(--line);
    vertical-align: middle;
    padding:.62rem .65rem;
    font-size: .82rem;
  }

  .item-main{ font-weight: 850; line-height:1.15; }
  .item-sub{ font-size: .74rem; color: var(--muted); margin-top:.08rem; }
  .return-metrics{ display:flex; gap:.35rem; flex-wrap:wrap; justify-content:flex-end; }
  .metric-pill{ display:inline-flex; align-items:center; gap:.28rem; border:1px solid var(--line); border-radius:999px; padding:.12rem .48rem; font-size:.7rem; color:var(--muted); background:rgba(148,163,184,.06); }
  .metric-pill strong{ color:var(--text); font-weight:900; }
  .metric-pill.is-blue{ color:#2563eb; border-color:rgba(37,99,235,.25); background:rgba(37,99,235,.06); }
  .metric-pill.is-red{ color:#dc2626; border-color:rgba(220,38,38,.25); background:rgba(220,38,38,.06); }
  .metric-pill.is-green{ color:#15803d; border-color:rgba(22,163,74,.25); background:rgba(22,163,74,.06); }
  .summary-grid{
    display:grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap:.65rem;
  }
  .summary-box{
    border:1px solid var(--line);
    border-radius:8px;
    padding:.7rem .8rem;
    background:rgba(148,163,184,.07);
  }
  .summary-box .lbl{ display:block; font-size:.72rem; color:var(--muted); line-height:1.15; }
  .summary-box .val{ display:block; font-weight:850; line-height:1.25; }
  .summary-box.is-main{
    border-color:rgba(37,99,235,.22);
    background:rgba(37,99,235,.06);
  }
  .summary-box.is-main .val{ color:#2563eb; }
  .summary-box.is-danger{
    border-color:rgba(220,38,38,.25);
    background:rgba(220,38,38,.06);
  }
  .summary-box.is-danger .val{ color:#dc2626; }
  .return-meta{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:.5rem;
  }
  .line-stock-short{ color:#dc2626; font-weight:900; }
  .line-stock-ok{ color:#15803d; font-weight:900; }
  .qty-return-input{ min-height:38px; border-radius:10px; font-weight:900; font-size:.95rem; }
  .return-tools{ display:flex; gap:.35rem; flex-wrap:wrap; align-items:center; }
  .return-tools .btn{ border-radius:999px; font-weight:800; padding:.22rem .65rem; }
  .return-tools-summary{ margin-left:auto; color:var(--muted); font-size:.78rem; font-weight:750; }
  .return-formbar{ display:grid; grid-template-columns:150px minmax(180px, 1fr) auto; gap:.5rem; align-items:end; margin-bottom:.55rem; }
  .return-formbar .form-label{ font-size:.68rem; color:var(--muted); font-weight:850; text-transform:uppercase; letter-spacing:.04em; margin-bottom:.15rem; }
  .return-formbar-summary{ display:flex; justify-content:flex-end; align-items:center; gap:.45rem; color:var(--muted); font-size:.76rem; font-weight:800; padding-bottom:.32rem; }
  .return-dot::before{ content:"•"; opacity:.45; margin-right:.45rem; }
  .return-row.is-empty{ opacity:.74; }
  .return-row.has-qty{ background:rgba(37,99,235,.045); box-shadow:inset 3px 0 0 rgba(37,99,235,.45); }
  .quick-btn{ border-radius:999px; padding:.15rem .5rem; font-size:.7rem; font-weight:800; }
  .row-main-action{ display:flex; gap:.3rem; justify-content:flex-end; flex-wrap:wrap; margin-top:.35rem; }
  .return-input-wrap{ display:flex; flex-direction:column; align-items:flex-end; }
  .return-mobile-head{ display:none; }
  .return-total-live{ color:#2563eb; font-weight:900; }

  @media (max-width: 767.98px){
    html, body{ max-width:100%; overflow-x:hidden; }
    .pr-show-page{ overflow-x:hidden; }
    .pr-show-page .page-wrap{ padding-inline: .85rem; }

    .page-header{
      flex-direction: column;
      align-items: flex-start;
      gap: .45rem;
    }
    .page-header-actions{
      width: 100%;
      display:flex;
      flex-wrap:wrap;
      gap: .35rem;
    }
    .page-header-actions .btn{ flex: 1 1 auto; }
    .return-hero{ padding:.75rem; }
    .return-hero-main{ display:block; }
    .return-title{ font-size:1.08rem; }
    .return-actions{ margin-top:.7rem; justify-content:stretch; }
    .return-actions .btn{ flex:1 1 auto; }
    .return-meta-row{ flex-wrap:nowrap; overflow-x:auto; padding-bottom:.1rem; }
    .return-chip{ flex:0 0 auto; }

    /* mobile table -> card rows */
    .return-table thead{ display:none; }
    .return-table tbody tr{
      display:block;
      border: 1px solid var(--line);
      border-radius: 12px;
      margin-bottom: .6rem;
      padding: .55rem .65rem;
      background: rgba(15,23,42,.02);
    }
    .return-table tbody td{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:.75rem;
      border: 0;
      padding-block: .2rem;
    }
    .return-table tbody td[data-label]::before{
      content: attr(data-label);
      font-size: .75rem;
      color: var(--muted);
      margin-right: .75rem;
      text-align:left;
      flex:0 0 auto;
    }
    .return-table .td-item{ display:block; }
    .return-table .td-item::before{ display:none; }
    .return-metrics{ justify-content:flex-start; }
    .return-input-wrap{ width:100%; align-items:flex-end; }
    .qty-return-input{ max-width:150px; margin-left:auto; }
    .summary-grid,.return-meta{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .return-tools{ width:100%; }
    .return-tools .btn{ flex:1 1 auto; }
    .return-tools-summary{ width:100%; margin-left:0; text-align:center; }
    .return-formbar{ grid-template-columns:1fr 1fr; gap:.45rem; }
    .return-formbar-summary{ grid-column:1 / -1; justify-content:center; padding-bottom:0; }
    .return-mobile-head{ display:block; }
    .row-main-action{ justify-content:stretch; }
    .row-main-action .btn{ flex:1; }
  }
</style>
@endpush

@section('content')
@php
  $canSeeMoney = auth()->user()?->isOwner() ?? false;
  $isDraft = ($ret->status ?? '') === 'draft';
  $isPosted = ($ret->status ?? '') === 'posted';
  $isVoided = (bool) ($ret->voided_at);

  $statusLabel = strtoupper((string)($ret->status ?? '-'));

  $statusClass = 'bg-secondary-subtle text-secondary border border-secondary-subtle';
  if ($isPosted) $statusClass = 'bg-success-subtle text-success border border-success-subtle';
  if ($isDraft)  $statusClass = 'bg-warning-subtle text-warning border border-warning-subtle';
  if ($isVoided) $statusClass = 'bg-danger-subtle text-danger border border-danger-subtle';

  $grand = (float)($ret->total ?? 0);
  $totalLines = (int) (($returnRows ?? collect())->count());
  $totalReturnLines = (int) (($returnRows ?? collect())->filter(fn($row) => (float) $row->qty > 0.0001)->count());
  $totalQty = (float) (($returnRows ?? collect())->sum('qty'));
  $grnHref = route('purchasing.purchase_receipts.show', $ret->purchase_receipt_id ?? $ret->grn_id ?? ($ret->grn?->id ?? 0));
  $dateValue = old('date', $ret->date ? \Illuminate\Support\Carbon::parse($ret->date)->format('Y-m-d') : now()->toDateString());
@endphp

<div class="pr-show-page">
  <div class="page-wrap">

    {{-- HEADER --}}
    <div class="return-hero mb-3">
      <div class="return-hero-main">
        <div class="min-w-0">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <h1 class="return-title">Return Pembelian</h1>
            <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}@if($isVoided) • VOID @endif</span>
          </div>
          <div class="return-meta-row">
            <span class="return-chip">Kode <strong class="mono return-code">{{ $ret->code }}</strong></span>
            <span class="return-chip">Tanggal <strong>{{ $ret->date ? \Illuminate\Support\Carbon::parse($ret->date)->format('d/m/Y') : '-' }}</strong></span>
            <span class="return-chip">GRN <strong class="mono">{{ $ret->grn?->code ?? '-' }}</strong></span>
            <span class="return-chip">Supplier <strong>{{ $ret->grn?->supplier?->name ?? '-' }}</strong></span>
            <span class="return-chip">Gudang <strong>{{ $ret->grn?->warehouse?->code ?? $ret->grn?->warehouse?->name ?? '-' }}</strong></span>
            <span class="return-chip">Item <strong class="mono">{{ $totalReturnLines }}/{{ $totalLines }}</strong></span>
          </div>
        </div>

        <div class="return-actions">
          <a href="{{ $grnHref }}" class="btn btn-soft btn-sm btn-pill">GRN</a>
          <a href="{{ route('purchasing.purchase_returns.index') }}" class="btn btn-outline-secondary btn-sm btn-pill">Daftar</a>
        </div>
      </div>
    </div>

    {{-- ALERTS --}}
    @if(session('success'))
      <div class="alert alert-success py-2 mb-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger py-2 mb-3">{{ session('error') }}</div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger py-2 mb-3">
        <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
        <ul class="mb-0 ps-3">
          @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="card-soft mb-2">
      <div class="p-3">
        <div class="summary-grid">
          <div class="summary-box">
            <span class="lbl">Item</span>
            <span class="val mono">{{ angka($totalReturnLines) }} / {{ angka($totalLines) }}</span>
          </div>
          <div class="summary-box is-main">
            <span class="lbl">Qty Return</span>
            <span class="val mono">{{ decimal_id($totalQty, 2) }}</span>
          </div>
          <div class="summary-box">
            <span class="lbl">Stok</span>
            <span class="val {{ $stockReady ? 'line-stock-ok' : 'line-stock-short' }}">
              {{ $isDraft ? ($stockReady ? 'Siap' : 'Kurang') : ($mutationCount > 0 ? 'Sudah Keluar' : '-') }}
            </span>
          </div>
          <div class="summary-box">
            <span class="lbl">Jurnal</span>
            <span class="val">{{ $journalCount > 0 ? $journalCount . ' jurnal' : 'Belum' }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-3">
      {{-- DETAIL LINES --}}
      <div class="col-12">
        <div class="card-main">
          <div class="p-3 p-sm-3 border-bottom" style="border-color: var(--line) !important;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div class="fw-semibold small text-uppercase">Detail Return</div>
              <div class="small text-muted d-none d-md-block">
                Semua baris dari GRN ditampilkan. Isi qty hanya pada barang yang diretur.
              </div>
            </div>
          </div>

          <div class="p-2 p-sm-3">
            <form method="POST" action="{{ route('purchasing.purchase_returns.update', $ret->id) }}">
              @csrf
              @method('PUT')

              <div class="return-formbar">
                <div>
                  <label class="form-label">Tanggal</label>
                  <input type="text"
                    name="date"
                    class="form-control form-control-sm gf-date-input mono"
                    value="{{ $dateValue }}"
                    data-gf-date autocomplete="off"
                    required
                    {{ (!$isDraft || $isVoided) ? 'disabled' : '' }}>
                </div>
                <div>
                  <label class="form-label">Catatan</label>
                  <input type="text"
                    name="notes"
                    class="form-control form-control-sm"
                    value="{{ old('notes', $ret->notes) }}"
                    placeholder="Opsional"
                    {{ (!$isDraft || $isVoided) ? 'disabled' : '' }}>
                </div>
                <div class="return-formbar-summary">
                  <span>{{ $statusLabel }}@if($isVoided) / VOID @endif</span>
                  @if ($canSeeMoney)
                    <span class="return-dot mono">Rp {{ number_format($grand, 0, ',', '.') }}</span>
                  @endif
                  @if(!$isDraft || $isVoided)
                    <span class="return-dot">Terkunci</span>
                  @endif
                </div>
              </div>

              @if($isDraft && !$isVoided)
                <div class="return-tools mb-2">
                  <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-zero-all">Reset</button>
                  <button type="button" class="btn btn-sm btn-outline-primary" id="btn-focus-first">Isi Return</button>
                  <span class="return-tools-summary">
                    <span class="mono return-total-live" id="live-return-lines">{{ $totalReturnLines }}</span> item
                    ·
                    <span class="mono return-total-live" id="live-return-qty">{{ decimal_id($totalQty, 2) }}</span> qty
                  </span>
                </div>
              @endif

              <div class="table-wrap">
                <table class="table table-sm align-middle mb-0 return-table">
                  <thead>
                    <tr>
                      <th>Item</th>
                      <th class="text-end">Batas</th>
                      <th class="text-end" style="width: 190px;">Return</th>
                      @if ($canSeeMoney)
                        <th class="text-end">Nilai</th>
                      @endif
                    </tr>
                  </thead>
                  <tbody class="small">
                    @foreach($returnRows as $i => $row)
                      @php
                        $ln = $row->line;
                        $grnLine = $row->grnLine;
                        $received = (float)($row->received ?? 0);
                        $rem = (float)($row->remaining ?? 0);
                        $stock = (float)($row->stock ?? 0);
                        $lotStock = $row->lot_stock;
                        $shownStock = $lotStock !== null ? (float) $lotStock : $stock;
                        $maxReturn = (float)($row->max_return ?? $rem);
                        $isInventoryLine = (bool)($row->is_inventory ?? true);

                        $qty = (float)($row->qty ?? 0);
                        $unitPrice = (float)($row->unit_price ?? 0);
                        $lineTotal = (float)($row->line_total ?? 0);
                        $rowClass = $qty > 0.0001 ? 'has-qty' : 'is-empty';
                      @endphp
                      <tr class="return-row {{ $rowClass }}">
                        <td class="td-item" data-label="Item">
                          <div class="return-mobile-head mb-1">
                            @if($qty > 0.0001)
                              <span class="primary-label">Diretur</span>
                            @else
                              <span class="state-pill is-muted">Tidak diretur</span>
                            @endif
                          </div>
                          <div class="item-main">{{ $row->item?->name ?? '-' }}</div>
                          <div class="item-sub mono">
                            {{ $row->item?->code ?? '-' }}
                            @if($row->lot_id) • LOT #{{ $row->lot_id }} @endif
                            <span class="d-none d-md-inline"> • Terima {{ rtrim(rtrim(number_format($received, 4, ',', '.'), '0'), ',') }}</span>
                          </div>

                          @if($isDraft && !$isVoided)
                            <input type="hidden" name="lines[{{ $i }}][id]" value="{{ $ln?->id }}">
                            <input type="hidden" name="lines[{{ $i }}][purchase_receipt_line_id]" value="{{ $row->purchase_receipt_line_id }}">
                            <input type="text"
                              name="lines[{{ $i }}][notes]"
                              class="form-control form-control-sm mt-2"
                              placeholder="Catatan item (opsional)"
                              value="{{ old("lines.$i.notes", $row->notes) }}">
                          @endif
                        </td>

                        <td class="text-end" data-label="Batas">
                          <div class="return-metrics">
                            <span class="metric-pill is-blue">Maks <strong class="mono">{{ rtrim(rtrim(number_format($maxReturn, 4, ',', '.'), '0'), ',') }}</strong></span>
                            @if($isInventoryLine)
                              <span class="metric-pill {{ $shownStock + .0001 >= $qty ? 'is-green' : 'is-red' }}">
                                {{ $lotStock !== null ? 'Stok Lot' : 'Stok' }}
                                <strong class="mono">{{ rtrim(rtrim(number_format($shownStock, 4, ',', '.'), '0'), ',') }}</strong>
                              </span>
                            @else
                              <span class="metric-pill">Non-stok</span>
                            @endif
                            <span class="metric-pill d-md-none">Terima <strong class="mono">{{ rtrim(rtrim(number_format($received, 4, ',', '.'), '0'), ',') }}</strong></span>
                          </div>
                        </td>

                        <td class="text-end" data-label="Return">
                          @if($isDraft && !$isVoided)
                            <div class="return-input-wrap">
                              <input type="number"
                                name="lines[{{ $i }}][qty]"
                                class="form-control form-control-sm text-end mono qty-return-input"
                                value="{{ old("lines.$i.qty", $qty > 0.0001 ? $qty : '') }}"
                                step="0.0001" min="0" max="{{ $maxReturn }}"
                                inputmode="decimal" placeholder="0"
                                data-max="{{ $maxReturn }}"
                                data-row-code="{{ $row->item?->code ?? '-' }}">
                              <div class="row-main-action">
                                <button type="button" class="btn btn-outline-secondary btn-sm quick-btn js-zero-row">0</button>
                                <button type="button" class="btn btn-outline-primary btn-sm quick-btn js-max-row">Maks</button>
                              </div>
                            </div>
                          @else
                            <span class="mono">
                              {{ rtrim(rtrim(number_format($qty, 4, ',', '.'), '0'), ',') }}
                            </span>
                          @endif
                        </td>

                        @if ($canSeeMoney)
                          <td class="text-end mono" data-label="Nilai">
                            <div>Rp {{ number_format($lineTotal, 0, ',', '.') }}</div>
                            <div class="item-sub">Harga {{ number_format($unitPrice, 0, ',', '.') }}</div>
                          </td>
                        @endif
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>

              @if($isDraft && !$isVoided)
                <div class="d-flex justify-content-end gap-2 mt-3">
                  <button class="btn btn-primary btn-sm btn-pill" type="submit">
                    <i class="bi bi-save2 me-1"></i> Simpan Item Return
                  </button>
                </div>
              @endif
            </form>

            <hr class="my-3" style="border-color: var(--line); opacity: 1;">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div>
                @if($isDraft && !$stockReady)
                  <span class="status-badge bg-danger-subtle text-danger border border-danger-subtle">Stok kurang</span>
                @elseif($isDraft)
                  <span class="status-badge bg-success-subtle text-success border border-success-subtle">Siap diposting</span>
                @endif
              </div>

              <div class="d-flex gap-2">
                @if($isDraft && !$isVoided)
                  <form method="POST" action="{{ route('purchasing.purchase_returns.post', $ret->id) }}" class="js-post-return">
                    @csrf
                    <button class="btn {{ $stockReady ? 'btn-success' : 'btn-outline-danger' }} btn-sm btn-pill"
                      type="submit" {{ $stockReady ? '' : 'disabled' }}>
                      <i class="bi bi-check2-circle me-1"></i> {{ $stockReady ? 'POST Return' : 'Kurangi Qty Dulu' }}
                    </button>
                  </form>
                @endif

                @if($isPosted && !$isVoided)
                  <form method="POST" action="{{ route('purchasing.purchase_returns.void', $ret->id) }}" class="js-void-return">
                    @csrf
                    <button class="btn btn-outline-danger btn-sm btn-pill"
                      type="submit">
                      <i class="bi bi-x-circle me-1"></i> VOID
                    </button>
                  </form>
                @endif
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const qtyInputs = Array.from(document.querySelectorAll('.qty-return-input'));
  const liveLines = document.getElementById('live-return-lines');
  const liveQty = document.getElementById('live-return-qty');

  function toNumber(value) {
    if (value === null || value === '') return 0;
    return Number(String(value).replace(',', '.')) || 0;
  }

  function formatQty(value) {
    return new Intl.NumberFormat('id-ID', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 4
    }).format(value);
  }

  function refreshRow(input) {
    const row = input.closest('.return-row');
    if (!row) return;
    const qty = toNumber(input.value);
    row.classList.toggle('has-qty', qty > 0.0001);
    row.classList.toggle('is-empty', qty <= 0.0001);
    const badgeWrap = row.querySelector('.return-mobile-head');
    if (badgeWrap) {
      badgeWrap.innerHTML = qty > 0.0001
        ? '<span class="primary-label">Diretur</span>'
        : '<span class="state-pill is-muted">Tidak diretur</span>';
    }
  }

  function refreshTotals() {
    let count = 0;
    let total = 0;
    qtyInputs.forEach(function (input) {
      const qty = toNumber(input.value);
      if (qty > 0.0001) count++;
      total += qty;
      refreshRow(input);
    });
    if (liveLines) liveLines.textContent = count;
    if (liveQty) liveQty.textContent = formatQty(total);
  }

  qtyInputs.forEach(function (input) {
    input.addEventListener('focus', function () {
      setTimeout(function () { input.select(); }, 0);
    });
    input.addEventListener('input', refreshTotals);
  });

  document.querySelectorAll('.js-zero-row').forEach(function (button) {
    button.addEventListener('click', function () {
      const input = button.closest('.return-input-wrap')?.querySelector('.qty-return-input');
      if (!input) return;
      input.value = '';
      input.focus();
      refreshTotals();
    });
  });

  document.querySelectorAll('.js-max-row').forEach(function (button) {
    button.addEventListener('click', function () {
      const input = button.closest('.return-input-wrap')?.querySelector('.qty-return-input');
      if (!input) return;
      input.value = input.dataset.max || input.max || '';
      input.focus();
      refreshTotals();
    });
  });

  document.getElementById('btn-zero-all')?.addEventListener('click', function () {
    qtyInputs.forEach(function (input) { input.value = ''; });
    qtyInputs[0]?.focus();
    refreshTotals();
  });

  document.getElementById('btn-focus-first')?.addEventListener('click', function () {
    const emptyInput = qtyInputs.find(input => toNumber(input.value) <= 0.0001);
    (emptyInput || qtyInputs[0])?.focus();
  });

  refreshTotals();

  function confirmSubmit(form, options) {
    form.addEventListener('submit', function (event) {
      if (form.dataset.confirmed === '1' || !window.Swal) return;
      event.preventDefault();

      Swal.fire({
        icon: options.icon,
        title: options.title,
        text: options.text,
        showCancelButton: true,
        confirmButtonText: options.confirmText,
        cancelButtonText: 'Batal',
        confirmButtonColor: options.color,
        reverseButtons: true
      }).then(function (result) {
        if (!result.isConfirmed) return;
        form.dataset.confirmed = '1';
        form.submit();
      });
    });
  }

  document.querySelectorAll('.js-post-return').forEach(function (form) {
    confirmSubmit(form, {
      icon: 'question',
      title: 'Posting return?',
      text: 'Stok gudang akan berkurang dan jurnal return akan dibuat.',
      confirmText: 'Ya, Posting',
      color: '#16a34a'
    });
  });

  document.querySelectorAll('.js-void-return').forEach(function (form) {
    confirmSubmit(form, {
      icon: 'warning',
      title: 'Void return?',
      text: 'Stok akan dikembalikan dan jurnal return akan dibalik.',
      confirmText: 'Ya, Void',
      color: '#dc2626'
    });
  });
});
</script>
@endpush
