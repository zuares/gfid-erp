@extends('layouts.app')

@section('title', 'Pembayaran Supplier')

@php
  $fmt       = fn($n) => number_format((float) $n, 0, ',', '.');
  $typeLabel = ['dp' => 'DP', 'payment' => 'Pelunasan', 'dp_apply' => 'Offset DP', 'loan_apply' => 'Alokasi Pinjaman Supplier'];
@endphp

@push('head')
<style>
  .mono { font-variant-numeric:tabular-nums; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono"; }

  .payment-table tbody td { vertical-align:middle; }
  .payment-table .payment-date { color:#334155; font-size:.82rem; font-weight:700; }
  .payment-table .payment-ref { color:#94a3b8; font-size:.68rem; }
  .payment-link { color:#334155; text-decoration:none; font-weight:650; font-size:.74rem; }
  .payment-link:hover { color:#0f172a; text-decoration:underline; }
  .payment-secondary { color:#64748b; font-size:.72rem; }
  .payment-total { color:#0f172a; font-size:.8rem; font-weight:750; }
  .pay-row:hover td { background:rgba(59,130,246,.035); }
  .pay-row.voided td { opacity:.55; }

  .payment-type-badge {
    border-radius:7px; font-size:.68rem; padding:.16rem .48rem;
    border:1px solid transparent; white-space:nowrap; display:inline-block;
  }
  .payment-type-dp { background:rgba(59,130,246,.1); color:#1d4ed8; border-color:rgba(59,130,246,.4); }
  .payment-type-payment { background:rgba(22,163,74,.1); color:#15803d; border-color:rgba(22,163,74,.4); }
  .payment-type-dp_apply { background:rgba(139,92,246,.1); color:#7c3aed; border-color:rgba(139,92,246,.4); }
  .payment-type-loan_apply { background:rgba(14,165,233,.1); color:#0369a1; border-color:rgba(14,165,233,.4); }
  .payment-type-voided { background:rgba(220,38,38,.08); color:#b91c1c; border-color:rgba(220,38,38,.4); }

  /* PO cards in modal */
  .po-card {
    border:1px solid var(--line); border-radius:10px; padding:.7rem .9rem;
    cursor:pointer; transition:border-color .12s, background .12s;
  }
  .po-card:hover    { border-color:#94a3b8; background:rgba(59,130,246,.03); }
  .po-card.selected { border-color:#2563eb; background:rgba(59,130,246,.05); }
  .po-card-check { width:1rem; height:1rem; pointer-events:none; }
  .allocation-row { border-top:1px solid var(--line); padding-top:.45rem; margin-top:.45rem; }

  /* Tbl link */
  .tbl-link { color:inherit; text-decoration:none; font-weight:600; }
  .tbl-link:hover { text-decoration:underline; color:#2563eb; }

  .payment-filter-controls { gap:.45rem!important; }
  .payment-filter-controls .form-control, .payment-filter-controls .form-select { min-height:34px; }
  .payment-modal { border:1px solid rgba(148,163,184,.2); border-radius:8px; background:var(--card,#fff); overflow:hidden; }
  .payment-modal .modal-header { padding:.85rem 1rem; border-bottom:1px solid rgba(148,163,184,.18); }
  .payment-modal .modal-body { padding:1rem; }
  .payment-modal .modal-title { color:#0f172a; }
  .payment-modal .modal-caption { color:#64748b; font-size:.74rem; }
  .payment-modal .modal-kpi { display:inline-flex; gap:.3rem; align-items:baseline; padding:.2rem .45rem; border-radius:7px; border:1px solid rgba(148,163,184,.25); color:#64748b; font-size:.68rem; }
  .payment-modal .modal-kpi strong { color:#334155; }
  .payment-step-label { color:#64748b; font-size:.67rem; font-weight:800; letter-spacing:.05em; text-transform:uppercase; }

  @media(max-width:767.98px){
    .payment-filter-controls { display:grid!important; grid-template-columns:1fr; }
    .payment-filter-controls > * { width:100%!important; max-width:none!important; }
    .payment-table tbody tr { padding:.68rem .7rem; }
    .payment-table tbody td { padding:0; border:0; }
    .payment-table tbody td + td { margin-top:.3rem; }
    .payment-table .payment-total { font-size:.9rem; }
  }
</style>
@endpush

@section('content')
@php
  $idMonths = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
  $rangeDisplay = '';
  if (request('from') && request('to')) {
      try {
          $f = \Carbon\Carbon::parse(request('from'));
          $t = \Carbon\Carbon::parse(request('to'));
          $rangeDisplay = $f->day.' '.$idMonths[$f->month - 1].' – '.$t->day.' '.$idMonths[$t->month - 1].' '.$t->year;
      } catch (\Exception $e) { $rangeDisplay = request('from').' – '.request('to'); }
  } elseif (request('from')) {
      try {
          $f = \Carbon\Carbon::parse(request('from'));
          $rangeDisplay = $f->day.' '.$idMonths[$f->month - 1].' '.$f->year;
      } catch (\Exception $e) { $rangeDisplay = request('from'); }
  }
  $hasPayFilters = request()->filled('supplier_id') || request()->filled('type')
      || request()->filled('from') || request()->filled('to') || request()->filled('voided');
@endphp

<x-index-layout title="Pembayaran Supplier" subtitle="Pelunasan hutang supplier berdasarkan PO dan GRN.">
  <x-slot name="kpis">
    <span class="kpi"><span class="lbl">Transaksi</span><span class="val mono">{{ $summary['count'] }}</span></span>
    <span class="kpi"><span class="lbl">Pelunasan</span><span class="val mono">Rp {{ $fmt($summary['total_payment']) }}</span></span>
    <span class="kpi"><span class="lbl">DP</span><span class="val mono">Rp {{ $fmt($summary['total_dp']) }}</span></span>
  </x-slot>

  <x-slot name="actions">
    <button class="btn btn-sm btn-ship-primary btn-pill" data-bs-toggle="modal" data-bs-target="#modalBayar" onclick="resetPaymentPicker()">
      <i class="bi bi-plus-lg me-1"></i>Bayar Supplier / Gabungkan PO
    </button>
  </x-slot>

  <x-slot name="filters">
    <div class="filter-bar">
      <div class="filter-summary mb-2"><strong>Filter pembayaran</strong> — cari berdasarkan supplier, tipe, status, atau periode.</div>
      <form method="GET" action="{{ route('purchasing.purchase_payments.index') }}" id="pay-filter-form">
        <input type="hidden" name="from" id="pay-from" value="{{ request('from') }}" data-gf-date="off">
        <input type="hidden" name="to" id="pay-to" value="{{ request('to') }}" data-gf-date="off">
        <div class="d-flex flex-wrap align-items-center payment-filter-controls">
          <select name="supplier_id" class="form-select form-select-sm pay-filter-auto" style="max-width:210px;">
            <option value="">Semua supplier</option>
            @foreach ($suppliers as $s)
              <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
            @endforeach
          </select>
          <select name="type" class="form-select form-select-sm pay-filter-auto" style="max-width:170px;">
            <option value="">Semua tipe</option>
            <option value="payment" @selected(request('type') === 'payment')>Pelunasan</option>
            <option value="dp" @selected(request('type') === 'dp')>DP</option>
            <option value="dp_apply" @selected(request('type') === 'dp_apply')>Offset DP</option>
            <option value="loan_apply" @selected(request('type') === 'loan_apply')>Alokasi Pinjaman</option>
          </select>
          <select name="voided" class="form-select form-select-sm pay-filter-auto" style="max-width:130px;">
            <option value="no" @selected(request('voided', 'no') === 'no')>Aktif</option>
            <option value="yes" @selected(request('voided') === 'yes')>Void</option>
            <option value="" @selected(request('voided') === '')>Semua</option>
          </select>
          <input type="text" id="pay-date-range" value="{{ $rangeDisplay }}" placeholder="Pilih periode…"
                 autocomplete="off" readonly class="form-control form-control-sm" style="max-width:210px;cursor:pointer;" data-gf-date="off">
          @if ($hasPayFilters)
            <a href="{{ route('purchasing.purchase_payments.index') }}" class="btn btn-sm btn-ship-outline btn-pill">
              <i class="bi bi-x-lg me-1"></i>Reset Filter
            </a>
          @endif
        </div>
      </form>
    </div>
  </x-slot>

  <x-slot name="summary">
    <strong>Riwayat Pembayaran</strong> — menampilkan <strong>{{ $payments->total() }}</strong> transaksi dalam filter aktif.
  </x-slot>

  @if ($payments->count() === 0)
    <x-slot name="emptyState">
      <div class="empty">Belum ada pembayaran sesuai filter.</div>
    </x-slot>
  @endif

  <x-slot name="thead">
    <tr>
      <th>Tanggal / Referensi</th>
      <th>Supplier</th>
      <th>PO</th>
      <th>GRN</th>
      <th>Status</th>
      <th>Metode</th>
      <th class="mobile-hide">Akun</th>
      <th class="text-end">Jumlah</th>
      <th class="text-end">Aksi</th>
    </tr>
  </x-slot>

  @foreach ($payments as $pay)
    <tr class="pay-row {{ $pay->voided_at ? 'voided' : '' }}">
      <td>
        <div class="payment-date">{{ \Carbon\Carbon::parse($pay->date)->format('d/m/Y') }}</div>
        @if ($pay->ref_no)
          <div class="payment-ref mono">Ref: {{ $pay->ref_no }}</div>
        @endif
      </td>
      <td><span class="supplier-name">{{ $pay->purchaseOrder?->supplier?->name ?? '—' }}</span></td>
      <td>
        @if ($pay->purchaseOrder)
          <a href="{{ route('purchasing.purchase_orders.show', $pay->purchaseOrder) }}" class="payment-link mono">{{ $pay->purchaseOrder->code }}</a>
        @else <span class="payment-secondary">—</span>
        @endif
      </td>
      <td>
        @if ($pay->purchaseReceipt)
          <a href="{{ route('purchasing.purchase_receipts.show', $pay->purchaseReceipt) }}" class="payment-link mono">{{ $pay->purchaseReceipt->code }}</a>
        @else <span class="payment-secondary">PO-level</span>
        @endif
      </td>
      <td>
        <span class="payment-type-badge {{ $pay->voided_at ? 'payment-type-voided' : 'payment-type-' . $pay->type }}">
          {{ $pay->voided_at ? 'Void' : ($typeLabel[$pay->type] ?? $pay->type) }}
        </span>
      </td>
      <td><span class="payment-secondary">{{ $pay->paymentMethod?->name ?? '—' }}</span></td>
      <td class="mobile-hide"><span class="payment-secondary">{{ $pay->cashAccount?->name ?? '—' }}</span></td>
      <td class="text-end"><span class="payment-total mono">Rp {{ $fmt($pay->amount) }}</span></td>
      <td class="text-end">
        @if (!$pay->voided_at && $pay->purchaseOrder)
          <form method="POST" action="{{ route('purchasing.purchase_orders.payments.void', [$pay->purchaseOrder, $pay]) }}"
                onsubmit="return confirm('VOID pembayaran ini?\nTindakan ini tidak bisa dibatalkan.')">
            @csrf
            <button type="submit" class="btn btn-sm btn-ship-outline" style="font-size:.7rem;padding:.16rem .55rem;">Void</button>
          </form>
        @endif
      </td>
    </tr>
  @endforeach

  <x-slot name="pagination">
    {{ $payments->withQueryString()->links() }}
  </x-slot>
</x-index-layout>

{{-- ── MODAL BAYAR SUPPLIER ──────────────────────────────────────── --}}
<div class="modal fade" id="modalBayar" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content payment-modal">
      <div class="modal-header">
        <div>
          <h6 class="modal-title fw-semibold mb-1">Bayar Supplier</h6>
          <div class="modal-caption">Gabungkan beberapa PO dari supplier yang sama tanpa menghilangkan detail GRN.</div>
          <div class="d-flex gap-1 flex-wrap mt-2">
            <span class="modal-kpi">PO terbuka <strong class="mono">{{ $openPos->count() }}</strong></span>
            <span class="modal-kpi">Aturan <strong>1 supplier</strong></span>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        {{-- Step 1: Pilih PO --}}
        <div class="mb-3">
          <div class="payment-step-label mb-1">Langkah 1 · Pilih dokumen</div>
          <label class="form-label small fw-semibold">Pilih PO yang akan dibayar <span class="text-muted fw-normal">(bisa beberapa, supplier wajib sama)</span></label>
          <input type="search" id="poSearch" class="form-control form-control-sm mb-2"
                 placeholder="Cari kode PO atau nama supplier…" autocomplete="off">
          <div id="poList" style="display:grid;gap:.45rem;max-height:220px;overflow-y:auto;">
            @forelse ($openPos as $po)
            @php $outstanding = (float) ($po->payment_outstanding ?? 0); @endphp
            <div class="po-card" data-po-id="{{ $po->id }}" data-po-code="{{ $po->code }}"
                 data-supplier-id="{{ $po->supplier_id }}" data-supplier="{{ $po->supplier?->name }}"
                 data-po-date="{{ \Carbon\Carbon::parse($po->date)->format('d/m/Y') }}"
                 data-items="{{ $po->lines->pluck('item.name')->filter()->join(', ') }}"
                 data-outstanding="{{ $outstanding }}" onclick="togglePo(this)">
              <div class="d-flex align-items-start gap-2">
                <input type="checkbox" class="po-card-check mt-1" tabindex="-1" aria-hidden="true">
                <div class="d-flex justify-content-between align-items-start flex-grow-1">
                <div>
                  <div class="fw-semibold mono" style="font-size:.88rem;">{{ $po->code }}</div>
                  <div class="text-muted" style="font-size:.76rem;">
                    {{ $po->supplier?->name }} · {{ \Carbon\Carbon::parse($po->date)->format('d/m/Y') }}
                  </div>
                  @if ($po->lines->pluck('item.name')->filter()->isNotEmpty())
                    <div class="text-muted" style="font-size:.7rem;">Item: {{ $po->lines->pluck('item.name')->filter()->join(', ') }}</div>
                  @endif
                </div>
                <div class="text-end">
                  <div class="mono fw-bold text-danger" style="font-size:.88rem;">
                    Rp {{ number_format($outstanding, 0, ',', '.') }}
                  </div>
                  <div class="text-muted" style="font-size:.7rem;">outstanding</div>
                </div>
                </div>
              </div>
            </div>
            @empty
            <div class="text-muted text-center py-3 small">Tidak ada PO dengan hutang outstanding.</div>
            @endforelse
          </div>
        </div>

        {{-- Step 2: Form Bayar --}}
        <form id="payForm" method="POST" action="" style="display:none;">
          @csrf
          <input type="hidden" name="type" value="payment">
          <div id="combinedFields"></div>

          <div class="payment-step-label mb-1">Langkah 2 · Atur pembayaran</div>
          <div id="selectedPoInfo" class="mb-3 p-2 rounded"
               style="background:rgba(59,130,246,.05);border:1px solid rgba(59,130,246,.2);font-size:.85rem;"></div>

          <div class="row g-3">
            <div class="col-sm-6">
              <label class="form-label small fw-semibold">Tanggal <span class="text-danger">*</span></label>
              <input type="date" name="date" class="form-control form-control-sm"
                     value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label small fw-semibold"><span id="amountLabel">Jumlah</span> <span class="text-danger">*</span></label>
              <div class="input-group input-group-sm">
                <span class="input-group-text">Rp</span>
                <input type="text" name="amount" id="payAmount" class="form-control"
                       placeholder="0" required>
              </div>
            </div>
            <div class="col-sm-6">
              <label class="form-label small fw-semibold">Metode Bayar <span class="text-danger">*</span></label>
              <select name="payment_method_id" id="payMethod" class="form-select form-select-sm"
                      required onchange="updateCashAccount(this)">
                <option value="">— Pilih —</option>
                @foreach ($paymentMethods->whereIn('mode', ['cash','transfer']) as $pm)
                  <option value="{{ $pm->id }}" data-mode="{{ $pm->mode }}"
                          data-default-account="{{ $pm->default_cash_account_id }}">
                    {{ $pm->name }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-sm-6">
              <label class="form-label small fw-semibold">Bayar dari Akun <span class="text-danger">*</span></label>
              <select name="cash_account_id" id="payCashAccount" class="form-select form-select-sm" required>
                <option value="">— Pilih akun —</option>
                @foreach ($cashAccounts as $acc)
                  <option value="{{ $acc->id }}" data-code="{{ $acc->code }}">
                    {{ $acc->code }} – {{ $acc->name }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-sm-6">
              <label class="form-label small fw-semibold">No. Referensi</label>
              <input type="text" name="ref_no" class="form-control form-control-sm" placeholder="opsional">
            </div>
            <div class="col-sm-6">
              <label class="form-label small fw-semibold">Catatan</label>
              <input type="text" name="notes" class="form-control form-control-sm" placeholder="opsional">
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-3 pt-3" style="border-top:1px solid var(--line);">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-sm btn-primary" id="submitPaymentButton">Simpan Pembayaran</button>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const fmt = n => Math.round(n).toLocaleString('id-ID');

function selectPo(el) {
    togglePo(el);
}

function selectedPoCards() {
    return [...document.querySelectorAll('.po-card.selected')];
}

function togglePo(el) {
    const selected = selectedPoCards();
    const willSelect = !el.classList.contains('selected');
    const supplierId = el.dataset.supplierId;
    const currentSupplierId = selected[0]?.dataset.supplierId;

    if (willSelect && currentSupplierId && currentSupplierId !== supplierId) {
        alert('PO yang digabung harus berasal dari supplier yang sama.');
        return;
    }

    el.classList.toggle('selected', willSelect);
    el.querySelector('.po-card-check').checked = willSelect;
    syncPaymentSelection();
}

function syncPaymentSelection() {
    const cards = selectedPoCards();
    const form = document.getElementById('payForm');
    const info = document.getElementById('selectedPoInfo');
    const fields = document.getElementById('combinedFields');
    const amount = document.getElementById('payAmount');
    const label = document.getElementById('amountLabel');
    const submit = document.getElementById('submitPaymentButton');

    fields.innerHTML = '';
    if (!cards.length) {
        form.style.display = 'none';
        return;
    }

    const total = cards.reduce((sum, card) => sum + parseFloat(card.dataset.outstanding || 0), 0);
    const isCombined = cards.length > 1;
    form.action = isCombined
        ? '{{ route('purchasing.purchase_payments.combine') }}'
        : '{{ url("/purchasing/purchase-orders") }}/' + cards[0].dataset.poId + '/payments';
    amount.value = Math.round(total);
    amount.readOnly = isCombined;
    label.textContent = isCombined ? 'Total gabungan' : 'Jumlah';
    submit.textContent = isCombined ? 'Simpan Pembayaran Gabungan' : 'Simpan Pembayaran';

    let html = isCombined
        ? '<div class="fw-semibold mb-1">' + cards.length + ' PO supplier ' + cards[0].dataset.supplier + '</div>'
        : '';

    cards.forEach(card => {
        const out = parseFloat(card.dataset.outstanding || 0);
        if (isCombined) {
            const inputName = 'amounts[' + card.dataset.poId + ']';
            fields.insertAdjacentHTML('beforeend', '<input type="hidden" name="purchase_order_ids[]" value="' + card.dataset.poId + '">');
            html += '<div class="allocation-row d-flex justify-content-between align-items-center gap-2">'
                + '<div><strong>' + card.dataset.poCode + '</strong><div class="text-muted" style="font-size:.72rem;">Tanggal ' + card.dataset.poDate
                + (card.dataset.items ? ' · ' + card.dataset.items : '') + '</div></div>'
                + '<div class="input-group input-group-sm" style="max-width:180px;"><span class="input-group-text">Rp</span>'
                + '<input type="text" class="form-control combined-allocation" name="' + inputName + '" value="' + Math.round(out)
                + '" data-outstanding="' + out + '" inputmode="decimal"></div></div>';
        } else {
            html += '<strong>' + card.dataset.poCode + '</strong> — ' + card.dataset.supplier
                + ' &nbsp;·&nbsp; Outstanding: <strong class="text-danger">Rp ' + fmt(out) + '</strong>';
        }
    });
    info.innerHTML = html;
    form.style.display = 'block';

    document.querySelectorAll('.combined-allocation').forEach(input => {
        input.addEventListener('input', updateCombinedTotal);
    });
    updateCombinedTotal();
}

function updateCombinedTotal() {
    const inputs = [...document.querySelectorAll('.combined-allocation')];
    if (!inputs.length) return;
    const total = inputs.reduce((sum, input) => sum + parseAmount(input.value), 0);
    document.getElementById('payAmount').value = Math.round(total);
}

function parseAmount(value) {
    const raw = String(value || '').replace(/\s/g, '');
    if (raw.includes(',')) return parseFloat(raw.replace(/\./g, '').replace(',', '.')) || 0;
    if (/^\d{1,3}(\.\d{3})+$/.test(raw)) return parseFloat(raw.replace(/\./g, '')) || 0;
    return parseFloat(raw) || 0;
}

function resetPaymentPicker() {
    document.querySelectorAll('.po-card.selected').forEach(card => {
        card.classList.remove('selected');
        card.querySelector('.po-card-check').checked = false;
    });
    document.getElementById('payForm').reset();
    document.getElementById('payForm').style.display = 'none';
    document.getElementById('combinedFields').innerHTML = '';
}

function updateCashAccount(sel) {
    const defaultId = sel.selectedOptions[0]?.dataset.defaultAccount;
    if (defaultId) document.getElementById('payCashAccount').value = defaultId;
}

document.getElementById('poSearch').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.po-card').forEach(card => {
        const text = (card.dataset.poCode + ' ' + card.dataset.supplier).toLowerCase();
        card.style.display = text.includes(q) ? '' : 'none';
    });
});

// auto-submit selects
document.querySelectorAll('.pay-filter-auto').forEach(el =>
    el.addEventListener('change', () => document.getElementById('pay-filter-form').submit())
);

// Flatpickr range
const ID_MONTHS = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
function fmtDate(d, withYear) {
    return d.getDate() + ' ' + ID_MONTHS[d.getMonth()] + (withYear ? ' ' + d.getFullYear() : '');
}
function fmtRange(dates) {
    if (dates.length === 2) {
        const sameYear = dates[0].getFullYear() === dates[1].getFullYear();
        return fmtDate(dates[0], !sameYear) + ' – ' + fmtDate(dates[1], true);
    }
    if (dates.length === 1) return fmtDate(dates[0], true) + ' …';
    return '';
}
const payForm      = document.getElementById('pay-filter-form');
const payFromHidden = document.getElementById('pay-from');
const payToHidden   = document.getElementById('pay-to');
const payRangeInput = document.getElementById('pay-date-range');
if (payRangeInput) {
    flatpickr(payRangeInput, {
        mode: 'range', dateFormat: 'Y-m-d', locale: { firstDayOfWeek: 1 }, allowInput: false,
        defaultDate: [payFromHidden.value, payToHidden.value].filter(Boolean),
        onChange: function (selectedDates, dateStr, fp) {
            fp.input.value = fmtRange(selectedDates);
            if (selectedDates.length === 1) {
                payFromHidden.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                payToHidden.value = '';
            } else if (selectedDates.length === 2) {
                payFromHidden.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                payToHidden.value   = flatpickr.formatDate(selectedDates[1], 'Y-m-d');
                payForm.submit();
            }
        },
        onReady: function (selectedDates, dateStr, fp) {
            fp.input.classList.add('gf-date-input');
            if (selectedDates.length) fp.input.value = fmtRange(selectedDates);
        },
    });
}
</script>
@endpush
