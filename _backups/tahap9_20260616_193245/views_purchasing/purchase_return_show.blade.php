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

  .card-main{
    background: var(--card);
    border-radius: 16px;
    border: 1px solid rgba(148,163,184,.35);
    box-shadow: 0 10px 30px rgba(15,23,42,.10), 0 0 0 1px rgba(148,163,184,.08);
  }
  .card-soft{
    background: color-mix(in srgb, var(--card) 94%, var(--bg) 6%);
    border-radius: 16px;
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
  .table thead th{
    background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
    border-bottom-color: var(--line);
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    padding-top: .55rem;
    padding-bottom: .55rem;
    white-space: nowrap;
  }
  tbody.small td{
    border-bottom-color: var(--line);
    vertical-align: middle;
    padding-top: .5rem;
    padding-bottom: .5rem;
    font-size: .82rem;
  }

  .item-main{ font-weight: 650; }
  .item-sub{ font-size: .78rem; color: var(--muted); }
  .summary-grid{
    display:grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap:.65rem;
  }
  .summary-box{
    border:1px solid var(--line);
    border-radius:12px;
    padding:.7rem .8rem;
    background:rgba(148,163,184,.07);
  }
  .summary-box .lbl{ display:block; font-size:.72rem; color:var(--muted); line-height:1.15; }
  .summary-box .val{ display:block; font-weight:850; line-height:1.25; }

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

    /* mobile table -> card rows */
    .table thead{ display:none; }
    .table tbody tr{
      display:block;
      border: 1px solid var(--line);
      border-radius: 12px;
      margin-bottom: .6rem;
      padding: .5rem .65rem .55rem;
      background: rgba(15,23,42,.02);
    }
    .table tbody td{
      display:flex;
      justify-content:space-between;
      align-items:center;
      border: 0;
      padding-block: .2rem;
    }
    .table tbody td[data-label]::before{
      content: attr(data-label);
      font-size: .75rem;
      color: var(--muted);
      margin-right: .75rem;
      text-align:left;
    }
    .summary-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
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
  $totalLines = (int) ($ret->lines?->count() ?? 0);
  $totalQty = (float) ($ret->lines?->sum('qty') ?? 0);
  $grnHref = route('purchasing.purchase_receipts.show', $ret->purchase_receipt_id ?? $ret->grn_id ?? ($ret->grn?->id ?? 0));
@endphp

<div class="pr-show-page">
  <div class="page-wrap">

    {{-- HEADER --}}
    <div class="page-header d-flex justify-content-between align-items-start mb-3 gap-2">
      <div class="d-flex flex-column gap-1">
        <div class="d-flex flex-wrap align-items-center gap-2">
          <h1 class="mb-0 page-header-title">Return Pembelian</h1>

          <span class="status-badge {{ $statusClass }}">
            {{ $statusLabel }}@if($isVoided) • VOID @endif
          </span>

          <span class="pill d-none d-sm-inline">
            {{ $ret->lines?->count() ?? 0 }} baris
          </span>
        </div>

        <div class="page-header-subtitle">
          <span>Kode: <span class="fw-semibold mono">{{ $ret->code }}</span></span>

          @if($ret->date)
            <span class="mx-2">•</span>
            <span>Tanggal: {{ \Illuminate\Support\Carbon::parse($ret->date)->format('Y-m-d') }}</span>
          @endif

          @if($ret->grn?->code)
            <span class="mx-2">•</span>
            <span>GRN: <span class="mono fw-semibold">{{ $ret->grn->code }}</span></span>
          @endif
        </div>

        <div class="page-header-subtitle d-none d-sm-block">
          Supplier: <span class="fw-semibold">{{ $ret->grn?->supplier?->name ?? '-' }}</span>
          <span class="mx-2">•</span>
          Gudang: <span class="fw-semibold">{{ $ret->grn?->warehouse?->name ?? '-' }}</span>
        </div>
      </div>

      <div class="page-header-actions d-flex align-items-center gap-2">
        <a href="{{ $grnHref }}"
           class="btn btn-soft btn-sm btn-pill">
          <i class="bi bi-arrow-left me-1"></i> Kembali ke GRN
        </a>

        <a href="{{ route('purchasing.purchase_returns.index') }}"
           class="btn btn-outline-secondary btn-sm btn-pill">
          Daftar Return
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

    <div class="card-soft mb-3">
      <div class="p-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
          <div>
            <div class="section-title mb-1">Ringkasan</div>
            <div class="small text-muted">
              Draft belum mengurangi stok. Setelah POST, stok di gudang GRN akan berkurang.
            </div>
          </div>
          <a href="{{ $grnHref }}" class="btn btn-outline-primary btn-sm btn-pill">Lihat GRN</a>
        </div>

        <div class="summary-grid">
          <div class="summary-box">
            <span class="lbl">Item Return</span>
            <span class="val mono">{{ angka($totalLines) }}</span>
          </div>
          <div class="summary-box">
            <span class="lbl">Total Qty</span>
            <span class="val mono">{{ decimal_id($totalQty, 2) }}</span>
          </div>
          <div class="summary-box">
            <span class="lbl">GRN Asal</span>
            <span class="val mono">{{ $ret->grn?->code ?? '-' }}</span>
          </div>
          <div class="summary-box">
            <span class="lbl">Gudang</span>
            <span class="val">{{ $ret->grn?->warehouse?->code ?? '-' }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-3">
      {{-- INFO --}}
      <div class="col-12 col-lg-6">
        <div class="card-soft h-100">
          <div class="p-3 p-sm-4">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
              <h6 class="section-title mb-0">Informasi Return</h6>
              @if ($canSeeMoney)
                <span class="pill mono">Rp {{ number_format($grand, 0, ',', '.') }}</span>
              @endif
            </div>

            <dl class="row mb-0 small">
              <dt class="col-sm-4">Kode</dt>
              <dd class="col-sm-8 mono">{{ $ret->code }}</dd>

              <dt class="col-sm-4">Tanggal</dt>
              <dd class="col-sm-8">{{ $ret->date ? \Illuminate\Support\Carbon::parse($ret->date)->format('Y-m-d') : '-' }}</dd>

              <dt class="col-sm-4">Status</dt>
              <dd class="col-sm-8">
                <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}@if($isVoided) • VOID @endif</span>
              </dd>

              <dt class="col-sm-4">Catatan</dt>
              <dd class="col-sm-8">{{ $ret->notes ?: '-' }}</dd>
            </dl>
          </div>
        </div>
      </div>

      {{-- FORM EDIT DRAFT --}}
      <div class="col-12 col-lg-6">
        <div class="card-soft h-100">
          <div class="p-3 p-sm-4">
            <h6 class="section-title mb-3">Ubah Draft</h6>

            <form method="POST" action="{{ route('purchasing.purchase_returns.update', $ret->id) }}">
              @csrf
              @method('PUT')

              <div class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                  <label class="form-label small text-uppercase">Tanggal</label>
                  <input type="text"
                    name="date"
                    class="form-control form-control-sm gf-date-input"
                    value="{{ old('date', $ret->date) }}"
                    data-gf-date autocomplete="off"
                    {{ (!$isDraft || $isVoided) ? 'disabled' : '' }}>
                </div>
                <div class="col-12 col-md-7">
                  <label class="form-label small text-uppercase">Catatan</label>
                  <input type="text"
                    name="notes"
                    class="form-control form-control-sm"
                    value="{{ old('notes', $ret->notes) }}"
                    placeholder="optional"
                    {{ (!$isDraft || $isVoided) ? 'disabled' : '' }}>
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                  @if($isDraft && !$isVoided)
                    <button class="btn btn-primary btn-sm btn-pill" type="submit">
                      <i class="bi bi-save2 me-1"></i> Simpan Draft
                    </button>
                  @else
                    <button class="btn btn-outline-secondary btn-sm btn-pill" type="button" disabled>
                  Return terkunci
                    </button>
                  @endif
                </div>
              </div>
            </form>

            <div class="text-muted small mt-2">
              * Draft bisa dicek dulu. Setelah POST, stok di gudang GRN akan berkurang.
            </div>
          </div>
        </div>
      </div>

      {{-- DETAIL LINES --}}
      <div class="col-12">
        <div class="card-main">
          <div class="p-3 p-sm-3 border-bottom" style="border-color: var(--line) !important;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div class="fw-semibold small text-uppercase">Detail Return</div>
              <div class="small text-muted">
                GRN: <span class="mono">{{ $ret->grn?->code ?? '-' }}</span>
                • Supplier: <span class="fw-semibold">{{ $ret->grn?->supplier?->name ?? '-' }}</span>
              </div>
            </div>
          </div>

          <div class="p-2 p-sm-3">
            <form method="POST" action="{{ route('purchasing.purchase_returns.update', $ret->id) }}">
              @csrf
              @method('PUT')

              <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Item</th>
                      <th class="text-end">Diterima</th>
                      <th class="text-end">Sisa Bisa Return</th>
                      <th class="text-end" style="width: 180px;">Qty Return</th>
                      @if ($canSeeMoney)
                        <th class="text-end">Harga</th>
                        <th class="text-end">Subtotal</th>
                      @endif
                    </tr>
                  </thead>
                  <tbody class="small">
                    @foreach($ret->lines as $i => $ln)
                      @php
                        $grnLine = $ln->grnLine;
                        $received = (float)($grnLine?->qty_received ?? 0);
                        $rem = (float)($remainingMap[(int)$ln->purchase_receipt_line_id] ?? 0);

                        $qty = (float)($ln->qty ?? 0);
                        $unitPrice = (float)($ln->unit_price ?? 0);
                        $lineTotal = (float)($ln->line_total ?? 0);
                      @endphp
                      <tr>
                        <td class="mono" data-label="#"> {{ $loop->iteration }}</td>

                        <td data-label="Item">
                          <div class="item-main">{{ $ln->item?->name ?? '-' }}</div>
                          <div class="item-sub mono">
                            {{ $ln->item?->code ?? '-' }}
                            @if($ln->lot_id) • LOT #{{ $ln->lot_id }} @endif
                          </div>

                          @if($isDraft && !$isVoided)
                            <input type="hidden" name="lines[{{ $i }}][id]" value="{{ $ln->id }}">
                            <input type="text"
                              name="lines[{{ $i }}][notes]"
                              class="form-control form-control-sm mt-2"
                              placeholder="Catatan item (opsional)"
                              value="{{ old("lines.$i.notes", $ln->notes) }}">
                          @endif
                        </td>

                        <td class="text-end mono" data-label="Diterima">
                          {{ rtrim(rtrim(number_format($received, 4, ',', '.'), '0'), ',') }}
                        </td>

                        <td class="text-end mono" data-label="Sisa Bisa Return">
                          {{ rtrim(rtrim(number_format($rem, 4, ',', '.'), '0'), ',') }}
                        </td>

                        <td class="text-end" data-label="Qty Return">
                          @if($isDraft && !$isVoided)
                            <input type="text"
                              name="lines[{{ $i }}][qty]"
                              class="form-control form-control-sm text-end mono"
                              value="{{ old("lines.$i.qty", $ln->qty) }}"
                              placeholder="0">
                            <div class="text-muted small mt-1">max {{ rtrim(rtrim(number_format($rem, 4, ',', '.'), '0'), ',') }}</div>
                          @else
                            <span class="mono">
                              {{ rtrim(rtrim(number_format($qty, 4, ',', '.'), '0'), ',') }}
                            </span>
                          @endif
                        </td>

                        @if ($canSeeMoney)
                          <td class="text-end mono" data-label="Harga">
                            {{ number_format($unitPrice, 0, ',', '.') }}
                          </td>

                          <td class="text-end mono" data-label="Subtotal">
                            {{ number_format($lineTotal, 0, ',', '.') }}
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
              <div class="text-muted small">
                * POST akan mengurangi stok dan mengunci return. VOID akan membalik stok dan jurnal return.
              </div>

              <div class="d-flex gap-2">
                @if($isDraft && !$isVoided)
                  <form method="POST" action="{{ route('purchasing.purchase_returns.post', $ret->id) }}">
                    @csrf
                    <button class="btn btn-success btn-sm btn-pill"
                      type="submit"
                      onclick="return confirm('POST return? Stok akan berkurang.');">
                      <i class="bi bi-check2-circle me-1"></i> POST Return
                    </button>
                  </form>
                @endif

                @if($isPosted && !$isVoided)
                  <form method="POST" action="{{ route('purchasing.purchase_returns.void', $ret->id) }}">
                    @csrf
                    <button class="btn btn-outline-danger btn-sm btn-pill"
                      type="submit"
                      onclick="return confirm('VOID return? Stok & jurnal akan direverse.');">
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
