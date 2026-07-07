@extends('layouts.app')

@section('title', 'Log Produksi')

@push('head')
<style>
.lp-wrap{max-width:1000px;margin-inline:auto;padding:.75rem .8rem 3rem}
.lp-title{font-weight:900;font-size:1.1rem;color:#111827;margin-bottom:.65rem}

.lp-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.5rem;margin-bottom:.7rem}
.lp-kpi{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:9px;padding:.6rem .7rem}
.lp-kpi .lbl{font-size:.68rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.03em}
.lp-kpi .val{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:1.35rem;font-weight:900;color:#111827;line-height:1.1;margin-top:.1rem}

.lp-bar{display:flex;gap:.45rem;flex-wrap:wrap;align-items:center;margin-bottom:.7rem}
.lp-input,.lp-select{border:1px solid rgba(148,163,184,.32);border-radius:8px;padding:.4rem .55rem;font-size:.83rem;background:var(--card,#fff);color:#111827;min-height:34px}
.lp-btn{display:inline-flex;align-items:center;border-radius:8px;border:1px solid #334155;background:#334155;color:#fff;font-weight:800;font-size:.8rem;padding:.42rem .85rem;min-height:34px;cursor:pointer;text-decoration:none}
.lp-btn.ghost{background:transparent;color:#94a3b8;border-color:rgba(148,163,184,.28)}

.lp-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:10px;overflow:hidden}
.lp-scroll{overflow:auto}
.lp-table{width:100%;border-collapse:collapse}
.lp-table th,.lp-table td{padding:.6rem .8rem;border-bottom:1px solid rgba(148,163,184,.1);vertical-align:middle}
.lp-table th{text-align:left;font-size:.68rem;color:#94a3b8;font-weight:900;text-transform:uppercase;letter-spacing:.03em;white-space:nowrap;
  position:sticky;top:0;z-index:3;background:var(--card,#fff);box-shadow:inset 0 -1px 0 rgba(148,163,184,.22)}
.lp-table thead th{border-bottom:none}
/* header tetap terlihat saat area tabel di-scroll (desktop) */
@media(min-width:821px){ .lp-scroll{max-height:calc(100vh - 230px);overflow:auto} }
.lp-table td{font-size:.86rem;color:#334155}
.lp-table tr:last-child td{border-bottom:none}
.lp-table tbody tr:hover td{background:rgba(148,163,184,.04)}

.lp-time{font-family:ui-monospace,monospace;font-size:.82rem;color:#334155;white-space:nowrap}
.lp-time small{display:block;color:#cbd5e1;font-size:.68rem;font-weight:600}
.lp-ev{font-weight:800;color:#334155}
.lp-ev small{display:block;color:#94a3b8;font-size:.72rem;font-weight:600}
.lp-ev.audit{color:#b91c1c}
.lp-code{font-family:ui-monospace,monospace;font-weight:900;color:#111827}
.lp-code small{display:block;color:#94a3b8;font-size:.72rem;font-weight:500;font-family:inherit}
.lp-r{text-align:right}
.lp-qty{font-family:ui-monospace,monospace;font-weight:800;font-size:.9rem}
.lp-qty.in{color:#16a34a}.lp-qty.out{color:#dc2626}
.lp-val{font-family:ui-monospace,monospace;font-weight:700;color:#475569}
.lp-dash{color:#cbd5e1}
.lp-empty{padding:2rem 1rem;text-align:center;color:#94a3b8}

@media(max-width:820px){
  .lp-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
  .lp-table th:nth-child(5),.lp-table td:nth-child(5),
  .lp-table th:nth-child(6),.lp-table td:nth-child(6){display:none}
}
</style>
@endpush

@section('content')
@php
    $fmt = fn($n)=>$n===null?'':number_format((float)$n,0,',','.');
    $today = now()->toDateString();
    $totalAll  = $rows->count();
    $stockCnt  = $rows->where('kind','stock')->count();
    $auditCnt  = $rows->where('kind','event')->count();
    $todayCnt  = $rows->filter(fn($r)=>\Illuminate\Support\Str::of((string)($r->ts ?? $r->date))->startsWith($today))->count();
@endphp

<div class="lp-wrap">
    <div class="lp-title">Log Produksi</div>

    <div class="lp-grid">
        <div class="lp-kpi"><div class="lbl">Total</div><div class="val">{{ $fmt($totalAll) }}</div></div>
        <div class="lp-kpi"><div class="lbl">Pergerakan Stok</div><div class="val">{{ $fmt($stockCnt) }}</div></div>
        <div class="lp-kpi"><div class="lbl">Event Penting</div><div class="val">{{ $fmt($auditCnt) }}</div></div>
        <div class="lp-kpi"><div class="lbl">Hari Ini</div><div class="val">{{ $fmt($todayCnt) }}</div></div>
    </div>

    <form method="GET" action="{{ route('production.log.index') }}" class="lp-bar">
        <input type="date" name="date_from" class="lp-input" value="{{ $filters['date_from'] }}">
        <input type="date" name="date_to" class="lp-input" value="{{ $filters['date_to'] }}">
        <select name="source" class="lp-select" style="min-width:200px">
            <option value="">Semua aktivitas</option>
            @foreach($sourceOpts as $k => $lbl)
                <option value="{{ $k }}" @selected($filters['source']===$k)>{{ $lbl }}</option>
            @endforeach
        </select>
        <input type="text" name="q" class="lp-input" value="{{ $filters['q'] }}" placeholder="Cari item…" style="min-width:150px">
        <button type="submit" class="lp-btn">Terapkan</button>
        @if($filters['date_from'] || $filters['date_to'] || $filters['source'] || $filters['q'])
            <a href="{{ route('production.log.index') }}" class="lp-btn ghost">Reset</a>
        @endif
    </form>

    <div class="lp-card">
        @if($rows->isEmpty())
            <div class="lp-empty">Belum ada aktivitas.</div>
        @else
        <div class="lp-scroll">
        <table class="lp-table">
            <thead><tr>
                <th>Waktu</th><th>Aktivitas</th><th>Item</th>
                <th class="lp-r">Qty</th><th class="lp-r">Nilai</th><th>Oleh</th>
            </tr></thead>
            <tbody>
            @foreach($rows as $r)
                @php
                    $t = (string)($r->ts ?? $r->date);
                    $jam = \Illuminate\Support\Str::of($t)->substr(11,5);
                    $tgl = \Illuminate\Support\Str::of($t)->substr(0,10);
                    $qtyIn = $r->qty !== null && (float)$r->qty >= 0;
                @endphp
                <tr>
                    <td><span class="lp-time">{{ $jam ?: '—' }}<small>{{ $tgl }}</small></span></td>
                    <td><span class="lp-ev {{ $r->kind==='event' ? 'audit' : '' }}">{{ $log->label($r->source_type) }}<small>{{ $r->warehouse_code }}</small></span></td>
                    <td>
                        @if($r->item_code)
                            <span class="lp-code">{{ $r->item_code }}<small>{{ $r->item_name }}</small></span>
                        @else
                            <span class="lp-dash">—</span>
                        @endif
                    </td>
                    <td class="lp-r">
                        @if($r->qty !== null)
                            <span class="lp-qty {{ $qtyIn ? 'in' : 'out' }}">{{ $qtyIn ? '+' : '' }}{{ $fmt($r->qty) }}</span>
                        @else <span class="lp-dash">—</span> @endif
                    </td>
                    <td class="lp-r"><span class="lp-val">{{ $r->value!==null ? $fmt(abs((float)$r->value)) : '—' }}</span></td>
                    <td>{{ $r->actor ?? '' }}<span class="lp-dash">{{ $r->actor ? '' : '—' }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
</div>
@endsection
