@extends('layouts.app')

@section('title','Import Shopee Orders')

@php
  $result = session('result');

  $pv = session('shopee_orders_preview');
  $pvResult = data_get($pv, 'result');
  $pvOk = data_get($pvResult, 'ok') === true && data_get($pvResult, 'mode') === 'dry-run';
  $pvMissing = (array) data_get($pvResult, 'missing_base_skus', []);
@endphp

@section('content')
<div class="container py-3" style="max-width:980px;">

  {{-- Header --}}
  <div class="d-flex flex-wrap gap-2 align-items-start justify-content-between mb-3">
    <div>
      <h4 class="mb-1">Import Shopee Orders</h4>
      <div class="text-muted small">
        Alur: <b>Preview</b> → cek missing SKU & ringkasan → <b>Confirm</b> → baru masuk database.
      </div>
    </div>

    <div class="d-flex gap-2">
      <form method="POST" action="{{ route('marketplace.shopee.import_orders.reset') }}">
        @csrf
        <button class="btn btn-outline-secondary btn-sm">
          Reset Preview
        </button>
      </form>
    </div>
  </div>

  {{-- Result JSON (debug, optional) --}}
  @if($result)
    <details class="mb-3">
      <summary class="text-muted small">Lihat detail JSON result</summary>
      <pre class="p-3 bg-light border rounded mt-2 mb-0" style="white-space:pre-wrap;">{{ json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
    </details>
  @endif

  {{-- STEP 1: Preview --}}
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <div class="d-flex align-items-start justify-content-between gap-2">
        <div>
          <div class="fw-bold mb-1">Step 1 — Preview (Dry Run)</div>
          <div class="text-muted small">
            Upload file report Shopee, lalu klik <b>Preview</b>. Sistem tidak menulis DB.
          </div>
        </div>
        <span class="badge text-bg-primary">Preview</span>
      </div>

      <hr class="my-3">

      <form method="POST"
            action="{{ route('marketplace.shopee.import_orders.preview') }}"
            enctype="multipart/form-data"
            class="row g-3">
        @csrf

        <div class="col-md-6">
          <label class="form-label">Store <span class="text-danger">*</span></label>
          <select name="store_id" class="form-select" required>
            <option value="">-- pilih store --</option>
            @foreach($stores as $s)
              <option value="{{ $s->id }}" @selected(old('store_id') == $s->id)>
                {{ $s->name }}
              </option>
            @endforeach
          </select>
          <div class="form-text">Pilih store Shopee yang sesuai.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Type <span class="text-danger">*</span></label>
          <select name="type" class="form-select" required>
            @foreach($types as $k=>$v)
              <option value="{{ $k }}" @selected(old('type', 'shipping') == $k)>{{ $v }}</option>
            @endforeach
          </select>
          <div class="form-text">Pilih sesuai report (shipping / completed).</div>
        </div>

        <div class="col-12">
          <label class="form-label">File Report Shopee (.xlsx/.xls) <span class="text-danger">*</span></label>
          <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
          <div class="form-text">
            Upload file export dari Shopee. Pastikan format kolom sesuai report Shopee.
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Jika BASE SKU tidak ditemukan</label>
          <select name="on_missing" class="form-select">
            <option value="stop" @selected(old('on_missing','stop')==='stop')>STOP (batal import)</option>
            <option value="skip" @selected(old('on_missing')==='skip')>SKIP (lewati baris sku missing)</option>
            <option value="create" @selected(old('on_missing')==='create')>AUTO CREATE Item (saat confirm)</option>
          </select>
          <div class="form-text">
            Rekomendasi: <b>STOP</b> untuk kontrol penuh. CREATE hanya kalau kamu yakin SKU rapih.
          </div>
        </div>

        <div class="col-md-6 d-flex align-items-end">
          <button class="btn btn-primary w-100">
            Preview (Dry Run)
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- STEP 2: Review + Confirm --}}
  @if($pvOk)
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex flex-wrap gap-2 align-items-start justify-content-between">
          <div>
            <div class="fw-bold mb-1">Step 2 — Review hasil Preview</div>
            <div class="text-muted small">Kalau sudah benar, klik <b>Confirm Import</b> untuk menulis ke database.</div>
          </div>
          <span class="badge text-bg-success">Siap Confirm</span>
        </div>

        <hr class="my-3">

        {{-- Ringkasan --}}
        <div class="row g-2 mb-3">
          <div class="col-md-3">
            <div class="p-3 border rounded bg-light">
              <div class="text-muted small">Orders</div>
              <div class="fs-5 fw-bold">{{ (int) data_get($pvResult,'orders',0) }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 border rounded bg-light">
              <div class="text-muted small">Skipped Lines</div>
              <div class="fs-5 fw-bold">{{ (int) data_get($pvResult,'skipped_lines',0) }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 border rounded bg-light">
              <div class="text-muted small">Missing Base SKU</div>
              <div class="fs-5 fw-bold">{{ count($pvMissing) }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 border rounded bg-light">
              <div class="text-muted small">Mode</div>
              <div class="fs-6 fw-bold text-uppercase">{{ data_get($pvResult,'mode') }}</div>
            </div>
          </div>
        </div>

        {{-- Missing SKU --}}
        @if(count($pvMissing) > 0)
          <div class="alert alert-warning">
            <div class="fw-bold mb-1">Ada BASE SKU yang belum ada di Master Items</div>
            <div class="small">
              STOP = confirm akan berhenti. SKIP = baris dilewati. CREATE = item dibuat saat confirm.
            </div>
            <div class="mt-2 small font-monospace" style="white-space:normal;">
              {{ implode(', ', $pvMissing) }}
            </div>
          </div>
        @endif

        {{-- Tabel preview --}}
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
          <div class="fw-bold">Preview Orders (maks 50)</div>
          <div class="text-muted small">{{ data_get($pvResult,'note') }}</div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle">
            <thead>
              <tr>
                <th style="min-width:170px;">Order No</th>
                <th class="text-end">Rows</th>
                <th class="text-end">Kept</th>
                <th class="text-end">Qty (PCS)</th>
                <th style="min-width:170px;">Paid At</th>
              </tr>
            </thead>
            <tbody>
              @foreach((array) data_get($pvResult,'preview',[]) as $row)
                <tr>
                  <td class="font-monospace">{{ data_get($row,'order_no') }}</td>
                  <td class="text-end">{{ (int) data_get($row,'rows',0) }}</td>
                  <td class="text-end">{{ (int) data_get($row,'kept_rows',0) }}</td>
                  <td class="text-end">{{ (int) data_get($row,'qty_pcs',0) }}</td>
                  <td class="font-monospace">{{ data_get($row,'paid_at') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        {{-- Confirm --}}
        <div class="mt-3 d-flex flex-wrap gap-2 justify-content-end">
          <form method="POST" action="{{ route('marketplace.shopee.import_orders.confirm') }}">
            @csrf
            <input type="hidden" name="token" value="{{ data_get($pv,'token') }}">
            <button class="btn btn-success"
                    onclick="return confirm('Yakin import ke database? Ini akan membuat / update Sales Invoice & Lines.');">
              Confirm Import (Write to DB)
            </button>
          </form>
        </div>
      </div>
    </div>
  @endif

</div>
@endsection
