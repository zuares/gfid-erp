@extends('layouts.app')

@section('title', 'WIP Cleanup — Preview WIP Menggantung')

@push('head')
<style>
.wc-wrap{max-width:1180px;margin-inline:auto;padding:.8rem .9rem 3rem}
.wc-head{display:flex;align-items:flex-start;gap:.6rem;flex-wrap:wrap;margin-bottom:.7rem}
.wc-title{font-weight:900;font-size:1.15rem;color:#111827}
.wc-sub{color:#64748b;font-size:.84rem;margin-top:.15rem}
.wc-banner{display:flex;align-items:flex-start;gap:.55rem;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.28);color:#1e40af;border-radius:10px;padding:.6rem .8rem;font-size:.82rem;margin-bottom:.85rem}
.wc-banner b{font-weight:800}
.wc-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.6rem;margin-bottom:.9rem}
.wc-kpi{background:var(--card,#fff);border:1px solid rgba(148,163,184,.2);border-radius:10px;padding:.7rem .8rem}
.wc-kpi-label{font-size:.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.02em}
.wc-kpi-rows{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:1.5rem;font-weight:900;color:#111827;margin-top:.1rem}
.wc-kpi-qty{font-size:.78rem;color:#475569;margin-top:.05rem}
.wc-tabs{display:flex;gap:.25rem;margin-bottom:.75rem;border-bottom:1px solid rgba(148,163,184,.2);flex-wrap:wrap}
.wc-tab{appearance:none;display:inline-flex;align-items:center;gap:.4rem;border:none;background:transparent;color:#64748b;font-weight:800;font-size:.84rem;padding:.55rem .85rem;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px}
.wc-tab:hover{color:#334155}
.wc-tab.active{color:#111827;border-bottom-color:#334155}
.wc-tab-count{display:inline-flex;align-items:center;justify-content:center;min-width:1.35rem;height:1.35rem;padding:0 .3rem;border-radius:999px;background:rgba(148,163,184,.18);color:#475569;font-family:ui-monospace,monospace;font-size:.72rem;font-weight:900}
.wc-tab.active .wc-tab-count{background:#334155;color:#fff}
.wc-pane{display:none}
.wc-pane.active{display:block}
.wc-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:10px;overflow:hidden}
.wc-table-wrap{overflow:auto}
.wc-table{width:100%;border-collapse:collapse;font-size:.84rem}
.wc-table th{text-align:left;font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;font-weight:900;background:rgba(148,163,184,.06);padding:.5rem .7rem;border-bottom:1px solid rgba(148,163,184,.16);white-space:nowrap}
.wc-table td{padding:.5rem .7rem;border-bottom:1px solid rgba(148,163,184,.1);color:#334155;vertical-align:middle}
.wc-table tr:last-child td{border-bottom:none}
.wc-code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-weight:800;color:#111827}
.wc-muted{color:#94a3b8;font-size:.78rem}
.wc-r{text-align:right}
.wc-qty{font-family:ui-monospace,monospace;font-weight:800}
.wc-badge{display:inline-flex;align-items:center;border-radius:6px;border:1px solid rgba(148,163,184,.3);padding:.1rem .45rem;font-size:.7rem;font-weight:800;color:#475569;white-space:nowrap}
.wc-age{font-family:ui-monospace,monospace;font-weight:800}
.wc-age.warn{color:#b45309}
.wc-none{color:#94a3b8;font-weight:800;font-style:italic}
.wc-empty{padding:1.6rem 1rem;text-align:center;color:#64748b;font-size:.86rem}
@media(max-width:860px){.wc-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
@endpush

@section('content')
@php
    $ageClass = fn($d) => (is_numeric($d) && (int)$d >= $ageWarnDays) ? 'warn' : '';
    $fmt = fn($n) => number_format((float)$n, 0, ',', '.');
@endphp

<div class="wc-wrap">
    <div class="wc-head">
        <div style="flex:1;min-width:220px">
            <div class="wc-title">WIP Cleanup — Preview WIP Menggantung</div>
            <div class="wc-sub">Kandidat WIP menggantung dari data yang ada. Sumber: bundle cutting, ambil/setor jahit, stok gudang WIP, dan QC.</div>
        </div>
    </div>

    @php $isOwnerAdmin = in_array(auth()->user()->role ?? null, ['owner','admin'], true); @endphp
    <div class="wc-banner">
        <span>ℹ️</span>
        <div>
            Kandidat WIP menggantung. Tab <b>Residu Stok WIP</b> sudah bisa ditindak (Move / Finish / Reject / Write-off / Close Legacy)
            @if(!$isOwnerAdmin) — <i>khusus Owner/Admin</i>@endif. Tab lain masih informatif.
            Setiap aksi berjurnal dan bisa dibatalkan (void). Baris berumur ≥ {{ $ageWarnDays }} hari ditandai kuning.
        </div>
    </div>

    <div class="wc-grid">
        @foreach($summary as $key => $s)
            <div class="wc-kpi">
                <div class="wc-kpi-label">{{ $s['label'] }}</div>
                <div class="wc-kpi-rows">{{ $fmt($s['rows']) }}</div>
                <div class="wc-kpi-qty">{{ $fmt($s['qty']) }} pcs</div>
            </div>
        @endforeach
    </div>

    <div class="wc-tabs" role="tablist">
        <button type="button" class="wc-tab active" data-tab="cut">Cut Outstanding <span class="wc-tab-count">{{ $cutOutstanding->count() }}</span></button>
        <button type="button" class="wc-tab" data-tab="pickup">Jahit Belum Setor <span class="wc-tab-count">{{ $pickupsOpen->count() }}</span></button>
        <button type="button" class="wc-tab" data-tab="stock">Residu Stok WIP <span class="wc-tab-count">{{ $wipStock->count() }}</span></button>
        <button type="button" class="wc-tab" data-tab="qc">QC Pending <span class="wc-tab-count">{{ $qcPending->count() }}</span></button>
    </div>

    {{-- CUT OUTSTANDING --}}
    <div class="wc-pane active" id="wc-tab-cut">
        <div class="wc-card">
            @if($cutOutstanding->isEmpty())
                <div class="wc-empty">Tidak ada bundle cutting yang menggantung.</div>
            @else
            <div class="wc-table-wrap">
                <table class="wc-table">
                    <thead><tr>
                        <th>Bundle</th><th>Item</th><th class="wc-r">Sisa WIP</th>
                        <th class="wc-r">Cut WIP</th><th class="wc-r">Ditarik</th>
                        <th>Operator</th><th>Status</th><th class="wc-r">Umur</th><th class="wc-r">Buka</th>
                    </tr></thead>
                    <tbody>
                    @foreach($cutOutstanding as $r)
                        <tr>
                            <td><span class="wc-code">{{ $r->bundle_code ?? '-' }}</span><div class="wc-muted">{{ $r->cutting_code }}</div></td>
                            <td><span class="wc-code">{{ $r->item_code ?? '-' }}</span><div class="wc-muted">{{ $r->item_name }}</div></td>
                            <td class="wc-r"><span class="wc-qty">{{ $fmt($r->qty_outstanding) }}</span></td>
                            <td class="wc-r">{{ $fmt($r->cut_wip_qty) }}</td>
                            <td class="wc-r">{{ $fmt($r->sewing_picked_qty) }}</td>
                            <td>{!! $r->operator_name ? e($r->operator_name) : '<span class="wc-none">tanpa operator</span>' !!}</td>
                            <td><span class="wc-badge">{{ $r->status ?? '-' }}</span></td>
                            <td class="wc-r"><span class="wc-age {{ $ageClass($r->age_days) }}">{{ $r->age_days }}h</span></td>
                            <td class="wc-r">
                                @if($r->cutting_job_id)
                                    <a href="{{ route('production.cutting_jobs.show', $r->cutting_job_id) }}" style="color:#1d4ed8;font-weight:800;text-decoration:none">Potong →</a>
                                @else <span class="wc-muted">—</span> @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- PICKUP OPEN --}}
    <div class="wc-pane" id="wc-tab-pickup">
        <div class="wc-card">
            @if($pickupsOpen->isEmpty())
                <div class="wc-empty">Semua ambil jahit sudah disetor lengkap.</div>
            @else
            <div class="wc-table-wrap">
                <table class="wc-table">
                    <thead><tr>
                        <th>Ambil Jahit</th><th>Item</th><th>Bundle</th>
                        <th class="wc-r">Ditarik</th><th class="wc-r">Disetor</th><th class="wc-r">Sisa</th>
                        <th>Operator</th><th class="wc-r">Umur</th><th class="wc-r">Buka</th>
                    </tr></thead>
                    <tbody>
                    @foreach($pickupsOpen as $r)
                        <tr>
                            <td><span class="wc-code">{{ $r->pickup_code }}</span><div class="wc-muted">{{ \Illuminate\Support\Str::of($r->pickup_date)->substr(0,10) }}</div></td>
                            <td><span class="wc-code">{{ $r->item_code ?? '-' }}</span><div class="wc-muted">{{ $r->item_name }}</div></td>
                            <td>{!! $r->bundle_code ? '<span class="wc-code">'.e($r->bundle_code).'</span>' : '<span class="wc-none">tanpa bundle</span>' !!}</td>
                            <td class="wc-r">{{ $fmt($r->qty_bundle) }}</td>
                            <td class="wc-r">{{ $fmt($r->qty_returned) }}</td>
                            <td class="wc-r"><span class="wc-qty">{{ $fmt($r->qty_outstanding) }}</span></td>
                            <td>{!! $r->operator_name ? e($r->operator_name) : '<span class="wc-none">tanpa operator</span>' !!}</td>
                            <td class="wc-r"><span class="wc-age {{ $ageClass($r->age_days) }}">{{ $r->age_days }}h</span></td>
                            <td class="wc-r" style="white-space:nowrap">
                                @if($isOwnerAdmin)
                                    <a href="{{ route('production.wip_cleanup.pickup_close', ['pickup_line_id' => $r->pickup_line_id]) }}" style="color:#b45309;font-weight:800;text-decoration:none">Tutup</a>
                                    <span class="wc-muted">·</span>
                                @endif
                                @if($r->pickup_id)
                                    <a href="{{ route('production.sewing.pickups.show', $r->pickup_id) }}" style="color:#1d4ed8;font-weight:800;text-decoration:none">Ambil →</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- WIP STOCK RESIDUAL --}}
    <div class="wc-pane" id="wc-tab-stock">
        <div class="wc-card">
            @if($wipStock->isEmpty())
                <div class="wc-empty">Tidak ada residu stok di gudang WIP.</div>
            @else
            <div class="wc-table-wrap">
                <table class="wc-table">
                    <thead><tr>
                        <th>Gudang</th><th>Item</th><th class="wc-r">Qty</th><th class="wc-r">Umur</th><th class="wc-r">Aksi</th>
                    </tr></thead>
                    <tbody>
                    @foreach($wipStock as $r)
                        <tr>
                            <td><span class="wc-badge">{{ $r->warehouse_code }}</span><div class="wc-muted">{{ $r->warehouse_name }}</div></td>
                            <td><span class="wc-code">{{ $r->item_code ?? '-' }}</span><div class="wc-muted">{{ $r->item_name }}</div></td>
                            <td class="wc-r"><span class="wc-qty">{{ $fmt($r->qty) }}</span></td>
                            <td class="wc-r"><span class="wc-age {{ $ageClass($r->age_days) }}">{{ $r->age_days }}h</span></td>
                            <td class="wc-r">
                                @if($isOwnerAdmin)
                                    <a href="{{ route('production.wip_cleanup.action', ['warehouse_id' => $r->warehouse_id, 'item_id' => $r->item_id]) }}"
                                       style="color:#1d4ed8;font-weight:800;text-decoration:none">Aksi →</a>
                                @else
                                    <span class="wc-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- QC PENDING --}}
    <div class="wc-pane" id="wc-tab-qc">
        <div class="wc-card">
            @if($qcPending->isEmpty())
                <div class="wc-empty">Tidak ada QC pending.</div>
            @else
            <div class="wc-table-wrap">
                <table class="wc-table">
                    <thead><tr>
                        <th>Stage</th><th>Bundle</th><th>Item</th>
                        <th class="wc-r">OK</th><th class="wc-r">Reject</th><th>Operator</th><th class="wc-r">Umur</th><th class="wc-r">Buka</th>
                    </tr></thead>
                    <tbody>
                    @foreach($qcPending as $r)
                        <tr>
                            <td><span class="wc-badge">{{ $r->stage ?? '-' }}</span></td>
                            <td>{!! $r->bundle_code ? '<span class="wc-code">'.e($r->bundle_code).'</span>' : '<span class="wc-none">-</span>' !!}</td>
                            <td><span class="wc-code">{{ $r->item_code ?? '-' }}</span><div class="wc-muted">{{ $r->item_name }}</div></td>
                            <td class="wc-r">{{ $fmt($r->qty_ok) }}</td>
                            <td class="wc-r">{{ $fmt($r->qty_reject) }}</td>
                            <td>{!! $r->operator_name ? e($r->operator_name) : '<span class="wc-none">tanpa operator</span>' !!}</td>
                            <td class="wc-r"><span class="wc-age {{ $ageClass($r->age_days) }}">{{ $r->age_days }}h</span></td>
                            <td class="wc-r"><a href="{{ route('production.qc.index') }}" style="color:#1d4ed8;font-weight:800;text-decoration:none">QC →</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
(function(){
    var tabs = document.querySelectorAll('.wc-tab');
    var panes = document.querySelectorAll('.wc-pane');
    tabs.forEach(function(t){
        t.addEventListener('click', function(){
            var name = t.dataset.tab;
            tabs.forEach(function(x){ x.classList.toggle('active', x === t); });
            panes.forEach(function(p){ p.classList.toggle('active', p.id === 'wc-tab-' + name); });
        });
    });
})();
</script>
@endsection
