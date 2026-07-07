@extends('layouts.app')

@section('title', 'WIP Normalization')

@push('head')
<style>
.wn-wrap{max-width:1080px;margin-inline:auto;padding:.8rem .9rem 3rem}
.wn-head{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;margin-bottom:.8rem}
.wn-title{font-weight:900;font-size:1.15rem;color:#111827;flex:1}
.wn-btn{display:inline-flex;align-items:center;gap:.35rem;border-radius:8px;border:1px solid #334155;background:#334155;color:#fff;font-weight:800;font-size:.82rem;padding:.45rem .8rem;text-decoration:none}
.wn-btn.ghost{background:transparent;color:#334155}
.wn-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:10px;overflow:hidden}
.wn-table{width:100%;border-collapse:collapse;font-size:.85rem}
.wn-table th{text-align:left;font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;font-weight:900;background:rgba(148,163,184,.06);padding:.55rem .7rem;border-bottom:1px solid rgba(148,163,184,.16)}
.wn-table td{padding:.55rem .7rem;border-bottom:1px solid rgba(148,163,184,.1);color:#334155}
.wn-table tr:last-child td{border-bottom:none}
.wn-code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-weight:800;color:#111827}
.wn-badge{display:inline-flex;align-items:center;border-radius:999px;padding:.12rem .5rem;font-size:.7rem;font-weight:800}
.wn-badge.pending{background:rgba(245,158,11,.12);color:#92400e}
.wn-badge.approved{background:rgba(22,101,52,.12);color:#166534}
.wn-badge.void{background:rgba(148,163,184,.15);color:#475569}
.wn-empty{padding:1.8rem 1rem;text-align:center;color:#64748b}
a.wn-link{color:#1d4ed8;text-decoration:none;font-weight:800}
</style>
@endpush

@section('content')
<div class="wn-wrap">
    <div class="wn-head">
        <div class="wn-title">WIP Normalization</div>
        <a href="{{ route('production.wip_cleanup.index') }}" class="wn-btn ghost">Preview WIP Menggantung</a>
        <a href="{{ route('production.wip_normalization.create') }}" class="wn-btn">+ Normalisasi Baru</a>
    </div>

    <div class="wn-card">
        @if($rows->isEmpty())
            <div class="wn-empty">Belum ada normalisasi WIP.</div>
        @else
        <table class="wn-table">
            <thead><tr>
                <th>Kode</th><th>Gudang</th><th>Baris</th><th>Status</th><th>Dibuat</th><th>Disetujui</th><th></th>
            </tr></thead>
            <tbody>
            @foreach($rows as $r)
                <tr>
                    <td><span class="wn-code">{{ $r->code }}</span></td>
                    <td>{{ $r->warehouse?->code ?? '-' }}</td>
                    <td>{{ $r->lines_count }}</td>
                    <td><span class="wn-badge {{ $r->status }}">{{ ucfirst($r->status) }}</span></td>
                    <td>{{ $r->creator?->name ?? '-' }}<br><span style="color:#94a3b8;font-size:.75rem">{{ $r->created_at?->format('d M Y H:i') }}</span></td>
                    <td>{{ $r->approver?->name ?? '-' }}<br><span style="color:#94a3b8;font-size:.75rem">{{ $r->approved_at?->format('d M Y H:i') }}</span></td>
                    <td><a href="{{ route('production.wip_normalization.show', $r) }}" class="wn-link">Lihat →</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <div style="margin-top:.8rem">{{ $rows->links() }}</div>
</div>
@endsection
