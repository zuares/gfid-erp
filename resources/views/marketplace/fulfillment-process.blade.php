@extends('layouts.app')
@section('title', 'Proses Fulfillment')

@include('marketplace._shared')
@include('sales.shipments._scan_styles')

@section('content')
<div class="sr-scan-page">
    <div class="sr-topbar">
        <div class="sr-top-main">
            <h1 class="sr-title">Proses Order {{ $fulfillment->order->channel_order_id ?? ('#' . $fulfillment->id) }}</h1>
            <div class="sr-sub">{{ $fulfillment->order->store->name ?? 'Toko' }} &middot; {{ $fulfillment->order->store->channel ?? 'Channel' }}</div>
        </div>
        <div class="sr-top-actions">
            <a href="{{ route('marketplace.fulfillment') }}" class="sr-btn">← Kembali ke Antrean</a>
        </div>
    </div>

    <div class="sr-shell">
        <div style="display: flex; gap: 1rem;">
            {{-- Kiri: Scan Input --}}
            <div class="sr-panel" style="flex: 1;">
                <div class="sr-panel-body">
                    <div style="border-left:3px solid #6366f1;padding-left:1rem">
                        <div style="color:#4338ca;font-weight:800;font-size:.88rem;margin-bottom:.2rem">Scan Kode Item (SKU Internal)</div>
                        <div style="color:#94a3b8;font-size:.72rem;margin-bottom:.7rem">Ambil item dari gudang → scan barcode atau ketik kode, tekan Enter</div>

                        <div style="display:flex;gap:.5rem;align-items:center;background:#f5f3ff;border-radius:12px;padding:.45rem .55rem">
                            <input id="singleItemScanInput" type="text" placeholder="Contoh: KAO-M-RED"
                                autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                                style="flex:1;background:transparent;border:none;
                                       padding:.18rem .3rem;color:#0f172a;font-size:1rem;font-weight:700;font-family:monospace;
                                       outline:none;"
                                onkeydown="if(event.key==='Enter')singleScanAddItem()">
                            <button onclick="singleScanAddItem()" id="singleItemScanBtn"
                                style="background:#6366f1;border:none;border-radius:8px;padding:.5rem 1rem;color:#fff;
                                       font-weight:700;font-size:.85rem;cursor:pointer;white-space:nowrap;
                                       box-shadow:0 1px 6px rgba(99,102,241,.3)">
                                + Tambah
                            </button>
                        </div>
                        <div id="singleItemScanResult" style="margin-top:.45rem;font-size:.78rem;min-height:1rem;padding:0 .2rem"></div>

                        <div id="singleItemList" style="margin-top:.75rem;display:none">
                            <div style="color:#94a3b8;font-size:.67rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;margin-bottom:.4rem">Item Terscan</div>
                            <div id="singleItemRows" style="display:flex;flex-direction:column;gap:.3rem;max-height:200px;overflow-y:auto"></div>
                        </div>

                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1rem">
                            <button onclick="singleScanReset()"
                                style="background:transparent;border:1px solid #e2e8f0;border-radius:999px;
                                       padding:.3rem .8rem;color:#94a3b8;font-size:.76rem;font-weight:600;cursor:pointer">
                                🗑 Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kanan: Detail Pesanan --}}
            <div class="sr-panel" style="flex: 1.2;">
                <div class="sr-panel-body">
                    <div style="font-size:.7rem;font-weight:800;color:#94a3b8;letter-spacing:.05em;
                                text-transform:uppercase;margin-bottom:.5rem">📋 Data Pesanan</div>
                    <table style="width:100%;border-collapse:collapse;font-size:.82rem;margin-bottom:1rem">
                        <thead>
                            <tr style="background:#f8fafc">
                                <th style="padding:.4rem .65rem;font-size:.68rem;font-weight:700;color:#94a3b8;text-align:left;border-bottom:2px solid #e2e8f0">ITEM</th>
                                <th style="padding:.4rem .65rem;font-size:.68rem;font-weight:700;color:#94a3b8;text-align:center;border-bottom:2px solid #e2e8f0">DIPESAN</th>
                                <th style="padding:.4rem .65rem;font-size:.68rem;font-weight:700;color:#94a3b8;text-align:center;border-bottom:2px solid #e2e8f0">DI-PACK</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fulfillment->lines->where('is_split_parent', false) as $line)
                                @php
                                    $isZero = $line->qty_fulfilled == 0;
                                    $isOk = $line->qty_fulfilled >= $line->qty_ordered;
                                    $rowBg = $isZero ? 'rgba(239,68,68,.04)' : (!$isOk ? 'rgba(245,158,11,.04)' : '#fff');
                                    $borderClr = $isZero ? 'rgba(239,68,68,.15)' : (!$isOk ? 'rgba(245,158,11,.15)' : '#f1f5f9');
                                    $qtyColor = $isZero ? '#dc2626' : ($isOk ? '#16a34a' : '#d97706');
                                @endphp
                                <tr style="background:{{ $rowBg }}">
                                    <td style="padding:.5rem .65rem;border-bottom:1px solid {{ $borderClr }}">
                                        <code style="font-size:.78rem;color:#4338ca">{{ $line->item->code ?? $line->marketplace_sku ?? '—' }}</code>
                                        <div style="font-size:.68rem;color:#94a3b8;margin-top:.08rem">{{ $line->item->name ?? $line->marketplace_item_name ?? '—' }}</div>
                                    </td>
                                    <td style="padding:.5rem .65rem;border-bottom:1px solid {{ $borderClr }};text-align:center;font-weight:700;font-size:.82rem;color:#64748b">
                                        {{ $line->qty_ordered }}
                                    </td>
                                    <td style="padding:.5rem .65rem;border-bottom:1px solid {{ $borderClr }};text-align:center;font-weight:800;font-size:.88rem;color:{{ $qtyColor }}" id="line-qty-{{ $line->item_id }}">
                                        {{ $line->qty_fulfilled ?? 0 }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div style="padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                        <button id="btnConfirmAndCutStock" onclick="confirmAction()"
                            style="width: 100%; background:#16a34a;border:none;border-radius:12px;padding:.75rem 1.5rem;
                                   color:#fff;font-size:1rem;font-weight:800;cursor:pointer;
                                   box-shadow:0 3px 12px rgba(22,163,74,.35);transition:all .18s"
                            onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                            ✓ Konfirmasi &amp; Potong Stok
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const fulfillId = {{ $fulfillment->id }};
    const lines = @json($fulfillment->lines->where('is_split_parent', false)->values());
    const esc = str => (str || '').replace(/[&<>'"]/g, tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag]));
    const api = window.mpHelpers?.api || (async (url, opts = {}) => {
        const r = await fetch(url, { ...opts, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...(opts.headers||{}) }});
        const data = await r.json();
        if (!r.ok) throw new Error(data.message || 'Error API');
        return data;
    });

    let _singleScanItems = {};

    function renderSingleItemRows() {
        const rows = document.getElementById('singleItemRows');
        const list = document.getElementById('singleItemList');
        const items = Object.values(_singleScanItems);
        
        // Update table indicators
        lines.forEach(l => {
            const qtyEl = document.getElementById('line-qty-' + l.item_id);
            if(qtyEl) {
                const scannedQty = items.find(i => i.id === l.item_id)?.qty || 0;
                qtyEl.textContent = scannedQty;
                if(scannedQty === 0) qtyEl.style.color = '#dc2626';
                else if (scannedQty >= l.qty_ordered) qtyEl.style.color = '#16a34a';
                else qtyEl.style.color = '#d97706';
            }
        });

        if (!items.length) { list.style.display = 'none'; return; }
        list.style.display = 'block';
        rows.innerHTML = items.map(it => `
            <div class="sr-item-row" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:9px; padding:.4rem .7rem; display:flex; gap:.6rem; margin-bottom:.3rem; align-items:center;">
                <div style="flex:1; min-width:0">
                    <div style="font-size:.72rem;font-weight:800;color:#4338ca;font-family:monospace;">${esc(it.code)}</div>
                    <div style="font-size:.75rem;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(it.name)}</div>
                </div>
                <div style="display:flex;align-items:center;gap:.3rem;flex-shrink:0">
                    <button onclick="singleScanDec(${it.id})"
                        style="background:#e2e8f0;border:none;border-radius:5px;width:22px;height:22px;color:#475569;font-weight:800;cursor:pointer;font-size:.8rem;line-height:1">−</button>
                    <span style="font-weight:800;font-size:.82rem;min-width:20px;text-align:center">${it.qty}</span>
                    <button onclick="singleScanInc(${it.id})"
                        style="background:#e2e8f0;border:none;border-radius:5px;width:22px;height:22px;color:#475569;font-weight:800;cursor:pointer;font-size:.8rem;line-height:1">+</button>
                    <button onclick="singleScanRemove(${it.id})"
                        style="background:#fee2e2;border:none;border-radius:5px;width:22px;height:22px;color:#ef4444;font-weight:800;cursor:pointer;font-size:.8rem;line-height:1;margin-left:.2rem">✕</button>
                </div>
            </div>`).join('');
    }

    window.singleScanAddItem = async function () {
        const input    = document.getElementById('singleItemScanInput');
        const resultEl = document.getElementById('singleItemScanResult');
        const q = input.value.trim();
        if (!q) return;

        const localMatch = lines.find(l =>
            l.item?.code?.toLowerCase() === q.toLowerCase() ||
            l.marketplace_sku?.toLowerCase() === q.toLowerCase()
        );

        if (localMatch) {
            const it = localMatch.item;
            if (_singleScanItems[it.id]) _singleScanItems[it.id].qty++;
            else _singleScanItems[it.id] = { id: it.id, code: it.code, name: it.name, qty: 1 };
            
            input.value = '';
            renderSingleItemRows();
            resultEl.innerHTML = `<span style="color:#10b981">✓ ${esc(it.code)}</span>`;
            setTimeout(() => { resultEl.innerHTML = ''; }, 800);
            input.focus();
            return;
        }

        resultEl.innerHTML = '<span style="color:#f87171">Item tidak ditemukan di pesanan ini!</span>';
        input.value = '';
        input.focus();
    }

    window.singleScanInc = function (id) { if (_singleScanItems[id]) { _singleScanItems[id].qty++; renderSingleItemRows(); } };
    window.singleScanDec = function (id) {
        if (_singleScanItems[id]) {
            _singleScanItems[id].qty--;
            if (_singleScanItems[id].qty <= 0) delete _singleScanItems[id];
            renderSingleItemRows();
        }
    };
    window.singleScanRemove = function (id) { delete _singleScanItems[id]; renderSingleItemRows(); };
    window.singleScanReset = function () { _singleScanItems = {}; renderSingleItemRows(); document.getElementById('singleItemScanInput').focus(); };

    window.confirmAction = async function() {
        const btn = document.getElementById('btnConfirmAndCutStock');
        btn.disabled = true;
        btn.textContent = 'Memproses...';

        const items = Object.values(_singleScanItems).map(s => ({ item_id: s.id, qty: s.qty }));

        try {
            // 1. Pack items
            await api(`/api/fulfillments/${fulfillId}/pack`, { method: 'POST', body: JSON.stringify({ items }) });
            // 2. Confirm & Cut stock
            await api(`/api/fulfillments/${fulfillId}/confirm-packed`, { method: 'POST' });
            
            alert('Sukses! Pesanan berhasil dikemas dan stok dipotong.');
            window.location.href = "{{ route('marketplace.fulfillment') }}";
        } catch(e) {
            alert(e.message || 'Terjadi kesalahan.');
            btn.disabled = false;
            btn.textContent = '✓ Konfirmasi & Potong Stok';
        }
    }

    // Initialize with existing scanned items if any (qty_fulfilled)
    window.onload = () => {
        lines.forEach(l => {
            if(l.qty_fulfilled > 0 && l.item) {
                _singleScanItems[l.item_id] = { id: l.item_id, code: l.item.code, name: l.item.name, qty: l.qty_fulfilled };
            }
        });
        renderSingleItemRows();
        document.getElementById('singleItemScanInput').focus();
    }
</script>
@endpush
