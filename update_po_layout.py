import re

with open('resources/views/purchasing/purchase_orders/show.blade.php', 'r') as f:
    content = f.read()

start_idx = content.find('<div class="page-wrap py-4">')
end_idx = content.find('    @if ($canSeeMoney)\n    {{-- =========================================================\n    MODAL: ADD PAYMENT')

if start_idx == -1 or end_idx == -1:
    end_idx = content.find('{{-- =========================================================\n    MODAL: ADD PAYMENT')

new_html = """
<div class="po-topbar">
    <a href="{{ route('purchasing.purchase_orders.index') }}" class="po-btn" title="Kembali"><i class="bi bi-arrow-left"></i> Kembali</a>
    
    @php
       $statusBadgeClass = match($status) {
           'approved' => 'approved',
           'closed' => 'closed',
           'cancelled' => 'cancelled',
           default => 'draft'
       };
       $statusLabel = match($status) {
           'approved' => 'APPROVED',
           'closed' => 'CLOSED',
           'cancelled' => 'CANCELLED',
           default => 'DRAFT'
       };
    @endphp
    
    <span class="po-code">{{ $order->code }}</span>
    <span class="po-supplier d-none d-md-inline">{{ optional($order->supplier)->name ?? 'Purchase Order' }}</span>
    
    <span class="po-pill po-status {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
    
    @if ($order->isLocked())
        <span class="po-pill po-status" style="background:rgba(245,158,11,.1);color:#92400e;border:1px solid rgba(245,158,11,.3);" title="{{ $order->lock_reason }} ({{ optional($order->locked_at)->format('d/m/Y H:i') }})"><i class="bi bi-lock-fill"></i> Locked</span>
    @endif
    
    <span class="po-spacer"></span>

    {{-- PRIMARY ACTIONS --}}
    @if ($user && (in_array($user->role, ['owner','admin']) || $user->isDeveloper()) && $status === 'draft')
        @if ($poHasPrice)
            <form action="{{ route('purchasing.purchase_orders.approve', $order->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve PO ini? Setelah di-approve, PO tidak bisa diedit lagi.');">
                @csrf
                <button type="submit" class="po-btn po-primary" title="Approve PO">Approve</button>
            </form>
        @else
            <button type="button" class="po-btn po-primary" style="opacity:.5;cursor:not-allowed;" title="{{ $canSeeMoney ? 'Harga belum diisi, edit PO terlebih dahulu.' : 'Dokumen belum lengkap, hubungi owner.' }}">Approve</button>
        @endif
    @endif
    
    @if ($canSeeMoney && $canPay && $hasAp)
         <button type="button" class="po-btn po-success" data-bs-toggle="modal" data-bs-target="#modalAddPayment" title="Bayar PO"><i class="bi bi-cash-coin d-inline-block d-md-none"></i> <span class="d-none d-md-inline">Bayar PO</span></button>
    @endif
    
    {{-- Terima (buat GRN) --}}
    @if ($user && ($user->isOwner() || $isAdmin) && $order->isReceivableForGrn() && $status !== 'cancelled' && ($canCreateGrn ?? true))
         <a href="{{ route('purchasing.purchase_receipts.create_from_order', $order->id) }}" class="po-btn po-info" title="Terima Barang"><i class="bi bi-box-seam d-inline-block d-md-none"></i> <span class="d-none d-md-inline">Terima</span></a>
    @endif

    {{-- Opsi Lainnya --}}
    <div class="dropdown d-inline-block">
        <button class="po-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Opsi Lainnya"><i class="bi bi-three-dots-vertical"></i></button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="font-size: .85rem; border-radius: 12px; padding: .5rem 0;">
            @if ($canSeeMoney)
                <li>
                    <a class="dropdown-item py-2" href="{{ route('purchasing.purchase_orders.print_dot_matrix', $order->id) }}">
                        <i class="bi bi-printer me-2 text-muted"></i> Cetak (Dot Matrix)
                    </a>
                </li>
            @endif
            <li>
                <a class="dropdown-item py-2" href="{{ route('purchasing.purchase_orders.print_a4', $order->id) }}" target="_blank">
                    <i class="bi bi-file-earmark-pdf me-2 text-muted"></i> Cetak PDF (A4)
                </a>
            </li>
            @if ($status === 'draft' && (!$order->isLocked() || ($user && $user->canSeePurchasePrices())))
                <li>
                    <a class="dropdown-item py-2" href="{{ route('purchasing.purchase_orders.edit', $order->id) }}">
                        <i class="bi bi-pencil me-2 text-muted"></i> Edit PO
                    </a>
                </li>
            @endif
            @if ($canSeeMoney && $user && ($user->isOwner() || $user->isDeveloper()))
                @if ($order->hasUnappliedDp() || $order->hasApAvailableToApplyDp())
                    <li>
                        <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#modalOffsetDp">
                            <i class="bi bi-arrow-left-right me-2 text-muted"></i> Offset DP
                        </a>
                    </li>
                @endif
                @if ($hasAp && $apOutstanding > 0.0001 && $order->payments()->where('type', 'dp')->sum('amount') > 0)
                    <li>
                        <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#modalApplyDp">
                            <i class="bi bi-box-arrow-in-down me-2 text-muted"></i> Gunakan DP
                        </a>
                    </li>
                @endif
            @endif
            @if ($status === 'draft' && $user && in_array($user->role, ['owner','admin']))
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('purchasing.purchase_orders.cancel', $order->id) }}" method="POST"
                          onsubmit="return confirm('Cancel PO ini?');">
                        @csrf
                        <button type="submit" class="dropdown-item py-2 text-danger">
                            <i class="bi bi-x-circle me-2"></i> Cancel PO
                        </button>
                    </form>
                </li>
            @endif
        </ul>
    </div>
</div>

<div class="po-wrap">
    <div class="po-grid mt-2">
        <div class="po-card po-kpi">
            <div class="po-label">Item Batch</div>
            <div class="po-value">{{ $order->lines->count() }} <span class="po-muted" style="font-size:.8rem;font-weight:500;">Tipe</span> <span class="po-muted mx-1 fw-normal" style="opacity:.4;">•</span> {{ decimal_id($totalQty, 2) }} <span class="po-muted" style="font-size:.8rem;font-weight:500;">Pcs</span></div>
        </div>
        @if ($canSeeMoney)
        <div class="po-card po-kpi">
            <div class="po-label">Total PO</div>
            <div class="po-value">{{ rupiah($order->grand_total) }}</div>
        </div>
        <div class="po-card po-kpi">
            <div class="po-label">Total GRN</div>
            <div class="po-value">{{ rupiah($grnPostedTotal) }}</div>
        </div>
        <div class="po-card po-kpi">
            <div class="po-label">Sisa Tagihan</div>
            <div class="po-value" style="color:#b91c1c;">{{ rupiah($apOutstanding) }}</div>
        </div>
        @else
        <div class="po-card po-kpi">
            <div class="po-label">Status</div>
            <div class="po-value" style="font-size:1rem">{{ $statusLabel }}</div>
        </div>
        @endif
    </div>

    <div class="po-tabs" role="tablist">
        <button type="button" class="po-tab active" data-tab="item">Rincian Barang <span class="po-tab-count">{{ $order->lines->count() }}</span></button>
        @if ($user && ($user->isOwner() || $isAdmin))
        <button type="button" class="po-tab" data-tab="grn">Penerimaan (GRN) <span class="po-tab-count">{{ $grnCount }}</span></button>
        @endif
        @if ($canSeeMoney && $order->payments->count() > 0)
        <button type="button" class="po-tab" data-tab="payments">Riwayat Bayar <span class="po-tab-count">{{ $order->payments->count() }}</span></button>
        @endif
    </div>

    {{-- TAB ITEM BARANG --}}
    <div class="po-tabpane active" id="po-tab-item" role="tabpanel">
        <div class="po-card">
            <div class="po-head">
                <div>
                    <div class="po-title">Barang Dipesan</div>
                    <div class="po-muted">Ringkasan barang yang dipesan pada dokumen PO ini.</div>
                </div>
            </div>
            <div class="po-body">
                @if($order->lines->isEmpty())
                    <div class="po-empty">Belum ada item.</div>
                @else
                    <div class="po-table-wrap">
                        <table class="po-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="po-r">Qty</th>
                                    @if($canSeeMoney)
                                        <th class="po-r po-hide-mobile">@ Harga</th>
                                        <th class="po-r po-hide-mobile">Subtotal</th>
                                    @endif
                                    <th class="po-r">Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->lines as $line)
                                    @php
                                        $hasDiscount = (float) $line->discount > 0.0001;
                                        $rcv = (float) $line->qty_received;
                                        $ret = (float) $line->qty_returned;
                                        $qtyOut = $line->qty - $rcv;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="po-code-cell">{{ $line->item->code ?? '-' }}</div>
                                            <div class="po-name">{{ $line->item->name ?? '-' }}</div>
                                            
                                            {{-- MOBILE EXTRA INFO --}}
                                            <div class="d-md-none mt-1">
                                                @if($canSeeMoney)
                                                    <div style="font-size:.8rem;color:#475569;">{{ angka($line->unit_price) }} x {{ decimal_id($line->qty, 2) }} = <b>{{ angka($line->subtotal) }}</b></div>
                                                @else
                                                    <div style="font-size:.8rem;color:#475569;">Qty: <b>{{ decimal_id($line->qty, 2) }}</b> pcs</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="po-r po-hide-mobile">
                                            <span class="po-pill" style="border:none;background:rgba(148,163,184,.12)">{{ decimal_id($line->qty, 2) }} pcs</span>
                                        </td>
                                        @if($canSeeMoney)
                                            <td class="po-r po-hide-mobile">
                                                {{ angka($line->unit_price) }}
                                                @if($hasDiscount)
                                                    <br><span class="text-danger" style="font-size:.7rem">Disc: -{{ angka($line->discount) }}</span>
                                                @endif
                                            </td>
                                            <td class="po-r po-hide-mobile">{{ angka($line->subtotal) }}</td>
                                        @endif
                                        <td class="po-r">
                                            @if ($rcv > 0)
                                                <div style="font-size:.75rem; color:#15803d; font-weight:700;">Terima: {{ decimal_id($rcv, 2) }}</div>
                                            @endif
                                            @if ($ret > 0)
                                                <div style="font-size:.75rem; color:#b91c1c; font-weight:700;">Retur: {{ decimal_id($ret, 2) }}</div>
                                            @endif
                                            @if ($qtyOut > 0)
                                                <div style="font-size:.75rem; color:#d97706; font-weight:700;">Sisa: {{ decimal_id($qtyOut, 2) }}</div>
                                            @endif
                                            @if ($rcv == 0 && $ret == 0 && $qtyOut == 0)
                                                -
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
    </div>{{-- /po-tab-item --}}

    {{-- TAB GRN --}}
    @if ($user && ($user->isOwner() || $isAdmin))
    <div class="po-tabpane" id="po-tab-grn" role="tabpanel">
        <div class="po-card">
            <div class="po-head">
                <div>
                    <div class="po-title">Penerimaan Barang (GRN)</div>
                    <div class="po-muted">Daftar surat jalan yang telah diterbitkan untuk PO ini.</div>
                </div>
            </div>
            <div class="po-body">
                @if ($grnCount === 0)
                    <div class="po-empty">Belum ada penerimaan.</div>
                @else
                    <div class="po-table-wrap">
                        <table class="po-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Dokumen</th>
                                    <th>Gudang</th>
                                    @if ($canSeeMoney)
                                    <th class="po-r">Total</th>
                                    @endif
                                    <th style="text-align:center;">Status</th>
                                    <th class="po-r">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($grnList as $grn)
                                    @php
                                        $isPosted = ($grn->status ?? 'draft') === 'posted';
                                        $badgeStatusClass = $isPosted ? 'badge-posted' : 'badge-draft';
                                        $statusLabelGRN = $isPosted ? 'POSTED' : 'DRAFT';
                                        $wh = $grn->warehouse ?? null;
                                    @endphp
                                    <tr>
                                        <td class="po-code-cell" style="font-weight:normal;color:#64748b;">{{ $grn->date ? id_date($grn->date) : '-' }}</td>
                                        <td>
                                            <a href="{{ route('purchasing.purchase_receipts.show', $grn->id) }}" class="po-code-cell text-decoration-none" style="color:#0284c7;">{{ $grn->code ?? $grn->id }}</a>
                                            <div class="d-md-none mt-1">
                                                @if ($wh) <span style="font-size:.7rem; color:#64748b;">{{ $wh->name }}</span> @endif
                                            </div>
                                        </td>
                                        <td class="po-hide-mobile">
                                            @if ($wh)
                                                <div style="font-weight:700;">{{ $wh->code }}</div>
                                                <div class="po-muted" style="font-size:.7rem;">{{ $wh->name }}</div>
                                            @else
                                                <span class="po-muted">-</span>
                                            @endif
                                        </td>
                                        @if ($canSeeMoney)
                                        <td class="po-r" style="font-weight:700;">{{ isset($grn->grand_total) ? rupiah($grn->grand_total) : '—' }}</td>
                                        @endif
                                        <td style="text-align:center;">
                                            <span class="{{ $badgeStatusClass }}">{{ $statusLabelGRN }}</span>
                                        </td>
                                        <td class="po-r">
                                            <div class="d-flex justify-content-end gap-1">
                                                <a href="{{ route('purchasing.purchase_receipts.show', $grn->id) }}" class="po-btn" style="min-height:28px;padding:.1rem .5rem;">Detail</a>
                                                @if (!$isPosted)
                                                    <form action="{{ route('purchasing.purchase_receipts.post', $grn->id) }}" method="POST" onsubmit="return confirm('Post GRN ini? Setelah di-post, stok akan ter-update.');">
                                                        @csrf
                                                        <button type="submit" class="po-btn po-success" style="min-height:28px;padding:.1rem .5rem;">Post</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- TAB PEMBAYARAN --}}
    @if ($canSeeMoney && $order->payments->count() > 0)
    <div class="po-tabpane" id="po-tab-payments" role="tabpanel">
        <div class="po-card">
            <div class="po-head">
                <div>
                    <div class="po-title">Riwayat Pembayaran</div>
                    <div class="po-muted">Daftar mutasi uang keluar untuk PO ini.</div>
                </div>
            </div>
            <div class="po-body">
                <div class="po-table-wrap">
                    <table class="po-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Tipe</th>
                                <th>Metode</th>
                                <th class="po-r">Nominal</th>
                                <th class="po-hide-mobile">Ref</th>
                                <th class="po-r">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->payments as $p)
                                @php
                                    $type = (string) ($p->type ?? '');
                                    $typeLabel = match ($type) {
                                        'dp' => 'DP',
                                        'dp_apply' => 'Gunakan DP',
                                        'return_apply' => 'Retur',
                                        default => 'Pelunasan',
                                    };
                                @endphp
                                <tr>
                                    <td class="po-code-cell" style="font-weight:normal;color:#64748b;">{{ $p->date ? id_date($p->date) : '-' }}</td>
                                    <td><span class="po-pill" style="font-size:.7rem;padding:0 .4rem;">{{ $typeLabel }}</span></td>
                                    <td>
                                        <div style="font-weight:700;">{{ $p->paymentMethod?->code ?? '-' }}</div>
                                        <div class="po-muted" style="font-size:.7rem;">{{ $p->paymentMethod?->name ?? '-' }}</div>
                                    </td>
                                    <td class="po-r" style="font-weight:700;">
                                        @if(in_array($type, ['dp_apply','return_apply']))
                                            <span style="color:#d97706;">{{ rupiah($p->amount) }}</span>
                                        @else
                                            <span style="color:#15803d;">{{ rupiah($p->amount) }}</span>
                                        @endif
                                    </td>
                                    <td class="po-hide-mobile"><span class="po-code-cell" style="font-weight:normal;">{{ $p->reference_number ?? '-' }}</span></td>
                                    <td class="po-r">
                                        <form action="{{ route('purchasing.purchase_orders.payments.destroy', [$order->id, $p->id]) }}" method="POST" onsubmit="return confirm('Hapus/Void pembayaran ini?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="po-btn" style="color:#b91c1c;min-height:28px;padding:.1rem .5rem;"><i class="bi bi-trash"></i> Void</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
    
</div>

<script>
(function(){
    var tabs = document.querySelectorAll('.po-tab');
    var panes = document.querySelectorAll('.po-tabpane');
    function activate(name){
        tabs.forEach(function(t){ t.classList.toggle('active', t.dataset.tab === name); });
        panes.forEach(function(p){ p.classList.toggle('active', p.id === 'po-tab-' + name); });
        try { history.replaceState(null, '', '#' + name); } catch(e){}
    }
    tabs.forEach(function(t){
        t.addEventListener('click', function(){ activate(t.dataset.tab); });
    });
    var hash = (location.hash || '').replace('#','');
    if (['item','grn','payments'].indexOf(hash) !== -1) activate(hash);
})();
</script>
\n"""

# Apply substitution
content = content[:start_idx] + new_html + "\n" + content[end_idx:]

with open('resources/views/purchasing/purchase_orders/show.blade.php', 'w') as f:
    f.write(content)

print("Applied HTML successfully.")
