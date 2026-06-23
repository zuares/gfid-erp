{{-- resources/views/purchasing/purchase_returns/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Return Pembelian ' . ($ret->code ?? ''))

@push('head')
<style>
  /* ── shared layout ── */
  .page-wrap { max-width:1080px; margin-inline:auto; padding-bottom:3rem; }
  .mono { font-variant-numeric:tabular-nums; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono"; }

  /* Cards */
  .card-info  { background:var(--card); border-radius:14px; border:1px solid var(--line); padding:1.1rem 1.2rem; }
  .card-section { background:var(--card); border-radius:14px; border:1px solid var(--line); overflow:hidden; }
  .card-section-header {
    padding:.6rem 1rem;
    border-bottom:1px solid var(--line);
    font-size:.72rem; text-transform:uppercase; letter-spacing:.07em;
    color:var(--muted); font-weight:600;
  }

  /* Summary 4-col */
  .summary-col { padding:.85rem 1rem; border-right:1px solid var(--line); }
  .summary-col:last-child { border-right:none; }
  .summary-col-label { font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); font-weight:600; margin-bottom:.3rem; }
  .summary-col-value { font-size:.95rem; font-weight:600; }

  /* Info label */
  .info-label { font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); font-weight:600; margin-bottom:.2rem; }
  .info-value  { font-size:.88rem; }

  /* Status badges */
  .badge-status { border-radius:999px; font-size:.7rem; padding:.1rem .6rem; border:1px solid transparent; white-space:nowrap; }
  .badge-draft   { background:rgba(148,163,184,.12);color:#64748b;border-color:rgba(148,163,184,.5); }
  .badge-posted  { background:rgba(22,163,74,.12);color:#15803d;border-color:rgba(22,163,74,.6); }
  .badge-voided  { background:rgba(220,38,38,.08);color:#b91c1c;border-color:rgba(220,38,38,.4); }

  /* Row labels in table */
  .primary-label,.state-pill {
    display:inline-flex; align-items:center; border-radius:999px;
    padding:.12rem .5rem; font-size:.68rem; font-weight:700;
    border:1px solid transparent; white-space:nowrap;
  }
  .primary-label { color:#1d4ed8; background:rgba(37,99,235,.08); border-color:rgba(37,99,235,.32); }
  .state-pill.is-muted { color:#64748b; background:rgba(148,163,184,.1); border-color:rgba(148,163,184,.35); }

  /* Table */
  .table-wrap { overflow-x:auto; }
  .return-table { --bs-table-bg:transparent; }
  .return-table thead th {
    background:color-mix(in srgb,var(--card) 90%,var(--bg) 10%);
    border-bottom-color:var(--line);
    font-size:.68rem; text-transform:uppercase; letter-spacing:.06em;
    padding:.5rem .65rem; white-space:nowrap;
  }
  .return-table tbody td { border-bottom-color:var(--line); vertical-align:middle; padding:.55rem .65rem; font-size:.82rem; }

  .item-main { font-weight:650; line-height:1.15; }
  .item-sub  { font-size:.74rem; color:var(--muted); margin-top:.08rem; }

  /* metric pills */
  .return-metrics { display:flex; gap:.3rem; flex-wrap:wrap; justify-content:flex-end; }
  .metric-pill { display:inline-flex; align-items:center; gap:.25rem; border:1px solid var(--line); border-radius:999px; padding:.1rem .45rem; font-size:.7rem; color:var(--muted); background:rgba(148,163,184,.06); }
  .metric-pill strong { color:var(--text); }
  .metric-pill.is-blue  { color:#2563eb; border-color:rgba(37,99,235,.25); background:rgba(37,99,235,.06); }
  .metric-pill.is-red   { color:#dc2626; border-color:rgba(220,38,38,.25); background:rgba(220,38,38,.06); }
  .metric-pill.is-green { color:#15803d; border-color:rgba(22,163,74,.25); background:rgba(22,163,74,.06); }

  /* Return-specific */
  .line-stock-short { color:#dc2626; font-weight:700; }
  .line-stock-ok    { color:#15803d; font-weight:700; }
  .qty-return-input { min-height:36px; border-radius:8px; font-weight:700; font-size:.92rem; }
  .return-tools { display:flex; gap:.35rem; flex-wrap:wrap; align-items:center; }
  .return-tools-summary { margin-left:auto; color:var(--muted); font-size:.78rem; }
  .return-total-live { color:#2563eb; font-weight:700; }
  .return-formbar { display:grid; grid-template-columns:150px minmax(180px,1fr) auto; gap:.5rem; align-items:end; margin-bottom:.55rem; }
  .return-formbar .form-label { font-size:.68rem; color:var(--muted); font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin-bottom:.15rem; }
  .return-formbar-summary { display:flex; justify-content:flex-end; align-items:center; gap:.45rem; color:var(--muted); font-size:.76rem; padding-bottom:.32rem; }
  .return-dot::before { content:"•"; opacity:.4; margin-right:.4rem; }
  .return-row.is-empty { opacity:.7; }
  .return-row.has-qty { background:rgba(37,99,235,.04); box-shadow:inset 3px 0 0 rgba(37,99,235,.4); }
  .quick-btn { border-radius:999px; padding:.15rem .5rem; font-size:.7rem; }
  .row-main-action { display:flex; gap:.3rem; justify-content:flex-end; flex-wrap:wrap; margin-top:.3rem; }
  .return-input-wrap { display:flex; flex-direction:column; align-items:flex-end; }
  .return-mobile-head { display:none; }

  @media (max-width:767.98px){
    .page-wrap { padding-inline:.75rem; }
    .summary-col { border-right:none; border-bottom:1px solid var(--line); }
    .summary-col:last-child { border-bottom:none; }
    .return-table thead { display:none; }
    .return-table tbody tr {
      display:block; border:1px solid var(--line); border-radius:12px;
      margin-bottom:.6rem; padding:.55rem .65rem; background:rgba(15,23,42,.02);
    }
    .return-table tbody td {
      display:flex; justify-content:space-between; align-items:flex-start;
      gap:.75rem; border:0; padding-block:.2rem;
    }
    .return-table tbody td[data-label]::before {
      content:attr(data-label); font-size:.75rem; color:var(--muted);
      margin-right:.75rem; flex:0 0 auto;
    }
    .return-table .td-item { display:block; }
    .return-table .td-item::before { display:none; }
    .return-metrics { justify-content:flex-start; }
    .return-input-wrap { width:100%; align-items:flex-end; }
    .qty-return-input { max-width:150px; margin-left:auto; }
    .return-tools { width:100%; }
    .return-tools .btn { flex:1 1 auto; }
    .return-tools-summary { width:100%; margin-left:0; text-align:center; }
    .return-formbar { grid-template-columns:1fr 1fr; gap:.45rem; }
    .return-formbar-summary { grid-column:1 / -1; justify-content:center; padding-bottom:0; }
    .return-mobile-head { display:block; }
    .row-main-action { justify-content:stretch; }
    .row-main-action .btn { flex:1; }
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

<div class="page-wrap py-4">

    {{-- HEADER --}}
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
      {{-- Kiri --}}
      <div style="min-width:0;">
        <h2 class="mb-0 lh-1" style="font-size:1.35rem;">Return Pembelian</h2>
        <div class="text-muted mono mt-1" style="font-size:.8rem;">Kode: {{ $ret->code }}</div>
      </div>

      {{-- Kanan --}}
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('purchasing.purchase_returns.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <a href="{{ $grnHref }}" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-box-arrow-up-right me-1"></i>GRN
        </a>
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

    {{-- SUMMARY ROW --}}
    <div class="card-info mb-3">
      <div class="row g-0">
        <div class="col-6 col-md-3 summary-col">
          <div class="summary-col-label">Status</div>
          <div class="summary-col-value">
            @if($isVoided)
              <span class="badge-status badge-voided">VOID</span>
            @elseif($isPosted)
              <span class="badge-status badge-posted">Posted</span>
            @else
              <span class="badge-status badge-draft">Draft</span>
            @endif
          </div>
        </div>
        <div class="col-6 col-md-3 summary-col">
          <div class="summary-col-label">Item Return</div>
          <div class="summary-col-value mono">{{ $totalReturnLines }} / {{ $totalLines }}</div>
        </div>
        <div class="col-6 col-md-3 summary-col">
          <div class="summary-col-label">Qty Return</div>
          <div class="summary-col-value mono">{{ decimal_id($totalQty, 2) }}</div>
        </div>
        <div class="col-6 col-md-3 summary-col">
          <div class="summary-col-label">{{ $isDraft ? 'Stok' : 'Jurnal' }}</div>
          <div class="summary-col-value">
            @if($isDraft)
              <span class="{{ $stockReady ? 'line-stock-ok' : 'line-stock-short' }}" style="font-size:.9rem;">
                {{ $stockReady ? 'Siap' : 'Kurang' }}
              </span>
            @else
              {{ $journalCount > 0 ? $journalCount . ' jurnal' : 'Belum' }}
            @endif
          </div>
        </div>
      </div>
    </div>

    {{-- INFO CARD: metadata dokumen --}}
    <div class="row g-3 mb-3">
      <div class="col-12 col-lg-6">
        <div class="card-info h-100">
          <div class="info-label mb-3" style="font-size:.75rem;">Informasi Dokumen</div>
          <dl class="row mb-0 small">
            <dt class="col-sm-4 info-label mb-1">Tanggal</dt>
            <dd class="col-sm-8 mb-1 mono">{{ $ret->date ? \Illuminate\Support\Carbon::parse($ret->date)->format('d/m/Y') : '-' }}</dd>

            <dt class="col-sm-4 info-label mb-1">Dari GRN</dt>
            <dd class="col-sm-8 mb-1">
              <a href="{{ $grnHref }}" class="text-decoration-none mono fw-semibold">
                {{ $ret->grn?->code ?? '-' }}
              </a>
            </dd>

            <dt class="col-sm-4 info-label mb-1">Supplier</dt>
            <dd class="col-sm-8 mb-1">
              <div class="fw-semibold">{{ $ret->grn?->supplier?->name ?? '-' }}</div>
              @if($ret->grn?->supplier?->code)
                <div class="text-muted mono" style="font-size:.75rem;">{{ $ret->grn->supplier->code }}</div>
              @endif
            </dd>

            <dt class="col-sm-4 info-label mb-1">Gudang</dt>
            <dd class="col-sm-8 mb-1">
              <div class="fw-semibold">{{ $ret->grn?->warehouse?->name ?? '-' }}</div>
              @if($ret->grn?->warehouse?->code)
                <div class="text-muted mono" style="font-size:.75rem;">{{ $ret->grn->warehouse->code }}</div>
              @endif
            </dd>

            @if($ret->notes)
              <dt class="col-sm-4 info-label mb-1">Catatan</dt>
              <dd class="col-sm-8 mb-1">{{ $ret->notes }}</dd>
            @endif
          </dl>
        </div>
      </div>

      @if($canSeeMoney)
      <div class="col-12 col-lg-6">
        <div class="card-info h-100">
          <div class="info-label mb-3" style="font-size:.75rem;">Ringkasan Nilai</div>
          <div class="d-flex justify-content-between align-items-center">
            <span class="info-label mb-0">Total Return</span>
            <span class="mono fw-bold" style="font-size:1.05rem;">{{ rupiah($grand) }}</span>
          </div>
          @if($isVoided)
            <div class="mt-3 text-muted" style="font-size:.78rem;">Return ini sudah di-VOID. Stok dan jurnal telah dibalik.</div>
          @elseif($isPosted)
            <div class="mt-3 text-muted" style="font-size:.78rem;">Stok sudah keluar & jurnal return tercatat.</div>
          @endif
        </div>
      </div>
      @endif
    </div>

    <div class="row g-3 mb-3">
      {{-- DETAIL LINES --}}
      <div class="col-12">
        <div class="card-section">
          <div class="card-section-header d-flex justify-content-between align-items-center">
            <span>Detail Return</span>
            <span class="d-none d-md-inline" style="text-transform:none;letter-spacing:0;font-size:.75rem;">
              {{ $totalReturnLines }} / {{ $totalLines }} item diretur
            </span>
          </div>

          <div class="p-2 p-sm-3">
            <form method="POST" action="{{ route('purchasing.purchase_returns.update', $ret->id) }}">
              @csrf
              @method('PUT')

              {{-- Formbar hanya untuk draft --}}
              @if($isDraft && !$isVoided)
              <div class="return-formbar mb-3">
                <div>
                  <label class="form-label">Tanggal</label>
                  <input type="text"
                    name="date"
                    class="form-control form-control-sm gf-date-input mono"
                    value="{{ $dateValue }}"
                    data-gf-date autocomplete="off"
                    required>
                </div>
                <div>
                  <label class="form-label">Catatan</label>
                  <input type="text"
                    name="notes"
                    class="form-control form-control-sm"
                    value="{{ old('notes', $ret->notes) }}"
                    placeholder="Opsional">
                </div>
                <div class="return-formbar-summary">
                  @if ($canSeeMoney)
                    <span class="mono">Rp {{ number_format($grand, 0, ',', '.') }}</span>
                  @endif
                </div>
              </div>
              @else
              {{-- Hidden inputs agar form tetap valid --}}
              <input type="hidden" name="date" value="{{ $dateValue }}">
              <input type="hidden" name="notes" value="{{ $ret->notes }}">
              @endif

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

</div>{{-- /page-wrap --}}
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
