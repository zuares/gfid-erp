@extends('layouts.app')

@section('title', 'Imports • Marketplace Ads')

@push('head')
<style>
    :root { --bd:#e5e7eb; --muted:#6b7280; --r:14px; --shadow:0 10px 26px rgba(15,23,42,.08), 0 0 0 1px rgba(15,23,42,.03); }
    .wrap { max-width: 980px; margin: 0 auto; padding: 14px; }
    .cardx { background:#fff; border:1px solid var(--bd); border-radius: var(--r); padding: 14px; box-shadow: var(--shadow); }
    .muted { color:var(--muted); font-size:13px; }
    .rowx { display:flex; gap:12px; flex-wrap:wrap; }
    .rowx > * { flex:1; min-width:240px; }
    .input { width:100%; border:1px solid var(--bd); border-radius: 12px; padding:10px 12px; background:#fff; }
    .btnx { display:inline-flex; align-items:center; gap:8px; border-radius: 12px; padding:10px 14px; border:1px solid var(--bd); background:#111827; color:#fff; text-decoration:none; cursor:pointer; }
    .btnx:active { transform: translateY(1px); }
    .btn-outline { background:#fff; color:#111827; }
    .err { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:10px 12px; border-radius:12px; }
    .ok  { background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:10px 12px; border-radius:12px; }
    .hint { background:#f8fafc; border:1px dashed var(--bd); border-radius:12px; padding:10px 12px; }
    .code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size:12px; }
</style>
@endpush

@section('content')
<div class="wrap">

    <div class="cardx" style="margin-bottom:12px;">
        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div>
                <div style="font-weight:900; font-size:18px;">Import • Marketplace Ads (Shopee)</div>
                <div class="muted">
                    Upload CSV “Data Peringkat Kata Pencarian Iklan Produk”.
                    Anti-duplikat aktif: file sama ditolak, periode sama di-REPLACE.
                </div>
            </div>

            <div style="display:flex; gap:10px; align-items:flex-start; flex-wrap:wrap;">
                <a href="{{ route('imports.marketplace.index') }}" class="btnx btn-outline">Kembali ke Imports</a>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="ok" style="margin-bottom:12px;">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="err" style="margin-bottom:12px;">
            <div style="font-weight:800; margin-bottom:6px;">Ada error:</div>
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="frmImportAds" class="cardx"
          method="POST"
          action="{{ route('imports.marketplace_ads.preview') }}"
          enctype="multipart/form-data"
          autocomplete="off">
        @csrf

        <div class="rowx" style="margin-bottom:12px;">
            <div>
                <div class="muted" style="margin-bottom:6px;">Channel</div>
                <select name="channel" class="input">
                    <option value="shopee" selected>Shopee</option>
                </select>
                <div class="muted" style="margin-top:8px;">Importer aktif: Shopee Product Ads Search Term (ranking kata pencarian)</div>
            </div>

            <div>
                <div class="muted" style="margin-bottom:6px;">File CSV</div>
                <input type="file" name="file" class="input" accept=".csv,.txt" required>
                <div class="muted" style="margin-top:8px;">Max 20MB • Pastikan format seperti export Shopee Ads.</div>
            </div>
        </div>

        <div class="hint" style="margin-bottom:12px;">
            <div style="font-weight:800; margin-bottom:6px;">Aturan anti-duplikat</div>
            <div class="muted">
                • File sama persis (hash sama) → <b>ditolak</b><br>
                • Periode sama (toko + report_type + period_start/end sama) → <b>dataset di-REPLACE</b> (rows lama dihapus, insert rows baru)
            </div>
        </div>

        <button id="btnSubmit" class="btnx" type="submit">Preview Import</button>
        <span id="txtWait" class="muted" style="display:none; margin-left:10px;">Uploading & parsing…</span>
    </form>

    <div class="muted" style="margin-top:12px;">
        Route: <span class="code">imports.marketplace_ads.preview (POST)</span>
    </div>
</div>

<script>
    (function () {
        const form = document.getElementById('frmImportAds');
        const btn = document.getElementById('btnSubmit');
        const wait = document.getElementById('txtWait');

        if (!form) return;

        form.addEventListener('submit', function () {
            btn.disabled = true;
            btn.style.opacity = '0.7';
            wait.style.display = 'inline';
        });
    })();
</script>
@endsection
