{{-- resources/views/marketplace/import-income.blade.php --}}
@extends('layouts.app')
@section('title', 'Marketplace • Import Income')

@push('head')
<style>
  .page{ max-width:980px; margin:0 auto; padding:1rem .9rem 5rem; }
  @media(min-width:768px){ .page{ padding:1.1rem 1rem 5rem; } }

  .cardx{
    background: var(--card, #fff);
    border:1px solid rgba(148,163,184,.20);
    border-radius:16px;
    box-shadow:0 10px 24px rgba(15,23,42,.06), 0 0 0 1px rgba(15,23,42,.02);
  }
  .pad{ padding:1rem; }
  .muted{ color: rgba(100,116,139,1); }

  .tabs{ display:flex; gap:.5rem; flex-wrap:wrap; }
  .tab{
    display:inline-flex; align-items:center; gap:.45rem;
    padding:.45rem .7rem; border-radius:999px;
    border:1px solid rgba(148,163,184,.28);
    background:rgba(148,163,184,.08);
    text-decoration:none;
    color:inherit;
    font-weight:600;
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

  .btnx{
    display:inline-flex; align-items:center; justify-content:center;
    padding:.55rem .9rem; border-radius:12px;
    border:1px solid rgba(148,163,184,.28);
    background:rgba(148,163,184,.08);
    text-decoration:none; color:inherit; font-weight:700;
  }
  .btnx.primary{ background:rgba(59,130,246,.14); border-color:rgba(59,130,246,.35); }
</style>
@endpush

@section('content')
@php
  $result = session('result');
  $ok = (bool) data_get($result,'ok');
  $msg = (string) data_get($result,'message','');
@endphp

<div class="page">
  <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1">Import Income</h1>
      <div class="muted small">
        Platform: <span class="pill pill-info">{{ $platformLabel ?? strtoupper($platform) }}</span>
      </div>
    </div>

    <div class="tabs">
      @foreach(($platforms ?? []) as $key => $label)
        <a class="tab {{ $key === $platform ? 'active' : '' }}"
           href="{{ route('marketplace.import_income.form', ['platform' => $key]) }}">
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

  <div class="cardx">
    <div class="pad">
      <form method="POST"
            action="{{ route('marketplace.import_income.run', ['platform' => $platform]) }}"
            enctype="multipart/form-data">
        @csrf

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Store</label>
            <select class="form-select" name="store_id" required>
              <option value="">— pilih —</option>
              @foreach($stores as $s)
                <option value="{{ $s->id }}" {{ (int)old('store_id') === $s->id ? 'selected' : '' }}>
                  {{ $s->name }}
                </option>
              @endforeach
            </select>
            @error('store_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">On Missing SKU</label>
            <select class="form-select" name="on_missing">
              <option value="skip" {{ old('on_missing','skip')==='skip'?'selected':'' }}>Skip</option>
              <option value="stop" {{ old('on_missing')==='stop'?'selected':'' }}>Stop</option>
            </select>
            @error('on_missing')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-8">
            <label class="form-label">File (.xlsx)</label>
            <input type="file" class="form-control" name="file" accept=".xlsx,.xls" required>
            @error('file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Dry Run</label>
            <select class="form-select" name="dry_run">
              <option value="1" {{ old('dry_run','1')==='1'?'selected':'' }}>Yes (Preview Only)</option>
              <option value="0" {{ old('dry_run')==='0'?'selected':'' }}>No (Write)</option>
            </select>
            @error('dry_run')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="mt-3">
          <button class="btnx primary" type="submit">Run Import</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
