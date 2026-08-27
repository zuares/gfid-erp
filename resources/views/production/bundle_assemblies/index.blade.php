@extends('layouts.app')

@section('title', 'Assembly Bundle')

@push('head')
<style>
.ba-wrap{max-width:1180px;margin-inline:auto;padding:.8rem .9rem 3rem}
.ba-head{display:flex;align-items:center;gap:.65rem;flex-wrap:wrap;margin-bottom:.85rem}
.ba-title{font-size:1.15rem;font-weight:900;color:#111827;flex:1}
.ba-btn{display:inline-flex;align-items:center;gap:.35rem;border:1px solid #334155;border-radius:8px;background:#334155;color:#fff;padding:.48rem .8rem;font-size:.82rem;font-weight:800;text-decoration:none}
.ba-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.2);border-radius:10px;overflow:hidden}
.ba-table{width:100%;border-collapse:collapse;font-size:.84rem}
.ba-table th{text-align:left;padding:.58rem .7rem;background:rgba(148,163,184,.06);border-bottom:1px solid rgba(148,163,184,.18);color:#64748b;font-size:.67rem;letter-spacing:.04em;text-transform:uppercase;font-weight:900}
.ba-table td{padding:.58rem .7rem;border-bottom:1px solid rgba(148,163,184,.1);color:#334155;vertical-align:top}
.ba-table tr:last-child td{border-bottom:0}
.ba-code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-weight:900;color:#111827}
.ba-muted{color:#94a3b8;font-size:.76rem}
.ba-right{text-align:right;white-space:nowrap}
.ba-badge{display:inline-flex;border-radius:999px;padding:.14rem .5rem;font-size:.7rem;font-weight:800}
.ba-badge.draft{background:rgba(245,158,11,.12);color:#92400e}.ba-badge.posted{background:rgba(22,101,52,.12);color:#166534}.ba-badge.void{background:rgba(148,163,184,.15);color:#475569}
.ba-link{color:#1d4ed8;font-weight:800;text-decoration:none}
@media(max-width:700px){.ba-card{overflow-x:auto}.ba-table{min-width:760px}.ba-head{align-items:flex-start}.ba-title{min-width:100%}}
</style>
@endpush

@section('content')
<div class="ba-wrap">
    <div class="ba-head">
        <div class="ba-title">Assembly Bundle</div>
        <a href="{{ route('production.bundle_assemblies.create') }}" class="ba-btn">+ Assembly Baru</a>
    </div>

    <div class="ba-card">
        @if($assemblies->isEmpty())
            <div style="padding:2rem 1rem;text-align:center;color:#64748b">Belum ada assembly bundle.</div>
        @else
            <table class="ba-table">
                <thead><tr>
                    <th>Dokumen</th><th>Bundle</th><th>Gudang</th><th class="ba-right">Qty</th>
                    <th class="ba-right">Total HPP</th><th>Status</th><th></th>
                </tr></thead>
                <tbody>
                @foreach($assemblies as $assembly)
                    <tr>
                        <td><span class="ba-code">{{ $assembly->code }}</span><div class="ba-muted">{{ $assembly->date?->format('d M Y') }}</div></td>
                        <td><span class="ba-code">{{ $assembly->item?->code ?? '-' }}</span><div class="ba-muted">{{ $assembly->item?->name }}</div></td>
                        <td>{{ $assembly->warehouse?->code ?? '-' }}<div class="ba-muted">{{ $assembly->lines_count }} komponen</div></td>
                        <td class="ba-right">{{ number_format((float)$assembly->qty, 6, ',', '.') }} {{ $assembly->item?->stockUnit() }}</td>
                        <td class="ba-right">{{ $assembly->total_cost !== null ? 'Rp '.number_format((float)$assembly->total_cost, 0, ',', '.') : '-' }}</td>
                        <td><span class="ba-badge {{ $assembly->status }}">{{ ucfirst($assembly->status) }}</span></td>
                        <td><a class="ba-link" href="{{ route('production.bundle_assemblies.show', $assembly) }}">Lihat →</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div style="margin-top:.8rem">{{ $assemblies->links() }}</div>
</div>
@endsection
