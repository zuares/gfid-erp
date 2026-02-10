@extends('layouts.app')
@section('title','Marketplace • Reconcile Queue')

@php
  $countsArr = ($counts ?? collect())->toArray();
  $totalAll  = array_sum($countsArr);
  $countOf   = fn($k) => $k === 'all' ? $totalAll : ($countsArr[$k] ?? 0);
  $urlOf     = fn($k) => request()->fullUrlWithQuery(['status'=>$k,'page'=>null]);

  $resolveBaseUrl = route('marketplace.reconciliations.resolve',['rec'=>0]);
  $diffBaseUrl    = route('marketplace.reconciliations.diff',['rec'=>0]);
@endphp

@push('head')
<style>
.page{max-width:1220px;margin:auto;padding:1.2rem 1rem 4rem}
.cardx{border:1px solid rgba(148,163,184,.18);border-radius:14px;background:#fff;box-shadow:0 6px 18px rgba(0,0,0,.04)}
.mono{font-family:ui-monospace,monospace}
.muted{color:#64748b}
.badge-soft{border-radius:999px;padding:.2rem .6rem;font-size:.72rem;border:1px solid rgba(148,163,184,.3);background:rgba(148,163,184,.1)}
.b-review{background:rgba(245,158,11,.1);border-color:rgba(245,158,11,.3)}
.b-ok{background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.3)}
.b-auto{background:rgba(99,102,241,.1);border-color:rgba(99,102,241,.3)}
.b-skip{background:rgba(148,163,184,.1)}
.table thead th{font-size:.72rem;text-transform:uppercase;color:#64748b;background:#f8fafc}
.table tbody tr:hover td{background:rgba(148,163,184,.05)}
</style>
@endpush

@section('content')
<div class="page">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 fw-bold mb-0">Reconcile Queue</h1>
    <a class="btn btn-sm btn-outline-secondary" href="{{ route('marketplace.reconcile.queue') }}">Reset</a>
  </div>

  @if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="cardx mb-3 p-3">
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ $urlOf('needs_review') }}" class="badge-soft {{ ($status ?? 'needs_review')==='needs_review' ? 'b-review' : '' }}">
        Review ({{ $countOf('needs_review') }})
      </a>
      <a href="{{ $urlOf('resolved') }}" class="badge-soft {{ ($status ?? '')==='resolved' ? 'b-ok' : '' }}">
        OK ({{ $countOf('resolved') }})
      </a>
      <a href="{{ $urlOf('skipped') }}" class="badge-soft {{ ($status ?? '')==='skipped' ? 'b-skip' : '' }}">
        Skip ({{ $countOf('skipped') }})
      </a>
      <a href="{{ $urlOf('all') }}" class="badge-soft">
        Semua ({{ $totalAll }})
      </a>
    </div>
  </div>

  <div class="cardx">
    <div class="p-3 fw-bold">Queue</div>

    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead>
          <tr>
            <th>Order</th>
            <th>Status</th>
            <th>Conf</th>
            <th>Shipment</th>
            <th width="260">Action</th>
          </tr>
        </thead>
        <tbody>
        @forelse($rows as $r)
          @php
            $st = $r->status ?? 'needs_review';
            $badge = 'badge-soft b-review';
            if ($st==='resolved') $badge='badge-soft b-ok';
            elseif ($st==='auto_matched') $badge='badge-soft b-auto';
            elseif ($st==='skipped') $badge='badge-soft b-skip';

            $orderId = $r->mpShipment?->platform_order_id ?? ('MP#'.$r->mp_shipment_id);
            $tracking = $r->mpShipment?->tracking_no;
            $conf = (int)($r->match_confidence ?? 0);
            $ship = $r->shipment;
          @endphp

          <tr>
            <td>
              <div class="fw-bold mono">{{ $orderId }}</div>
              <div class="small muted mono">
                mp: {{ $r->mp_shipment_id }}
                @if($tracking) • resi: {{ $tracking }} @endif
              </div>
            </td>

            <td><span class="{{ $badge }}">{{ ucfirst($st) }}</span></td>
            <td class="mono fw-bold">{{ $conf }}</td>

            <td class="mono">
              @if($ship)
                <a href="{{ route('sales.shipments.show',$ship) }}">{{ $ship->code }}</a>
              @else
                -
              @endif
            </td>

            <td>
              <div class="d-flex gap-2 flex-wrap">
                <button type="button"
                        class="btn btn-success btn-sm"
                        data-open-resolve
                        data-rec-id="{{ $r->id }}">
                  Apply
                </button>

                <button type="button"
                        class="btn btn-outline-secondary btn-sm"
                        data-open-diff
                        data-rec-id="{{ $r->id }}"
                        @disabled(!$r->shipment_id)>
                  Diff
                </button>

                <form method="POST"
                      action="{{ route('marketplace.reconciliations.resolve',$r) }}">
                  @csrf
                  <input type="hidden" name="action" value="mark_skipped">
                  <button class="btn btn-outline-danger btn-sm">Skip</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center p-4 text-muted">Tidak ada data</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="p-3">
      {{ $rows->links() }}
    </div>
  </div>
</div>

{{-- APPLY MODAL --}}
<div class="modal fade" id="resolveModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content cardx p-3">
      <form method="POST" id="resolveForm">
        @csrf
        <input type="hidden" name="action" value="apply_to_lines">

        <div class="mb-3">
          <label class="small muted">Shipment Target</label>
          <select name="shipment_id" class="form-select" required>
            <option value="">-- pilih shipment --</option>
            @foreach(\App\Models\Shipment::orderByDesc('date')->limit(50)->get() as $s)
              <option value="{{ $s->id }}">
                {{ $s->code }} • {{ \Carbon\Carbon::parse($s->date)->format('Y-m-d') }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="mb-3">
          <label class="small muted">Mode</label>
          <select name="mode" class="form-select">
            <option value="replace">Replace (qty = MP)</option>
            <option value="add">Add (qty += MP)</option>
          </select>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Apply</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- DIFF MODAL --}}
<div class="modal fade" id="diffModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content cardx p-3">
      <div id="diffContent">Loading...</div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const resolveBase = @json($resolveBaseUrl);
const diffBase    = @json($diffBaseUrl);

const resolveModal = new bootstrap.Modal(document.getElementById('resolveModal'));
const diffModal    = new bootstrap.Modal(document.getElementById('diffModal'));

document.querySelectorAll('[data-open-resolve]').forEach(btn=>{
  btn.addEventListener('click',()=>{
    const id = btn.dataset.recId;
    document.getElementById('resolveForm').action =
      resolveBase.replace(/\/0\/resolve$/,`/${id}/resolve`);
    resolveModal.show();
  });
});

document.querySelectorAll('[data-open-diff]').forEach(btn=>{
  btn.addEventListener('click',async()=>{
    const id = btn.dataset.recId;
    diffModal.show();
    const res = await fetch(diffBase.replace(/\/0\/diff$/,`/${id}/diff`));
    const html = await res.text();
    document.getElementById('diffContent').innerHTML = html;
  });
});
</script>
@endpush
