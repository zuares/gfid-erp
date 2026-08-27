@extends('layouts.app')

@section('title', 'Assembly '.$assembly->code)

@push('head')
<style>
.ba-wrap{max-width:1100px;margin-inline:auto;padding:.8rem .9rem 3rem}
.ba-head{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;margin-bottom:.75rem}
.ba-code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-weight:900;color:#111827;font-size:1.05rem}
.ba-spacer{flex:1}
.ba-btn{display:inline-flex;align-items:center;gap:.35rem;border:1px solid #334155;border-radius:8px;background:#334155;color:#fff;padding:.48rem .8rem;font-size:.82rem;font-weight:800;text-decoration:none;cursor:pointer}
.ba-btn.ghost{background:transparent;color:#334155}.ba-btn.danger{background:#b91c1c;border-color:#b91c1c}
.ba-badge{display:inline-flex;border-radius:999px;padding:.14rem .5rem;font-size:.7rem;font-weight:800}
.ba-badge.draft{background:rgba(245,158,11,.12);color:#92400e}.ba-badge.posted{background:rgba(22,101,52,.12);color:#166534}.ba-badge.void{background:rgba(148,163,184,.15);color:#475569}
.ba-meta{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.55rem;margin-bottom:.8rem}
.ba-box,.ba-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.2);border-radius:10px}
.ba-box{padding:.65rem .75rem}.ba-label{font-size:.66rem;font-weight:900;color:#64748b;text-transform:uppercase;letter-spacing:.04em}.ba-value{font-size:.86rem;font-weight:800;color:#111827;margin-top:.15rem}.ba-muted{font-size:.76rem;color:#94a3b8}
.ba-card{overflow:hidden}.ba-table{width:100%;border-collapse:collapse;font-size:.84rem}.ba-table th{text-align:left;padding:.58rem .7rem;background:rgba(148,163,184,.06);border-bottom:1px solid rgba(148,163,184,.18);color:#64748b;font-size:.67rem;text-transform:uppercase;font-weight:900}.ba-table td{padding:.58rem .7rem;border-bottom:1px solid rgba(148,163,184,.1);color:#334155}.ba-table tr:last-child td{border-bottom:0}.ba-right{text-align:right;white-space:nowrap}
.ba-note{margin:.8rem 0;padding:.65rem .8rem;border-radius:9px;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.25);color:#1e40af;font-size:.82rem}
@media(max-width:700px){.ba-meta{grid-template-columns:repeat(2,minmax(0,1fr))}.ba-card{overflow-x:auto}.ba-table{min-width:780px}.ba-spacer{display:none}}
@media(max-width:430px){.ba-meta{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="ba-wrap">
    <div class="ba-head">
        <a href="{{ route('production.bundle_assemblies.index') }}" class="ba-btn ghost">← Daftar</a>
        <span class="ba-code">{{ $assembly->code }}</span>
        <span class="ba-badge {{ $assembly->status }}">{{ ucfirst($assembly->status) }}</span>
        <span class="ba-spacer"></span>
        @if($assembly->isDraft())
            <form method="POST" action="{{ route('production.bundle_assemblies.post', $assembly) }}" id="baPostForm">
                @csrf
                <button type="submit" class="ba-btn" data-ba-confirm="post">Post Assembly</button>
            </form>
        @elseif($assembly->isPosted())
            <form method="POST" action="{{ route('production.bundle_assemblies.void', $assembly) }}" id="baVoidForm">
                @csrf
                <button type="submit" class="ba-btn danger" data-ba-confirm="void">Void Assembly</button>
            </form>
        @endif
    </div>

    @if($assembly->isDraft())
        <div class="ba-note">Draft belum mengubah stok. Saat diposting, {{ number_format((float)$assembly->qty, 6, ',', '.') }} {{ $assembly->item?->stockUnit() }} bundle dibuat dan seluruh komponen BOM dikonsumsi dari gudang yang sama.</div>
    @elseif($assembly->isPosted())
        <div class="ba-note" style="background:rgba(22,101,52,.08);border-color:rgba(22,101,52,.25);color:#166534">Sudah diposting pada {{ $assembly->posted_at?->format('d M Y H:i') }}. Stok bundle bertambah dan komponen berkurang.</div>
    @else
        <div class="ba-note" style="background:rgba(148,163,184,.12);border-color:rgba(148,163,184,.3);color:#475569">Sudah di-void pada {{ $assembly->voided_at?->format('d M Y H:i') }}. Mutasi stok sudah direversal.</div>
    @endif

    <div class="ba-meta">
        <div class="ba-box"><div class="ba-label">Bundle</div><div class="ba-value">{{ $assembly->item?->code ?? '-' }}</div><div class="ba-muted">{{ $assembly->item?->name }}</div></div>
        <div class="ba-box"><div class="ba-label">Gudang</div><div class="ba-value">{{ $assembly->warehouse?->code ?? '-' }}</div><div class="ba-muted">{{ $assembly->warehouse?->name }}</div></div>
        <div class="ba-box"><div class="ba-label">Qty / HPP unit</div><div class="ba-value">{{ number_format((float)$assembly->qty, 6, ',', '.') }} {{ $assembly->item?->stockUnit() }}</div><div class="ba-muted">{{ $assembly->unit_cost !== null ? 'Rp '.number_format((float)$assembly->unit_cost, 0, ',', '.') : 'HPP belum tersedia' }}</div></div>
        <div class="ba-box"><div class="ba-label">Total HPP</div><div class="ba-value">{{ $assembly->total_cost !== null ? 'Rp '.number_format((float)$assembly->total_cost, 0, ',', '.') : '-' }}</div><div class="ba-muted">{{ $assembly->creator?->name ?? '-' }}</div></div>
    </div>

    <div class="ba-card">
        <table class="ba-table">
            <thead><tr><th>Komponen</th><th class="ba-right">Qty / bundle</th><th class="ba-right">Scrap</th><th class="ba-right">Qty konsumsi</th><th>Satuan</th><th class="ba-right">HPP unit</th><th class="ba-right">Total</th></tr></thead>
            <tbody>
            @foreach($assembly->lines as $line)
                <tr>
                    <td><span class="ba-code">{{ $line->material?->code ?? '-' }}</span><div class="ba-muted">{{ $line->material?->name }}</div></td>
                    <td class="ba-right">{{ number_format((float)$line->qty_per_unit, 6, ',', '.') }}</td>
                    <td class="ba-right">{{ number_format((float)$line->scrap_pct, 2, ',', '.') }}%</td>
                    <td class="ba-right">{{ number_format((float)($line->qty_consumed ?? $line->qty_required), 6, ',', '.') }}</td>
                    <td>{{ $line->uom }}</td>
                    <td class="ba-right">{{ $line->unit_cost !== null ? 'Rp '.number_format((float)$line->unit_cost, 0, ',', '.') : '-' }}</td>
                    <td class="ba-right">{{ $line->total_cost !== null ? 'Rp '.number_format((float)$line->total_cost, 0, ',', '.') : '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @if($assembly->notes)
        <div class="ba-note" style="background:rgba(148,163,184,.08);border-color:rgba(148,163,184,.25);color:#475569"><b>Catatan:</b> {{ $assembly->notes }}</div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-ba-confirm]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            if (button.dataset.confirmed === '1') return;
            event.preventDefault();
            var action = button.dataset.baConfirm;
            var form = button.closest('form');
            var message = action === 'post'
                ? 'Stok komponen akan berkurang dan bundle akan masuk stok.'
                : 'Mutasi assembly akan dibalik. Pastikan bundle belum dipakai.';
            if (!window.Swal) {
                if (window.confirm(message)) { button.dataset.confirmed = '1'; form.submit(); }
                return;
            }
            window.Swal.fire({
                icon: 'warning',
                title: action === 'post' ? 'Post assembly?' : 'Void assembly?',
                text: message,
                showCancelButton: true,
                confirmButtonText: action === 'post' ? 'Ya, post' : 'Ya, void',
                cancelButtonText: 'Batal',
                confirmButtonColor: action === 'post' ? '#334155' : '#b91c1c'
            }).then(function (result) {
                if (result.isConfirmed) { button.dataset.confirmed = '1'; form.submit(); }
            });
        });
    });
});
</script>
@endsection
