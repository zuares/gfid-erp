@extends('layouts.app')

@section('title', 'Pembayaran Supplier')

@php
  $fmt       = fn($n) => number_format((float) $n, 0, ',', '.');
  $typeLabel = ['dp' => 'DP', 'payment' => 'Pelunasan', 'dp_apply' => 'Offset DP'];
@endphp

@push('head')
<style>
  .page-wrap { max-width:1080px; margin-inline:auto; padding-bottom:3rem; }
  .mono { font-variant-numeric:tabular-nums; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono"; }

  /* Summary row */
  .card-info    { background:var(--card); border:1px solid var(--line); border-radius:14px; padding:1rem 1.15rem; }
  .summary-col  { padding:.8rem 1rem; border-right:1px solid var(--line); }
  .summary-col:last-child { border-right:none; }
  .summary-col-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.07em; color:var(--muted); font-weight:600; margin-bottom:.25rem; }
  .summary-col-value { font-size:.9rem; font-weight:700; }

  /* Filter */
  .card-filter { background:var(--card); border:1px solid var(--line); border-radius:14px; padding:.75rem .95rem; }

  /* Table */
  .card-section { background:var(--card); border:1px solid var(--line); border-radius:14px; overflow:hidden; }
  .card-section-header {
    padding:.6rem 1rem; border-bottom:1px solid var(--line);
    font-size:.72rem; text-transform:uppercase; letter-spacing:.07em;
    color:var(--muted); font-weight:600;
    display:flex; align-items:center; justify-content:space-between;
  }
  .table thead th {
    border-bottom:1px solid var(--line);
    font-size:.72rem; text-transform:uppercase; letter-spacing:.07em;
    color:var(--muted); padding:.5rem .75rem; white-space:nowrap; font-weight:600;
  }
  .table tbody td { vertical-align:middle; font-size:.83rem; padding:.48rem .75rem; border-bottom:1px solid var(--line); }
  .table tbody tr:last-child td { border-bottom:none; }
  .pay-row:hover td { background:rgba(59,130,246,.035); }
  .pay-row.voided td { opacity:.55; }

  /* Badges */
  .badge-status {
    border-radius:999px; font-size:.7rem; padding:.1rem .55rem;
    border:1px solid transparent; white-space:nowrap; display:inline-block;
  }
  .badge-dp       { background:rgba(59,130,246,.1);  color:#1d4ed8; border-color:rgba(59,130,246,.4); }
  .badge-payment  { background:rgba(22,163,74,.1);   color:#15803d; border-color:rgba(22,163,74,.4); }
  .badge-dp_apply { background:rgba(139,92,246,.1);  color:#7c3aed; border-color:rgba(139,92,246,.4); }
  .badge-voided   { background:rgba(220,38,38,.08);  color:#b91c1c; border-color:rgba(220,38,38,.4); }

  /* PO cards in modal */
  .po-card {
    border:1px solid var(--line); border-radius:10px; padding:.7rem .9rem;
    cursor:pointer; transition:border-color .12s, background .12s;
  }
  .po-card:hover    { border-color:#94a3b8; background:rgba(59,130,246,.03); }
  .po-card.selected { border-color:#2563eb; background:rgba(59,130,246,.05); }

  /* Tbl link */
  .tbl-link { color:inherit; text-decoration:none; font-weight:600; }
  .tbl-link:hover { text-decoration:underline; color:#2563eb; }

  @media(max-width:767.98px){
    .page-wrap { padding-inline:.75rem; }
    .summary-col { border-right:none; border-bottom:1px solid var(--line); }
    .summary-col:last-child { border-bottom:none; }
  }
</style>
@endpush

@section('content')
<div class="page-wrap py-3">

  {{-- HEADER --}}
  <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
    <div>
      <h2 class="mb-0">Pembayaran Supplier</h2>
      <div class="text-muted small">Jurnal: Dr 2101 Hutang Dagang / Cr Bank</div>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalBayar">
      <i class="bi bi-plus me-1"></i>Bayar Supplier
    </button>
  </div>

  {{-- FLASH --}}
  @if (session('success'))
    <div class="alert alert-success py-2 small mb-3">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger py-2 small mb-3">{{ session('error') }}</div>
  @endif

  {{-- SUMMARY ROW --}}
  <div class="card-info mb-3">
    <div class="row g-0">
      <div class="col-6 col-md-4 summary-col">
        <div class="summary-col-label">Total Transaksi</div>
        <div class="summary-col-value">{{ $summary['count'] }}</div>
      </div>
      <div class="col-6 col-md-4 summary-col">
        <div class="summary-col-label">Total Pelunasan</div>
        <div class="summary-col-value mono">Rp {{ $fmt($summary['total_payment']) }}</div>
      </div>
      <div class="col-6 col-md-4 summary-col">
        <div class="summary-col-label">Total DP</div>
        <div class="summary-col-value mono">Rp {{ $fmt($summary['total_dp']) }}</div>
      </div>
    </div>
  </div>

  {{-- FILTER --}}
  @php
    $idMonths = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $rangeDisplay = '';
    if (request('from') && request('to')) {
        try {
            $f = \Carbon\Carbon::parse(request('from'));
            $t = \Carbon\Carbon::parse(request('to'));
            $rangeDisplay = $f->day . ' ' . $idMonths[$f->month-1]
                . ' – ' . $t->day . ' ' . $idMonths[$t->month-1] . ' ' . $t->year;
        } catch (\Exception $e) { $rangeDisplay = request('from') . ' – ' . request('to'); }
    } elseif (request('from')) {
        try {
            $f = \Carbon\Carbon::parse(request('from'));
            $rangeDisplay = $f->day . ' ' . $idMonths[$f->month-1] . ' ' . $f->year;
        } catch (\Exception $e) { $rangeDisplay = request('from'); }
    }
  @endphp
  <div class="card-filter mb-3">
    <form method="GET" action="{{ route('purchasing.purchase_payments.index') }}" id="pay-filter-form">
      <input type="hidden" name="from" id="pay-from" value="{{ request('from') }}" data-gf-date="off">
      <input type="hidden" name="to"   id="pay-to"   value="{{ request('to') }}"   data-gf-date="off">

      <div class="d-flex flex-wrap gap-2 align-items-center">

        <select name="supplier_id" class="form-select form-select-sm pay-filter-auto" style="max-width:195px;">
          <option value="">Semua supplier</option>
          @foreach ($suppliers as $s)
            <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
          @endforeach
        </select>

        <select name="type" class="form-select form-select-sm pay-filter-auto" style="max-width:140px;">
          <option value="">Semua tipe</option>
          <option value="payment"  @selected(request('type') === 'payment')>Pelunasan</option>
          <option value="dp"       @selected(request('type') === 'dp')>DP</option>
          <option value="dp_apply" @selected(request('type') === 'dp_apply')>Offset DP</option>
        </select>

        <select name="voided" class="form-select form-select-sm pay-filter-auto" style="max-width:120px;">
          <option value="no"  @selected(request('voided', 'no') === 'no')>Aktif</option>
          <option value="yes" @selected(request('voided') === 'yes')>Void</option>
          <option value=""    @selected(request('voided') === '')>Semua</option>
        </select>

        <input type="text" id="pay-date-range" value="{{ $rangeDisplay }}"
               placeholder="Pilih tanggal…" autocomplete="off" readonly
               class="form-control form-control-sm" style="max-width:195px;cursor:pointer;"
               data-gf-date="off">

        <a href="{{ route('purchasing.purchase_payments.index') }}"
           class="btn btn-sm btn-outline-secondary" style="font-size:.78rem;padding:.25rem .65rem;">
          <i class="bi bi-x me-1"></i>Reset
        </a>
      </div>
    </form>
  </div>

  {{-- TABLE --}}
  <div class="card-section">
    <div class="card-section-header">
      <span>Riwayat Pembayaran</span>
      <span style="font-weight:700;color:var(--body);font-size:.78rem;">{{ $payments->total() }} transaksi</span>
    </div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Supplier</th>
            <th>No PO</th>
            <th>Tipe</th>
            <th>Metode</th>
            <th>Akun</th>
            <th class="text-end">Jumlah</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($payments as $pay)
          <tr class="pay-row {{ $pay->voided_at ? 'voided' : '' }}">
            <td class="mono text-muted" style="white-space:nowrap;">
              {{ \Carbon\Carbon::parse($pay->date)->format('d/m/Y') }}
            </td>
            <td>{{ $pay->purchaseOrder?->supplier?->name ?? '—' }}</td>
            <td>
              @if ($pay->purchaseOrder)
                <a href="{{ route('purchasing.purchase_orders.show', $pay->purchaseOrder) }}" class="tbl-link mono">
                  {{ $pay->purchaseOrder->code }}
                </a>
              @else —
              @endif
            </td>
            <td>
              <span class="badge-status {{ $pay->voided_at ? 'badge-voided' : 'badge-' . $pay->type }}">
                {{ $pay->voided_at ? 'Void' : ($typeLabel[$pay->type] ?? $pay->type) }}
              </span>
            </td>
            <td class="text-muted">{{ $pay->paymentMethod?->name ?? '—' }}</td>
            <td class="text-muted">{{ $pay->cashAccount?->name ?? '—' }}</td>
            <td class="text-end mono fw-semibold">Rp {{ $fmt($pay->amount) }}</td>
            <td>
              @if (!$pay->voided_at)
                <form method="POST"
                      action="{{ route('purchasing.purchase_orders.payments.void', [$pay->purchaseOrder, $pay]) }}"
                      onsubmit="return confirm('VOID pembayaran ini?\nTindakan ini tidak bisa dibatalkan.')">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-outline-danger"
                          style="font-size:.7rem;padding:.15rem .55rem;">
                    Void
                  </button>
                </form>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="text-center text-muted py-4">Belum ada pembayaran.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    @if ($payments->hasPages())
    <div class="px-3 py-2 border-top" style="font-size:.8rem;">
      {{ $payments->withQueryString()->links() }}
    </div>
    @endif
  </div>

</div>

{{-- ── MODAL BAYAR SUPPLIER ──────────────────────────────────────── --}}
<div class="modal fade" id="modalBayar" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="border-bottom:1px solid var(--line);padding:.85rem 1.15rem;">
        <h6 class="modal-title fw-semibold mb-0">Bayar Supplier</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:1.1rem 1.15rem;">

        {{-- Step 1: Pilih PO --}}
        <div class="mb-3">
          <label class="form-label small fw-semibold">Pilih PO yang akan dibayar</label>
          <input type="search" id="poSearch" class="form-control form-control-sm mb-2"
                 placeholder="Cari kode PO atau nama supplier…" autocomplete="off">
          <div id="poList" style="display:grid;gap:.45rem;max-height:220px;overflow-y:auto;">
            @forelse ($openPos as $po)
            @php $outstanding = \App\Models\PurchaseOrder::normalizePaymentRemainder((float) $po->grand_total - (float) $po->paid_amount); @endphp
            <div class="po-card" data-po-id="{{ $po->id }}" data-po-code="{{ $po->code }}"
                 data-supplier="{{ $po->supplier?->name }}" data-outstanding="{{ $outstanding }}"
                 onclick="selectPo(this)">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="fw-semibold mono" style="font-size:.88rem;">{{ $po->code }}</div>
                  <div class="text-muted" style="font-size:.76rem;">
                    {{ $po->supplier?->name }} · {{ \Carbon\Carbon::parse($po->date)->format('d/m/Y') }}
                  </div>
                </div>
                <div class="text-end">
                  <div class="mono fw-bold text-danger" style="font-size:.88rem;">
                    Rp {{ number_format($outstanding, 0, ',', '.') }}
                  </div>
                  <div class="text-muted" style="font-size:.7rem;">outstanding</div>
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

          <div id="selectedPoInfo" class="mb-3 p-2 rounded"
               style="background:rgba(59,130,246,.05);border:1px solid rgba(59,130,246,.2);font-size:.85rem;"></div>

          <div class="row g-3">
            <div class="col-sm-6">
              <label class="form-label small fw-semibold">Tanggal <span class="text-danger">*</span></label>
              <input type="date" name="date" class="form-control form-control-sm"
                     value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label small fw-semibold">Jumlah <span class="text-danger">*</span></label>
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
            <button type="submit" class="btn btn-sm btn-primary">Simpan Pembayaran</button>
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
    document.querySelectorAll('.po-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');

    const route = '{{ url("/purchasing/purchase-orders") }}/' + el.dataset.poId + '/payments';
    document.getElementById('payForm').action = route;

    const out = parseFloat(el.dataset.outstanding);
    document.getElementById('payAmount').value = Math.round(out);
    document.getElementById('selectedPoInfo').innerHTML =
        '<strong>' + el.dataset.poCode + '</strong> — ' + el.dataset.supplier +
        ' &nbsp;·&nbsp; Outstanding: <strong class="text-danger">Rp ' + fmt(out) + '</strong>';

    document.getElementById('payForm').style.display = 'block';
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
