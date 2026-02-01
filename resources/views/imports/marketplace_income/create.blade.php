@extends('layouts.app')
@section('title','Import • Marketplace Income')

@push('head')
<style>
  .page{ max-width:980px; margin:0 auto; padding:.9rem .85rem 5rem; }
  .cardx{ background:var(--card,#fff); border:1px solid rgba(148,163,184,.22); border-radius:16px; box-shadow:0 10px 24px rgba(15,23,42,.06); }
  .pad{ padding:1rem; }
  .muted{ color:rgba(100,116,139,1); }
  .mono{ font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; }
  .chip{ display:inline-flex; align-items:center; gap:.35rem; padding:.22rem .55rem; border-radius:999px; border:1px solid rgba(148,163,184,.30); background:rgba(148,163,184,.08); font-size:.82rem; }
  .chip b{ font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-weight:700; }
  .hint{ font-size:.82rem; color:rgba(100,116,139,1); }
</style>
@endpush

@section('content')
<div class="page">
  <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1">Import Marketplace • Income</h1>
      <div class="muted small">
        Import report payout ke <span class="chip"><b>mp_incomes</b></span> dan apply snapshot ke <span class="chip"><b>mp_shipments</b></span> (primary).
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('imports.marketplace.create') }}" class="btn btn-outline-secondary btn-sm">Ke Import Shipments</a>
    </div>
  </div>

  @if(session('error')) <div class="alert alert-danger py-2">{{ session('error') }}</div> @endif
  @if(session('success')) <div class="alert alert-success py-2">{{ session('success') }}</div> @endif

  @if(!empty($draft))
    <div class="cardx pad mb-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="fw-semibold">Draft preview income masih ada</div>
          <div class="muted small mt-1">
            <span class="chip">channel <b>{{ $draft['channel'] }}</b></span>
            <span class="chip">store_id <b>{{ $draft['store_id'] }}</b></span>
            <span class="chip">file <b>{{ $draft['source_file'] }}</b></span>
          </div>
        </div>
        <div class="d-flex gap-2">
          <form method="POST" action="{{ route('imports.marketplace_income.cancel') }}">
            @csrf
            <button class="btn btn-outline-danger btn-sm" type="submit">Buang Draft</button>
          </form>
        </div>
      </div>
      <div class="hint mt-2">
        Kamu bisa langsung commit dari halaman preview (atau upload ulang untuk ganti file).
      </div>
    </div>
  @endif

  <div class="cardx pad">
    <form method="POST" action="{{ route('imports.marketplace_income.preview') }}" enctype="multipart/form-data">
      @csrf

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Channel</label>
          <select name="channel" class="form-select" required>
            <option value="shopee">Shopee (store_id=1)</option>
            <option value="tiktok">TikTok (store_id=2)</option>
          </select>
          <div class="hint mt-1">Pastikan sesuai mapping kamu: Shopee=1, TikTok=2.</div>
        </div>

        <div class="col-md-4">
          <label class="form-label">Store</label>
          <select name="store_id" class="form-select" required>
            <option value="" disabled selected>Pilih store…</option>
            @foreach($stores as $s)
              <option value="{{ $s->id }}">{{ $s->name }} (ID: {{ $s->id }})</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">File Income (xlsx/xls/csv)</label>
          <input type="file" name="file" class="form-control" required />
          <div class="hint mt-1">Shopee: biasanya ada sheet <span class="mono">Income</span>. TikTok: payout report per order.</div>
        </div>

        <div class="col-12 d-flex justify-content-end">
          <button class="btn btn-primary" type="submit">Preview Income</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection
