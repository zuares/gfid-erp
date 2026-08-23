@extends('layouts.app')

@section('title', 'Edit GRN ' . $purchase_receipt->code)

@push('head')
<style>
  .grn-edit-page { min-height: 100vh; }
  .grn-edit-page .page-wrap{
    max-width: 1150px;
    margin-inline:auto;
    padding: 1rem 1rem 4rem;
  }
  body[data-theme="light"] .grn-edit-page .page-wrap{
    background: radial-gradient(circle at top left,
      rgba(59, 130, 246, 0.12) 0,
      rgba(45, 212, 191, 0.10) 26%,
      #f9fafb 60%);
  }

  .card-main{
    background: var(--card);
    border-radius: 16px;
    border: 1px solid rgba(148, 163, 184, 0.35);
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.10), 0 0 0 1px rgba(148, 163, 184, 0.08);
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

  .show-header-title{ font-size: 1.35rem; font-weight: 650; }
  .show-header-subtitle{ font-size: .8rem; color: var(--muted); }
  .show-header-pill{
    font-size: .75rem;
    border-radius: 999px;
    padding: .16rem .7rem;
    border: 1px solid rgba(148, 163, 184, .55);
    background: color-mix(in srgb, var(--card) 80%, var(--bg) 20%);
  }
  .show-header-status{
    font-size:.78rem;
    border-radius:999px;
    padding:.14rem .7rem;
  }

  .section-title{
    font-size:.86rem;
    text-transform: uppercase;
    letter-spacing:.08em;
    color: var(--muted);
  }

  /* table wrapper */
  @media (min-width: 992px){
    .grn-detail-wrapper{ max-height: 58vh; overflow-y:auto; overflow-x:hidden; }
    .grn-detail-wrapper::-webkit-scrollbar{ width:6px; height:6px; }
    .grn-detail-wrapper::-webkit-scrollbar-thumb{
      background: color-mix(in srgb, var(--muted) 60%, transparent);
      border-radius: 999px;
    }
    .grn-detail-wrapper::-webkit-scrollbar-track{ background: transparent; }
  }
  @media (max-width: 991.98px){
    .grn-detail-wrapper{ overflow-x:auto; overflow-y:auto; }
  }

  .grn-edit-page .table thead th{
    background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
    border-bottom-color: var(--line);
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    padding-top: .55rem;
    padding-bottom: .55rem;
    white-space: nowrap;
  }
  .grn-edit-page tbody.small td{
    border-bottom-color: var(--line);
    vertical-align: middle;
    padding-top: .55rem;
    padding-bottom: .55rem;
    font-size: .8rem;
  }

  .btn-soft{
    border: 1px solid rgba(148,163,184,.35);
    background: color-mix(in srgb, var(--card) 85%, var(--bg) 15%);
  }

  /* input sizing inside table */
  .grn-edit-page .table .form-control,
  .grn-edit-page .table .form-select{
    border-radius: 10px;
  }

  @media (max-width: 767.98px){
    html, body{ max-width:100%; overflow-x:hidden; }
    .grn-edit-page{ overflow-x:hidden; }
    .grn-edit-page .page-wrap{ padding-inline:.85rem; }
    .show-header{ flex-direction:column; align-items:flex-start; gap:.45rem; }
    .show-header-title{ font-size: 1.15rem; }
    .show-header-actions{ width:100%; display:flex; flex-wrap:wrap; gap:.35rem; }
    .show-header-actions .btn{ flex: 1 1 auto; }
  }

  /* make remove button center */
  .btn-icon{
    width: 34px;
    height: 34px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius: 10px;
  }
</style>
@endpush

@section('content')
@php
  $user = auth()->user();
  $isOwner = $user?->isOwner() ?? false;
  $isAdmin = strtolower((string)($user->role ?? '')) === 'admin';
  $canSeeMoney = $isOwner;          // hanya owner
@endphp
<div class="grn-edit-page">
  <div class="page-wrap">

    {{-- HEADER --}}
    <div class="show-header d-flex justify-content-between align-items-start mb-3 gap-2">
      <div class="d-flex flex-column gap-1">
        <div class="d-flex flex-wrap align-items-center gap-2">
          <h1 class="mb-0 show-header-title">Edit Goods Receipt</h1>

          {{-- STATUS --}}
          @if ($purchase_receipt->status === 'posted')
            <span class="show-header-status bg-success-subtle text-success border border-success-subtle">Posted</span>
          @elseif ($purchase_receipt->status === 'draft')
            <span class="show-header-status bg-warning-subtle text-warning border border-warning-subtle">Draft</span>
          @else
            <span class="show-header-status bg-secondary-subtle text-secondary border border-secondary-subtle">
              {{ ucfirst($purchase_receipt->status) }}
            </span>
          @endif

          <span class="show-header-pill d-none d-sm-inline">
            {{ $purchase_receipt->lines->count() }} baris barang
          </span>
        </div>

        <div class="show-header-subtitle">
          <span>Kode: <span class="fw-semibold mono">{{ $purchase_receipt->code }}</span></span>
          @if ($purchase_receipt->date)
            <span class="mx-2">•</span>
            <span>Tanggal: {{ $purchase_receipt->date->format('Y-m-d') }}</span>
          @endif
          @if ($purchase_receipt->updated_at)
            <span class="mx-2 d-none d-sm-inline">•</span>
            <span class="d-none d-sm-inline">Update: {{ $purchase_receipt->updated_at->format('Y-m-d H:i') }}</span>
          @endif
        </div>
      </div>

      <div class="show-header-actions d-flex align-items-center gap-2">
        <a href="{{ route('purchasing.purchase_receipts.show', $purchase_receipt->id) }}"
           class="btn btn-outline-secondary btn-sm">
          &larr; Kembali
        </a>

        <button type="submit" form="grn-edit-form" class="btn btn-primary btn-sm">
          Simpan
        </button>
      </div>
    </div>

    {{-- ALERTS --}}
    @if (session('error'))
      <div class="alert alert-danger py-2 mb-3">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
      <div class="alert alert-danger mb-3">
        <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
        <ul class="mb-0">
          @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
        </ul>
      </div>
    @endif

    @if ($purchase_receipt->status !== 'draft')
      <div class="alert alert-warning mb-3">
        GRN ini <strong>bukan draft</strong>. Sistem hanya mengizinkan edit saat draft.
      </div>
    @endif

    <form id="grn-edit-form" action="{{ route('purchasing.purchase_receipts.update', $purchase_receipt->id) }}" method="POST">
      @csrf
      @method('PUT')

      {{-- TOP: Info + Ringkasan (mirip show) --}}
      <div class="row g-3 mb-3">
        {{-- Informasi --}}
        <div class="col-12 col-lg-6 order-1">
          <div class="card-soft h-100">
            <div class="card-body p-3 p-md-4">
              <h6 class="section-title mb-3">Informasi Dokumen</h6>

              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label small text-muted">Tanggal</label>
                  <input type="text" name="date" class="form-control gf-date-input"
                         value="{{ old('date', $purchase_receipt->date?->toDateString()) }}"
                         data-gf-date autocomplete="off" required>
                </div>

                <div class="col-md-8">
                  <label class="form-label small text-muted">Supplier</label>
                  <select name="supplier_id" class="form-select" required>
                    <option value="">-- Pilih Supplier --</option>
                    @foreach ($suppliers as $sup)
                      <option value="{{ $sup->id }}" @selected(old('supplier_id', $purchase_receipt->supplier_id) == $sup->id)>
                        {{ $sup->code }} - {{ $sup->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="col-md-8">
                  <label class="form-label small text-muted">Gudang</label>
                  <select name="warehouse_id" class="form-select" required>
                    <option value="">-- Pilih Gudang --</option>
                    @foreach ($warehouses as $wh)
                      <option value="{{ $wh->id }}" @selected(old('warehouse_id', $purchase_receipt->warehouse_id) == $wh->id)>
                        {{ $wh->code }} - {{ $wh->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                @if ($canSeeMoney)
                  <div class="col-md-4">
                    <label class="form-label small text-muted">PPN (%)</label>
                    <input type="text" name="tax_percent" class="form-control mono text-end"
                           value="{{ old('tax_percent', $purchase_receipt->tax_percent) }}">
                  </div>

                  <div class="col-md-4">
                    <label class="form-label small text-muted">Diskon</label>
                    <input type="text" name="discount" class="form-control mono text-end"
                           value="{{ old('discount', $purchase_receipt->discount) }}">
                  </div>

                  <div class="col-md-4">
                    <label class="form-label small text-muted">Ongkir</label>
                    <input type="text" name="shipping_cost" class="form-control mono text-end"
                           value="{{ old('shipping_cost', $purchase_receipt->shipping_cost) }}">
                  </div>
                @endif

                <div class="col-12 col-md-6">
                  <label class="form-label small text-muted">No. Surat Jalan</label>
                  <input type="text" name="surat_jalan_no"
                    class="form-control @error('surat_jalan_no') is-invalid @enderror"
                    value="{{ old('surat_jalan_no', $purchase_receipt->surat_jalan_no) }}"
                    placeholder="Opsional — dari supplier"
                    maxlength="100">
                  @error('surat_jalan_no')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-12 col-md-6">
                  <label class="form-label small text-muted">Catatan</label>
                  <textarea name="notes" rows="2" class="form-control">{{ old('notes', $purchase_receipt->notes) }}</textarea>
                </div>
              </div>

            </div>
          </div>
        </div>

        @if ($canSeeMoney)
          {{-- Ringkasan (estimasi live di client) --}}
          <div class="col-12 col-lg-6 order-2">
            <div class="card-soft h-100">
              <div class="card-body p-3 p-md-4">
                <h6 class="section-title mb-3">Ringkasan Nilai (Preview)</h6>

                <dl class="row mb-0 small mono">
                  <dt class="col-6">Subtotal</dt>
                  <dd class="col-6 text-end" id="js-subtotal">0</dd>

                  <dt class="col-6">Diskon</dt>
                  <dd class="col-6 text-end" id="js-discount">0</dd>

                  <dt class="col-6">PPN</dt>
                  <dd class="col-6 text-end" id="js-tax">0</dd>

                  <dt class="col-6">Ongkir</dt>
                  <dd class="col-6 text-end" id="js-ship">0</dd>

                  <hr class="my-2" style="border-top-color: var(--line); opacity:1;">

                  <dt class="col-6 fw-semibold">Grand Total</dt>
                  <dd class="col-6 text-end fw-semibold fs-6" id="js-grand">0</dd>
                </dl>

                <div class="small text-muted mt-2">
                  Preview ini dihitung dari input di form (akan final di server saat Simpan).
                </div>
              </div>
            </div>
          </div>
        @endif

      </div>

      {{-- DETAIL --}}
      <div class="card-main">
        <div class="card-header d-flex justify-content-between align-items-center gap-2">
          <div class="fw-semibold small text-uppercase">Detail Barang Diterima</div>

          <div class="d-flex gap-2">
            <button type="button" class="btn btn-soft btn-sm" id="btn-add-line">
              + Tambah Baris
            </button>
          </div>
        </div>

        <div class="card-body">
          <div class="grn-detail-wrapper">
            <table class="table table-sm mb-0 align-middle">
              <thead class="table-light sticky-top">
                <tr>
                  <th style="width:4%" class="text-center">#</th>
                  <th style="width:34%">Item</th>
                  <th style="width:12%" class="text-end">Qty In</th>
                  <th style="width:12%" class="text-end">Reject</th>
                  @if ($canSeeMoney)
                    <th style="width:16%" class="text-end">Harga</th>
                  @endif
                  <th style="width:10%">Unit</th>
                  <th style="width:18%">Catatan</th>
                  <th style="width:4%"></th>
                </tr>
              </thead>

              <tbody class="small" id="grn-lines-body">
                @php
                  // old fallback
                  $oldItemIds = old('item_id', []);
                  $oldQtyReceived = old('qty_received', []);
                  $oldQtyReject = old('qty_reject', []);
                  $oldUnitPrice = old('unit_price', []);
                  $oldUnits = old('unit', []);
                  $oldLineNotes = old('line_notes', []);
                  $useOld = is_array($oldItemIds) && count($oldItemIds) > 0;
                @endphp

                @if ($useOld)
                  @foreach ($oldItemIds as $i => $itemId)
                    <tr class="js-line">
                      <td class="text-center mono js-no">{{ $loop->iteration }}</td>

                      <td>
                        <input type="hidden" name="po_line_id[]" value="{{ old('po_line_id.' . $i, '') }}">
                        <input type="hidden" name="allocation[]" value="{{ old('allocation.' . $i, 'hpp') }}">
                        <input type="hidden" name="expense_account_id[]" value="{{ old('expense_account_id.' . $i, '') }}">
                        <x-item-suggest
                          idName="item_id[]"
                          :items="$items"
                          :idValue="(string)($itemId ?? '')"
                          :displayValue="''"
                          placeholder="Kode / nama barang"
                          variant="default"
                          displayMode="code-name"
                          :showName="true"
                          :showCategory="true"
                          :skipSubmitValidation="true"
                        />
                        @php
                          $oldAllocation = old('allocation.' . $i, 'hpp') === 'expense' ? 'expense' : 'hpp';
                          $oldExpenseAccount = old('expense_account_id.' . $i, '');
                        @endphp
                        <span class="badge rounded-pill mt-1 {{ $oldAllocation === 'expense' ? 'text-bg-warning' : 'text-bg-primary' }}">
                          {{ $oldAllocation === 'expense' ? 'Biaya' . ($oldExpenseAccount ? ' · akun terpilih' : '') : 'Persediaan / HPP' }}
                        </span>
                      </td>

                      <td>
                        <input type="text" name="qty_received[]"
                               class="form-control form-control-sm text-end mono js-qty"
                               value="{{ $oldQtyReceived[$i] ?? 0 }}">
                      </td>
                      <td>
                        <input type="text" name="qty_reject[]"
                               class="form-control form-control-sm text-end mono js-reject"
                               value="{{ $oldQtyReject[$i] ?? 0 }}">
                      </td>
                      @if ($canSeeMoney)
                        <td>
                          <input type="text" name="unit_price[]"
                                 class="form-control form-control-sm text-end mono js-price"
                                 value="{{ $oldUnitPrice[$i] ?? 0 }}">
                        </td>
                      @endif
                      <td>
                        <input type="text" name="unit[]"
                               class="form-control form-control-sm mono"
                               value="{{ $oldUnits[$i] ?? '' }}" placeholder="kg/pcs/m">
                      </td>
                      <td>
                        <input type="text" name="line_notes[]"
                               class="form-control form-control-sm"
                               value="{{ $oldLineNotes[$i] ?? '' }}">
                      </td>
                      <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-icon btn-sm js-remove">
                          &times;
                        </button>
                      </td>
                    </tr>
                  @endforeach
                @else
                  @forelse ($purchase_receipt->lines as $line)
                    <tr class="js-line">
                      <td class="text-center mono js-no">{{ $loop->iteration }}</td>

                      <td>
                        <input type="hidden" name="po_line_id[]" value="{{ $line->purchase_order_line_id ?? '' }}">
                        @php
                          $lineAllocation = ($line->allocation ?? ($line->item?->default_allocation ?? 'hpp')) === 'expense' ? 'expense' : 'hpp';
                          $lineExpenseAccount = $line->expense_account_id ?? '';
                        @endphp
                        <input type="hidden" name="allocation[]" value="{{ $lineAllocation }}">
                        <input type="hidden" name="expense_account_id[]" value="{{ $lineExpenseAccount }}">
                        <x-item-suggest
                          idName="item_id[]"
                          :items="$items"
                          :idValue="(string)$line->item_id"
                          :displayValue="strtoupper(($line->item?->code ?? '').(($line->item?->name) ? ' — '.$line->item->name : ''))"
                          placeholder="Kode / nama barang"
                          variant="default"
                          displayMode="code-name"
                          :showName="true"
                          :showCategory="true"
                          :skipSubmitValidation="true"
                        />
                        <span class="badge rounded-pill mt-1 {{ $lineAllocation === 'expense' ? 'text-bg-warning' : 'text-bg-primary' }}">
                          {{ $lineAllocation === 'expense' ? 'Biaya' . ($line->expenseAccount?->code ? ' · ' . $line->expenseAccount->code : '') : 'Persediaan / HPP' }}
                        </span>
                      </td>

                      <td>
                        <input type="text" name="qty_received[]"
                               class="form-control form-control-sm text-end mono js-qty"
                               value="{{ (float) $line->qty_received }}">
                      </td>
                      <td>
                        <input type="text" name="qty_reject[]"
                               class="form-control form-control-sm text-end mono js-reject"
                               value="{{ (float) $line->qty_reject }}">
                      </td>
                      @if ($canSeeMoney)
                        @php
                            $currentPrice = $line->unit_price;
                            if ((float)$currentPrice == 0 && $line->purchaseOrderLine) {
                                $currentPrice = $line->purchaseOrderLine->unit_price;
                            }
                        @endphp
                        <td>
                          <input type="text" name="unit_price[]"
                                 class="form-control form-control-sm text-end mono js-price"
                                 value="{{ $currentPrice }}">
                        </td>
                      @endif
                      <td>
                        <input type="text" name="unit[]"
                               class="form-control form-control-sm mono"
                               value="{{ $line->unit }}" placeholder="kg/pcs/m">
                      </td>
                      <td>
                        <input type="text" name="line_notes[]"
                               class="form-control form-control-sm"
                               value="{{ $line->notes }}">
                      </td>
                      <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-icon btn-sm js-remove">
                          &times;
                        </button>
                      </td>
                    </tr>
                  @empty
                    <tr class="js-line">
                      <td class="text-center mono js-no">1</td>
                      <td>
                        <x-item-suggest
                          idName="item_id[]"
                          :items="$items"
                          idValue=""
                          displayValue=""
                          placeholder="Kode / nama barang"
                          variant="default"
                          displayMode="code-name"
                          :showName="true"
                          :showCategory="true"
                          :skipSubmitValidation="true"
                        />
                      </td>
                      <td><input type="text" name="qty_received[]" class="form-control form-control-sm text-end mono js-qty" value="0"></td>
                      <td><input type="text" name="qty_reject[]" class="form-control form-control-sm text-end mono js-reject" value="0"></td>
                      @if ($canSeeMoney)
                        <td><input type="text" name="unit_price[]" class="form-control form-control-sm text-end mono js-price" value="0"></td>
                      @endif
                      <td><input type="text" name="unit[]" class="form-control form-control-sm mono" placeholder="kg/pcs/m"></td>
                      <td><input type="text" name="line_notes[]" class="form-control form-control-sm"></td>
                      <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-icon btn-sm js-remove">&times;</button>
                      </td>
                    </tr>
                  @endforelse
                @endif
              </tbody>

              <tfoot class="table-light">
                <tr class="fw-semibold">
                  <td colspan="2" class="text-end">Total</td>
                  <td class="text-end mono" id="js-total-qty">0</td>
                  <td class="text-end mono" id="js-total-reject">0</td>
                  @if ($canSeeMoney)
                    <td class="text-end mono" id="js-total-amount">0</td>
                  @endif
                  <td colspan="{{ $canSeeMoney ? 3 : 2 }}"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-3">
        <a href="{{ route('purchasing.purchase_receipts.show', $purchase_receipt->id) }}"
           class="btn btn-outline-secondary">
          Batal
        </a>
        <button type="submit" class="btn btn-primary">
          Simpan Perubahan
        </button>
      </div>

    </form>

  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  function toNum(v){
    if (v === null || v === undefined) return 0;
    v = String(v).trim().replace(/\s+/g,'');
    if (v.includes(',')){
      v = v.replace(/\./g,'').replace(',', '.');
      return parseFloat(v) || 0;
    }
    if (/^\d{1,3}(\.\d{3})+$/.test(v)){
      v = v.replace(/\./g,'');
      return parseFloat(v) || 0;
    }
    return parseFloat(v) || 0;
  }

  function fmtId(n){
    n = (Math.round((n + Number.EPSILON) * 100) / 100);
    const parts = n.toFixed(2).split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return parts[1] === '00' ? parts[0] : parts[0] + ',' + parts[1];
  }

  function recalc(){
    const body = document.getElementById('grn-lines-body');
    if (!body) return;

    let subtotal = 0;
    let tQty = 0;
    let tRej = 0;

    body.querySelectorAll('tr.js-line').forEach(tr => {
      const qty = toNum(tr.querySelector('.js-qty')?.value);
      const rej = toNum(tr.querySelector('.js-reject')?.value);
      const price = toNum(tr.querySelector('.js-price')?.value);

      tQty += qty;
      tRej += rej;
      subtotal += Math.max(0, qty) * price;
    });

    const discount = toNum(document.querySelector('[name="discount"]')?.value);
    const taxPercent = toNum(document.querySelector('[name="tax_percent"]')?.value);
    const ship = toNum(document.querySelector('[name="shipping_cost"]')?.value);

    const base = Math.max(0, subtotal - Math.max(0, discount));
    const tax = (base * Math.max(0, taxPercent) / 100);
    const grand = base + tax + Math.max(0, ship);

    // footer
    const totalQtyEl = document.getElementById('js-total-qty');
    const totalRejectEl = document.getElementById('js-total-reject');
    const totalAmountEl = document.getElementById('js-total-amount');
    if (totalQtyEl) totalQtyEl.textContent = fmtId(tQty);
    if (totalRejectEl) totalRejectEl.textContent = fmtId(tRej);
    if (totalAmountEl) totalAmountEl.textContent = fmtId(subtotal);

    // summary
    const subtotalEl = document.getElementById('js-subtotal');
    const discountEl = document.getElementById('js-discount');
    const taxEl = document.getElementById('js-tax');
    const shipEl = document.getElementById('js-ship');
    const grandEl = document.getElementById('js-grand');
    if (subtotalEl) subtotalEl.textContent = fmtId(subtotal);
    if (discountEl) discountEl.textContent = fmtId(discount);
    if (taxEl) taxEl.textContent = fmtId(tax);
    if (shipEl) shipEl.textContent = fmtId(ship);
    if (grandEl) grandEl.textContent = fmtId(grand);
  }

  function renumber(){
    const body = document.getElementById('grn-lines-body');
    if (!body) return;
    [...body.querySelectorAll('tr.js-line')].forEach((tr, idx) => {
      const no = tr.querySelector('.js-no');
      if (no) no.textContent = String(idx + 1);
    });
  }

  function cloneRow(){
    const body = document.getElementById('grn-lines-body');
    if (!body) return;

    const first = body.querySelector('tr.js-line');
    if (!first) return;

    const clone = first.cloneNode(true);

    // reset inputs
    clone.querySelectorAll('input').forEach(inp => {
      const name = inp.getAttribute('name') || '';
      if (name === 'qty_received[]' || name === 'qty_reject[]' || name === 'unit_price[]'){
        inp.value = '0';
      } else if (name === 'unit[]' || name === 'line_notes[]'){
        inp.value = '';
      } else if (name === 'po_line_id[]' || name === 'allocation[]' || name === 'expense_account_id[]') {
        inp.value = name === 'allocation[]' ? 'hpp' : '';
      } else {
        // item-suggest input visible & hidden
        if (inp.classList.contains('js-item-suggest-input')) inp.value = '';
        if (inp.classList.contains('js-item-suggest-id')) inp.value = '';
        if (inp.classList.contains('js-item-suggest-category')) inp.value = '';
      }
    });

    const badge = clone.querySelector('.badge');
    if (badge) {
      badge.classList.remove('text-bg-warning');
      badge.classList.add('text-bg-primary');
      badge.textContent = 'Persediaan / HPP';
    }

    // remove "inited" marker so it will re-init
    const wrap = clone.querySelector('.item-suggest-wrap');
    if (wrap){
      wrap.removeAttribute('data-suggest-inited');
      // also randomize input id (avoid duplicated ids)
      const txt = wrap.querySelector('.js-item-suggest-input');
      if (txt) txt.id = 'item-suggest-' + Math.random().toString(16).slice(2,8);
      // hide dropdown
      const dd = wrap.querySelector('.item-suggest-dropdown');
      if (dd) dd.style.display = 'none';
    }

    body.appendChild(clone);

    // re-init item suggest on the new row
    if (window.initItemSuggestInputs) window.initItemSuggestInputs(clone);

    renumber();
    recalc();
  }

  document.addEventListener('DOMContentLoaded', function(){
    const body = document.getElementById('grn-lines-body');
    const btnAdd = document.getElementById('btn-add-line');

    btnAdd?.addEventListener('click', cloneRow);

    body?.addEventListener('click', function(e){
      const btn = e.target.closest('.js-remove');
      if (!btn) return;

      const rows = body.querySelectorAll('tr.js-line');
      if (rows.length > 1){
        btn.closest('tr')?.remove();
      } else {
        // reset single row
        const tr = btn.closest('tr');
        if (!tr) return;
        tr.querySelectorAll('input').forEach(inp => {
          const name = inp.getAttribute('name') || '';
          if (name === 'qty_received[]' || name === 'qty_reject[]' || name === 'unit_price[]'){
            inp.value = '0';
          } else if (name === 'unit[]' || name === 'line_notes[]'){
            inp.value = '';
          } else {
            if (inp.classList.contains('js-item-suggest-input')) inp.value = '';
            if (inp.classList.contains('js-item-suggest-id')) inp.value = '';
            if (inp.classList.contains('js-item-suggest-category')) inp.value = '';
          }
        });
        // re-init (ensure dropdown works again)
        const wrap = tr.querySelector('.item-suggest-wrap');
        if (wrap){
          wrap.removeAttribute('data-suggest-inited');
          if (window.initItemSuggestInputs) window.initItemSuggestInputs(tr);
        }
      }

      renumber();
      recalc();
    });

    // live recalc
    document.addEventListener('input', function(e){
      const name = e.target?.getAttribute?.('name') || '';
      if (name === 'qty_received[]' || name === 'qty_reject[]' || name === 'unit_price[]'
          || name === 'discount' || name === 'tax_percent' || name === 'shipping_cost'){
        recalc();
      }
    });

    // init item suggest if needed + initial calc
    if (window.initItemSuggestInputs) window.initItemSuggestInputs(document);
    renumber();
    recalc();
  });
})();
</script>
@endpush
