{{-- resources/views/marketplace/import-orders.blade.php --}}
@extends('layouts.app')
@section('title', 'Marketplace • Import Orders')

@push('head')
<style>
  .page{ max-width:1150px; margin:0 auto; padding:1rem .9rem 6rem; }
  @media(min-width:768px){ .page{ padding:1.1rem 1rem 6rem; } }

  .cardx{
    background: var(--card, #fff);
    border:1px solid rgba(148,163,184,.20);
    border-radius:16px;
    box-shadow:0 10px 24px rgba(15,23,42,.06), 0 0 0 1px rgba(15,23,42,.02);
  }
  .pad{ padding:1rem; }
  .muted{ color: rgba(100,116,139,1); }
  .mono{ font-family: ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; }

  .tabs{ display:flex; gap:.5rem; flex-wrap:wrap; }
  .tab{
    display:inline-flex; align-items:center; gap:.45rem;
    padding:.45rem .7rem; border-radius:999px;
    border:1px solid rgba(148,163,184,.28);
    background:rgba(148,163,184,.08);
    text-decoration:none; color:inherit; font-weight:700;
  }
  .tab.active{ background:rgba(59,130,246,.12); border-color:rgba(59,130,246,.35); }

  .pill{
    display:inline-flex; align-items:center;
    padding:.15rem .55rem;
    border-radius:999px;
    border:1px solid rgba(148,163,184,.25);
    background:rgba(148,163,184,.08);
    font-size:.82rem;
  }
  .pill-ok{ background:rgba(16,185,129,.12); border-color:rgba(16,185,129,.30); }
  .pill-bad{ background:rgba(239,68,68,.12); border-color:rgba(239,68,68,.30); }
  .pill-info{ background:rgba(59,130,246,.12); border-color:rgba(59,130,246,.30); }

  .grid2{ display:grid; gap:.9rem; }
  @media(min-width:960px){ .grid2{ grid-template-columns: 1fr 1fr; } }

  .kpis{ display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:.6rem; }
  @media(min-width:768px){ .kpis{ grid-template-columns:repeat(4, minmax(0,1fr)); } }
  .kpi{
    border:1px solid rgba(148,163,184,.18);
    background:rgba(148,163,184,.06);
    border-radius:14px;
    padding:.75rem .8rem;
  }
  .kpi .label{ font-size:.78rem; letter-spacing:.03em; text-transform:uppercase; color:rgba(100,116,139,1); font-weight:800; }
  .kpi .val{ font-size:1.15rem; font-weight:900; margin-top:.15rem; }

  table{ width:100%; border-collapse:separate; border-spacing:0; }
  th,td{ padding:.55rem .6rem; border-bottom:1px solid rgba(148,163,184,.18); vertical-align:top; }
  th{ font-size:.78rem; color:rgba(100,116,139,1); font-weight:800; text-transform:uppercase; letter-spacing:.04em; }

  .row-exists{ opacity:.7; }
  .hide-exists .row-exists{ display:none; }

  .actionbar{
    position:fixed; left:0; right:0; bottom:0;
    padding:.8rem .9rem calc(.8rem + env(safe-area-inset-bottom));
    background:rgba(255,255,255,.9);
    backdrop-filter: blur(8px);
    border-top:1px solid rgba(148,163,184,.22);
  }
  body[data-theme="dark"] .actionbar{ background:rgba(15,23,42,.78); }
  .actionbar .wrap{ max-width:1150px; margin:0 auto; display:flex; gap:.6rem; justify-content:flex-end; flex-wrap:wrap; }

  .btnx{
    display:inline-flex; align-items:center; justify-content:center;
    padding:.6rem .95rem; border-radius:12px;
    border:1px solid rgba(148,163,184,.28);
    background:rgba(148,163,184,.08);
    text-decoration:none; color:inherit; font-weight:900;
  }
  .btnx.primary{ background:rgba(59,130,246,.14); border-color:rgba(59,130,246,.35); }
  .btnx.danger{ background:rgba(239,68,68,.12); border-color:rgba(239,68,68,.30); }
  .btnx:disabled{ opacity:.5; cursor:not-allowed; }

  .hint{ font-size:.85rem; color:rgba(100,116,139,1); }
</style>
@endpush

@section('content')
@php
  $flash = session('result'); // last action result
  $previewData = $preview ?? null;
  $previewResult = data_get($previewData, 'result');

  $money = fn($n) => 'Rp ' . number_format((float)($n ?? 0), 0, ',', '.');

  $hasPreview = (bool) $previewData;
  $pOk = (bool) data_get($previewResult,'ok');
  $sum = (array) data_get($previewResult,'summary',[]);
  $list = (array) data_get($previewResult,'preview',[]);

  $msg = (string) data_get($flash,'message','');
  $ok = (bool) data_get($flash,'ok');
@endphp

<div class="page">
  <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1">Import Orders</h1>
      <div class="muted small">
        Platform: <span class="pill pill-info">{{ $platformLabel ?? strtoupper($platform) }}</span>
      </div>
    </div>

    <div class="tabs">
      @foreach(($platforms ?? []) as $key => $label)
        <a class="tab {{ $key === $platform ? 'active' : '' }}"
           href="{{ route('marketplace.import_orders.form', ['platform' => $key]) }}">
          {{ $label }}
        </a>
      @endforeach
    </div>
  </div>

  @if($msg !== '')
    <div class="cardx pad mb-3">
      <span class="pill {{ $ok ? 'pill-ok' : 'pill-bad' }}">{{ $ok ? 'OK' : 'ERROR' }}</span>
      <div class="mt-2">{{ $msg }}</div>
    </div>
  @endif

  <div class="grid2">
    {{-- LEFT: Form --}}
    <div class="cardx">
      <div class="pad">
        <div class="d-flex justify-content-between align-items-center">
          <h2 class="h6 mb-1">1) Upload & Preview</h2>
          @if($hasPreview)
            <span class="pill {{ $pOk ? 'pill-ok' : 'pill-bad' }}">{{ $pOk ? 'PREVIEW READY' : 'PREVIEW ERROR' }}</span>
          @endif
        </div>
        <div class="hint mb-3">
          Upload file → klik <b>Preview</b>. Sistem akan menandai order yang sudah ada dan otomatis <b>di-skip</b> saat Confirm.
        </div>

        <form method="POST" action="{{ route('marketplace.import_orders.preview', ['platform' => $platform]) }}"
              enctype="multipart/form-data">
          @csrf

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Store</label>
              <select class="form-select" name="store_id" required>
                <option value="">— pilih —</option>
                @foreach($stores as $s)
                  <option value="{{ $s->id }}"
                    {{ (int)old('store_id', data_get($previewData,'params.store_id')) === $s->id ? 'selected' : '' }}>
                    {{ $s->name }}
                  </option>
                @endforeach
              </select>
              @error('store_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">Type</label>
              <select class="form-select" name="type" required>
                @foreach($types as $k => $v)
                  <option value="{{ $k }}"
                    {{ old('type', data_get($previewData,'params.type','shipping')) === $k ? 'selected' : '' }}>
                    {{ $v }}
                  </option>
                @endforeach
              </select>
              @error('type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-8">
              <label class="form-label">File (.xlsx)</label>
              <input type="file" class="form-control" name="file" accept=".xlsx,.xls" required>
              @error('file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
              <label class="form-label">Jika SKU tidak ada</label>
              @php $om = old('on_missing', data_get($previewData,'params.on_missing','stop')); @endphp
              <select class="form-select" name="on_missing">
                <option value="stop" {{ $om==='stop'?'selected':'' }}>Stop</option>
                <option value="skip" {{ $om==='skip'?'selected':'' }}>Skip line</option>
                <option value="create" {{ $om==='create'?'selected':'' }}>Create item (saat confirm)</option>
              </select>
              @error('on_missing')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="mt-3 d-flex gap-2 flex-wrap">
            <button class="btnx primary" type="submit">Preview</button>
            <a class="btnx" href="{{ route('marketplace.import_orders.form', ['platform'=>$platform]) }}">Refresh</a>
          </div>
        </form>

        @if($hasPreview && !empty(data_get($previewResult,'missing_base_skus')))
          <div class="mt-3">
            <div class="pill pill-bad">Missing BASE SKU ({{ count(data_get($previewResult,'missing_base_skus')) }})</div>
            <div class="muted small mt-2">
              {{ implode(', ', array_slice((array)data_get($previewResult,'missing_base_skus'), 0, 20)) }}
              @if(count((array)data_get($previewResult,'missing_base_skus')) > 20)
                … (lebih banyak)
              @endif
            </div>
          </div>
        @endif
      </div>
    </div>

    {{-- RIGHT: Preview summary + table --}}
    <div class="cardx">
      <div class="pad">
        <h2 class="h6 mb-2">2) Review & Confirm</h2>

        @if($hasPreview)
          <div class="kpis mb-3">
            <div class="kpi">
              <div class="label">Total Orders</div>
              <div class="val">{{ (int)($sum['total_orders'] ?? 0) }}</div>
            </div>
            <div class="kpi">
              <div class="label">New (Diimport)</div>
              <div class="val">{{ (int)($sum['new_orders'] ?? 0) }}</div>
            </div>
            <div class="kpi">
              <div class="label">Existing (Skip)</div>
              <div class="val">{{ (int)($sum['existing_orders'] ?? 0) }}</div>
            </div>
            <div class="kpi">
              <div class="label">New Amount</div>
              <div class="val">{{ $money((float)($sum['new_amount'] ?? 0)) }}</div>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-bold">Preview Orders (max {{ count($list) }})</div>
            <label class="d-flex align-items-center gap-2 small">
              <input type="checkbox" id="toggleExists">
              Tampilkan yang sudah ada
            </label>
          </div>

          <div id="previewWrap" class="hide-exists">
            <table>
              <thead>
                <tr>
                  <th style="width:30%">Order</th>
                  <th style="width:18%">Paid At</th>
                  <th class="text-end" style="width:12%">Qty</th>
                  <th class="text-end" style="width:18%">Subtotal</th>
                  <th style="width:16%">Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($list as $row)
                  @php
                    $exists = (bool) data_get($row,'exists');
                  @endphp
                  <tr class="{{ $exists ? 'row-exists' : '' }}">
                    <td class="mono">
                      {{ data_get($row,'order_no') }}
                      @if($exists)
                        <div class="muted small">invoice_id: {{ data_get($row,'existing_invoice_id') }}</div>
                      @endif
                    </td>
                    <td class="mono">{{ data_get($row,'paid_at','-') }}</td>
                    <td class="text-end">{{ (int)data_get($row,'qty_pcs',0) }}</td>
                    <td class="text-end">{{ $money((float)data_get($row,'subtotal',0)) }}</td>
                    <td>
                      <span class="pill {{ $exists ? 'pill-bad' : 'pill-ok' }}">
                        {{ $exists ? 'EXISTS (skip)' : 'NEW' }}
                      </span>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>

            <div class="hint mt-2">
              * Yang bertanda <b>EXISTS</b> tidak akan diinsert/update saat Confirm.
            </div>
          </div>

        @else
          <div class="muted">
            Belum ada preview. Upload file dan klik <b>Preview</b> dulu.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- Action bar: Confirm & Reset (muncul kalau preview ada) --}}
@if($hasPreview)
  @php
    $canConfirm = (bool) data_get($previewResult,'ok');
  @endphp
  <div class="actionbar">
    <div class="wrap">
      <form method="POST" action="{{ route('marketplace.import_orders.reset', ['platform'=>$platform]) }}">
        @csrf
        <button class="btnx danger" type="submit">Reset Preview</button>
      </form>

      <form method="POST" action="{{ route('marketplace.import_orders.confirm', ['platform'=>$platform]) }}">
        @csrf
        <input type="hidden" name="token" value="{{ data_get($previewData,'token') }}">
        <button class="btnx primary" type="submit" {{ $canConfirm ? '' : 'disabled' }}>
          Confirm Import (NEW saja)
        </button>
      </form>
    </div>
  </div>

  @push('scripts')
  <script>
    (function(){
      const toggle = document.getElementById('toggleExists');
      const wrap = document.getElementById('previewWrap');
      if(!toggle || !wrap) return;

      toggle.addEventListener('change', () => {
        if(toggle.checked){
          wrap.classList.remove('hide-exists');
        }else{
          wrap.classList.add('hide-exists');
        }
      });
    })();
  </script>
  @endpush
@endif
@endsection
