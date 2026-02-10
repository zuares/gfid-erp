@extends('layouts.app')
@section('title','Marketplace • Reconcile Items')

@php
  $shipmentId = (int)($shipmentId ?? 0);
  $mode = (string)($mode ?? 'replace');
  $q = (string)($q ?? '');
  $status = (string)request()->get('status','all'); // all|missing|extra|ok

  $meta = $meta ?? null;
  $rows = $rows ?? collect();

  $chipUrl = fn($k) => request()->fullUrlWithQuery(['status'=>$k,'page'=>null]);

  $filtered = $rows;
  if ($status !== 'all') {
    $filtered = $rows->filter(fn($r) => ($r['status'] ?? 'ok') === $status)->values();
  }
@endphp

@push('head')
<style>
  .page{max-width:1220px;margin:0 auto;padding:16px 14px 72px}
  @media(min-width:768px){.page{padding:18px 16px 72px}}

  .cardx{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:14px;box-shadow:0 10px 24px rgba(15,23,42,.05)}
  body[data-theme="dark"] .cardx{border-color:rgba(148,163,184,.14);box-shadow:0 12px 28px rgba(0,0,0,.35)}

  .muted{color:rgba(100,116,139,1)}
  body[data-theme="dark"] .muted{color:rgba(148,163,184,.85)}
  .mono{font-variant-numeric:tabular-nums;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}

  .chip{
    display:inline-flex;align-items:center;gap:.35rem;
    padding:.22rem .62rem;border-radius:999px;
    border:1px solid rgba(148,163,184,.22);
    background:rgba(148,163,184,.08);
    text-decoration:none;color:inherit;font-size:.78rem;white-space:nowrap;
  }
  .chip .count{padding:.05rem .45rem;border-radius:999px;border:1px solid rgba(148,163,184,.22);background:rgba(255,255,255,.65)}
  body[data-theme="dark"] .chip .count{background:rgba(2,6,23,.50)}
  .chip.active{border-color:rgba(99,102,241,.85);box-shadow:0 0 0 3px rgba(99,102,241,.14)}

  .kpi{display:flex;gap:10px;flex-wrap:wrap}
  .kpi .chip{background:rgba(148,163,184,.06)}

  .btn-pill{border-radius:999px}
  .btn-ghost{background:transparent;border:1px solid rgba(148,163,184,.22)}
  body[data-theme="dark"] .btn-ghost{color:inherit}

  .table thead th{
    font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;
    color:rgba(100,116,139,1);background:rgba(248,250,252,.98);
    position:sticky;top:0;z-index:3;
  }
  body[data-theme="dark"] .table thead th{background:rgba(15,23,42,.98);color:rgba(148,163,184,.85)}
  .table tbody tr:hover td{background:rgba(148,163,184,.06)}

  .badge-soft{border-radius:999px;padding:.18rem .55rem;font-size:.72rem;border:1px solid rgba(148,163,184,.25);background:rgba(148,163,184,.08)}
  .b-ok{border-color:rgba(34,197,94,.25);background:rgba(34,197,94,.10)}
  .b-missing{border-color:rgba(245,158,11,.28);background:rgba(245,158,11,.12)}
  .b-extra{border-color:rgba(99,102,241,.28);background:rgba(99,102,241,.12)}

  .cell-num{font-weight:800}
  .delta-pos{color:rgba(99,102,241,.95)}
  .delta-neg{color:rgba(245,158,11,1)}
</style>
@endpush

@section('content')
<div class="page">

  {{-- Top bar --}}
  <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
      <div class="fw-bold">Reconcile Items</div>
      @if($shipment)
        <span class="chip mono">{{ $shipment->code }}</span>
      @endif
    </div>

    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-ghost btn-pill" href="{{ route('marketplace.reconcile.queue') }}">Queue</a>
      <a class="btn btn-sm btn-ghost btn-pill" href="{{ route('marketplace.reconcile.items') }}">Reset</a>
    </div>
  </div>

  @if (session('ok'))
    <div class="alert alert-success" role="alert">{{ session('ok') }}</div>
  @elseif (session('error'))
    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
  @elseif ($errors->any())
    <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
  @endif

  {{-- Controls --}}
  <div class="cardx p-3 mb-3">
    <form class="d-flex flex-wrap gap-2 align-items-end" method="GET" action="{{ route('marketplace.reconcile.items') }}">
      <div style="min-width:280px;flex:1">
        <label class="small muted">Shipment</label>
        <select class="form-select" name="shipment_id">
          <option value="">—</option>
          @foreach($shipments as $s)
            <option value="{{ $s->id }}" @selected($shipmentId === (int)$s->id)>
              {{ $s->code }} • {{ \Carbon\Carbon::parse($s->date)->format('Y-m-d') }} • {{ $s->status }}
            </option>
          @endforeach
        </select>
      </div>

      <div style="min-width:150px">
        <label class="small muted">Mode</label>
        <select class="form-select" name="mode">
          <option value="replace" @selected($mode==='replace')>Replace</option>
          <option value="add" @selected($mode==='add')>Add</option>
        </select>
      </div>

      <div style="min-width:240px;flex:1">
        <label class="small muted">Search</label>
        <input class="form-control" name="q" value="{{ $q }}" placeholder="S4RDM / Jogger">
      </div>

      <input type="hidden" name="status" value="{{ $status }}">

      <button class="btn btn-primary btn-pill px-3">Load</button>
    </form>

    {{-- KPI + Apply --}}
    @if($shipment && $meta)
      <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
        <div class="kpi">
          <span class="chip">MP <span class="count mono">{{ number_format((int)$meta['mp_total']) }}</span></span>
          <span class="chip">Ship <span class="count mono">{{ number_format((int)$meta['ship_total']) }}</span></span>
          @php $d = (int)($meta['delta_total'] ?? 0); @endphp
          <span class="chip">Δ <span class="count mono {{ $d>0?'delta-pos':($d<0?'delta-neg':'') }}">{{ number_format($d) }}</span></span>
          <span class="chip">Lines <span class="count mono">{{ number_format((int)$meta['lines']) }}</span></span>
        </div>

        <form method="POST" action="{{ route('marketplace.reconcile.items.apply') }}" class="ms-auto d-flex gap-2">
          @csrf
          <input type="hidden" name="shipment_id" value="{{ $shipment->id }}">
          <input type="hidden" name="mode" value="{{ $mode }}">
          <button class="btn btn-success btn-pill px-3">
            Apply ({{ $mode }})
          </button>
        </form>
      </div>
    @endif

    {{-- Status chips --}}
    <div class="d-flex flex-wrap gap-2 mt-3">
      <a class="chip {{ $status==='all'?'active':'' }}" href="{{ $chipUrl('all') }}">All</a>
      <a class="chip {{ $status==='missing'?'active':'' }}" href="{{ $chipUrl('missing') }}">Missing</a>
      <a class="chip {{ $status==='extra'?'active':'' }}" href="{{ $chipUrl('extra') }}">Extra</a>
      <a class="chip {{ $status==='ok'?'active':'' }}" href="{{ $chipUrl('ok') }}">OK</a>
    </div>
  </div>

  {{-- Table --}}
  <div class="cardx">
    <div class="p-3 d-flex justify-content-between align-items-center">
      <div class="fw-bold">Items</div>
      <div class="chip mono">{{ number_format($filtered->count()) }}</div>
    </div>

    <div class="table-responsive" style="max-height:72vh;overflow:auto;">
      <table class="table table-sm align-middle mb-0">
        <thead>
          <tr>
            <th>Item</th>
            <th class="text-end" style="width:120px;">MP</th>
            <th class="text-end" style="width:120px;">Ship</th>
            <th class="text-end" style="width:120px;">Diff</th>
            <th style="width:120px;">Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($filtered as $r)
            @php
              $st = $r['status'] ?? 'ok';
              $badge = 'badge-soft b-ok';
              if ($st === 'missing') $badge = 'badge-soft b-missing';
              elseif ($st === 'extra') $badge = 'badge-soft b-extra';

              $diff = (int)($r['diff'] ?? 0);
            @endphp
            <tr>
              <td>
                <div class="fw-bold mono">{{ $r['code'] }}</div>
                <div class="small muted">{{ $r['name'] }}</div>
              </td>
              <td class="text-end mono cell-num">{{ (int)$r['mp_qty'] }}</td>
              <td class="text-end mono cell-num">{{ (int)$r['ship_qty'] }}</td>
              <td class="text-end mono cell-num {{ $diff>0?'delta-pos':($diff<0?'delta-neg':'') }}">
                {{ $diff }}
              </td>
              <td><span class="{{ $badge }}">{{ strtoupper($st) }}</span></td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="p-4 text-center muted">—</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>

</div>
@endsection
