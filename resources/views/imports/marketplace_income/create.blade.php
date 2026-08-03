@extends('layouts.app')
@section('title','Import • Marketplace Income')

@push('head')
<style>
  .page{ max-width:1440px; margin:0 auto; padding:1rem .9rem 5rem; }
  .cardx{ background:var(--card,#fff); border:1px solid rgba(148,163,184,.22); border-radius:16px; box-shadow:0 10px 24px rgba(15,23,42,.06); }
  .pad{ padding:1rem; }
  .muted{ color:rgba(100,116,139,1); }
  .mono{ font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; }
  .chip{ display:inline-flex; align-items:center; gap:.35rem; padding:.22rem .55rem; border-radius:999px; border:1px solid rgba(148,163,184,.30); background:rgba(148,163,184,.08); font-size:.82rem; }
  .chip b{ font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-weight:700; }
  .hint{ font-size:.82rem; color:rgba(100,116,139,1); }

  :root{
    --income-ink:#0f172a;
    --income-muted:#64748b;
    --income-line:rgba(148,163,184,.18);
    --income-soft:rgba(148,163,184,.08);
    --income-card:#fff;
    --income-shadow:0 14px 34px rgba(15,23,42,.06);
  }
  body[data-theme="dark"]{
    --income-ink:#e2e8f0;
    --income-muted:#94a3b8;
    --income-line:rgba(148,163,184,.16);
    --income-soft:rgba(148,163,184,.10);
    --income-card:rgba(15,23,42,.92);
    --income-shadow:0 14px 34px rgba(0,0,0,.24);
  }
  .income-hero{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:1rem;
    flex-wrap:wrap;
    margin-bottom:.9rem;
    padding:1.15rem 1.2rem;
    border:1px solid #e5e7eb;
    border-radius:18px;
    background:#fff;
    box-shadow:var(--income-shadow);
  }
  .income-hero-title{ margin:0; color:#1f2937; font-size:1.35rem; font-weight:900; letter-spacing:-.04em; }
  .income-hero-sub{ max-width:52rem; margin-top:.25rem; color:#64748b; font-size:.82rem; }
  .income-eyebrow{ display:inline-flex; align-items:center; gap:.35rem; margin-bottom:.35rem; color:#475569; font-size:.65rem; font-weight:900; letter-spacing:.1em; text-transform:uppercase; }
  .income-badges,.income-actions,.income-tabs{ display:flex; align-items:center; flex-wrap:wrap; gap:.45rem; }
  .income-badges{ margin-top:.8rem; }
  .income-chip{ display:inline-flex; align-items:center; gap:.35rem; padding:.33rem .62rem; border:1px solid #e2e8f0; border-radius:999px; background:#f8fafc; color:#475569; font-size:.7rem; font-weight:800; white-space:nowrap; }
  .income-hero .btn{ border-radius:999px; font-weight:800; }
  .income-hero .btn-outline-secondary{ background:#fff; border-color:#cbd5e1; color:#334155; }
  .income-tabs-wrap{ display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:.9rem; padding:.28rem; border:1px solid var(--income-line); border-radius:999px; background:var(--income-card); box-shadow:var(--income-shadow); }
  .income-tab{ display:inline-flex; align-items:center; gap:.35rem; padding:.52rem .82rem; border-radius:999px; color:var(--income-ink); background:transparent; font-size:.76rem; font-weight:850; text-decoration:none; }
  .income-tab:hover{ color:var(--income-ink); background:var(--income-soft); }
  .income-tab.is-active{ background:#0f172a; color:#fff; }
  .income-tab-meta{ padding:0 .8rem; color:var(--income-muted); font-size:.72rem; font-weight:700; }
  .income-card{ border:1px solid var(--income-line); border-radius:18px; background:var(--income-card); box-shadow:var(--income-shadow); }
  .income-card-head{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap; padding:1rem 1rem .75rem; }
  .income-card-title{ margin:0; color:var(--income-ink); font-size:.9rem; font-weight:900; }
  .income-card-note{ margin-top:.2rem; color:var(--income-muted); font-size:.72rem; }
  .income-form-grid{ display:grid; grid-template-columns:1fr 1.25fr 1.55fr; gap:.75rem; padding:0 1rem 1rem; }
  .income-field label{ display:block; margin-bottom:.3rem; color:var(--income-muted); font-size:.68rem; font-weight:850; }
  .income-field .form-control,.income-field .form-select{ min-height:40px; }
  .income-upload-note{ display:flex; align-items:center; gap:.45rem; padding:.7rem .8rem; border:1px dashed rgba(148,163,184,.30); border-radius:12px; background:var(--income-soft); color:var(--income-muted); font-size:.75rem; }
  .income-form-actions{ display:flex; justify-content:flex-end; gap:.5rem; padding:0 1rem 1rem; }
  .income-draft-card{ margin-bottom:.9rem; }
  @media(max-width:1100px){ .income-form-grid{ grid-template-columns:1fr 1fr; } }
  @media(max-width:820px){ .income-tabs-wrap{ border-radius:18px; } .income-tabs{ flex:1 1 100%; } .income-tab{ flex:1 1 auto; justify-content:center; } .income-tab-meta{ width:100%; padding:.3rem .55rem .5rem; } }
  @media(max-width:620px){ .income-form-grid{ grid-template-columns:1fr; } .income-actions{ width:100%; } .income-actions .btn{ flex:1 1 auto; } .income-form-actions .btn{ width:100%; } }
</style>
@endpush

@section('content')
<div class="page">
  <section class="income-hero">
    <div>
      <div class="income-eyebrow"><i class="bi bi-wallet2"></i> Marketplace income • Import</div>
      <h1 class="income-hero-title">Import Marketplace Income</h1>
      <div class="income-hero-sub">
        Upload report payout, periksa hasil parsing per order, lalu commit ke <span class="mono">mp_incomes</span> dan snapshot shipment yang cocok.
      </div>
      <div class="income-badges">
        <span class="income-chip"><i class="bi bi-wallet2"></i> mp_incomes</span>
        <span class="income-chip"><i class="bi bi-truck"></i> mp_shipments</span>
        <span class="income-chip"><i class="bi bi-search"></i> Preview sebelum commit</span>
      </div>
    </div>
    <div class="income-actions">
      <a href="{{ route('imports.marketplace_income.index') }}" class="btn btn-outline-secondary btn-sm px-3"><i class="bi bi-arrow-left"></i> Kembali</a>
      <a href="{{ route('imports.marketplace.create') }}" class="btn btn-outline-secondary btn-sm px-3"><i class="bi bi-box-seam"></i> Import Order</a>
    </div>
  </section>

  <div class="income-tabs-wrap">
    <div class="income-tabs" role="tablist" aria-label="Navigasi import marketplace">
      <a class="income-tab" role="tab" aria-selected="false" href="{{ route('imports.marketplace.create') }}"><i class="bi bi-box-seam"></i> Import Order</a>
      <a class="income-tab is-active" role="tab" aria-selected="true" href="{{ route('imports.marketplace_income.create') }}"><i class="bi bi-wallet2"></i> Import Income</a>
    </div>
    <div class="income-tab-meta">Payout / income marketplace</div>
  </div>

  @if(session('error')) <div class="alert alert-danger py-2">{{ session('error') }}</div> @endif
  @if(session('success')) <div class="alert alert-success py-2">{{ session('success') }}</div> @endif

  @if(!empty($draft))
    <section class="income-card income-draft-card pad">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="fw-semibold">Draft preview income masih ada</div>
          <div class="muted small mt-1">
            <span class="income-chip">channel <b>{{ $draft['channel'] }}</b></span>
            <span class="income-chip">store_id <b>{{ $draft['store_id'] }}</b></span>
            <span class="income-chip">file <b>{{ $draft['source_file'] }}</b></span>
          </div>
        </div>
        <div class="d-flex gap-2">
          <a class="btn btn-primary btn-sm px-3" href="{{ route('imports.marketplace_income.preview_page') }}">
            <i class="bi bi-eye"></i> Buka Preview
          </a>
          <form method="POST" action="{{ route('imports.marketplace_income.cancel') }}">
            @csrf
            <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash3"></i> Buang Draft</button>
          </form>
        </div>
      </div>
      <div class="hint mt-2">
        Kamu bisa langsung commit dari halaman preview (atau upload ulang untuk ganti file).
      </div>
    </section>
  @endif

  <section class="income-card">
    <form method="POST" action="{{ route('imports.marketplace_income.preview') }}" enctype="multipart/form-data">
      @csrf

      <div class="income-card-head">
        <div>
          <h2 class="income-card-title"><i class="bi bi-cloud-arrow-up"></i> Upload File Income</h2>
          <div class="income-card-note">Pilih sumber payout dan file yang ingin diproses ke preview.</div>
        </div>
        <span class="income-chip mono">.xlsx • .xls • .csv</span>
      </div>

      <div class="income-form-grid">
        <div class="income-field">
          <label>Toko</label>
          <select name="store_id" id="incomeStoreSelect" class="form-select" required>
            <option value="" disabled @selected(empty($selectedStoreId))>Pilih toko…</option>
            @foreach($stores as $s)
              @php
                $incomeChannelCode = strtoupper((string)($s->channel?->code ?? ''));
                $incomeChannelName = strtoupper((string)($s->channel?->name ?? ''));
                $incomeChannel = str_contains($incomeChannelCode . ' ' . $incomeChannelName, 'TIKTOK') || in_array($incomeChannelCode, ['TTK', 'TKT'], true)
                  ? 'tiktok'
                  : (str_contains($incomeChannelCode . ' ' . $incomeChannelName, 'SHOPEE') || $incomeChannelCode === 'SHP' ? 'shopee' : '');
              @endphp
              @if($incomeChannel !== '')
                <option value="{{ $s->id }}" data-income-channel="{{ $incomeChannel }}" @selected((string)($selectedStoreId ?? '') === (string)$s->id)>
                  {{ $s->name }} — {{ $s->channel?->name ?? ucfirst($incomeChannel) }}
                </option>
              @endif
            @endforeach
          </select>
          <div class="hint mt-1">Channel income mengikuti channel toko secara otomatis.</div>
        </div>

        <div class="income-field">
          <label>Channel terdeteksi</label>
          <input type="hidden" name="channel" id="incomeChannelInput" value="{{ old('channel', '') }}">
          <div class="form-control d-flex align-items-center gap-2" id="incomeChannelDisplay" style="min-height:40px; background:var(--income-soft);">
            <span class="text-muted">Pilih toko terlebih dahulu</span>
          </div>
          <div class="hint mt-1">Tidak perlu memilih channel manual.</div>
        </div>

        <div class="income-field">
          <label>File Income</label>
          <input type="file" name="file" id="incomeFileInput" class="form-control" required accept=".xlsx,.xls,.csv" />
          <div class="income-upload-note mt-2" id="incomeFileHint"><i class="bi bi-info-circle"></i> Pilih toko untuk melihat format yang sesuai.</div>
        </div>
      </div>

      <div class="income-form-actions">
        <button class="btn btn-primary px-3" id="incomePreviewButton" type="submit" disabled><i class="bi bi-search"></i> Preview Income</button>
      </div>
    </form>
  </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const store = document.getElementById('incomeStoreSelect');
  const channel = document.getElementById('incomeChannelInput');
  const channelDisplay = document.getElementById('incomeChannelDisplay');
  const file = document.getElementById('incomeFileInput');
  const fileHint = document.getElementById('incomeFileHint');
  const submit = document.getElementById('incomePreviewButton');
  if (!store || !channel || !channelDisplay || !file || !submit) return;

  function updateIncomeForm() {
    const selected = store.options[store.selectedIndex];
    const key = selected?.dataset?.incomeChannel || '';
    const hasFile = file.files && file.files.length > 0;
    channel.value = key;
    submit.disabled = !key || !hasFile;

    if (key === 'shopee') {
      channelDisplay.innerHTML = '<span class="badge text-bg-warning">Shopee</span><span class="small text-muted">Sheet Income</span>';
      fileHint.innerHTML = '<i class="bi bi-info-circle"></i> Gunakan report income/payout Shopee dengan sheet Income.';
    } else if (key === 'tiktok') {
      channelDisplay.innerHTML = '<span class="badge text-bg-dark">TikTok Shop</span><span class="small text-muted">Payout per order</span>';
      fileHint.innerHTML = '<i class="bi bi-info-circle"></i> Gunakan report settlement/payout TikTok Shop per order.';
    } else {
      channelDisplay.innerHTML = '<span class="text-muted">Pilih toko terlebih dahulu</span>';
      fileHint.innerHTML = '<i class="bi bi-info-circle"></i> Pilih toko untuk melihat format yang sesuai.';
    }
  }

  store.addEventListener('change', updateIncomeForm);
  file.addEventListener('change', updateIncomeForm);
  updateIncomeForm();
})();
</script>
@endpush
