{{-- resources/views/imports/marketplace/create.blade.php --}}
@extends('layouts.app')
@section('title','Imports • Import Marketplace Shipments')

@php
  use Illuminate\Support\Facades\Storage;

  $channels = $channels ?? [];
  $stores   = $stores ?? [];
  $draft    = $draft ?? session('mp_import_preview');
  $selectedChannelId = old('channel_id', $selectedChannelId ?? null);
  $selectedStoreId = old('store_id', $selectedStoreId ?? null);
  $channelNameCounts = collect($channels)->countBy(fn ($channel) => strtolower(trim((string) $channel->name)));

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
  .page{ max-width:1440px; margin:0 auto; padding:1rem .9rem 4.8rem; }
  @media(min-width:768px){ .page{ padding:1.1rem 1rem 4.8rem; } }

  :root{
    --import-line:#e5e7eb;
    --import-ink:#111827;
    --import-muted:#64748b;
    --import-soft:#f8fafc;
    --import-soft-strong:#f1f5f9;
    --import-card:#fff;
    --import-accent:#111827;
    --import-shadow:0 10px 28px rgba(15,23,42,.055);
  }
  body[data-theme="dark"]{
    --import-line:rgba(148,163,184,.17);
    --import-ink:#e5e7eb;
    --import-muted:#94a3b8;
    --import-soft:rgba(148,163,184,.07);
    --import-soft-strong:rgba(148,163,184,.11);
    --import-card:var(--card,#111827);
    --import-accent:#f8fafc;
    --import-shadow:0 12px 32px rgba(0,0,0,.28);
  }

  .mono{ font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace; }
  .muted{ color:var(--import-muted); }

  .shipment-hero{
    display:flex; align-items:center; justify-content:space-between; gap:1.25rem;
    margin-bottom:.75rem; padding:1.15rem 1.25rem;
    border:1px solid var(--import-line); border-radius:16px;
    background:var(--import-card); box-shadow:var(--import-shadow);
  }
  .shipment-hero-main{ display:flex; align-items:center; gap:.9rem; min-width:0; }
  .shipment-hero-icon{
    display:grid; place-items:center; width:42px; height:42px; flex:0 0 42px;
    border-radius:12px; background:#111827; color:#fff; font-size:1.05rem;
  }
  body[data-theme="dark"] .shipment-hero-icon{ background:#f8fafc; color:#111827; }
  .shipment-eyebrow{ margin-bottom:.18rem; color:var(--import-muted); font-size:.66rem; font-weight:750; letter-spacing:.08em; text-transform:uppercase; }
  .shipment-hero-title{ margin:0; color:var(--import-ink); font-size:1.18rem; font-weight:800; letter-spacing:-.025em; }
  .shipment-hero-sub{ margin-top:.22rem; color:var(--import-muted); font-size:.77rem; line-height:1.45; }
  .shipment-actions{ display:flex; align-items:center; flex-wrap:wrap; justify-content:flex-end; gap:.45rem; }
  .shipment-actions .btn{ display:inline-flex; align-items:center; gap:.35rem; min-height:34px; border-radius:9px; font-size:.75rem; font-weight:700; white-space:nowrap; }

  .shipment-tabs-wrap{
    display:flex; align-items:center; justify-content:space-between; gap:1rem;
    margin-bottom:.75rem; padding:.35rem;
    border:1px solid var(--import-line); border-radius:14px;
    background:var(--import-card); box-shadow:var(--import-shadow);
  }
  .import-tabs{ display:flex; align-items:center; gap:.25rem; }
  .import-tab{
    display:inline-flex; align-items:center; justify-content:center; gap:.42rem;
    min-height:36px; padding:.48rem .82rem; border-radius:9px;
    color:var(--import-muted); font-size:.75rem; font-weight:750; text-decoration:none;
  }
  .import-tab:hover{ color:var(--import-ink); background:var(--import-soft); }
  .import-tab.is-active{ background:#111827; color:#fff; }
  body[data-theme="dark"] .import-tab.is-active{ background:#f8fafc; color:#111827; }
  .shipment-tab-meta{ display:flex; align-items:center; gap:.4rem; padding-right:.65rem; color:var(--import-muted); font-size:.7rem; }
  .shipment-tab-meta::before{ content:""; width:6px; height:6px; border-radius:50%; background:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.12); }

  .import-alert{ display:flex; align-items:flex-start; gap:.55rem; margin-bottom:.75rem; border:0; border-radius:12px; font-size:.78rem; }
  .import-alert i{ margin-top:.05rem; }

  .shipment-card{ border:1px solid var(--import-line); border-radius:16px; background:var(--import-card); box-shadow:var(--import-shadow); }
  .shipment-draft-card{ margin-bottom:.75rem; padding:1rem 1.1rem; }
  .shipment-draft-layout{ display:flex; align-items:center; justify-content:space-between; gap:1rem; }
  .shipment-draft-main{ display:flex; align-items:flex-start; gap:.75rem; min-width:0; }
  .shipment-draft-main > div{ min-width:0; }
  .shipment-draft-icon{ display:grid; place-items:center; width:36px; height:36px; flex:0 0 36px; border-radius:10px; background:#fff7ed; color:#c2410c; }
  body[data-theme="dark"] .shipment-draft-icon{ background:rgba(249,115,22,.13); color:#fb923c; }
  .shipment-draft-title{ color:var(--import-ink); font-size:.8rem; font-weight:800; }
  .shipment-draft-meta{ display:flex; flex-wrap:wrap; gap:.3rem .9rem; margin-top:.32rem; color:var(--import-muted); font-size:.7rem; }
  .shipment-draft-meta span{ display:inline-flex; align-items:center; gap:.28rem; min-width:0; }
  .shipment-draft-meta .mono{ max-width:23rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .shipment-draft-warning{ margin-top:.38rem; color:#b45309; font-size:.7rem; }
  .shipment-draft-actions{ display:flex; align-items:center; gap:.4rem; flex:0 0 auto; }
  .shipment-draft-actions .btn{ border-radius:9px; font-size:.72rem; font-weight:700; }

  .shipment-workspace{ display:grid; grid-template-columns:minmax(0,1fr) 310px; gap:.75rem; align-items:start; }
  .shipment-card-head{ display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; padding:1.1rem 1.15rem .9rem; border-bottom:1px solid var(--import-line); }
  .shipment-step-label{ display:inline-flex; align-items:center; gap:.35rem; margin-bottom:.28rem; color:var(--import-muted); font-size:.64rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
  .shipment-step-label span{ display:grid; place-items:center; width:18px; height:18px; border-radius:6px; background:#111827; color:#fff; font-size:.6rem; }
  body[data-theme="dark"] .shipment-step-label span{ background:#f8fafc; color:#111827; }
  .shipment-card-title{ margin:0; color:var(--import-ink); font-size:.94rem; font-weight:800; }
  .shipment-card-note{ margin-top:.22rem; color:var(--import-muted); font-size:.72rem; }
  .shipment-file-types{ display:inline-flex; align-items:center; gap:.32rem; padding:.3rem .5rem; border:1px solid var(--import-line); border-radius:8px; color:var(--import-muted); background:var(--import-soft); font-size:.64rem; font-weight:750; white-space:nowrap; }
  .shipment-form-body{ padding:1rem 1.15rem; }
  .shipment-fields-row{ display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-bottom:.8rem; }
  .shipment-field label{ display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-bottom:.38rem; color:var(--import-ink); font-size:.7rem; font-weight:750; }
  .shipment-field-number{ color:var(--import-muted); font-size:.62rem; font-weight:700; }
  .shipment-field .form-control,.shipment-field .form-select{ min-height:40px; border-color:var(--import-line); border-radius:10px; color:var(--import-ink); background-color:var(--import-card); font-size:.78rem; box-shadow:none; }
  .shipment-field .form-control:focus,.shipment-field .form-select:focus{ border-color:#64748b; box-shadow:0 0 0 3px rgba(100,116,139,.12); }
  .form-help{ margin-top:.35rem; color:var(--import-muted); font-size:.66rem; }

  .shipment-dropzone{
    display:flex; align-items:center; gap:.85rem; min-height:106px; padding:1rem;
    border:1px dashed #cbd5e1; border-radius:12px;
    background:var(--import-soft); cursor:pointer; transition:border-color .16s ease,background .16s ease,transform .16s ease;
  }
  .shipment-dropzone:hover,.shipment-dropzone:focus-within{ border-color:#64748b; background:var(--import-soft-strong); }
  .shipment-dropzone.is-dragging{ border-color:#111827; background:rgba(15,23,42,.055); transform:translateY(-1px); }
  .shipment-dropzone.has-file{ border-style:solid; border-color:rgba(34,197,94,.45); background:rgba(34,197,94,.055); }
  .shipment-dropzone-icon{ display:grid; place-items:center; width:42px; height:42px; flex:0 0 42px; border:1px solid var(--import-line); border-radius:11px; background:var(--import-card); color:var(--import-ink); font-size:1.05rem; }
  .shipment-dropzone-copy{ min-width:0; flex:1; }
  .shipment-dropzone-title{ color:var(--import-ink); font-size:.78rem; font-weight:800; }
  .shipment-dropzone-sub{ margin-top:.18rem; color:var(--import-muted); font-size:.68rem; }
  .shipment-file-name{ display:block; max-width:100%; margin-top:.3rem; overflow:hidden; color:#15803d; font-size:.7rem; font-weight:750; text-overflow:ellipsis; white-space:nowrap; }
  .shipment-browse{ padding:.38rem .62rem; border:1px solid var(--import-line); border-radius:8px; background:var(--import-card); color:var(--import-ink); font-size:.66rem; font-weight:750; white-space:nowrap; }

  .shipment-form-actions{ display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.85rem 1.15rem; border-top:1px solid var(--import-line); }
  .shipment-form-status{ display:flex; align-items:center; gap:.4rem; color:var(--import-muted); font-size:.67rem; }
  .shipment-form-status i{ color:#22c55e; }
  .shipment-submit{ display:inline-flex; align-items:center; justify-content:center; gap:.45rem; min-height:38px; padding-inline:1rem; border-color:#111827; border-radius:9px; background:#111827; font-size:.75rem; font-weight:750; }
  .shipment-submit:hover,.shipment-submit:focus{ border-color:#000; background:#000; }
  .shipment-submit:disabled{ border-color:#cbd5e1; background:#cbd5e1; color:#fff; opacity:1; }
  body[data-theme="dark"] .shipment-submit{ border-color:#f8fafc; background:#f8fafc; color:#111827; }

  .shipment-guide{ position:sticky; top:1rem; padding:1.05rem; }
  .shipment-guide-kicker{ color:var(--import-muted); font-size:.63rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
  .shipment-guide-title{ margin:.25rem 0 0; color:var(--import-ink); font-size:.86rem; font-weight:800; }
  .shipment-guide-list{ display:grid; gap:.8rem; margin-top:1rem; }
  .shipment-guide-item{ display:grid; grid-template-columns:24px 1fr; gap:.6rem; }
  .shipment-guide-number{ display:grid; place-items:center; width:24px; height:24px; border-radius:8px; background:var(--import-soft-strong); color:var(--import-ink); font-size:.62rem; font-weight:800; }
  .shipment-guide-copy strong{ display:block; color:var(--import-ink); font-size:.7rem; }
  .shipment-guide-copy span{ display:block; margin-top:.12rem; color:var(--import-muted); font-size:.66rem; line-height:1.45; }
  .shipment-guide-footer{ display:flex; align-items:center; gap:.45rem; margin-top:1rem; padding:.68rem .72rem; border-radius:10px; background:var(--import-soft); color:var(--import-muted); font-size:.65rem; line-height:1.4; }

  @media(max-width:1024px){ .shipment-workspace{ grid-template-columns:1fr; } .shipment-guide{ position:static; } }
  @media(max-width:760px){
    .shipment-hero{ align-items:flex-start; flex-direction:column; }
    .shipment-actions{ width:100%; justify-content:flex-start; }
    .shipment-tabs-wrap{ align-items:stretch; flex-direction:column; gap:.25rem; }
    .import-tabs{ width:100%; }
    .import-tab{ flex:1; }
    .shipment-tab-meta{ padding:.25rem .55rem .45rem; }
    .shipment-draft-layout{ align-items:flex-start; flex-direction:column; }
    .shipment-draft-actions{ width:100%; }
    .shipment-fields-row{ grid-template-columns:1fr; }
  }
  @media(max-width:520px){
    .shipment-hero-main{ align-items:flex-start; }
    .shipment-hero-icon{ width:38px; height:38px; flex-basis:38px; }
    .shipment-actions .btn{ flex:1; justify-content:center; }
    .shipment-card-head{ align-items:flex-start; flex-direction:column; }
    .shipment-dropzone{ align-items:flex-start; }
    .shipment-browse{ display:none; }
    .shipment-form-actions{ align-items:stretch; flex-direction:column; }
    .shipment-submit{ width:100%; }
  }
</style>
@endpush

@section('content')
<div class="page">

  <section class="shipment-hero">
    <div class="shipment-hero-main">
      <div class="shipment-hero-icon" aria-hidden="true"><i class="bi bi-cloud-arrow-up"></i></div>
      <div>
        <div class="shipment-eyebrow">Marketplace · Impor data</div>
        <h1 class="shipment-hero-title">Import Pesanan Marketplace</h1>
        <div class="shipment-hero-sub">
          Unggah file pesanan, tinjau hasilnya, lalu simpan ke data marketplace.
        </div>
      </div>
    </div>

    <div class="shipment-actions">
      <a class="btn btn-outline-secondary btn-sm px-3" href="{{ route('imports.marketplace.index') }}"><i class="bi bi-arrow-left"></i> Riwayat Import</a>

      @if(!empty($draft) && $canResume)
        <a class="btn btn-dark btn-sm px-3" href="{{ route('imports.marketplace.draft') }}">
          <i class="bi bi-clock-history"></i> Lanjutkan Draft
        </a>
      @elseif(!empty($draft))
        <a class="btn btn-outline-secondary btn-sm px-3" href="{{ route('imports.marketplace.create') }}">
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
    <div class="shipment-tab-meta">Impor siap digunakan</div>
  </div>

  @if(session('error'))
    <div class="alert alert-danger import-alert py-2"><i class="bi bi-exclamation-circle-fill"></i><span>{{ session('error') }}</span></div>
  @endif
  @if(session('success'))
    <div class="alert alert-success import-alert py-2"><i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span></div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger import-alert py-2" role="alert">
      <i class="bi bi-exclamation-circle-fill"></i>
      <div>
        <strong>File belum dapat diproses.</strong>
        <div>{{ $errors->first() }}</div>
      </div>
    </div>
  @endif

  @if(!empty($draft))
    <section class="shipment-card shipment-draft-card">
      <div class="shipment-draft-layout">
        <div class="shipment-draft-main">
          <div class="shipment-draft-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></div>
          <div class="min-w-0">
            <div class="shipment-draft-title">Draft import belum diselesaikan</div>
            <div class="shipment-draft-meta">
              <span><i class="bi bi-broadcast"></i>{{ $draft['channel_key'] ?? ($draft['channel_name'] ?? '-') }}</span>
              <span><i class="bi bi-shop"></i>{{ $draft['store_name'] ?? ($draft['store_id'] ?? '-') }}</span>
              <span><i class="bi bi-paperclip"></i><span class="mono">{{ $draft['source_file'] ?? '-' }}</span></span>
            </div>
            @if(!$canResume)
              <div class="shipment-draft-warning"><i class="bi bi-exclamation-triangle me-1"></i>File draft tidak tersedia. Silakan unggah ulang.</div>
            @endif
          </div>
        </div>

        <div class="shipment-draft-actions">
          @if($canResume)
            <a class="btn btn-dark btn-sm px-3" href="{{ route('imports.marketplace.draft') }}">
              Buka Preview
            </a>
          @endif

          <form method="POST" action="{{ route('imports.marketplace.cancel') }}" onsubmit="return confirm('Batalkan draft?')">
            @csrf
            <button class="btn btn-outline-danger btn-sm px-3" type="submit">Hapus Draft</button>
          </form>
        </div>
      </div>
    </section>
  @endif

  <div class="shipment-workspace">
    <form method="POST" action="{{ route('imports.marketplace.preview') }}" enctype="multipart/form-data" class="shipment-card" id="marketplaceImportForm">
      @csrf

      <div class="shipment-card-head">
        <div>
          <div class="shipment-step-label"><span>1</span> Persiapan data</div>
          <h2 class="shipment-card-title">Upload File Pesanan</h2>
          <div class="shipment-card-note">Pastikan channel dan toko sesuai dengan sumber file.</div>
        </div>
        <span class="shipment-file-types"><i class="bi bi-file-earmark-spreadsheet"></i> XLSX · XLS · CSV</span>
      </div>

      <div class="shipment-form-body">
        <div class="shipment-fields-row">
          <div class="shipment-field">
            <label for="channelSelect"><span>Channel Marketplace</span><span class="shipment-field-number">01</span></label>
            <select name="channel_id" id="channelSelect" class="form-select" required>
              <option value="">Pilih channel</option>
              @foreach($channels as $ch)
                @php $hasDuplicateName = ($channelNameCounts[strtolower(trim((string) $ch->name))] ?? 0) > 1; @endphp
                <option value="{{ $ch->id }}" @selected((string) $selectedChannelId === (string) $ch->id)>
                  {{ $ch->name }}{{ $hasDuplicateName && $ch->code ? ' · '.strtoupper($ch->code) : '' }}
                </option>
              @endforeach
            </select>
            <div class="form-help">Menentukan format pembacaan file.</div>
          </div>

          <div class="shipment-field">
            <label for="storeSelect"><span>Toko Tujuan</span><span class="shipment-field-number">02</span></label>
            <select name="store_id" id="storeSelect" class="form-select" required disabled data-selected-store-id="{{ $selectedStoreId ?? '' }}">
              <option value="">Pilih toko</option>
              @foreach($stores as $st)
                <option value="{{ $st->id }}" data-channel-id="{{ (string)($st->channel_id ?? '') }}" @selected((string) $selectedStoreId === (string) $st->id)>
                  {{ $st->name }}
                </option>
              @endforeach
            </select>
            <div class="form-help" id="storeHint">Pilih channel untuk menampilkan toko.</div>
          </div>
        </div>

        <div class="shipment-field">
          <label for="importFileInput"><span>File Pesanan</span><span class="shipment-field-number">03</span></label>
          <input type="file" name="file" id="importFileInput" class="visually-hidden" required accept=".xlsx,.xls,.csv" aria-describedby="importFileHelp">
          <label class="shipment-dropzone" for="importFileInput" id="importDropzone">
            <span class="shipment-dropzone-icon" aria-hidden="true"><i class="bi bi-cloud-arrow-up"></i></span>
            <span class="shipment-dropzone-copy">
              <span class="shipment-dropzone-title">Pilih atau jatuhkan file di sini</span>
              <span class="shipment-dropzone-sub" id="importFileHelp">Gunakan file ekspor asli dari marketplace.</span>
              <span class="shipment-file-name d-none" id="importFileName"></span>
            </span>
            <span class="shipment-browse">Pilih File</span>
          </label>
        </div>
      </div>

      <div class="shipment-form-actions">
        <div class="shipment-form-status"><i class="bi bi-shield-check"></i> Data diperiksa terlebih dahulu sebelum disimpan.</div>
        <button class="btn btn-primary shipment-submit" id="previewImportButton" type="submit" disabled>
          Preview Import <i class="bi bi-arrow-right"></i>
        </button>
      </div>
    </form>

    <aside class="shipment-card shipment-guide" aria-label="Panduan import">
      <div class="shipment-guide-kicker">Alur import</div>
      <h2 class="shipment-guide-title">Tiga langkah singkat</h2>
      <div class="shipment-guide-list">
        <div class="shipment-guide-item">
          <span class="shipment-guide-number">1</span>
          <div class="shipment-guide-copy"><strong>Pilih sumber</strong><span>Tentukan channel dan toko yang sesuai.</span></div>
        </div>
        <div class="shipment-guide-item">
          <span class="shipment-guide-number">2</span>
          <div class="shipment-guide-copy"><strong>Periksa preview</strong><span>Cek jumlah pesanan, item, dan peringatan.</span></div>
        </div>
        <div class="shipment-guide-item">
          <span class="shipment-guide-number">3</span>
          <div class="shipment-guide-copy"><strong>Simpan data</strong><span>Commit hanya setelah hasil preview sesuai.</span></div>
        </div>
      </div>
      <div class="shipment-guide-footer"><i class="bi bi-info-circle"></i><span>File TikTok <span class="mono">OrderSKUList</span> serta ekspor Shopee didukung.</span></div>
    </aside>
  </div>

</div>
@endsection

@push('scripts')
<script>
(function(){
  const ch = document.getElementById('channelSelect');
  const st = document.getElementById('storeSelect');
  const hint = document.getElementById('storeHint');
  const form = document.getElementById('marketplaceImportForm');
  const fileInput = document.getElementById('importFileInput');
  const dropzone = document.getElementById('importDropzone');
  const fileName = document.getElementById('importFileName');
  const submitButton = document.getElementById('previewImportButton');
  if (!ch || !st) return;

  const allOptions = Array.from(st.querySelectorAll('option')).slice(1); // skip placeholder

  function norm(v){ return String(v ?? '').trim(); }

  function updateSubmitState(){
    if (!submitButton) return;
    submitButton.disabled = !(norm(ch.value) && norm(st.value) && fileInput?.files?.length);
  }

  function updateFileState(){
    const file = fileInput?.files?.[0] ?? null;

    if (dropzone) dropzone.classList.toggle('has-file', Boolean(file));
    if (fileName) {
      fileName.classList.toggle('d-none', !file);
      fileName.textContent = file
        ? `${file.name} · ${formatFileSize(file.size)}`
        : '';
    }

    updateSubmitState();
  }

  function formatFileSize(bytes){
    if (!Number.isFinite(bytes) || bytes <= 0) return '0 KB';
    if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
  }

  function applyStoreFilter(preserveInitialStore = false){
    const channelId = norm(ch.value);

    if (!channelId){
      st.value = '';
      st.disabled = true;
      allOptions.forEach(opt => { opt.hidden = true; opt.disabled = true; });
      if (hint){ hint.style.display = 'block'; hint.textContent = 'Pilih channel untuk menampilkan toko.'; }
      updateSubmitState();
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
        hint.style.display = 'block';
        hint.textContent = `${visible} toko tersedia.`;
      }
    }

    updateSubmitState();
  }

  ch.addEventListener('change', () => applyStoreFilter(false));
  st.addEventListener('change', updateSubmitState);
  fileInput?.addEventListener('change', updateFileState);

  if (dropzone && fileInput) {
    ['dragenter', 'dragover'].forEach(eventName => {
      dropzone.addEventListener(eventName, event => {
        event.preventDefault();
        dropzone.classList.add('is-dragging');
      });
    });

    ['dragleave', 'drop'].forEach(eventName => {
      dropzone.addEventListener(eventName, event => {
        event.preventDefault();
        dropzone.classList.remove('is-dragging');
      });
    });

    dropzone.addEventListener('drop', event => {
      const files = event.dataTransfer?.files;
      if (!files?.length) return;
      fileInput.files = files;
      updateFileState();
    });
  }

  form?.addEventListener('submit', () => {
    if (!submitButton || submitButton.disabled) return;
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Memproses File';
  });

  applyStoreFilter(true);
  updateFileState();
})();
</script>
@endpush
