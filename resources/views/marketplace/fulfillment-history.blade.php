@extends('layouts.app')
@section('title', 'Fulfillment #' . $fulfillment->id . ' • History')

@include('marketplace._shared')

@section('content')
<div style="max-width:860px;margin:0 auto;padding:1.5rem 1rem 3rem;">

    {{-- Header --}}
    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem;flex-wrap:wrap;">
        <a href="/marketplace/fulfillment"
           style="display:inline-flex;align-items:center;gap:.3rem;font-size:.82rem;color:#64748b;text-decoration:none;">
            ← Fulfillment
        </a>
        <span style="color:#cbd5e1;">/</span>
        <span style="font-size:.82rem;color:#64748b;">
            #{{ $fulfillment->id }}
            @if($fulfillment->order)
                &nbsp;·&nbsp;{{ $fulfillment->order->channel_order_id }}
            @endif
        </span>
        <span style="margin-left:auto;display:inline-flex;align-items:center;gap:.4rem;">
            <span style="font-size:.72rem;font-weight:800;padding:.14rem .5rem;border-radius:999px;background:rgba(148,163,184,.16);color:#64748b;">
                {{ strtoupper($fulfillment->status) }}
            </span>
            @if($fulfillment->order?->store)
                <span style="font-size:.72rem;color:#94a3b8;">
                    {{ $fulfillment->order->store->name }}
                </span>
            @endif
        </span>
    </div>

    <h2 style="font-size:1.15rem;font-weight:900;color:#0f172a;margin:0 0 1.5rem;">
        📋 Audit History
    </h2>

    {{-- Timeline container --}}
    <div id="timelineWrap">
        <div style="text-align:center;padding:3rem 1rem;color:#94a3b8;font-size:.85rem;">
            <span class="prod-tab-spinner" style="display:inline-block;vertical-align:middle;margin-right:.4rem;"></span>
            Memuat log…
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const { api, fmt, esc } = window.mpHelpers;
const FULFILLMENT_ID = {{ $fulfillment->id }};

const ACTION_ICON = {
    create_draft:     '📝',
    scan_order:       '🔍',
    confirm:          '✅',
    start_picking:    '🚀',
    complete_picking: '🏁',
    mark_packed:      '📦',
    unpack:           '📂',
    toggle_picked:    '☑️',
    flag_problem:     '⚠️',
    resolve_problem:  '🔧',
    substitute:       '🔄',
    split:            '✂️',
    restore_split:    '↩️',
};

const ACTION_COLOR = {
    confirm:          '#16a34a',
    complete_picking: '#16a34a',
    flag_problem:     '#b91c1c',
    split:            '#7c3aed',
    restore_split:    '#7c3aed',
    substitute:       '#0284c7',
    mark_packed:      '#0369a1',
};

function renderMeta(action, meta) {
    if (!meta) return '';
    const items = [];

    if (action === 'toggle_picked') {
        items.push(`${meta.picked ? '✔ Picked' : '✘ Un-picked'}: <b>${esc(meta.item_code || '—')}</b> ×${meta.qty || '?'}`);
    } else if (action === 'substitute') {
        items.push(`${esc(meta.from_item_code || '—')} → <b>${esc(meta.to_item_code || '—')}</b> ×${meta.qty || '?'}`);
    } else if (action === 'split') {
        items.push(`Dari <b>${esc(meta.original_item_code || '—')}</b> ×${meta.original_qty}`);
        if (meta.splits?.length) {
            meta.splits.forEach(s => items.push(`&nbsp;&nbsp;↳ <b>${esc(s.item_code || '—')}</b> ×${s.qty}`));
        }
    } else if (action === 'restore_split') {
        items.push(`Restore ke <b>${esc(meta.item_code || '—')}</b> ×${meta.qty_restored}`);
        if (meta.force) items.push('<span style="color:#b45309;font-size:.75rem;">Force (ada yang sudah dipick)</span>');
    } else if (action === 'flag_problem') {
        items.push(`<b>${esc(meta.item_code || '—')}</b>: ${esc(meta.reason || '—')}`);
    } else if (action === 'scan_order') {
        items.push(`Order no: <b>${esc(meta.order_no || '—')}</b>`);
    } else if (action === 'confirm') {
        items.push(`${meta.lines_count ?? '?'} lines dikonfirmasi`);
    }

    if (!items.length) return '';
    return `<div style="margin-top:.35rem;font-size:.78rem;color:#475569;line-height:1.6;">${items.join('<br>')}</div>`;
}

function renderTimeline(logs) {
    if (!logs.length) {
        document.getElementById('timelineWrap').innerHTML =
            '<div style="text-align:center;padding:3rem 1rem;color:#94a3b8;font-size:.85rem;">Belum ada log.</div>';
        return;
    }

    // Group by date
    const groups = {};
    logs.forEach(l => {
        const d = new Date(l.created_at).toLocaleDateString('id-ID', { weekday:'long', day:'2-digit', month:'long', year:'numeric' });
        if (!groups[d]) groups[d] = [];
        groups[d].push(l);
    });

    let html = '';
    for (const [date, entries] of Object.entries(groups)) {
        html += `
        <div style="margin-bottom:2rem;">
            <div style="font-size:.72rem;font-weight:900;text-transform:uppercase;letter-spacing:.06em;
                        color:#94a3b8;margin-bottom:.75rem;display:flex;align-items:center;gap:.5rem;">
                <span>${date}</span>
                <span style="flex:1;height:1px;background:#e2e8f0;"></span>
            </div>
            <div style="display:flex;flex-direction:column;gap:.5rem;">`;

        entries.forEach(l => {
            const icon  = ACTION_ICON[l.action] || '•';
            const color = ACTION_COLOR[l.action] || '#475569';
            const time  = new Date(l.created_at).toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
            const lineBadge = l.line_sku
                ? `<span style="font-size:.68rem;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:4px;padding:.1rem .35rem;color:#64748b;margin-left:.4rem;">${esc(l.line_sku)}</span>`
                : '';

            html += `
            <div style="display:flex;gap:.75rem;align-items:flex-start;">
                <div style="flex-shrink:0;width:2rem;height:2rem;border-radius:50%;
                            background:${color}18;border:2px solid ${color}30;
                            display:flex;align-items:center;justify-content:center;
                            font-size:.9rem;margin-top:.15rem;">
                    ${icon}
                </div>
                <div style="flex:1;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:.6rem .85rem;
                             box-shadow:0 1px 3px rgba(15,23,42,.04);">
                    <div style="display:flex;align-items:center;flex-wrap:wrap;gap:.35rem;">
                        <span style="font-weight:800;font-size:.85rem;color:${color};">${esc(l.label)}</span>
                        ${lineBadge}
                        <span style="margin-left:auto;font-size:.72rem;color:#94a3b8;white-space:nowrap;">${time}</span>
                    </div>
                    <div style="font-size:.75rem;color:#94a3b8;margin-top:.1rem;">
                        oleh <b style="color:#64748b;">${esc(l.user)}</b>
                    </div>
                    ${renderMeta(l.action, l.meta)}
                </div>
            </div>`;
        });

        html += '</div></div>';
    }

    document.getElementById('timelineWrap').innerHTML = html;
}

// Load on page ready
api(`/api/fulfillments/${FULFILLMENT_ID}/audit-logs`)
    .then(renderTimeline)
    .catch(e => {
        document.getElementById('timelineWrap').innerHTML =
            `<div style="text-align:center;padding:2rem;color:#b91c1c;font-size:.85rem;">Gagal memuat log: ${esc(e.message)}</div>`;
    });
</script>
@endpush
