{{-- resources/views/purchasing/purchase_orders/show.blade.php --}}
@extends('layouts.app')

@section('title', 'PO ' . $order->code)

@push('head')
    <style>
.po-wrap{max-width:1080px;margin-inline:auto;padding:.7rem .75rem 3rem}
.po-topbar{position:sticky;top:0;z-index:250;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;padding:.55rem .75rem;background:var(--card,#fff);border-bottom:1px solid rgba(148,163,184,.18)}
.po-code{font-weight:900;font-size:1.05rem;color:#111827;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
.po-supplier{font-size:.8rem;color:#64748b;margin-left:.25rem}
.po-spacer{flex:1}
.po-document-date-row{display:flex;align-items:center;gap:.65rem;margin-bottom:.7rem;padding:.8rem 1rem;border:1px solid rgba(37,99,235,.2);border-left:4px solid #2563eb;border-radius:9px;background:linear-gradient(90deg,rgba(239,246,255,.92),rgba(255,255,255,.96));color:#64748b;font-size:.78rem;box-shadow:0 3px 12px rgba(37,99,235,.07)}
.po-document-date-row i{display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:7px;background:#dbeafe;color:#2563eb;font-size:1rem}
.po-document-date-row strong{color:#1e3a8a;font-size:1rem;font-weight:900;letter-spacing:-.01em}
.po-document-date-label{font-size:.68rem;font-weight:900;letter-spacing:.06em;text-transform:uppercase;color:#64748b}
body[data-theme="dark"] .po-document-date-row{border-color:rgba(96,165,250,.35);background:linear-gradient(90deg,rgba(30,58,138,.24),rgba(15,23,42,.92));box-shadow:0 3px 12px rgba(2,6,23,.25)}
body[data-theme="dark"] .po-document-date-row i{background:rgba(59,130,246,.2);color:#93c5fd}
body[data-theme="dark"] .po-document-date-row strong{color:#bfdbfe}
.po-btn,.po-pill{display:inline-flex;align-items:center;justify-content:center;gap:.35rem;border-radius:7px;border:1px solid rgba(148,163,184,.3);background:transparent;color:#475569;text-decoration:none;font-size:.76rem;padding:.28rem .6rem;min-height:34px}
.po-btn{font-weight:800; cursor:pointer;}
.po-btn:hover{background:rgba(148,163,184,.09);color:#111827;text-decoration:none}
.po-primary{background:#334155!important;border-color:#334155!important;color:#fff!important}
.po-success{background:#15803d!important;border-color:#15803d!important;color:#fff!important}
.po-success:hover{background:#166534!important;border-color:#166534!important}
.po-info{background:#0ea5e9!important;border-color:#0ea5e9!important;color:#fff!important}
.po-info:hover{background:#0284c7!important}
.po-wa{background:#25d366!important;border-color:#25d366!important;color:#fff!important}
.po-wa:hover{background:#16a34a!important;border-color:#16a34a!important;color:#fff!important}
.po-wa-sent{background:#ecfdf5!important;border-color:#86efac!important;color:#15803d!important}
.po-wa-sent:hover{background:#dcfce7!important;border-color:#4ade80!important;color:#166534!important}
.po-status{font-weight:850;color:#334155;background:rgba(148,163,184,.08)}
.po-status.approved{color:#166534;background:rgba(22,101,52,.08);border-color:rgba(22,101,52,.2)}
.po-status.closed{color:#0f172a;background:rgba(15,23,42,.08);border-color:rgba(15,23,42,.2)}
.po-status.cancelled{color:#991b1b;background:rgba(153,27,27,.08);border-color:rgba(153,27,27,.2)}
.pay-badge{font-weight:850;color:#475569;background:rgba(148,163,184,.08)}
.pay-badge.pay-paid{color:#166534;background:rgba(22,101,52,.08);border-color:rgba(22,101,52,.2)}
.pay-badge.pay-partial{color:#a16207;background:rgba(234,179,8,.1);border-color:rgba(234,179,8,.25)}
.pay-badge.pay-overpaid{color:#6d28d9;background:rgba(124,58,237,.1);border-color:rgba(124,58,237,.25)}
.pay-badge.pay-unpaid{color:#64748b;background:rgba(148,163,184,.08)}
.po-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.55rem;margin-bottom:.65rem}
.po-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:8px;overflow:hidden;margin-bottom:.65rem}
.po-kpi{padding:.65rem .75rem}
.po-label{font-size:.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.02em}
.po-value{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:1.18rem;font-weight:900;color:#111827;margin-top:.12rem}
.po-head{display:flex;align-items:center;gap:.55rem;justify-content:space-between;padding:.7rem .85rem;border-bottom:1px solid rgba(148,163,184,.12)}
.po-title{font-weight:900;color:#334155}
.po-muted{color:#64748b;font-size:.8rem}
.po-body{padding:.75rem .85rem}
.po-empty{padding:1.6rem 1rem;text-align:center;color:#64748b;font-size:.84rem}
.po-table-wrap{overflow:auto;border:1px solid rgba(148,163,184,.16);border-radius:8px}
.po-table{width:100%;border-collapse:collapse}
.po-table th,.po-table td{padding:.55rem .65rem;border-bottom:1px solid rgba(148,163,184,.12);vertical-align:middle}
.po-table th{text-align:left;font-size:.72rem;color:#64748b;font-weight:900;text-transform:uppercase;letter-spacing:.02em;background:rgba(148,163,184,.04)}
.po-table td{font-size:.86rem;color:#334155}
.po-code-cell{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#111827}
.po-name{color:#64748b;font-size:.8rem;margin-top:.08rem}
.po-r{text-align:right}
.po-total td{font-weight:900;color:#111827;background:rgba(148,163,184,.04)}
.po-unit-value{font-weight:800;color:#334155;white-space:nowrap}
.po-unit-conversion{color:#64748b;font-size:.74rem;line-height:1.35;white-space:nowrap}
.po-unit-conversion strong{color:#475569;font-weight:800}
body[data-theme="dark"] .po-unit-value{color:#e2e8f0}
body[data-theme="dark"] .po-unit-conversion{color:#94a3b8}
body[data-theme="dark"] .po-unit-conversion strong{color:#cbd5e1}
.po-tabs{display:flex;gap:.25rem;margin-bottom:.65rem;border-bottom:1px solid rgba(148,163,184,.18);flex-wrap:wrap}
.po-tab{appearance:none;display:inline-flex;align-items:center;gap:.4rem;border:none;background:transparent;color:#64748b;font-weight:800;font-size:.82rem;padding:.55rem .8rem;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px}
.po-tab:hover{color:#334155}
.po-tab.active{color:#111827;border-bottom-color:#334155}
.po-tab-count{display:inline-flex;align-items:center;justify-content:center;min-width:1.35rem;height:1.35rem;padding:0 .3rem;border-radius:999px;background:rgba(148,163,184,.16);color:#475569;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.7rem;font-weight:900}
.po-tab.active .po-tab-count{background:#334155;color:#fff}
.po-tabpane{display:none}
.po-tabpane.active{display:block}
.po-tabpane .po-card{margin-bottom:0}

@media(max-width:860px){
  .po-wrap{padding:.5rem .5rem 3.5rem}
  .po-topbar{padding:.5rem}
  .po-code{flex:1;min-width:150px;font-size:1.02rem}
  .po-supplier{display:none;}
  .po-topbar .po-pill:not(.po-status),.po-hide-mobile{display:none}
  .po-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:.45rem}
  .po-kpi{padding:.58rem .62rem}
  .po-value{font-size:1.08rem}
  .po-head{padding:.65rem .7rem}
  .po-body{padding:.65rem .7rem}
  .po-table-wrap{border:none;border-radius:0;overflow:visible}
  .po-table,.po-table tbody,.po-table tr,.po-table td{display:block;width:100%}
  .po-table thead{display:none}
  .po-table tr{border:1px solid rgba(148,163,184,.16);border-radius:8px;margin-bottom:.45rem;padding:.55rem .6rem;background:var(--card,#fff)}
  .po-table td{border:0;padding:0}
  .po-table td[data-label]{display:grid;grid-template-columns:7.2rem minmax(0,1fr);gap:.55rem;align-items:baseline;margin-top:.35rem}
  .po-table td[data-label]::before{content:attr(data-label);color:#94a3b8;font-size:.64rem;font-weight:900;letter-spacing:.05em;text-transform:uppercase}
  .po-table td[data-label] > *{min-width:0}
  .po-table td.item-cell{display:block}
  .po-table td.item-cell::before{display:none}
  .po-table td.po-r{text-align:left;margin-top:.35rem}
  .po-table td.progress-cell { margin-top: .5rem; padding-top: .5rem; border-top: 1px dashed rgba(148,163,184,.3); }
  .po-total{display:none!important}
}
.mono {
    font-variant-numeric: tabular-nums;
    font-family: ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono";
}
/* Utility */
.badge-posted { background:rgba(22,163,74,.12);color:#15803d;border-color:rgba(22,163,74,.6); border: 1px solid; border-radius: 999px; padding: 0.15rem 0.5rem; font-size: 0.72rem; }
.badge-draft { background:rgba(148,163,184,.12);color:#64748b;border-color:rgba(148,163,184,.5); border: 1px solid; border-radius: 999px; padding: 0.15rem 0.5rem; font-size: 0.72rem; }
</style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $isAdmin = strtolower((string)($user->role ?? '')) === 'admin';
        $canSendWhatsapp = $user && ($user->isOwner() || $isAdmin || $user->isDeveloper());
        $supplierPhone = trim((string) ($order->supplier?->phone ?? ''));
        $canSeeMoney = $user?->canSeePurchasePrices() ?? false;
        $poHasPrice  = (float) ($order->grand_total ?? 0) > 0;

        // Status PO
        $status = (string) ($order->status ?? 'draft');
        $statusClass = match ($status) {
            'approved' => 'tag tag-status-approved',
            'cancelled' => 'tag tag-status-cancelled',
            default => 'tag tag-status-draft',
        };

        // GRN list
        $grnList = $order->purchaseReceipts ?? collect();
        $grnCount = $grnList->count();
        $hasActivePayments = $order->activePayments()->exists();

        // Payment method "preferensi" PO
        $pm = $order->paymentMethod ?? null;

        // Hutang real berbasis GRN posted (dari controller show)
        $grnPostedTotal = (float) ($grnPostedTotal ?? 0);
        $returnPostedTotal = (float) ($returnPostedTotal ?? 0);
        $apDebt = (float) ($apDebt ?? max(0, round($grnPostedTotal - $returnPostedTotal, 2)));
        $paidPaymentTotal = (float) ($paidPaymentTotal ?? 0); // type=payment
        $dpTotal = (float) ($dpTotal ?? 0); // type=dp

        // DP APPLY total (buat UI)
        $dpAppliedTotal =
            (float) ($dpAppliedTotal ?? ($order->activePayments()?->where('type', 'dp_apply')->sum('amount') ?? 0));
        $dpAvailable = max(0, round($dpTotal - $dpAppliedTotal, 2));

        // outstanding hutang (should include dp_apply)
        $apOutstanding =
            (float) ($apOutstanding ?? max(0, round($apDebt - $paidPaymentTotal - $dpAppliedTotal, 2)));

        // Tampilkan pecahan rupiah jika saldo aktual memang memiliki desimal.
        // Tanpa ini, sisa Rp0,98 terlihat sebagai Rp1 dan tombol "Sisa"
        // mengirim nominal yang lebih besar dari hutang sebenarnya.
        $formatPaymentMoney = static function ($value): string {
            $value = (float) $value;
            $decimals = abs($value - round($value)) > 0.0001 ? 2 : 0;
            return rupiah($value, $decimals);
        };

        // status bayar
        $payStatus = (string) ($order->payment_status ?? 'unpaid');
        $payBadgeClass = match ($payStatus) {
            'paid' => 'pay-badge pay-paid',
            'partial' => 'pay-badge pay-partial',
            'overpaid' => 'pay-badge pay-overpaid',
            default => 'pay-badge pay-unpaid',
        };
        $payStatusLabel = match ($payStatus) {
            'paid' => 'LUNAS',
            'partial' => 'SEBAGIAN BAYAR',
            'overpaid' => 'PIUTANG SUPPLIER',
            default => 'BELUM BAYAR',
        };

        $canPay = $status !== 'cancelled';
        $hasPayments = ($order->payments?->count() ?? 0) > 0;

        // strict cash/bank list
        $cashAccountsCol = collect($cashAccounts ?? []);
        $cash1101 = $cashAccountsCol->firstWhere('code', '1101');

        $transferBankCodes = ['1111', '1112', '1113', '1114'];
        $transferBanks = $cashAccountsCol
            ->filter(fn($a) => in_array((string) ($a->code ?? ''), $transferBankCodes, true))
            ->values();

        // guard: payment hanya boleh kalau hutang real sudah ada
        $hasAp = $grnPostedTotal > 0.0001;
        $poGrandTotal = (float) ($order->grand_total ?? 0);
        $dpRemaining = max(0, round($poGrandTotal - $dpTotal, 2));
        $supplierReceivable = max(0, round((float) ($order->paid_amount ?? 0) - $poGrandTotal, 2));
        $canPaySettlement = $canPay && $hasAp && $apOutstanding > 0.01;
        // DP boleh melebihi nilai PO agar selisihnya tercatat sebagai piutang supplier.
        $canPayDp = $canPay && $poGrandTotal > 0.01;
        $canOpenPayment = $canPaySettlement || $canPayDp;

        // apply DP guard
        $canApplyDp = $canPay && $hasAp && $dpAvailable > 0.01 && $apOutstanding > 0.01;
        $maxApplyDp = max(0, round(min($dpAvailable, $apOutstanding), 2));
        $linePurchaseUnits = ($order->lines ?? collect())
            ->map(fn ($line) => $line->effectivePurchaseUnit())
            ->filter()
            ->unique()
            ->values();
        $totalQty = $linePurchaseUnits->count() === 1
            ? (float) ($order->lines?->sum('qty') ?? 0)
            : null;
        $totalQtyUnit = $linePurchaseUnits->count() === 1
            ? $linePurchaseUnits->first()
            : ($linePurchaseUnits->isEmpty() ? null : 'beragam');
        $hasDiscount = (float) ($order->lines?->sum('discount') ?? 0) > 0.0001;

        // for JS/open modal routing
        $voidActionTemplate = route('purchasing.purchase_orders.payments.void', [
            'purchase_order' => $order->id,
            'payment' => '___PAYMENT___',
        ]);
    @endphp

    
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
    @if ($canSeeMoney)
        <span class="po-pill {{ $payBadgeClass }}">{{ $payStatusLabel }}</span>
    @endif
    
    @if ($order->isLocked())
        <span class="po-pill po-status" style="background:rgba(245,158,11,.1);color:#92400e;border:1px solid rgba(245,158,11,.3);" title="{{ $order->lock_reason }} ({{ optional($order->locked_at)->format('d/m/Y H:i') }})"><i class="bi bi-lock-fill"></i> Locked</span>
    @endif
    
    <span class="po-spacer"></span>

    {{-- PRIMARY ACTIONS --}}
    @if ($status === 'draft')
        @if ($user && ($user->isOwner() || $user->isDeveloper()))
            @if ($poHasPrice)
                <form action="{{ route('purchasing.purchase_orders.approve', $order->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve PO ini? Setelah di-approve, PO tidak bisa diedit lagi.');">
                    @csrf
                    <button type="submit" class="po-btn po-primary" title="Approve PO">Approve</button>
                </form>
            @else
                <button type="button" class="po-btn po-primary" style="opacity:.5;cursor:not-allowed;" title="{{ $canSeeMoney ? 'Harga belum diisi, edit PO terlebih dahulu.' : 'Dokumen belum lengkap, hubungi owner.' }}">Approve</button>
            @endif
        @endif
    @endif
    
    @if ($canSeeMoney && $canOpenPayment)
        @php $paymentButtonLabel = $canPaySettlement ? 'Bayar PO' : 'Bayar DP'; @endphp
        <button type="button" class="po-btn po-success" data-bs-toggle="modal" data-bs-target="#modalAddPayment" title="{{ $paymentButtonLabel }}">
            <i class="bi bi-cash-coin d-inline-block d-md-none"></i>
            <span class="d-none d-md-inline">{{ $paymentButtonLabel }}</span>
        </button>
    @endif
    
    {{-- Terima (buat GRN) --}}
    @if ($user && ($user->isOwner() || $isAdmin) && $order->isReceivableForGrn() && $status !== 'cancelled' && ($canCreateGrn ?? true))
         <a href="{{ route('purchasing.purchase_receipts.create_from_order', $order->id) }}" class="po-btn po-info" title="Terima Barang"><i class="bi bi-box-seam d-inline-block d-md-none"></i> <span class="d-none d-md-inline">Terima</span></a>
    @endif

    @if ($canSendWhatsapp && $supplierPhone !== '')
        <a href="{{ route('whatsapp.messages.compose.purchase_order', $order->id) }}"
           class="po-btn {{ $lastWhatsappMessage ? 'po-wa-sent' : 'po-wa' }}"
           title="{{ $lastWhatsappMessage ? 'Sudah dikirim pada ' . optional($lastWhatsappMessage->sent_at)->format('d/m/Y H:i') . '. Klik untuk kirim ulang.' : 'Review dan kirim ke supplier via WhatsApp' }}">
            <i class="bi {{ $lastWhatsappMessage ? 'bi-check-circle-fill' : 'bi-whatsapp' }}"></i>
            <span class="d-none d-md-inline">{{ $lastWhatsappMessage ? 'Sudah dikirim' : 'WA Supplier' }}</span>
        </a>
    @elseif ($canSendWhatsapp)
        <button type="button" class="po-btn" disabled title="Nomor WhatsApp supplier belum diisi">
            <i class="bi bi-whatsapp"></i>
            <span class="d-none d-md-inline">WA Supplier</span>
        </button>
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
            @if ($status === 'draft' && (!$order->isLocked() || ($user && $user->canSeePurchasePrices())))
                <li>
                    <a class="dropdown-item py-2" href="{{ route('purchasing.purchase_orders.edit', $order->id) }}">
                        <i class="bi bi-pencil me-2 text-muted"></i> Edit PO
                    </a>
                </li>
            @endif
            @if ($canSeeMoney && $canPay && $hasAp)
                <li>
                    <button type="button" class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#modalApplyDp" @if (!$canApplyDp) disabled @endif>
                        <i class="bi bi-arrow-left-right me-2 text-muted"></i> Offset DP
                    </button>
                </li>
            @endif
            @if ($status === 'approved' && $user && ($user->isOwner() || in_array($user->role ?? '', ['accounting', 'developer'])))
                @if (\Illuminate\Support\Facades\Route::has('purchasing.supplier_invoices.create'))
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('purchasing.supplier_invoices.create', ['purchase_order_id' => $order->id]) }}">
                            <i class="bi bi-receipt me-2 text-muted"></i> Buat Invoice Supplier
                        </a>
                    </li>
                @endif
            @endif
            @if ($status === 'approved' && $user && ($user->isOwner() || $isAdmin || $user->isDeveloper()) && $grnCount === 0 && !$hasActivePayments)
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('purchasing.purchase_orders.unapprove', $order->id) }}" method="POST"
                          onsubmit="return confirm('Kembalikan PO ini ke Draft untuk koreksi qty? Lock stale akan dilepas jika sudah tidak ada GRN, pembayaran, atau retur.');">
                        @csrf
                        <button type="submit" class="dropdown-item py-2 text-warning-emphasis">
                            <i class="bi bi-pencil-square me-2"></i> Koreksi PO / Kembalikan ke Draft
                        </button>
                    </form>
                </li>
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
            @if ($status === 'approved' && $user && $user->isOwner())
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('purchasing.purchase_orders.close', $order->id) }}" method="POST"
                          onsubmit="return confirm('Tutup Paksa (Short Close) PO ini? Pastikan semua tagihan telah lunas.');">
                        @csrf
                        <button type="submit" class="dropdown-item py-2 text-dark fw-semibold">
                            <i class="bi bi-check2-all me-2"></i> Tutup Paksa PO
                        </button>
                    </form>
                </li>
            @endif
        </ul>
    </div>
</div>

<div class="po-wrap">
    <div class="po-document-date-row" aria-label="Tanggal dokumen PO">
        <i class="bi bi-calendar3" aria-hidden="true"></i>
        <span class="po-document-date-label">Tanggal dokumen</span>
        <strong>{{ id_day($order->date) }}</strong>
    </div>

    @if ($status === 'approved' && ($order->received_status ?? 'not_received') === 'partial' && ($order->payment_status ?? 'unpaid') === 'paid')
        <div class="alert alert-warning d-flex align-items-center mb-3 mt-2" style="font-size: .85rem;">
            <i class="bi bi-info-circle-fill me-2 fs-5"></i>
            <div>
                <strong>Informasi Penerimaan Sebagian:</strong> Terdapat barang yang tidak lengkap/di-reject pada PO ini, namun tagihan untuk barang yang diterima sudah lunas. Jika tidak ada penerimaan lanjutan dari Supplier, Anda dapat menutup transaksi ini dengan memilih <b>Opsi Lainnya > Tutup Paksa PO</b>.
            </div>
        </div>
    @endif
    
    <div class="po-grid mt-2">
        <div class="po-card po-kpi">
            <div class="po-label">Item Batch</div>
            <div class="po-value">
                {{ $order->lines->count() }} <span class="po-muted" style="font-size:.8rem;font-weight:500;">Tipe</span>
                @if ($totalQty !== null)
                    <span class="po-muted mx-1 fw-normal" style="opacity:.4;">•</span>
                    {{ decimal_id($totalQty, 2) }} <span class="po-muted" style="font-size:.8rem;font-weight:500;">{{ $totalQtyUnit }}</span>
                @elseif ($totalQtyUnit)
                    <span class="po-muted mx-1 fw-normal" style="opacity:.4;">•</span>
                    <span class="po-muted" style="font-size:.8rem;font-weight:500;">Satuan {{ $totalQtyUnit }}</span>
                @endif
            </div>
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
            <div class="po-label">{{ $payStatus === 'overpaid' ? 'Piutang Supplier' : 'Sisa Tagihan' }}</div>
            <div class="po-value" style="color:{{ $payStatus === 'overpaid' ? '#6d28d9' : '#b91c1c' }};">{{ $formatPaymentMoney($payStatus === 'overpaid' ? $supplierReceivable : $apOutstanding) }}</div>
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
                                    <th>Satuan Beli</th>
                                    <th>Konversi Stok</th>
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
                                        // Nilai tersimpan di purchase_order_lines.line_total.
                                        // Fallback menjaga PO lama tetap menampilkan subtotal
                                        // jika line_total belum terisi.
                                        $lineSubtotal = (float) ($line->line_total ?? 0);
                                        if (abs($lineSubtotal) < 0.0001) {
                                            $lineSubtotal = max(0, $line->stockQty() * $line->stockUnitPrice() - (float) $line->discount);
                                        }
                                    @endphp
                                    <tr>
                                        <td class="item-cell">
                                            <div class="po-code-cell">{{ $line->item->code ?? '-' }}</div>
                                            <div class="po-name">{{ $line->item->name ?? '-' }}</div>
                                            @if($line->expenseAccount)
                                                <div class="text-muted mt-1" style="font-size: .7rem;"><i class="bi bi-wallet2"></i> {{ $line->expenseAccount->code }} - {{ $line->expenseAccount->name }}</div>
                                            @endif
                                            
                                            {{-- MOBILE EXTRA INFO --}}
                                            <div class="d-md-none mt-1">
                                                @if($canSeeMoney)
                                                    <div style="font-size:.8rem;color:#475569;">{{ angka($line->unit_price) }} / {{ $line->effectivePurchaseUnit() }} × {{ decimal_id($line->qty, 2) }} = <b>{{ angka($lineSubtotal) }}</b></div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="po-r" data-label="Qty">
                                            <div class="po-code-cell">{{ decimal_id($line->qty, 2) }}</div>
                                        </td>
                                        <td data-label="Satuan beli">
                                            <span class="po-unit-value">{{ $line->effectivePurchaseUnit() }}</span>
                                        </td>
                                        <td data-label="Konversi stok">
                                            @if($line->effectiveConversionFactor() != 1)
                                                <span class="po-unit-conversion"><strong>{{ decimal_id($line->effectiveConversionFactor(), 2) }}</strong> {{ $line->effectiveStockUnit() }} / {{ $line->effectivePurchaseUnit() }}</span>
                                            @else
                                                <span class="po-unit-conversion">{{ $line->effectiveStockUnit() }}</span>
                                            @endif
                                        </td>
                                        @if($canSeeMoney)
                                            <td class="po-r po-hide-mobile">
                                                {{ angka($line->unit_price) }}
                                                @if($hasDiscount)
                                                    <br><span class="text-danger" style="font-size:.7rem">Disc: -{{ angka($line->discount) }}</span>
                                                @endif
                                            </td>
                                            <td class="po-r po-hide-mobile">{{ angka($lineSubtotal) }}</td>
                                        @endif
                                        <td class="progress-cell" style="min-width: 140px; vertical-align: top;">
                                            @if ($rcv > 0)
                                                <div class="d-flex justify-content-between mb-1" style="font-size:.78rem;">
                                                    <span style="color:#64748b;">Terima</span>
                                                    <strong style="color:#15803d; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;">{{ decimal_id($rcv, 2) }} {{ $line->effectivePurchaseUnit() }}</strong>
                                                </div>
                                            @endif
                                            @if ($ret > 0)
                                                <div class="d-flex justify-content-between mb-1" style="font-size:.78rem;">
                                                    <span style="color:#64748b;">Retur</span>
                                                    <strong style="color:#b91c1c; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;">{{ decimal_id($ret, 2) }} {{ $line->effectivePurchaseUnit() }}</strong>
                                                </div>
                                            @endif
                                            @if ($qtyOut > 0)
                                                <div class="d-flex justify-content-between" style="font-size:.78rem;">
                                                    <span style="color:#64748b;">Sisa</span>
                                                    <strong style="color:#d97706; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;">{{ decimal_id($qtyOut, 2) }} {{ $line->effectivePurchaseUnit() }}</strong>
                                                </div>
                                            @endif
                                            @if ($rcv == 0 && $ret == 0 && $qtyOut == 0)
                                                <div class="text-end text-muted">-</div>
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
                                        <form action="{{ route('purchasing.purchase_orders.payments.void', [$order->id, $p->id]) }}" method="POST" onsubmit="return confirm('Hapus/Void pembayaran ini?');">
                                            @csrf
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


    @if ($canSeeMoney)
    {{-- =========================================================
    MODAL: ADD PAYMENT / DP (punya kamu, tetap, cuma sedikit touch)
========================================================= --}}
    <div class="modal fade" id="modalAddPayment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content gf-modal">
                <div class="modal-header">
                    <div>
                        <h6 class="modal-title fw-semibold mb-0">{{ $canPaySettlement ? 'Bayar PO' : 'Bayar DP' }}</h6>
                        <div class="d-flex gap-1 flex-wrap mt-2">
                            @if ($hasAp)
                                <span class="modal-kpi">GRN <strong class="mono">{{ rupiah($grnPostedTotal) }}</strong></span>
                                @if ($returnPostedTotal > 0.0001)
                                    <span class="modal-kpi">Retur <strong class="mono">{{ rupiah($returnPostedTotal) }}</strong></span>
                                @endif
                                <span class="modal-kpi">Sisa <strong class="mono">{{ $formatPaymentMoney($apOutstanding) }}</strong></span>
                            @else
                                <span class="modal-kpi">Total PO <strong class="mono">{{ rupiah($poGrandTotal) }}</strong></span>
                                <span class="modal-kpi">DP tercatat <strong class="mono">{{ rupiah($dpTotal) }}</strong></span>
                                <span class="modal-kpi">Sisa DP <strong class="mono">{{ rupiah($dpRemaining) }}</strong></span>
                            @endif
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form method="POST" action="{{ route('purchasing.purchase_orders.payments.store', $order->id) }}"
                    id="paymentForm">
                    @csrf

                    <div class="modal-body">
                        <div class="row g-2">
                            @if (!$hasAp)
                                <div class="col-12">
                                    <div class="alert alert-info py-2 px-3 mb-1 small">
                                        PO masih draft dan belum ada GRN. Pembayaran ini akan dicatat sebagai DP; pelunasan tersedia setelah GRN POSTED.
                                    </div>
                                </div>
                            @endif

                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold">Tanggal</label>
                                <input type="text" name="date" class="form-control form-control-sm gf-date-input"
                                    value="{{ old('date', now()->toDateString()) }}" data-gf-date
                                    autocomplete="off" required>
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold">Jenis</label>
                                @php $defaultPaymentType = $hasAp ? old('type', 'payment') : 'dp'; @endphp
                                <select name="type" class="form-select form-select-sm" required id="typeSelectModal">
                                    @if ($hasAp)
                                        <option value="payment" @selected($defaultPaymentType === 'payment')>Pelunasan</option>
                                    @endif
                                    <option value="dp" @selected($defaultPaymentType === 'dp')>DP (Uang Muka)</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Metode <span class="text-danger">*</span></label>
                                <select name="payment_method_id" class="form-select form-select-sm" required id="pmSelectModal">
                                    @foreach ($paymentMethods ?? [] as $pmOpt)
                                        <option value="{{ $pmOpt->id }}"
                                            data-mode="{{ strtolower($pmOpt->mode ?? '') }}"
                                            data-code="{{ strtoupper($pmOpt->code ?? '') }}"
                                            @selected(old('payment_method_id', $order->payment_method_id) == $pmOpt->id)>
                                            {{ $pmOpt->name }}
                                            @if (!empty($pmOpt->mode))— {{ strtoupper($pmOpt->mode) }}@endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="text-danger small mt-1 d-none" id="creditPayErrModal">
                                    TEMPO/CREDIT hanya untuk DP, bukan pelunasan.
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Kas/Bank <span class="text-danger">*</span></label>

                                <div id="cashWrap" class="d-none">
                                    <select name="cash_account_id" class="form-select form-select-sm" id="cashSelectCash">
                                        <option value="">— Pilih Kas —</option>
                                        @if ($cash1101)
                                            <option value="{{ $cash1101->id }}" data-code="1101"
                                                @selected(old('cash_account_id') == $cash1101->id)>
                                                1101 — {{ $cash1101->name }}
                                            </option>
                                        @endif
                                    </select>
                                    <div class="text-danger small mt-1 d-none" id="cashErrModal">Wajib pilih akun 1101 (Kas).</div>
                                </div>

                                <div id="bankWrap" class="d-none">
                                    <select name="cash_account_id" class="form-select form-select-sm" id="cashSelectBank">
                                        <option value="">— Pilih Bank/E-Wallet —</option>
                                        @foreach ($transferBanks as $acc)
                                            <option value="{{ $acc->id }}" data-code="{{ $acc->code }}"
                                                @selected(old('cash_account_id') == $acc->id)>
                                                {{ $acc->code }} — {{ $acc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="text-danger small mt-1 d-none" id="bankErrModal">Wajib pilih akun 1111–1114.</div>
                                </div>

                                <div id="creditWrap" class="d-none">
                                    <input type="text" class="form-control form-control-sm"
                                        value="Tidak perlu kas/bank (TEMPO/CREDIT)" disabled>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Nominal <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" name="amount" class="form-control mono" id="amountInput"
                                        placeholder="0" value="{{ old('amount') }}" required>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnFillRemaining">Sisa</button>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold">No. Ref</label>
                                <input type="text" name="ref_no" class="form-control form-control-sm mono" placeholder="Opsional"
                                    value="{{ old('ref_no') }}">
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold">Catatan</label>
                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Opsional"
                                    value="{{ old('notes') }}">
                            </div>

                            @php $unpaidInvList = $unpaidInvoices ?? collect(); @endphp
                            @if ($unpaidInvList->isNotEmpty())
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Faktur Supplier <span class="text-muted fw-normal">(opsional)</span></label>
                                <select name="supplier_invoice_id" class="form-select form-select-sm">
                                    <option value="">— Tidak dikaitkan ke faktur —</option>
                                    @foreach ($unpaidInvList as $inv)
                                        <option value="{{ $inv->id }}"
                                            {{ old('supplier_invoice_id') == $inv->id ? 'selected' : '' }}>
                                            {{ $inv->invoice_no }}
                                            @if ($inv->supplier_invoice_ref) [{{ $inv->supplier_invoice_ref }}] @endif
                                            — {{ rupiah($inv->total_amount - $inv->paid_amount) }} outstanding
                                            ({{ strtoupper($inv->status) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger mt-3 mb-0 py-2 small">
                                <div class="fw-semibold mb-1">Gagal:</div>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-sm btn-primary" id="btnSavePayment">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- =========================================================
    MODAL: APPLY DP (OFFSET)
========================================================= --}}
    <div class="modal fade" id="modalApplyDp" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('purchasing.purchase_orders.payments.apply_dp', $order->id) }}"
                class="modal-content gf-modal" id="applyDpForm">
                @csrf

                <div class="modal-header">
                    <div>
                        <h6 class="modal-title fw-semibold mb-0">Offset DP</h6>
                        <div class="d-flex gap-1 flex-wrap mt-2">
                            <span class="modal-kpi">DP <strong class="mono">{{ rupiah($dpAvailable) }}</strong></span>
                            @if ($returnPostedTotal > 0.0001)
                                <span class="modal-kpi">Retur <strong class="mono">{{ rupiah($returnPostedTotal) }}</strong></span>
                            @endif
                            <span class="modal-kpi">Sisa <strong class="mono">{{ $formatPaymentMoney($apOutstanding) }}</strong></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tanggal</label>
                            <input type="text" name="date" class="form-control form-control-sm gf-date-input"
                                value="{{ old('date', now()->toDateString()) }}" data-gf-date
                                autocomplete="off" required>
                            @error('date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6">
                            <label class="form-label small fw-semibold">Nominal Offset</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="amount" class="form-control mono" id="applyDpAmount"
                                    placeholder="0" value="{{ old('amount') }}" required>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    id="btnFillMaxApplyDp">Max</button>
                            </div>
                            @error('amount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan <span class="text-muted fw-normal">(opsional)</span></label>
                            <input type="text" name="notes" class="form-control form-control-sm" maxlength="255"
                                value="{{ old('notes') }}">
                            @error('notes')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary"
                        @if (!$canApplyDp) disabled @endif>Proses Offset</button>
                </div>
            </form>
        </div>
    </div>

    {{-- =========================================================
    MODAL: VOID PAYMENT + REASON
========================================================= --}}
    <div class="modal fade" id="modalVoidPayment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="" class="modal-content gf-modal" id="voidPaymentForm">
                @csrf

                <div class="modal-header">
                    <div>
                        <h6 class="modal-title fw-semibold mb-0">Void Pembayaran</h6>
                        <div class="text-muted small mono mt-1" id="voidInfo">—</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <label class="form-label small fw-semibold">Alasan Void</label>
                    <input type="text" name="reason" class="form-control form-control-sm" maxlength="255"
                        placeholder="contoh: salah nominal / duplikat / salah metode" value="{{ old('reason') }}"
                        required>
                    @error('reason')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger">Void</button>
                </div>
            </form>
        </div>
    </div>



    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // =========================================================
                // ADD PAYMENT (existing logic kamu)
                // =========================================================
                const pm = document.getElementById('pmSelectModal');
                const btnSave = document.getElementById('btnSavePayment');
                const cashWrap = document.getElementById('cashWrap');
                const bankWrap = document.getElementById('bankWrap');
                const creditWrap = document.getElementById('creditWrap');

                const cashSelectCash = document.getElementById('cashSelectCash');
                const cashSelectBank = document.getElementById('cashSelectBank');

                const bankErr = document.getElementById('bankErrModal');
                const cashErr = document.getElementById('cashErrModal');
                const creditPayErr = document.getElementById('creditPayErrModal');

                const amountInput = document.getElementById('amountInput');
                const btnFill = document.getElementById('btnFillRemaining');

                const remaining = {{ (float) $apOutstanding }};
                const dpRemaining = {{ (float) $dpRemaining }};
                const typeSelect = document.getElementById('typeSelectModal');

                function fmtMoneyInput(n) {
                    const value = Number(n || 0);
                    const hasFraction = Math.abs(value - Math.round(value)) > 0.0001;

                    return new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: hasFraction ? 2 : 0,
                        maximumFractionDigits: 2
                    }).format(value);
                }

                function detectMode() {
                    const opt = pm?.selectedOptions?.[0];
                    const mode = (opt?.dataset?.mode || '').toLowerCase();
                    const code = (opt?.dataset?.code || '').toUpperCase();

                    if (mode) return mode;
                    if (code.includes('CASH')) return 'cash';
                    if (code.includes('TRF') || code.includes('TRANSFER') || code.includes('BANK')) return 'transfer';
                    if (code.includes('TEMPO') || code.includes('CREDIT')) return 'credit';
                    return '';
                }

                function showOnly(which) {
                    cashWrap?.classList.toggle('d-none', which !== 'cash');
                    bankWrap?.classList.toggle('d-none', which !== 'bank');
                    creditWrap?.classList.toggle('d-none', which !== 'credit');
                }

                function setBankError(show) {
                    if (bankErr) bankErr.classList.toggle('d-none', !show);
                }

                function setCashError(show) {
                    if (cashErr) cashErr.classList.toggle('d-none', !show);
                }

                function setCreditPayError(show) {
                    if (creditPayErr) creditPayErr.classList.toggle('d-none', !show);
                }

                function autoPickCash() {
                    if (!cashSelectCash) return;
                    if (!cashSelectCash.value && cashSelectCash.options.length > 1) cashSelectCash.selectedIndex = 1;
                }

                function validateCashBank() {
                    const mode = detectMode();
                    if (mode === 'credit') {
                        setBankError(false);
                        setCashError(false);
                        return true;
                    }
                    if (mode === 'transfer') {
                        const ok = !!(cashSelectBank && cashSelectBank.value);
                        setBankError(!ok);
                        setCashError(false);
                        return ok;
                    }
                    const ok = !!(cashSelectCash && cashSelectCash.value);
                    setCashError(!ok);
                    setBankError(false);
                    return ok;
                }

                function validateTypeVsMode() {
                    const mode = detectMode();
                    const type = (typeSelect?.value || 'payment');
                    if (type === 'payment' && mode === 'credit') {
                        setCreditPayError(true);
                        if (btnSave) btnSave.disabled = true;
                        return false;
                    }
                    setCreditPayError(false);
                    return true;
                }

                function applyAllValidations() {
                    const okCashBank = validateCashBank();
                    const okTypeMode = validateTypeVsMode();
                    if (btnSave) btnSave.disabled = !(okCashBank && okTypeMode);
                }

                function syncModeUI() {
                    const mode = detectMode();

                    if (mode === 'credit') {
                        showOnly('credit');
                        if (cashSelectCash) cashSelectCash.value = '';
                        if (cashSelectBank) cashSelectBank.value = '';
                        setBankError(false);
                        setCashError(false);
                        applyAllValidations();
                        return;
                    }

                    if (mode === 'transfer') {
                        showOnly('bank');
                        applyAllValidations();
                        return;
                    }

                    showOnly('cash');
                    autoPickCash();
                    applyAllValidations();
                }

                function syncTypeRules() {
  // tidak ada rule khusus lagi
  applyAllValidations();
}


                btnFill?.addEventListener('click', function() {
                    if (!amountInput) return;
                    const maxAmount = (typeSelect?.value || 'payment') === 'dp' ? dpRemaining : remaining;
                    amountInput.value = fmtMoneyInput(maxAmount);
                    amountInput.focus();
                    amountInput.select?.();
                });

                amountInput?.addEventListener('focusout', function() {
                    const raw = (amountInput.value || '').toString().trim();
                    if (raw === '') return;
                    const n = Number(raw.replace(/\./g, '').replace(/,/g, '.'));
                    if (!isNaN(n)) amountInput.value = fmtMoneyInput(n);
                });

                typeSelect?.addEventListener('change', syncTypeRules);
                pm?.addEventListener('change', syncModeUI);
                cashSelectBank?.addEventListener('change', applyAllValidations);
                cashSelectCash?.addEventListener('change', applyAllValidations);

                // init
                syncTypeRules();
                syncModeUI();

                // =========================================================
                // APPLY DP modal helpers
                // =========================================================
                const applyDpAmount = document.getElementById('applyDpAmount');
                const btnFillMaxApplyDp = document.getElementById('btnFillMaxApplyDp');
                const maxApplyDp = {{ (float) $maxApplyDp }};

                btnFillMaxApplyDp?.addEventListener('click', function() {
                    if (!applyDpAmount) return;
                    applyDpAmount.value = fmtMoneyInput(maxApplyDp);
                    applyDpAmount.focus();
                    applyDpAmount.select?.();
                });

                applyDpAmount?.addEventListener('focusout', function() {
                    const raw = (applyDpAmount.value || '').toString().trim();
                    if (raw === '') return;
                    const n = Number(raw.replace(/\./g, '').replace(/,/g, '.'));
                    if (!isNaN(n)) applyDpAmount.value = fmtMoneyInput(n);
                });

                // =========================================================
                // VOID modal wiring
                // =========================================================
                const modalVoidEl = document.getElementById('modalVoidPayment');
                const voidForm = document.getElementById('voidPaymentForm');
                const voidInfo = document.getElementById('voidInfo');
                const actionTpl = @json($voidActionTemplate);

                modalVoidEl?.addEventListener('show.bs.modal', function(event) {
                    const btn = event.relatedTarget;
                    if (!btn) return;

                    const pid = btn.getAttribute('data-payment-id');
                    const ptype = btn.getAttribute('data-payment-type') || '-';
                    const pamt = btn.getAttribute('data-payment-amount') || '-';
                    const pdate = btn.getAttribute('data-payment-date') || '-';

                    if (voidForm) voidForm.action = actionTpl.replace('___PAYMENT___', pid);
                    if (voidInfo) voidInfo.textContent = `${ptype} • ${pamt} • ${pdate}`;

                    const reasonInput = voidForm?.querySelector('input[name="reason"]');
                    if (reasonInput) reasonInput.value = '';
                });

                // =========================================================
                // Auto open modal if validation errors (best-effort)
                // =========================================================
                const hasErrors = {{ $errors->any() ? 'true' : 'false' }};

                if (hasErrors && window.bootstrap) {
                    // Heuristic:
                    // - if reason error exists -> open void modal and expand history
                    // - else if amount/date/notes and request came from apply dp -> open apply dp modal
                    // - else open add payment modal
                    const reasonErr = {!! json_encode($errors->has('reason')) !!};
                    const amountErr = {!! json_encode($errors->has('amount')) !!};
                    const dateErr = {!! json_encode($errors->has('date')) !!};

                    const hist = document.getElementById('paymentHistoryCollapse');
                    if (hist) new bootstrap.Collapse(hist, {
                        toggle: true
                    });

                    if (reasonErr) {
                        const el = document.getElementById('modalVoidPayment');
                        if (el) new bootstrap.Modal(el).show();
                        return;
                    }

                    // apply dp biasanya error amount/date juga, tapi add payment juga.
                    // trigger jika user terakhir tekan tombol offset dp (kita pakai session flash flag kalau ada)
                    const fromApplyDp = {!! json_encode((bool) session('from_apply_dp')) !!};

                    if (fromApplyDp) {
                        const el = document.getElementById('modalApplyDp');
                        if (el) new bootstrap.Modal(el).show();
                        return;
                    }

                    // default: add payment
                    const addModalEl = document.getElementById('modalAddPayment');
                    if (addModalEl) new bootstrap.Modal(addModalEl).show();
                }
            });
        </script>


    @endpush
    @endif
@endsection
