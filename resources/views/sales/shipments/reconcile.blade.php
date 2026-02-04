{{-- resources/views/sales/shipments/reconcile.blade.php --}}
@extends('layouts.app')

@section('title', 'Rekonsiliasi • ' . $shipment->code)

@push('head')
<style>
  .page-wrap{max-width:1150px;margin-inline:auto;padding:.75rem .75rem 3.5rem;min-height:100vh}
  body[data-theme="light"] .page-wrap{background:#f3f4f6}
  body[data-theme="dark"] .page-wrap{background:radial-gradient(circle at top left, rgba(15,23,42,.9) 0, #020617 65%)}

  .card-main{
    background:var(--card);
    border-radius:14px;
    border:1px solid rgba(148,163,184,.18);
    box-shadow:0 4px 18px rgba(15,23,42,.05)
  }
  body[data-theme="dark"] .card-main{
    border-color:rgba(30,64,175,.6);
    box-shadow:0 12px 32px rgba(15,23,42,.8)
  }

  .meta-label{font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:#6b7280}
  body[data-theme="dark"] .meta-label{color:#9ca3af}

  .mono{font-variant-numeric:tabular-nums;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono"}

  .summary-pill{
    border-radius:999px;padding:.25rem .75rem;font-size:.8rem;
    border:1px solid rgba(148,163,184,.3);background:rgba(248,250,252,.96)
  }
  body[data-theme="dark"] .summary-pill{
    background:rgba(15,23,42,.98);
    border-color:rgba(30,64,175,.7);color:#e5e7eb
  }

  .chip{
    display:inline-flex;align-items:center;gap:.45rem;
    border-radius:999px;padding:.28rem .85rem;
    font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;
    border:1px solid rgba(148,163,184,.35);
    background:rgba(248,250,252,.96);color:#334155;text-decoration:none
  }
  .chip .count{
    border-radius:999px;padding:.08rem .5rem;font-size:.72rem;
    border:1px solid rgba(148,163,184,.35);
    background:rgba(255,255,255,.9);color:#111827
  }
  .chip.active{border-color:rgba(34,197,94,.55);box-shadow:0 0 0 3px rgba(34,197,94,.10)}
  body[data-theme="dark"] .chip{background:rgba(15,23,42,.98);border-color:rgba(30,64,175,.75);color:#e5e7eb}
  body[data-theme="dark"] .chip .count{background:rgba(2,6,23,.9);color:#e5e7eb}

  .btn-theme-outline,.btn-theme-main{
    border-radius:999px;font-size:.78rem;letter-spacing:.06em;
    text-transform:uppercase;padding-inline:1rem;padding-block:.35rem;border-width:1px
  }
  .btn-theme-outline{background:transparent}
  body[data-theme="dark"] .btn-theme-outline{color:#e5e7eb;border-color:rgba(148,163,184,.6)}

  .table thead th{
    font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;
    background:rgba(248,250,252,.98)
  }
  body[data-theme="dark"] .table thead th{color:#9ca3af;background:rgba(15,23,42,.98)}
  .table thead th.sticky{position:sticky;top:0;z-index:5;border-bottom-width:1px}
  .table tbody td{vertical-align:middle;border-top-color:rgba(148,163,184,.18)}
  body[data-theme="dark"] .table tbody td{border-top-color:rgba(51,65,85,.85)}

  .order-id{font-weight:800;font-size:.98rem;line-height:1.1}
  body[data-theme="dark"] .order-id{color:#e5e7eb}
  .subline{font-size:.78rem;color:#6b7280}
  body[data-theme="dark"] .subline{color:#9ca3af}

  .badge{font-size:.74rem}

  .action-wrap{display:flex;justify-content:flex-end;gap:.5rem;align-items:center;flex-wrap:wrap}
  .action-wrap .form-select,.action-wrap .form-control{height:32px;font-size:.85rem}
  .action-wrap .notes{width:220px}
  .action-wrap .btn{height:32px;padding-block:0;display:inline-flex;align-items:center}
  .notes.is-hidden{display:none !important}
  @media (max-width: 860px){
    .action-wrap{justify-content:flex-start}
    .action-wrap .notes{width:100%}
  }

  /* ===== NEW: packet items section ===== */
  .mini-title{font-weight:800;font-size:.95rem}
  .muted{color:#6b7280}
  body[data-theme="dark"] .muted{color:#9ca3af}

  .pill{
    border-radius:999px;padding:.18rem .6rem;font-size:.78rem;
    border:1px solid rgba(148,163,184,.30);background:rgba(248,250,252,.96)
  }
  body[data-theme="dark"] .pill{
    background:rgba(15,23,42,.98);border-color:rgba(30,64,175,.70);color:#e5e7eb
  }

  .btn-mini{
    border-radius:999px;
    padding:.22rem .7rem;
    font-size:.72rem;
    text-transform:uppercase;
    letter-spacing:.06em;
  }
</style>
@endpush

@section('content')
@php
  $countsArr = $mpCounts->toArray();
  $totalAll = array_sum($countsArr);

  $countOf = function($k) use ($countsArr, $totalAll){
    return $k === 'all' ? $totalAll : ($countsArr[$k] ?? 0);
  };

  $urlOf = fn($k) => request()->fullUrlWithQuery(['status'=>$k]);
  $isActive = fn($k) => ($status ?? 'all') === $k ? 'active' : '';

  $label = [
    'all'          => 'Semua',
    'needs_review' => 'Review',
    'resolved'     => 'OK',
    'skipped'      => 'Skip',
    'auto_matched' => 'Auto',
  ];
@endphp

<div class="page-wrap">

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <div class="meta-label mb-1">Rekonsiliasi</div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <h1 class="h5 mb-0">{{ $shipment->code }}</h1>
        <span class="text-muted small">
          {{ $shipment->store?->name ?? '-' }}
          @if($shipment->store?->code) ({{ strtoupper($shipment->store->code) }}) @endif
        </span>
      </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('sales.shipments.show', $shipment->id) }}" class="btn btn-sm btn-theme-outline">
        ← Kembali
      </a>
    </div>
  </div>

  {{-- FLASH --}}
  @if (session('ok'))
    <div class="alert alert-success js-auto-hide-alert" role="alert">{{ session('ok') }}</div>
  @elseif ($errors->any())
    <div class="alert alert-danger js-auto-hide-alert" role="alert">{{ $errors->first() }}</div>
  @endif

  {{-- SUMMARY + FILTER --}}
  <div class="card card-main mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div class="d-flex flex-wrap gap-2">
          <div class="summary-pill">Total <span class="fw-semibold ms-1 mono">{{ $mpStats->total ?? 0 }}</span></div>
          <div class="summary-pill">Review <span class="fw-semibold ms-1 mono">{{ $mpStats->needs_review ?? 0 }}</span></div>
          <div class="summary-pill">OK <span class="fw-semibold ms-1 mono">{{ $mpStats->resolved ?? 0 }}</span></div>
          <div class="summary-pill">Skip <span class="fw-semibold ms-1 mono">{{ $mpStats->skipped ?? 0 }}</span></div>
          <div class="summary-pill">Auto <span class="fw-semibold ms-1 mono">{{ $mpStats->auto_matched ?? 0 }}</span></div>
        </div>
      </div>

      <hr class="my-3">

      <div class="d-flex flex-wrap gap-2">
        <a class="chip {{ $isActive('all') }}" href="{{ $urlOf('all') }}">
          {{ $label['all'] }} <span class="count">{{ $countOf('all') }}</span>
        </a>
        <a class="chip {{ $isActive('needs_review') }}" href="{{ $urlOf('needs_review') }}">
          {{ $label['needs_review'] }} <span class="count">{{ $countOf('needs_review') }}</span>
        </a>
        <a class="chip {{ $isActive('resolved') }}" href="{{ $urlOf('resolved') }}">
          {{ $label['resolved'] }} <span class="count">{{ $countOf('resolved') }}</span>
        </a>
        <a class="chip {{ $isActive('skipped') }}" href="{{ $urlOf('skipped') }}">
          {{ $label['skipped'] }} <span class="count">{{ $countOf('skipped') }}</span>
        </a>
        <a class="chip {{ $isActive('auto_matched') }}" href="{{ $urlOf('auto_matched') }}">
          {{ $label['auto_matched'] }} <span class="count">{{ $countOf('auto_matched') }}</span>
        </a>
      </div>
    </div>
  </div>

  {{-- TABLE PACKETS --}}
  <div class="card card-main">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr class="text-muted">
              <th class="sticky">Order ID</th>
              <th class="sticky" style="width: 140px;">Status</th>
              <th class="sticky" style="width: 220px;">Key</th>
              <th class="sticky text-end" style="width: 90px;">Conf</th>
              <th class="sticky" style="width: 190px;">Waktu</th>
              <th class="sticky text-end" style="width: 360px;">Aksi</th>
            </tr>
          </thead>

          <tbody>
          @forelse($mpPackets as $r)
            @php
              $st = $r->status ?? 'needs_review';
              $badge = 'bg-secondary';
              if ($st === 'needs_review') $badge = 'bg-warning text-dark';
              elseif ($st === 'resolved') $badge = 'bg-success';
              elseif ($st === 'auto_matched') $badge = 'bg-primary';

              $orderId = $r->mpShipment?->platform_order_id ?? ('MP#' . $r->mp_shipment_id);
              $hasNotes = !empty($r->notes);

              $items = $packetItems[$r->mp_shipment_id] ?? collect();
              $itemsCount = $items->count();
              $itemsQty = (int) $items->sum('qty');
              $itemsUnmapped = (int) $items->whereNull('item_id')->count();

              $collapseId = 'pi_' . $r->id;
            @endphp

            <tr>
              <td>
                <div class="order-id">{{ $orderId }}</div>
                <div class="subline mono">
                  mp: {{ $r->mp_shipment_id }}
                  <span class="mx-1">•</span>
                  rec: {{ $r->id }}
                </div>

                {{-- quick stats + toggle --}}
                <div class="mt-2 d-flex gap-2 flex-wrap align-items-center">
                  <span class="pill">
                    Items: <span class="mono fw-semibold">{{ $itemsCount }}</span>
                  </span>
                  <span class="pill">
                    Qty: <span class="mono fw-semibold">{{ $itemsQty }}</span>
                  </span>
                  @if($itemsCount > 0 && $itemsUnmapped > 0)
                    <span class="pill">
                      Unmapped: <span class="mono fw-semibold">{{ $itemsUnmapped }}</span>
                    </span>
                  @endif

                  <button type="button"
                          class="btn btn-sm btn-theme-outline btn-mini ms-auto js-toggle-items"
                          data-bs-toggle="collapse"
                          data-bs-target="#{{ $collapseId }}"
                          aria-expanded="false"
                          aria-controls="{{ $collapseId }}">
                    Lihat Items
                  </button>
                </div>

                {{-- collapsible items --}}
                <div class="collapse mt-2" id="{{ $collapseId }}">
                  @if($itemsCount === 0)
                    <div class="small muted">
                      Belum ada packet items untuk MP ini.
                      Pastikan hook sync sudah jalan setelah reconcile write.
                    </div>
                  @else
                    <div class="table-responsive">
                      <table class="table table-sm align-middle mb-0">
                        <thead>
                          <tr>
                            <th>SKU</th>
                            <th>Mapped Item</th>
                            <th class="text-end" style="width:90px;">Qty</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach($items as $li)
                            <tr>
                              <td class="mono">
                                {{ $li->sku }}
                                @if($li->name)
                                  <div class="subline">{{ $li->name }}</div>
                                @endif
                              </td>
                              <td>
                                @if($li->item)
                                  <span class="badge rounded-pill bg-success">{{ $li->item->code }}</span>
                                  <span class="ms-1">{{ $li->item->name }}</span>
                                @else
                                  <span class="badge rounded-pill bg-warning text-dark">Unmapped</span>
                                  <span class="subline ms-1">belum ketemu item master</span>
                                @endif
                              </td>
                              <td class="text-end mono">{{ (int) $li->qty }}</td>
                            </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                  @endif
                </div>

                @if($hasNotes)
                  <div class="subline mt-2">Catatan: {{ $r->notes }}</div>
                @endif
              </td>

              <td>
                <span class="badge rounded-pill {{ $badge }}">{{ $label[$st] ?? $st }}</span>
              </td>

              <td class="mono">{{ $r->match_key }}</td>

              <td class="text-end mono">{{ (int) $r->match_confidence }}</td>

              <td class="small text-muted">
                {{ $r->matched_at ? id_datetime($r->matched_at) : '-' }}
              </td>

              <td class="text-end">
                <form method="POST" action="{{ route('marketplace.reconciliations.resolve', $r->id) }}"
                      class="js-row-form">
                  @csrf
                  <input type="hidden" name="shipment_id" value="{{ $shipment->id }}">

                  <div class="action-wrap">
                    <select name="action" class="form-select form-select-sm js-action" style="max-width: 170px;">
                      <option value="link_to_shipment">Link</option>
                      <option value="mark_needs_review" {{ $st === 'needs_review' ? 'selected' : '' }}>Review</option>
                      <option value="mark_skipped" {{ $st === 'skipped' ? 'selected' : '' }}>Skip</option>
                    </select>

                    <input type="text" name="notes"
                           class="form-control form-control-sm notes js-notes is-hidden"
                           placeholder="Catatan (opsional)">

                    <button class="btn btn-sm btn-theme-main" type="submit">Simpan</button>
                  </div>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-muted text-center py-4">Tidak ada data.</td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3">
        {{ $mpPackets->links() }}
      </div>
    </div>
  </div>

  {{-- OPTIONAL: global packet items summary --}}
  @if(isset($packetItemStats))
    <div class="mt-3 d-flex gap-2 flex-wrap">
      <span class="summary-pill">
        Packet items rows <span class="fw-semibold ms-1 mono">{{ $packetItemStats['rows'] ?? 0 }}</span>
      </span>
      <span class="summary-pill">
        Total qty <span class="fw-semibold ms-1 mono">{{ $packetItemStats['total_qty'] ?? 0 }}</span>
      </span>
      <span class="summary-pill">
        Unmapped <span class="fw-semibold ms-1 mono">{{ $packetItemStats['unmapped'] ?? 0 }}</span>
      </span>
    </div>
  @endif

</div>
@endsection

@push('scripts')
<script>
  (function() {
    // auto-hide alerts
    const autoAlerts = document.querySelectorAll('.js-auto-hide-alert');
    if (autoAlerts.length) {
      setTimeout(() => {
        autoAlerts.forEach((el) => {
          el.style.transition = 'opacity .4s ease';
          el.style.opacity = '0';
          setTimeout(() => { if (el && el.parentNode) el.parentNode.removeChild(el); }, 450);
        });
      }, 2600);
    }

    // show notes only when needed (review / skip)
    const rows = document.querySelectorAll('.js-row-form');
    rows.forEach((form) => {
      const sel = form.querySelector('.js-action');
      const notes = form.querySelector('.js-notes');

      const apply = () => {
        const v = sel.value;
        const needsNotes = (v === 'mark_skipped' || v === 'mark_needs_review');
        if (needsNotes) notes.classList.remove('is-hidden');
        else notes.classList.add('is-hidden');
      };

      sel.addEventListener('change', apply);
      apply();
    });
  })();
</script>
@endpush
