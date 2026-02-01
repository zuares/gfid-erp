{{-- resources/views/imports/marketplace/create.blade.php --}}
@extends('layouts.app')
@section('title','Imports • Import Marketplace Shipments')

@php
  use Illuminate\Support\Facades\Storage;

  $channels = $channels ?? [];
  $stores   = $stores ?? [];
  $draft    = $draft ?? session('mp_import_preview');

  $canResume = false;
  if (!empty($draft) && !empty($draft['disk']) && !empty($draft['stored_path']) && !empty($draft['channel_key'])) {
    try {
      $canResume = Storage::disk($draft['disk'])->exists($draft['stored_path']);
    } catch (\Throwable $e) {
      $canResume = false;
    }
  }
@endphp

@push('head')
<style>
  .page{ max-width:1220px; margin:0 auto; padding: 1rem .9rem 4.8rem; }
  @media(min-width:768px){ .page{ padding: 1.1rem 1rem 4.8rem; } }

  :root{
    --line: rgba(148,163,184,.18);
    --line2: rgba(148,163,184,.22);
    --ink: rgba(15,23,42,.92);
    --muted: rgba(100,116,139,1);
    --soft: rgba(148,163,184,.06);
    --soft2: rgba(148,163,184,.10);
    --shadow: 0 10px 24px rgba(15,23,42,.05);
  }
  body[data-theme="dark"]{
    --ink: rgba(226,232,240,.92);
    --muted: rgba(148,163,184,.85);
    --line: rgba(148,163,184,.14);
    --line2: rgba(148,163,184,.18);
    --soft: rgba(148,163,184,.08);
    --soft2: rgba(148,163,184,.12);
    --shadow: 0 12px 28px rgba(0,0,0,.35);
  }

  .cardx{ border:1px solid var(--line); border-radius:14px; background: var(--card, #fff); box-shadow: var(--shadow); }
  .chip{
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.18rem .55rem; border-radius:999px; font-size:.78rem;
    border:1px solid var(--line2); background: var(--soft); white-space:nowrap;
  }
  .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }
  .head-actions .btn{ white-space:nowrap; }
  .form-help{ color: var(--muted); font-size:.85rem; }
  .row-title{ font-weight:800; letter-spacing:.01em; color: var(--ink); }

  .muted{ color: var(--muted); }
</style>
@endpush

@section('content')
<div class="page">

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1 fw-bold">Import Marketplace Shipments</h1>
      <div class="text-muted small d-flex flex-wrap gap-1 align-items-center">
        <span class="chip">Upload file → Preview → Commit</span>

        @if(!empty($draft))
          <span class="chip" style="border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.10);">
            Draft import tersedia
          </span>
          @if(!$canResume)
            <span class="chip" style="border-color: rgba(239,68,68,.35); background: rgba(239,68,68,.10);">
              File draft tidak ditemukan (upload ulang)
            </span>
          @endif
        @endif
      </div>
    </div>

    <div class="d-flex gap-2 align-items-center head-actions">
      <a class="btn btn-outline-secondary btn-sm px-3" href="{{ route('imports.marketplace.index') }}">← Kembali</a>

      @if(!empty($draft) && $canResume)
        <a class="btn btn-outline-warning btn-sm px-3" href="{{ route('imports.marketplace.draft') }}">
          Lanjutkan Draft
        </a>
      @elseif(!empty($draft))
        <a class="btn btn-outline-warning btn-sm px-3" href="{{ route('imports.marketplace.create') }}">
          Draft ada (upload ulang)
        </a>
      @endif
    </div>
  </div>

  @if(session('error'))
    <div class="alert alert-danger py-2">{{ session('error') }}</div>
  @endif
  @if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
  @endif

  {{-- DRAFT CARD --}}
  @if(!empty($draft))
    <div class="cardx p-3 mb-3">
      <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div class="flex-grow-1">
          <div class="d-flex align-items-center gap-2 mb-1">
            <div class="fw-bold">Draft preview masih ada</div>
            <span class="chip" style="border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.10);">
              Draft
            </span>
          </div>

          <div class="text-muted small d-flex flex-wrap gap-2">
            <span class="chip">Channel: <span class="mono">{{ $draft['channel_key'] ?? ($draft['channel_name'] ?? '-') }}</span></span>
            <span class="chip">Store: <span class="mono">{{ $draft['store_name'] ?? ($draft['store_id'] ?? '-') }}</span></span>
            <span class="chip">File: <span class="mono">{{ $draft['source_file'] ?? '-' }}</span></span>
          </div>

          @if(!$canResume)
            <div class="muted small mt-2">
              Draft ada di session, tapi file tidak ada di storage. Kamu perlu upload ulang file.
            </div>
          @endif
        </div>

        <div class="d-flex gap-2">
          @if($canResume)
            <a class="btn btn-success btn-sm px-3" href="{{ route('imports.marketplace.draft') }}">
              Buka Preview Draft
            </a>
          @endif

          <form method="POST" action="{{ route('imports.marketplace.cancel') }}" onsubmit="return confirm('Batalkan draft?')">
            @csrf
            <button class="btn btn-outline-danger btn-sm px-3">Cancel</button>
          </form>
        </div>
      </div>
    </div>
  @endif

  {{-- UPLOAD FORM --}}
  <form method="POST" action="{{ route('imports.marketplace.preview') }}" enctype="multipart/form-data" class="cardx p-3">
    @csrf

    <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="row-title">Upload File</div>
      <div class="text-muted small">Format: xlsx / xls / csv</div>
    </div>

    <div class="row g-3">
      {{-- Channel --}}
      <div class="col-md-3">
        <label class="form-label small" style="color:var(--muted)">Channel</label>
        <select name="channel_id" id="channelSelect" class="form-select" required>
          <option value="">— pilih channel —</option>
          @foreach($channels as $ch)
            <option value="{{ $ch->id }}">{{ $ch->name }}</option>
          @endforeach
        </select>
        <div class="form-help mt-1">Pilih channel terlebih dahulu</div>
      </div>

      {{-- Store (filtered by channel_id) --}}
      <div class="col-md-4">
        <label class="form-label small" style="color:var(--muted)">Store</label>
        <select name="store_id" id="storeSelect" class="form-select" required disabled>
          <option value="">— pilih store —</option>
          @foreach($stores as $st)
            <option value="{{ $st->id }}" data-channel-id="{{ (string)($st->channel_id ?? '') }}">
              {{ $st->name }}
            </option>
          @endforeach
        </select>
        <div class="form-help mt-1" id="storeHint" style="display:none;"></div>
      </div>

      {{-- File --}}
      <div class="col-md-5">
        <label class="form-label small" style="color:var(--muted)">File</label>
        <input type="file" name="file" class="form-control" required accept=".xlsx,.xls,.csv">
        <div class="form-help mt-1">Pastikan header kolom sesuai template marketplace.</div>
      </div>

      <div class="col-12 d-flex justify-content-end">
        <button class="btn btn-success px-3">Preview Import →</button>
      </div>
    </div>
  </form>

</div>
@endsection

@push('scripts')
<script>
(function(){
  const ch = document.getElementById('channelSelect');
  const st = document.getElementById('storeSelect');
  const hint = document.getElementById('storeHint');
  if (!ch || !st) return;

  const allOptions = Array.from(st.querySelectorAll('option')).slice(1); // skip placeholder

  function norm(v){ return String(v ?? '').trim(); }

  function applyStoreFilter(){
    const channelId = norm(ch.value);

    if (!channelId){
      st.value = '';
      st.disabled = true;
      allOptions.forEach(opt => { opt.hidden = true; opt.disabled = true; });
      if (hint){ hint.style.display = 'none'; hint.textContent = ''; }
      return;
    }

    st.disabled = false;
    st.value = '';
    let visible = 0;

    allOptions.forEach(opt => {
      const storeChId = norm(opt.dataset.channelId);
      const ok = (storeChId === channelId) || (storeChId === '');
      opt.hidden = !ok;
      opt.disabled = !ok;
      if (ok) visible++;
    });

    if (hint){
      if (visible === 0){
        hint.style.display = 'block';
        hint.textContent = 'Tidak ada store untuk channel ini.';
      } else {
        hint.style.display = 'none';
        hint.textContent = '';
      }
    }
  }

  ch.addEventListener('change', applyStoreFilter);
  applyStoreFilter();
})();
</script>
@endpush
