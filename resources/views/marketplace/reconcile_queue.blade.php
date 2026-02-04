@extends('layouts.app')
@section('title','Marketplace • Reconcile Queue')

@php
  $countsArr = ($counts ?? collect())->toArray();
  $totalAll  = array_sum($countsArr);
  $countOf   = fn($k) => $k === 'all' ? $totalAll : ($countsArr[$k] ?? 0);
  $urlOf     = fn($k) => request()->fullUrlWithQuery(['status'=>$k,'page'=>null]);

  $label = [
    'all' => 'Semua',
    'needs_review' => 'Review',
    'auto_matched' => 'Auto',
    'resolved' => 'OK',
    'skipped' => 'Skip',
  ];

  $preview = $preview ?? null;
  $pvStats = $preview['result']['stats'] ?? null;

  // run values: prefer preview params then GET then default
  $runDate    = old('date', $preview['params']['date'] ?? ($date ?? now()->toDateString()));
  $runChannel = old('channel', $preview['params']['channel'] ?? ($channel ?? ''));
  $runStoreId = (string) old('store_id', $preview['params']['store_id'] ?? ($storeId ?? ''));

  $resolveBaseUrl = route('marketplace.reconciliations.resolve', ['rec'=>0]);
  $diffBaseUrl    = route('marketplace.reconciliations.diff', ['rec'=>0]);
@endphp

@push('head')
<style>
  /* ===== layout + tokens (mirip imports, beda tone) ===== */
  .page{max-width:1220px;margin:0 auto;padding:1rem .9rem 4.8rem}
  @media(min-width:768px){.page{padding:1.1rem 1rem 4.8rem}}

  :root{
    --line: rgba(148,163,184,.18);
    --line2: rgba(148,163,184,.22);
    --ink: rgba(15,23,42,.92);
    --muted: rgba(100,116,139,1);
    --soft: rgba(148,163,184,.06);
    --soft2: rgba(148,163,184,.10);
    --shadow: 0 10px 24px rgba(15,23,42,.05);

    /* accent indigo */
    --accent: rgba(99,102,241,.95);
    --accentSoft: rgba(99,102,241,.12);
    --warn: rgba(245,158,11,.12);
    --warnLine: rgba(245,158,11,.35);
  }
  body[data-theme="dark"]{
    --ink: rgba(226,232,240,.92);
    --muted: rgba(148,163,184,.85);
    --line: rgba(148,163,184,.14);
    --line2: rgba(148,163,184,.18);
    --soft: rgba(148,163,184,.08);
    --soft2: rgba(148,163,184,.12);
    --shadow: 0 12px 28px rgba(0,0,0,.35);

    --accent: rgba(129,140,248,.95);
    --accentSoft: rgba(129,140,248,.14);
  }

  .cardx{border:1px solid var(--line);border-radius:14px;background:var(--card,#fff);box-shadow:var(--shadow)}
  .chip{
    display:inline-flex;align-items:center;gap:.35rem;
    padding:.18rem .55rem;border-radius:999px;font-size:.78rem;
    border:1px solid var(--line2);background:var(--soft);white-space:nowrap;
    text-decoration:none;color:var(--ink);
  }
  .chip .count{padding:.05rem .45rem;border-radius:999px;border:1px solid var(--line2);background:rgba(255,255,255,.65)}
  body[data-theme="dark"] .chip .count{background:rgba(2,6,23,.5)}
  .chip.active{border-color:var(--accent);box-shadow:0 0 0 3px var(--accentSoft)}
  .chip-warn{border-color:var(--warnLine);background:var(--warn)}
  .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
  .muted{color:var(--muted)}

  .btn-pill{border-radius:999px;letter-spacing:.04em}
  .btn-ghost{background:transparent;border:1px solid var(--line2)}
  body[data-theme="dark"] .btn-ghost{color:var(--ink)}

  .table thead th{font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);background:rgba(248,250,252,.98)}
  body[data-theme="dark"] .table thead th{background:rgba(15,23,42,.98)}
  .table thead th.sticky{position:sticky;top:0;z-index:5}
  .table tbody tr:hover td{background:var(--soft)}

  .badge-soft{border-radius:999px;padding:.18rem .55rem;font-size:.72rem;border:1px solid var(--line2);background:var(--soft)}
  .b-review{border-color:rgba(245,158,11,.35);background:rgba(245,158,11,.10);color:#92400e}
  .b-ok{border-color:rgba(34,197,94,.30);background:rgba(34,197,94,.10);color:#065f46}
  .b-auto{border-color:rgba(99,102,241,.30);background:rgba(99,102,241,.10);color:#3730a3}
  .b-skip{border-color:rgba(148,163,184,.35);background:rgba(148,163,184,.10);color:#334155}

  /* modal skin */
  .modal-backdrop.show{opacity:.35}
  body[data-theme="dark"] .modal-backdrop.show{opacity:.55}
  .modal .modal-content.cardx{border-radius:14px}
  body[data-theme="dark"] .modal .modal-content{background:rgba(2,6,23,.98)}
</style>
@endpush

@section('content')
<div class="page">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1 fw-bold">Reconcile Queue</h1>
      <div class="small muted d-flex flex-wrap gap-2 align-items-center">
        <span class="chip chip-warn">Scope: <span class="mono">{{ $runChannel ?: 'ALL' }}</span></span>
        @if($pvStats)
          <span class="chip">Scanned <span class="count mono">{{ $pvStats['scanned'] ?? 0 }}</span></span>
          <span class="chip">Auto <span class="count mono">{{ $pvStats['matched'] ?? 0 }}</span></span>
          <span class="chip">Review <span class="count mono">{{ $pvStats['needs_review'] ?? 0 }}</span></span>
        @endif
      </div>

      <div class="d-flex flex-wrap gap-2 mt-2">
        <a class="chip {{ ($status ?? 'needs_review')==='needs_review' ? 'active' : '' }}" href="{{ $urlOf('needs_review') }}">
          Review <span class="count">{{ $countOf('needs_review') }}</span>
        </a>
        <a class="chip {{ ($status ?? '')==='auto_matched' ? 'active' : '' }}" href="{{ $urlOf('auto_matched') }}">
          Auto <span class="count">{{ $countOf('auto_matched') }}</span>
        </a>
        <a class="chip {{ ($status ?? '')==='resolved' ? 'active' : '' }}" href="{{ $urlOf('resolved') }}">
          OK <span class="count">{{ $countOf('resolved') }}</span>
        </a>
        <a class="chip {{ ($status ?? '')==='skipped' ? 'active' : '' }}" href="{{ $urlOf('skipped') }}">
          Skip <span class="count">{{ $countOf('skipped') }}</span>
        </a>
        <a class="chip {{ ($status ?? '')==='all' ? 'active' : '' }}" href="{{ $urlOf('all') }}">
          Semua <span class="count">{{ $totalAll }}</span>
        </a>
      </div>
    </div>

    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-ghost btn-pill" href="{{ route('marketplace.reconcile.queue') }}">Reset</a>
    </div>
  </div>

  @if (session('ok'))
    <div class="alert alert-success" role="alert">{{ session('ok') }}</div>
  @elseif (session('error'))
    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
  @elseif ($errors->any())
    <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
  @endif

  {{-- Run panel (ringkas) --}}
  <div class="cardx p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
      <div class="fw-bold">Run Reconcile</div>
      <div class="small muted">Preview → Commit</div>
    </div>

    <div class="d-flex flex-wrap gap-2 align-items-end">
      <form method="POST" action="{{ route('marketplace.reconcile.preview') }}" class="d-flex flex-wrap gap-2 align-items-end">
        @csrf
        <div style="min-width:170px">
          <label class="small muted">Date</label>
          <input type="date" name="date" class="form-control" value="{{ $runDate }}">
        </div>

        <div style="min-width:160px">
          <label class="small muted">Channel</label>
          <select name="channel" class="form-select">
            <option value="">ALL</option>
            <option value="shopee" @selected($runChannel==='shopee')>Shopee</option>
            <option value="tiktok" @selected($runChannel==='tiktok')>TikTok</option>
          </select>
        </div>

        <div style="min-width:220px">
          <label class="small muted">Store</label>
          <select name="store_id" class="form-select">
            <option value="">ALL</option>
            @foreach($stores as $st)
              <option value="{{ $st->id }}" @selected($runStoreId === (string)$st->id)>{{ $st->code }} — {{ $st->name }}</option>
            @endforeach
          </select>
        </div>

        <div style="min-width:120px">
          <label class="small muted">Window</label>
          <select name="window" class="form-select">
            @foreach([0,1,2,3] as $w)
              <option value="{{ $w }}" @selected((int)($preview['params']['window'] ?? 1) === $w)>±{{ $w }}</option>
            @endforeach
          </select>
        </div>

        <div style="min-width:140px">
          <label class="small muted">Threshold</label>
          <select name="threshold" class="form-select">
            @foreach([90,85,80,75,70,65] as $t)
              <option value="{{ $t }}" @selected((int)($preview['params']['threshold'] ?? 80) === $t)>{{ $t }}</option>
            @endforeach
          </select>
        </div>

        <button class="btn btn-primary btn-pill btn-sm px-3" type="submit">Preview</button>
      </form>

      <form method="POST" action="{{ route('marketplace.reconcile.commit') }}" class="d-flex gap-2 align-items-end">
        @csrf
        <input type="hidden" name="date" value="{{ $preview['params']['date'] ?? $runDate }}">
        <input type="hidden" name="channel" value="{{ $preview['params']['channel'] ?? $runChannel }}">
        <input type="hidden" name="store_id" value="{{ $preview['params']['store_id'] ?? $runStoreId }}">
        <input type="hidden" name="window" value="{{ $preview['params']['window'] ?? 1 }}">
        <input type="hidden" name="threshold" value="{{ $preview['params']['threshold'] ?? 80 }}">

        <button class="btn btn-success btn-pill btn-sm px-3" type="submit" @disabled(!$preview)>Commit</button>
      </form>
    </div>
  </div>

  {{-- Table --}}
  <div class="cardx">
    <div class="p-3 d-flex justify-content-between align-items-center gap-2">
      <div class="fw-bold">Queue</div>
      <form method="POST" action="{{ route('marketplace.reconcile.queue.bulk') }}" id="bulkForm" class="d-flex gap-2">
        @csrf
        <select name="action" class="form-select form-select-sm" style="max-width:150px">
          <option value="approve">Approve</option>
          <option value="skip">Skip</option>
        </select>
        <button class="btn btn-primary btn-pill btn-sm px-3">Bulk</button>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead>
          <tr class="text-muted">
            <th class="sticky" style="width:44px;"><input type="checkbox" id="checkAll"></th>
            <th class="sticky">Order</th>
            <th class="sticky" style="width:120px;">Status</th>
            <th class="sticky" style="width:90px;">Conf</th>
            <th class="sticky" style="width:160px;">Shipment</th>
            <th class="sticky" style="width:280px;">Action</th>
          </tr>
        </thead>
        <tbody>
        @forelse($rows as $r)
          @php
            $st = $r->status ?? 'needs_review';
            $badge = 'badge-soft b-review';
            if ($st === 'resolved') $badge = 'badge-soft b-ok';
            elseif ($st === 'auto_matched') $badge = 'badge-soft b-auto';
            elseif ($st === 'skipped') $badge = 'badge-soft b-skip';

            $orderId = $r->mpShipment?->platform_order_id ?? ('MP#'.$r->mp_shipment_id);
            $tracking = $r->mpShipment?->tracking_no ?? null;

            $conf = (int)($r->match_confidence ?? 0);
            $ship = $r->shipment;
            $shipCode = $ship?->code ?? '-';
            $shipUrl = $ship ? route('sales.shipments.show', $ship) : null;
          @endphp

          <tr>
            <td class="ps-3">
              <input type="checkbox" class="row-check" name="ids[]" form="bulkForm" value="{{ $r->id }}">
            </td>

            <td>
              <div class="fw-bold mono">{{ $orderId }}</div>
              <div class="small muted mono">
                mp: {{ $r->mp_shipment_id }}
                @if($tracking) • resi: {{ $tracking }} @endif
              </div>
            </td>

            <td><span class="{{ $badge }}">{{ $label[$st] ?? $st }}</span></td>
            <td class="mono fw-bold">{{ $conf }}</td>

            <td class="mono">
              @if($shipUrl)
                <a class="text-decoration-none" href="{{ $shipUrl }}">{{ $shipCode }}</a>
              @else
                {{ $shipCode }}
              @endif
            </td>

            <td>
              <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-success btn-sm btn-pill px-3"
                        data-open-resolve data-rec-id="{{ $r->id }}" data-action="mark_resolved" data-title="Approve">
                  Approve
                </button>
                <button type="button" class="btn btn-ghost btn-sm btn-pill px-3"
                        data-open-resolve data-rec-id="{{ $r->id }}" data-action="mark_skipped" data-title="Skip">
                  Skip
                </button>
                <button type="button" class="btn btn-ghost btn-sm btn-pill px-3"
                        data-open-diff data-rec-id="{{ $r->id }}" @disabled(!$r->shipment_id)>
                  Diff
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="p-3 text-muted text-center">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="p-3">
      {{ $rows->links() }}
    </div>
  </div>
</div>

{{-- Resolve Modal --}}
<div class="modal fade" id="resolveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content cardx">
      <div class="modal-header">
        <div>
          <div class="small muted">Action</div>
          <div id="resolveTitle" class="fw-bold">Resolve</div>
        </div>
        <button type="button" class="btn btn-sm btn-ghost btn-pill" data-bs-dismiss="modal">Close</button>
      </div>
      <div class="modal-body">
        <form id="resolveForm" method="POST" action="">
          @csrf
          <input type="hidden" name="action" id="resolveAction">
          <label class="small muted">Notes (optional)</label>
          <textarea class="form-control" name="notes" id="resolveNotes" rows="3"></textarea>
          <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="button" class="btn btn-sm btn-ghost btn-pill" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-sm btn-primary btn-pill" id="resolveSubmit">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- Diff Modal + Unmapped --}}
<div class="modal fade" id="diffModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content cardx">
      <div class="modal-header">
        <div>
          <div class="small muted">Compare</div>
          <div class="fw-bold">Items Diff</div>
          <div id="diffMeta" class="small muted"></div>
        </div>
        <button type="button" class="btn btn-sm btn-ghost btn-pill" data-bs-dismiss="modal">Close</button>
      </div>
      <div class="modal-body">
        <div id="diffLoading" class="small muted">Loading…</div>
        <div id="diffError" class="alert alert-danger d-none"></div>

        <div id="unmappedWrap" class="d-none mb-3">
          <div class="small muted mb-2">Unmapped SKUs</div>
          <div id="unmappedList" class="d-flex flex-wrap gap-2"></div>
        </div>

        <div class="table-responsive" style="max-height:55vh;overflow:auto;">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr class="text-muted">
                <th class="sticky">Item</th>
                <th class="sticky" style="width:110px;">MP</th>
                <th class="sticky" style="width:110px;">Ship</th>
                <th class="sticky" style="width:110px;">Diff</th>
                <th class="sticky" style="width:110px;">Status</th>
              </tr>
            </thead>
            <tbody id="diffTbody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  // bulk select
  const checkAll = document.getElementById('checkAll');
  const checks = document.querySelectorAll('.row-check');
  if (checkAll) checkAll.addEventListener('change', () => checks.forEach(c => c.checked = checkAll.checked));

  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const badge = (st) => {
    if (st==='ok') return '<span class="badge-soft b-ok">OK</span>';
    if (st==='missing') return '<span class="badge-soft b-review">Missing</span>';
    if (st==='extra') return '<span class="badge-soft b-auto">Extra</span>';
    return '<span class="badge-soft b-skip">-</span>';
  };

  // Resolve modal
  const resolveBase = @json($resolveBaseUrl);
  const resolveModalEl = document.getElementById('resolveModal');
  const resolveForm = document.getElementById('resolveForm');
  const resolveAction = document.getElementById('resolveAction');
  const resolveNotes = document.getElementById('resolveNotes');
  const resolveTitle = document.getElementById('resolveTitle');
  const resolveSubmit = document.getElementById('resolveSubmit');
  const bsResolve = new bootstrap.Modal(resolveModalEl);

  const resolveUrl = (id) => resolveBase.replace(/\/0\/resolve$/, `/${id}/resolve`);

  document.querySelectorAll('[data-open-resolve]').forEach(btn => {
    btn.addEventListener('click', () => {
      const recId = btn.getAttribute('data-rec-id');
      const act = btn.getAttribute('data-action');
      const title = btn.getAttribute('data-title') || 'Resolve';

      resolveForm.action = resolveUrl(recId);
      resolveAction.value = act;
      resolveNotes.value = '';
      resolveTitle.textContent = title;
      resolveSubmit.textContent = title;

      bsResolve.show();
      setTimeout(() => resolveNotes.focus(), 150);
    });
  });

  // Diff modal
  const diffBase = @json($diffBaseUrl);
  const diffModalEl = document.getElementById('diffModal');
  const diffLoading = document.getElementById('diffLoading');
  const diffError = document.getElementById('diffError');
  const diffTbody = document.getElementById('diffTbody');
  const diffMeta = document.getElementById('diffMeta');
  const unmappedWrap = document.getElementById('unmappedWrap');
  const unmappedList = document.getElementById('unmappedList');
  const bsDiff = new bootstrap.Modal(diffModalEl);

  const diffUrl = (id) => diffBase.replace(/\/0\/diff$/, `/${id}/diff`);

  async function openDiff(recId){
    diffLoading.classList.remove('d-none');
    diffError.classList.add('d-none');
    diffTbody.innerHTML = '';
    diffMeta.textContent = '';
    if (unmappedWrap && unmappedList) { unmappedWrap.classList.add('d-none'); unmappedList.innerHTML=''; }

    bsDiff.show();

    try{
      const res = await fetch(diffUrl(recId), { headers: { 'Accept': 'application/json' }});
      const json = await res.json();
      if (!res.ok || !json.ok) throw new Error(json.message || 'Gagal load diff.');

      diffMeta.textContent = `mp_shipment_id: ${json.mp_shipment_id} • shipment_id: ${json.shipment_id}`;

      // unmapped chips
      const u = json.unmapped || [];
      if (unmappedWrap && unmappedList) {
        if (u.length > 0) {
          unmappedWrap.classList.remove('d-none');
          unmappedList.innerHTML = u.map(x => (
            `<span class="chip mono chip-warn">${esc(x.sku)} <span class="count">${x.qty}</span></span>`
          )).join('');
        }
      }

      const rows = (json.lines || []).map(l => {
        const item = `<div class="fw-bold mono">${esc(l.code)}</div><div class="small muted">${esc(l.name)}</div>`;
        return `<tr>
          <td>${item}</td>
          <td class="mono">${l.mp_qty}</td>
          <td class="mono">${l.ship_qty}</td>
          <td class="mono fw-bold">${l.diff}</td>
          <td>${badge(l.status)}</td>
        </tr>`;
      }).join('');

      diffTbody.innerHTML = rows || `<tr><td colspan="5" class="p-3 text-muted text-center">Tidak ada data.</td></tr>`;
    } catch(e){
      diffError.textContent = e.message || 'Error.';
      diffError.classList.remove('d-none');
    } finally {
      diffLoading.classList.add('d-none');
    }
  }

  document.querySelectorAll('[data-open-diff]').forEach(btn => {
    btn.addEventListener('click', () => {
      const recId = btn.getAttribute('data-rec-id');
      if (recId) openDiff(recId);
    });
  });
})();
</script>
@endpush
