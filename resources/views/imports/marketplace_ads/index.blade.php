@extends('layouts.app')

@section('title', 'Imports • Ads Daily Report')

@push('head')
<style>
    :root { --bd:#e5e7eb; --muted:#6b7280; --r:14px; --shadow:0 10px 26px rgba(15,23,42,.08), 0 0 0 1px rgba(15,23,42,.03); }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 14px; }
    .cardx { background:#fff; border:1px solid var(--bd); border-radius: var(--r); padding: 14px; box-shadow: var(--shadow); }
    .muted { color:var(--muted); font-size:13px; }
    .rowx { display:flex; gap:12px; flex-wrap:wrap; }
    .rowx > * { flex:1; min-width: 220px; }
    .input { width:100%; border:1px solid var(--bd); border-radius: 12px; padding:10px 12px; background:#fff; }
    .btnx { display:inline-flex; align-items:center; gap:8px; border-radius: 12px; padding:10px 14px; border:1px solid var(--bd); background:#111827; color:#fff; text-decoration:none; cursor:pointer; }
    .btn-outline { background:#fff; color:#111827; }
    .kpi { display:flex; gap:10px; flex-wrap:wrap; }
    .kpi .box { border:1px solid var(--bd); border-radius: 14px; padding:10px 12px; min-width: 180px; background:#fff; }
    .kpi .label { font-size:12px; color:var(--muted); }
    .kpi .value { font-size:18px; font-weight:900; }
    table { width:100%; border-collapse: collapse; }
    th, td { border-bottom:1px solid var(--bd); padding:10px 8px; vertical-align:top; }
    th { text-align:left; font-size:12px; color:var(--muted); }
    td { font-size:13px; }
    .right { text-align:right; }
    .code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size:12px; }
</style>
@endpush

@section('content')
<div class="wrap">

    <div class="cardx" style="margin-bottom:12px;">
        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div>
                <div style="font-weight:900; font-size:18px;">Ads Daily Report</div>
                <div class="muted">
                    Report harian dari dataset import Ads yang <b>overlap</b> tanggal terpilih.
                    (Kalau kamu import harian, hasilnya 1:1.)
                </div>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a class="btnx btn-outline" href="{{ route('imports.marketplace.index') }}">Kembali</a>
                <a class="btnx" href="{{ route('imports.marketplace_ads.create') }}">Import Ads</a>
            </div>
        </div>
    </div>

    <form class="cardx" method="GET" action="{{ route('imports.marketplace_ads.index') }}" style="margin-bottom:12px;">
        <div class="rowx">
            <div>
                <div class="muted" style="margin-bottom:6px;">Tanggal</div>
                <input class="input" type="date" name="date" value="{{ $filters['date'] }}">
            </div>

            <div>
                <div class="muted" style="margin-bottom:6px;">Channel</div>
                <select class="input" name="channel">
                    <option value="shopee" @selected($filters['channel']==='shopee')>Shopee</option>
                </select>
            </div>

            <div>
                <div class="muted" style="margin-bottom:6px;">Toko (ID / Nama)</div>
                <input class="input" type="text" name="shop" value="{{ $filters['shop'] }}" placeholder="contoh: 123456 / Greatfit">
            </div>

            <div>
                <div class="muted" style="margin-bottom:6px;">Filter Search Term</div>
                <input class="input" type="text" name="q" value="{{ $filters['q'] }}" placeholder="contoh: cargo / jogger">
            </div>
        </div>

        <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
            <button class="btnx" type="submit">Terapkan</button>
            <a class="btnx btn-outline" href="{{ route('imports.marketplace_ads.index') }}">Reset</a>
        </div>
    </form>

    <div class="cardx" style="margin-bottom:12px;">
        <div style="font-weight:900; margin-bottom:8px;">Ringkasan</div>
        <div class="kpi">
            <div class="box">
                <div class="label">Spend</div>
                <div class="value">{{ number_format((float)$kpi['spend'], 0, ',', '.') }}</div>
            </div>
            <div class="box">
                <div class="label">GMV</div>
                <div class="value">{{ number_format((float)$kpi['gmv'], 0, ',', '.') }}</div>
            </div>
            <div class="box">
                <div class="label">Clicks</div>
                <div class="value">{{ number_format((int)$kpi['clicks']) }}</div>
            </div>
            <div class="box">
                <div class="label">Impressions</div>
                <div class="value">{{ number_format((int)$kpi['impressions']) }}</div>
            </div>
            <div class="box">
                <div class="label">ROAS</div>
                <div class="value">{{ $kpi['roas'] === null ? '-' : number_format((float)$kpi['roas'], 2) }}</div>
            </div>
        </div>

        <div class="muted" style="margin-top:10px;">
            Dataset yang dipakai (overlap tanggal): <b>{{ $imports->count() }}</b>
            @if($imports->count())
                <div style="margin-top:6px;">
                    @foreach($imports as $imp)
                        <span class="code">#{{ $imp->id }}</span>
                        <span class="muted">{{ $imp->shop_name ?? '-' }} ({{ $imp->shop_platform_id ?? '-' }})</span>
                        <span class="muted">• {{ $imp->period_start }} → {{ $imp->period_end }}</span>
                        <br>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="cardx">
        <div style="font-weight:900; margin-bottom:8px;">Top Search Terms</div>
        <div class="muted" style="margin-bottom:10px;">
            Ditampilkan max 200. Urut: GMV desc, lalu clicks desc.
        </div>

        <div style="overflow:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Search Term</th>
                        <th class="right">Impr</th>
                        <th class="right">Clicks</th>
                        <th class="right">Spend</th>
                        <th class="right">GMV</th>
                        <th class="right">ROAS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topTerms as $r)
                        <tr>
                            <td>{{ $r['search_term'] }}</td>
                            <td class="right">{{ number_format($r['impressions']) }}</td>
                            <td class="right">{{ number_format($r['clicks']) }}</td>
                            <td class="right">{{ number_format($r['spend'], 0, ',', '.') }}</td>
                            <td class="right">{{ number_format($r['gmv'], 0, ',', '.') }}</td>
                            <td class="right">{{ $r['roas'] === null ? '-' : number_format($r['roas'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted">Tidak ada data untuk filter ini. Coba pilih tanggal lain / import dulu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="muted" style="margin-top:10px;">
            Route: <span class="code">imports.marketplace_ads.index</span>
        </div>
    </div>

</div>
@endsection
