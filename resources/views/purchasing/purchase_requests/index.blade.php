@extends('layouts.app')

@section('title', 'Purchase Requests')

@php
    $user        = auth()->user();
    $canManagePr = $user && ($user->isOwner() || in_array($user->role, ['admin'], true));
    $activeStatus = request('status', '');

    $idMonths = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $rangeDisplay = '';
    if (request('from_date') && request('to_date')) {
        try {
            $f = \Carbon\Carbon::parse(request('from_date'));
            $t = \Carbon\Carbon::parse(request('to_date'));
            $rangeDisplay = $f->day . ' ' . $idMonths[$f->month-1]
                . ' – ' . $t->day . ' ' . $idMonths[$t->month-1] . ' ' . $t->year;
        } catch (\Exception $e) {
            $rangeDisplay = request('from_date') . ' – ' . request('to_date');
        }
    } elseif (request('from_date')) {
        try {
            $f = \Carbon\Carbon::parse(request('from_date'));
            $rangeDisplay = $f->day . ' ' . $idMonths[$f->month-1] . ' ' . $f->year;
        } catch (\Exception $e) {
            $rangeDisplay = request('from_date');
        }
    }

    $hasFilters = request()->filled('search') || request()->filled('status')
               || request()->filled('supplier_id') || request()->filled('from_date')
               || request()->filled('to_date');
@endphp

@push('head')
<style>
  .page-wrap { max-width:1080px; margin-inline:auto; padding-bottom:3rem; }
  .mono { font-variant-numeric:tabular-nums; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono"; }

  /* Filter card */
  .card-filter {
    background:var(--card);
    border:1px solid var(--line);
    border-radius:14px;
    padding:.7rem 1rem;
  }

  /* Table card */
  .card-section { background:var(--card); border-radius:14px; border:1px solid var(--line); overflow:hidden; }
  .card-section-header {
    padding:.6rem 1rem;
    border-bottom:1px solid var(--line);
    font-size:.72rem; text-transform:uppercase; letter-spacing:.07em;
    color:var(--muted); font-weight:600;
  }

  /* Summary pills */
  .pr-pill {
    display:inline-flex; align-items:center; gap:.3rem;
    padding:.2rem .6rem; border:1px solid var(--line); border-radius:999px;
    color:var(--muted); text-decoration:none; font-size:.74rem;
    background:var(--card); white-space:nowrap;
    transition:border-color .15s, color .15s, background .15s;
  }
  .pr-pill:hover    { color:#1d4ed8; border-color:#93c5fd; background:rgba(37,99,235,.05); }
  .pr-pill.is-active { color:#1d4ed8; border-color:#60a5fa; background:rgba(37,99,235,.08); font-weight:600; }
  .pr-pill-count { font-weight:700; }

  /* Table */
  .pr-table thead th {
    background:color-mix(in srgb,var(--card) 90%,var(--bg) 10%);
    border-bottom:1px solid var(--line);
    font-size:.72rem; text-transform:uppercase; letter-spacing:.05em;
    color:var(--muted); padding:.5rem .75rem; white-space:nowrap;
  }
  .pr-table tbody td { border-bottom:1px solid var(--line); vertical-align:middle; padding:.45rem .75rem; font-size:.82rem; }
  .pr-table tbody tr:last-child td { border-bottom:0; }
  .pr-row { cursor:pointer; }
  .pr-row:hover td { background:color-mix(in srgb,var(--card) 94%,var(--accent,#2563eb) 6%); }

  /* Status badges — selaras PO */
  .pr-badge { display:inline-flex; border-radius:999px; font-size:.7rem; padding:.1rem .55rem; border:1px solid transparent; white-space:nowrap; }
  .pr-draft     { background:rgba(148,163,184,.12);color:#64748b;border-color:rgba(148,163,184,.5); }
  .pr-approved  { background:rgba(22,163,74,.1);color:#15803d;border-color:rgba(22,163,74,.45); }
  .pr-rejected  { background:rgba(220,38,38,.08);color:#b91c1c;border-color:rgba(220,38,38,.45); }
  .pr-converted { background:rgba(59,130,246,.1);color:#1d4ed8;border-color:rgba(59,130,246,.45); }
  .pr-cancelled { background:rgba(100,116,139,.08);color:#475569;border-color:rgba(100,116,139,.4); }

  /* Items preview */
  .pr-item-line { display:flex; justify-content:space-between; gap:.5rem; padding:.1rem 0; }
  .pr-item-name { font-size:.8rem; font-weight:600; }
  .pr-item-code { color:var(--muted); font-size:.7rem; }
  .pr-item-qty  { font-size:.76rem; flex:0 0 auto; font-variant-numeric:tabular-nums; }
  .pr-items-toggle { font-size:.72rem; color:var(--muted); cursor:pointer; text-decoration:none; }
  .pr-po-link { font-size:.73rem; text-decoration:none; font-weight:600; }

  @media (max-width:767.98px){
    .page-wrap { padding-inline:.75rem; }
    .d-col-hide { display:none !important; }
  }
</style>
@endpush

@section('content')
<div class="page-wrap py-3">

  {{-- HEADER --}}
  <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
    <div>
      <h2 class="mb-0 lh-1" style="font-size:1.35rem;">Purchase Requests</h2>
      <div class="text-muted mt-1" style="font-size:.82rem;">Daftar kebutuhan barang sebelum dibuatkan PO.</div>
    </div>
    <a href="{{ route('purchasing.purchase_requests.create') }}" class="btn btn-primary btn-sm flex-shrink-0">
      <i class="bi bi-plus me-1"></i>PR Baru
    </a>
  </div>

  @if (session('success'))<div class="alert alert-success py-2 small mb-3">{{ session('success') }}</div>@endif
  @if (session('error'))<div class="alert alert-danger py-2 small mb-3">{{ session('error') }}</div>@endif

  {{-- FILTER --}}
  <div class="card-filter mb-3">
    <form id="pr-filter-form" method="GET" action="{{ route('purchasing.purchase_requests.index') }}">
      <input type="hidden" name="from_date" id="pr-from-date" value="{{ request('from_date') }}">
      <input type="hidden" name="to_date"   id="pr-to-date"   value="{{ request('to_date') }}">

      {{-- Summary pills --}}
      <div class="d-flex flex-wrap gap-1 mb-2">
        @foreach ([
          ''          => ['Semua',    $summary['total']],
          'draft'     => ['Draft',    $summary['draft']],
          'approved'  => ['Siap PO',  $summary['approved']],
          'converted' => ['Sudah PO', $summary['converted']],
          'rejected'  => ['Ditolak',  $summary['rejected']],
        ] as $sKey => [$sLabel, $sCount])
          <a href="{{ route('purchasing.purchase_requests.index', $sKey ? ['status' => $sKey] : []) }}"
             class="pr-pill {{ $activeStatus === $sKey ? 'is-active' : '' }}">
            {{ $sLabel }} <span class="pr-pill-count">{{ $sCount }}</span>
          </a>
        @endforeach
      </div>

      {{-- Filter row --}}
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <input type="text" name="search" id="pr-search"
               value="{{ request('search') }}"
               placeholder="Cari kode / item…"
               class="form-control form-control-sm" style="max-width:180px;"
               autocomplete="off" />

        <select name="status" class="form-select form-select-sm pr-filter-auto" style="max-width:140px;">
          <option value="">Semua status</option>
          @foreach (['draft'=>'Draft','approved'=>'Siap PO','converted'=>'Sudah PO','rejected'=>'Ditolak','cancelled'=>'Dibatalkan'] as $v => $l)
            <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
          @endforeach
        </select>

        <select name="supplier_id" class="form-select form-select-sm pr-filter-auto" style="max-width:160px;">
          <option value="">Semua supplier</option>
          @foreach ($suppliers as $sup)
            <option value="{{ $sup->id }}" @selected((string)request('supplier_id') === (string)$sup->id)>{{ $sup->name }}</option>
          @endforeach
        </select>

        <input type="text" id="pr-date-range" value="{{ $rangeDisplay }}"
               placeholder="Pilih tanggal…"
               class="form-control form-control-sm" style="max-width:190px;cursor:pointer;"
               data-gf-date="off" readonly />

        @if ($hasFilters)
          <a href="{{ route('purchasing.purchase_requests.index') }}"
             class="btn btn-sm btn-outline-secondary" style="font-size:.78rem;padding:.25rem .65rem;">
            <i class="bi bi-x me-1"></i>Reset
          </a>
        @endif
      </div>
    </form>
  </div>

  {{-- TABLE --}}
  <div class="card-section">
    <div class="card-section-header d-flex justify-content-between align-items-center">
      <span>Daftar PR</span>
      <span>{{ $prs->total() }} dokumen</span>
    </div>

    <div style="overflow-x:auto;">
      <table class="table table-hover table-sm mb-0 pr-table">
        <thead>
          <tr>
            <th>PR</th>
            <th class="d-col-hide" style="width:32%;">Kebutuhan Barang</th>
            <th class="d-col-hide">Supplier</th>
            <th>Status</th>
            <th class="text-end" style="width:40px;"></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($prs as $pr)
            @php
              $actualSuppliers = $pr->lines->pluck('supplier')->filter()->unique('id');
              $badgeClass = 'pr-badge pr-' . $pr->status;
            @endphp
            <tr class="pr-row" data-href="{{ route('purchasing.purchase_requests.show', $pr) }}">
              <td>
                <span class="fw-semibold mono" style="font-size:.82rem;white-space:nowrap;">{{ $pr->code }}</span>
                <div class="text-muted mono" style="font-size:.72rem;">{{ $pr->date?->format('d/m/Y') }}</div>
                @if ($pr->requestedBy)
                  <div class="text-muted" style="font-size:.72rem;">{{ $pr->requestedBy->name }}</div>
                @endif
                @if ($pr->purchaseOrders->isNotEmpty())
                  <div class="mt-1 d-flex flex-wrap gap-1">
                    @foreach ($pr->purchaseOrders->take(2) as $order)
                      <a href="{{ route('purchasing.purchase_orders.show', $order) }}"
                         class="pr-po-link" onclick="event.stopPropagation();">{{ $order->code }}</a>
                    @endforeach
                    @if ($pr->purchaseOrders->count() > 2)
                      <span class="text-muted" style="font-size:.72rem;">+{{ $pr->purchaseOrders->count()-2 }}</span>
                    @endif
                  </div>
                @endif
              </td>

              <td class="d-col-hide">
                @include('purchasing.purchase_requests._index_item_summary', ['surface' => 'desktop'])
              </td>

              <td class="d-col-hide" style="font-size:.8rem;">
                @if ($actualSuppliers->isNotEmpty())
                  @foreach ($actualSuppliers->take(2) as $sup)
                    <div class="fw-semibold">{{ $sup->code }}</div>
                  @endforeach
                  @if ($actualSuppliers->count() > 2)
                    <div class="text-muted" style="font-size:.72rem;">+{{ $actualSuppliers->count()-2 }} supplier</div>
                  @endif
                @elseif ($pr->supplier)
                  <div class="fw-semibold">{{ $pr->supplier->code }}</div>
                  <div class="text-muted" style="font-size:.72rem;">Supplier awal</div>
                @else
                  <span class="text-muted" style="font-size:.75rem;">—</span>
                @endif
              </td>

              <td>
                <span class="{{ $badgeClass }}">{{ pr_status_label($pr->status) }}</span>
                @if ($pr->notes)
                  <div class="text-muted" style="font-size:.72rem;max-width:160px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                    {{ $pr->notes }}
                  </div>
                @endif
              </td>

              <td class="text-end">
                @if ($pr->status === 'draft')
                  <a href="{{ route('purchasing.purchase_requests.edit', $pr) }}"
                     class="text-muted" title="Edit" style="font-size:.85rem;"
                     onclick="event.stopPropagation();">
                    <i class="bi bi-pencil-square"></i>
                  </a>
                @else
                  <i class="bi bi-chevron-right text-muted" style="font-size:.8rem;opacity:.4;"></i>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-muted py-4" style="font-size:.85rem;">
                Belum ada Purchase Request.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @if ($prs->hasPages())
    <div class="mt-3">{{ $prs->links() }}</div>
  @endif

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form        = document.getElementById('pr-filter-form');
  const searchInput = document.getElementById('pr-search');
  const fromHidden  = document.getElementById('pr-from-date');
  const toHidden    = document.getElementById('pr-to-date');
  const rangeInput  = document.getElementById('pr-date-range');

  // Row click
  document.querySelectorAll('tr.pr-row').forEach(function (row) {
    row.addEventListener('click', function (e) {
      if (e.target.closest('a, button, form')) return;
      const href = row.dataset.href;
      if (href) window.location = href;
    });
  });

  // Selects auto-submit
  form.querySelectorAll('select.pr-filter-auto').forEach(function (el) {
    el.addEventListener('change', function () { form.submit(); });
  });

  // Search debounce
  if (searchInput) {
    const len = (searchInput.value || '').length;
    setTimeout(function () { searchInput.focus(); searchInput.setSelectionRange(len, len); }, 100);
    let debounceTimer;
    searchInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(function () { form.submit(); }, 500);
    });
  }

  // Items toggle
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.pr-items-toggle');
    if (!btn) return;
    const target = document.getElementById(btn.dataset.target);
    if (!target) return;
    const willShow = target.classList.contains('d-none');
    target.classList.toggle('d-none', !willShow);
    btn.textContent = willShow ? 'Sembunyikan' : `+${btn.dataset.count} barang`;
  });

  // Flatpickr range
  if (rangeInput && window.flatpickr) {
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
    flatpickr(rangeInput, {
      mode: 'range',
      dateFormat: 'Y-m-d',
      locale: { firstDayOfWeek: 1 },
      allowInput: false,
      defaultDate: [fromHidden.value, toHidden.value].filter(Boolean),
      onChange: function (selectedDates, dateStr, fp) {
        fp.input.value = fmtRange(selectedDates);
        if (selectedDates.length === 1) {
          fromHidden.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
          toHidden.value = '';
        } else if (selectedDates.length === 2) {
          fromHidden.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
          toHidden.value   = flatpickr.formatDate(selectedDates[1], 'Y-m-d');
          form.submit();
        }
      },
      onReady: function (selectedDates, dateStr, fp) {
        fp.input.classList.add('gf-date-input');
        if (selectedDates.length) fp.input.value = fmtRange(selectedDates);
      },
    });
  }
});
</script>
@endpush
