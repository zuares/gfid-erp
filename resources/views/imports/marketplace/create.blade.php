{{-- resources/views/imports/marketplace/create.blade.php --}}
@extends('layouts.app')
@section('title','Imports • Import Marketplace Shipments')

@php
  use Illuminate\Support\Facades\Storage;

  $channels = $channels ?? [];
  $stores   = $stores ?? [];
  $draft    = $draft ?? session('mp_import_preview');
  $selectedChannelId = $selectedChannelId ?? null;
  $selectedStoreId = $selectedStoreId ?? null;

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
  .page{ max-width:1440px; margin:0 auto; padding: 1rem .9rem 4.8rem; }
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

  .shipment-hero{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:1rem;
    flex-wrap:wrap;
    margin-bottom:.9rem;
    padding:1.15rem 1.2rem;
    border:1px solid var(--line);
    border-radius:18px;
    background:var(--card,#fff);
    box-shadow:var(--shadow);
  }
  .shipment-hero-title{ margin:0; color:var(--ink); font-size:1.35rem; font-weight:900; letter-spacing:-.04em; }
  .shipment-hero-sub{ max-width:48rem; margin-top:.25rem; color:var(--muted); font-size:.82rem; }
  .shipment-eyebrow{ display:inline-flex; align-items:center; gap:.35rem; margin-bottom:.35rem; color:var(--muted); font-size:.65rem; font-weight:900; letter-spacing:.1em; text-transform:uppercase; }
  .shipment-badges,.shipment-actions,.shipment-tabs{ display:flex; align-items:center; flex-wrap:wrap; gap:.45rem; }
  .shipment-badges{ margin-top:.8rem; }
  .shipment-chip{ display:inline-flex; align-items:center; gap:.35rem; padding:.33rem .62rem; border:1px solid var(--line2); border-radius:999px; background:var(--soft); color:var(--muted); font-size:.7rem; font-weight:800; white-space:nowrap; }
  .shipment-hero .btn{ border-radius:999px; font-weight:800; }
  .shipment-hero .btn-outline-secondary,.shipment-hero .btn-outline-warning{ background:var(--card,#fff); }

  .shipment-tabs-wrap{ display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:.9rem; padding:.28rem; border:1px solid var(--line); border-radius:999px; background:var(--card,#fff); box-shadow:var(--shadow); }
  .import-tabs{ display:flex; gap:.2rem; }
  .import-tab{ display:inline-flex; align-items:center; gap:.35rem; padding:.52rem .82rem; border-radius:999px; color:var(--ink); background:transparent; font-size:.76rem; font-weight:850; text-decoration:none; }
  .import-tab:hover{ color:var(--ink); background:var(--soft); }
  .import-tab.is-active{ background:#0f172a; color:#fff; }
  .shipment-tab-meta{ padding:0 .8rem; color:var(--muted); font-size:.72rem; font-weight:700; }

  .shipment-card{ border:1px solid var(--line); border-radius:18px; background:var(--card,#fff); box-shadow:var(--shadow); }
  .shipment-card-head{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap; padding:1rem 1rem .75rem; }
  .shipment-card-title{ margin:0; color:var(--ink); font-size:.9rem; font-weight:900; }
  .shipment-card-note{ margin-top:.2rem; color:var(--muted); font-size:.72rem; }
  .shipment-form-grid{ display:grid; grid-template-columns:1fr 1.25fr 1.55fr; gap:.75rem; padding:0 1rem 1rem; }
  .shipment-field label{ display:block; margin-bottom:.3rem; color:var(--muted); font-size:.68rem; font-weight:850; }
  .shipment-field .form-control,.shipment-field .form-select{ min-height:40px; }
  .shipment-field .form-help{ margin-top:.35rem; font-size:.72rem; }
  .shipment-upload-note{ display:flex; align-items:center; gap:.45rem; padding:.7rem .8rem; border:1px dashed var(--line2); border-radius:12px; background:var(--soft); color:var(--muted); font-size:.75rem; }
  .shipment-form-actions{ display:flex; justify-content:flex-end; gap:.5rem; padding:0 1rem 1rem; }
  .shipment-draft-card{ margin-bottom:.9rem; }
  @media(max-width:1100px){ .shipment-form-grid{ grid-template-columns:1fr 1fr; } }
  @media(max-width:820px){ .shipment-tabs-wrap{ border-radius:18px; } .shipment-tabs{ flex:1 1 100%; } .import-tab{ flex:1 1 auto; justify-content:center; } .shipment-tab-meta{ width:100%; padding:.3rem .55rem .5rem; } }
  @media(max-width:620px){ .shipment-form-grid{ grid-template-columns:1fr; } .shipment-actions{ width:100%; } .shipment-actions .btn{ flex:1 1 auto; } .shipment-form-actions{ padding-inline:1rem; } .shipment-form-actions .btn{ width:100%; } }
</style>
@endpush

@section('content')
<div class="page">

  {{-- HEADER --}}
  <section class="shipment-hero">
    <div>
      <div class="shipment-eyebrow"><i class="bi bi-truck"></i> Marketplace shipments • Import</div>
      <h1 class="shipment-hero-title">Import Marketplace Shipments</h1>
      <div class="shipment-hero-sub">
        Upload data pengiriman dari marketplace, periksa hasil normalisasi, lalu commit ke <span class="mono">mp_shipments</span>.
      </div>
      <div class="shipment-badges">
        <span class="shipment-chip"><i class="bi bi-upload"></i> Upload</span>
        <span class="shipment-chip"><i class="bi bi-search"></i> Preview</span>
        <span class="shipment-chip"><i class="bi bi-check2-circle"></i> Commit</span>

        @if(!empty($draft))
          <span class="shipment-chip" style="border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.10); color:#a16207;">
            Draft import tersedia
          </span>
          @if(!$canResume)
            <span class="shipment-chip" style="border-color: rgba(239,68,68,.35); background: rgba(239,68,68,.10); color:#b91c1c;">
              File draft tidak ditemukan (upload ulang)
            </span>
          @endif
        @endif
      </div>
    </div>

    <div class="shipment-actions head-actions">
      <a class="btn btn-outline-secondary btn-sm px-3" href="{{ route('imports.marketplace.index') }}"><i class="bi bi-arrow-left"></i> Kembali</a>

      @if(!empty($draft) && $canResume)
        <a class="btn btn-outline-warning btn-sm px-3" href="{{ route('imports.marketplace.draft') }}">
          <i class="bi bi-file-earmark-arrow-up"></i> Lanjutkan Draft
        </a>
      @elseif(!empty($draft))
        <a class="btn btn-outline-warning btn-sm px-3" href="{{ route('imports.marketplace.create') }}">
          <i class="bi bi-arrow-repeat"></i> Upload Ulang Draft
        </a>
      @endif
    </div>
  </section>

  <div class="shipment-tabs-wrap">
    <div class="import-tabs" role="tablist" aria-label="Navigasi import marketplace">
      <a class="import-tab is-active" role="tab" aria-selected="true" href="{{ route('imports.marketplace.create') }}"><i class="bi bi-box-seam"></i> Import Order</a>
      <a class="import-tab" role="tab" aria-selected="false" href="{{ route('imports.marketplace_income.create') }}"><i class="bi bi-wallet2"></i> Import Income</a>
    </div>
    <div class="shipment-tab-meta">Order / shipment marketplace</div>
  </div>

  @if(session('error'))
    <div class="alert alert-danger py-2">{{ session('error') }}</div>
  @endif
  @if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
  @endif

  {{-- DRAFT CARD --}}
  @if(!empty($draft))
    <section class="shipment-card shipment-draft-card p-3">
      <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div class="flex-grow-1">
          <div class="d-flex align-items-center gap-2 mb-1">
            <div class="fw-bold">Draft preview masih ada</div>
            <span class="shipment-chip" style="border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.10); color:#a16207;">
              Draft
            </span>
          </div>

          <div class="text-muted small d-flex flex-wrap gap-2">
            <span class="shipment-chip">Channel: <span class="mono">{{ $draft['channel_key'] ?? ($draft['channel_name'] ?? '-') }}</span></span>
            <span class="shipment-chip">Store: <span class="mono">{{ $draft['store_name'] ?? ($draft['store_id'] ?? '-') }}</span></span>
            <span class="shipment-chip">File: <span class="mono">{{ $draft['source_file'] ?? '-' }}</span></span>
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
    </section>
  @endif

  {{-- UPLOAD FORM --}}
  <form method="POST" action="{{ route('imports.marketplace.preview') }}" enctype="multipart/form-data" class="shipment-card">
    @csrf

    <div class="shipment-card-head">
      <div>
        <h2 class="shipment-card-title"><i class="bi bi-cloud-arrow-up"></i> Upload File</h2>
        <div class="shipment-card-note">Pilih sumber data dan file yang ingin diproses ke preview.</div>
      </div>
      <span class="shipment-chip mono">.xlsx • .xls • .csv</span>
    </div>

    <div class="shipment-form-grid">
      {{-- Channel --}}
      <div class="shipment-field">
        <label class="form-label small" style="color:var(--muted)">Channel</label>
        <select name="channel_id" id="channelSelect" class="form-select" required>
          <option value="">— pilih channel —</option>
          @foreach($channels as $ch)
            <option value="{{ $ch->id }}" @selected((string) $selectedChannelId === (string) $ch->id)>{{ $ch->name }}</option>
          @endforeach
        </select>
        <div class="form-help mt-1">Pilih channel terlebih dahulu</div>
      </div>

      {{-- Store (filtered by channel_id) --}}
      <div class="shipment-field">
        <label class="form-label small" style="color:var(--muted)">Store</label>
        <select name="store_id" id="storeSelect" class="form-select" required disabled data-selected-store-id="{{ $selectedStoreId ?? '' }}">
          <option value="">— pilih store —</option>
          @foreach($stores as $st)
            <option value="{{ $st->id }}" data-channel-id="{{ (string)($st->channel_id ?? '') }}" @selected((string) $selectedStoreId === (string) $st->id)>
              {{ $st->name }}
            </option>
          @endforeach
        </select>
        <div class="form-help mt-1" id="storeHint" style="display:none;"></div>
      </div>

      {{-- File --}}
      <div class="shipment-field">
        <label class="form-label small" style="color:var(--muted)">File</label>
        <input type="file" name="file" class="form-control" required accept=".xlsx,.xls,.csv">
        <div class="shipment-upload-note mt-2"><i class="bi bi-info-circle"></i> Pastikan header kolom sesuai template marketplace.</div>
      </div>
    </div>

    <div class="shipment-form-actions">
      <button class="btn btn-primary px-3"><i class="bi bi-search"></i> Preview Import</button>
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

  function applyStoreFilter(preserveInitialStore = false){
    const channelId = norm(ch.value);

    if (!channelId){
      st.value = '';
      st.disabled = true;
      allOptions.forEach(opt => { opt.hidden = true; opt.disabled = true; });
      if (hint){ hint.style.display = 'none'; hint.textContent = ''; }
      return;
    }

    st.disabled = false;
    let visible = 0;

    allOptions.forEach(opt => {
      const storeChId = norm(opt.dataset.channelId);
      const ok = (storeChId === channelId) || (storeChId === '');
      opt.hidden = !ok;
      opt.disabled = !ok;
      if (ok) visible++;
    });

    const preferredStoreId = preserveInitialStore ? norm(st.dataset.selectedStoreId) : '';
    const preferredOption = preferredStoreId
      ? allOptions.find(opt => opt.value === preferredStoreId && !opt.disabled)
      : null;
    st.value = preferredOption ? preferredStoreId : '';

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

  ch.addEventListener('change', () => applyStoreFilter(false));
  applyStoreFilter(true);
})();
</script>
@endpush
