@extends('layouts.app')

@section('title', 'Imports • Preview Marketplace Ads')

@push('head')
<style>
    :root { --bd:#e5e7eb; --muted:#6b7280; --r:14px; --shadow:0 10px 26px rgba(15,23,42,.08), 0 0 0 1px rgba(15,23,42,.03); }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 14px; }
    .cardx { background:#fff; border:1px solid var(--bd); border-radius: var(--r); padding: 14px; box-shadow: var(--shadow); }
    .muted { color:var(--muted); font-size:13px; }
    .btnx { display:inline-flex; align-items:center; gap:8px; border-radius: 12px; padding:10px 14px; border:1px solid var(--bd); background:#111827; color:#fff; text-decoration:none; cursor:pointer; }
    .btn-outline { background:#fff; color:#111827; }
    .kpi { display:flex; gap:10px; flex-wrap:wrap; margin-top:10px; }
    .kpi .box { border:1px solid var(--bd); border-radius: 14px; padding:10px 12px; min-width: 180px; background:#fff; }
    .kpi .label { font-size:12px; color:var(--muted); }
    .kpi .value { font-size:18px; font-weight:900; }
    table { width:100%; border-collapse: collapse; }
    th, td { border-bottom:1px solid var(--bd); padding:10px 8px; vertical-align:top; }
    th { text-align:left; font-size:12px; color:var(--muted); }
    td { font-size:13px; }
    .right { text-align:right; }
    .code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size:12px; }
    .warn { background:#fffbeb; border:1px solid #fde68a; color:#92400e; padding:10px 12px; border-radius:12px; }
    .ok  { background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:10px 12px; border-radius:12px; }
    .err { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:10px 12px; border-radius:12px; }
</style>
@endpush

@section('content')
<div class="wrap">

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

    <div class="cardx" style="margin-bottom:12px;">
        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div>
                <div style="font-weight:900; font-size:18px;">Preview Import Ads</div>
                <div class="muted">File: <span class="code">{{ $fileName }}</span></div>
                <div class="muted">
                    Toko: <b>{{ $meta['shop_name'] ?? '-' }}</b>
                    • ID: <span class="code">{{ $meta['shop_platform_id'] ?? '-' }}</span>
                </div>
                <div class="muted">
                    Periode: <b>{{ $meta['period_start'] ?? '-' }}</b> s/d <b>{{ $meta['period_end'] ?? '-' }}</b>
                    • Generated: <span class="code">{{ $meta['report_generated_at'] ?? '-' }}</span>
                </div>
            </div>

            <div style="display:flex; gap:10px; align-items:flex-start; flex-wrap:wrap;">
                <a href="{{ route('imports.marketplace_ads.create') }}" class="btnx btn-outline">Kembali</a>

                <form id="frmCommit" method="POST" action="{{ route('imports.marketplace_ads.commit') }}">
                    @csrf
                    <input type="hidden" name="tmp_key" value="{{ $tmpKey }}">
                    <button id="btnCommit" class="btnx" type="submit"
                        onclick="return confirm('Commit import? Jika periode sama sudah ada, dataset lama akan di-REPLACE (tanpa duplikasi).');">
                        Commit Import
                    </button>
                </form>
            </div>
        </div>

        <div class="kpi">
            <div class="box">
                <div class="label">Total Rows</div>
                <div class="value">{{ number_format($rowsCount) }}</div>
            </div>
            <div class="box">
                <div class="label">Spend</div>
                <div class="value">{{ number_format((float)($summary['spend'] ?? 0), 0, ',', '.') }}</div>
            </div>
            <div class="box">
                <div class="label">Clicks</div>
                <div class="value">{{ number_format((int)($summary['clicks'] ?? 0)) }}</div>
            </div>
            <div class="box">
                <div class="label">Impressions</div>
                <div class="value">{{ number_format((int)($summary['impressions'] ?? 0)) }}</div>
            </div>
            <div class="box">
                <div class="label">GMV</div>
                <div class="value">{{ number_format((float)($summary['gmv'] ?? 0), 0, ',', '.') }}</div>
            </div>
            <div class="box">
                <div class="label">ROAS (approx)</div>
                <div class="value">{{ ($summary['roas'] ?? null) === null ? '-' : number_format((float)$summary['roas'], 2) }}</div>
            </div>
        </div>

        <div class="warn" style="margin-top:12px;">
            Anti-duplikat aktif: file hash unik + dataset periode unik. Commit akan <b>REPLACE</b> dataset periode yang sama.
        </div>
    </div>

    <div class="cardx">
        <div style="font-weight:900; margin-bottom:6px;">Preview 50 baris pertama</div>
        <div class="muted" style="margin-bottom:10px;">
            Total baris: <b>{{ number_format($rowsCount) }}</b> • Ditampilkan: <b>{{ count($rowsPreview) }}</b>
        </div>

        <div style="overflow:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Iklan</th>
                        <th>Produk</th>
                        <th>Search Term</th>
                        <th>Match</th>
                        <th class="right">Impr</th>
                        <th class="right">Clicks</th>
                        <th class="right">Spend</th>
                        <th class="right">GMV</th>
                        <th class="right">ROAS</th>
                        <th class="right">ACOS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rowsPreview as $r)
                        <tr>
                            <td class="code">{{ $r['row_no'] ?? '-' }}</td>
                            <td>{{ $r['ad_name'] ?? '-' }}</td>
                            <td class="code">{{ $r['product_code'] ?? '-' }}</td>
                            <td>{{ $r['search_term'] ?? '-' }}</td>
                            <td class="code">{{ $r['match_type'] ?? '-' }}</td>
                            <td class="right">{{ number_format((int)($r['impressions'] ?? 0)) }}</td>
                            <td class="right">{{ number_format((int)($r['clicks'] ?? 0)) }}</td>
                            <td class="right">{{ number_format((float)($r['spend'] ?? 0), 0, ',', '.') }}</td>
                            <td class="right">{{ number_format((float)($r['gmv'] ?? 0), 0, ',', '.') }}</td>
                            <td class="right">{{ ($r['roas'] ?? null) === null ? '-' : number_format((float)$r['roas'], 2) }}</td>
                            <td class="right">
                                @if(($r['acos'] ?? null) === null)
                                    -
                                @else
                                    {{ number_format(((float)$r['acos'] * 100), 2) }}%
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="muted" style="margin-top:10px;">
            Note: halaman preview ini hanya valid dari POST preview. Kalau kebuka via GET, route guard akan balik ke create.
        </div>
    </div>
</div>

<script>
    (function () {
        const form = document.getElementById('frmCommit');
        const btn = document.getElementById('btnCommit');
        if (!form || !btn) return;

        form.addEventListener('submit', function () {
            btn.disabled = true;
            btn.style.opacity = '0.7';
        });
    })();
</script>
@endsection
