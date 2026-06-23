{{-- resources/views/purchasing/purchase_orders/show.blade.php --}}
@extends('layouts.app')

@section('title', 'PO ' . $order->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
        }

        th.sticky {
            position: sticky;
            top: 0;
            background: var(--card);
            z-index: 1;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .tag {
            border-radius: 999px;
            padding: .15rem .65rem;
            font-size: .7rem;
            border: 1px solid var(--line);
            background: rgba(148, 163, 184, .12);
            white-space: nowrap;
        }

        .tag-status-draft {
            background: rgba(148, 163, 184, .12);
            color: #64748b;
            border-color: rgba(148, 163, 184, .6);
        }

        .tag-status-approved {
            background: rgba(22, 163, 74, .12);
            color: #15803d;
            border-color: rgba(22, 163, 74, .6);
        }

        .tag-status-cancelled {
            background: rgba(220, 38, 38, .08);
            color: #b91c1c;
            border-color: rgba(220, 38, 38, .6);
        }

        .tag-grn {
            border-radius: 999px;
            padding: .15rem .6rem;
            font-size: .7rem;
            border: 1px solid rgba(59, 130, 246, .5);
            background: rgba(59, 130, 246, .08);
            color: #1d4ed8;
            white-space: nowrap;
        }

        .badge-pill {
            border-radius: 999px;
            font-size: .7rem;
            padding: .1rem .5rem;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .badge-posted {
            background: rgba(22, 163, 74, .12);
            color: #15803d;
            border-color: rgba(22, 163, 74, .5);
        }

        .badge-draft {
            background: rgba(148, 163, 184, .12);
            color: #64748b;
            border-color: rgba(148, 163, 184, .5);
        }

        /* payment badges */
        .pay-badge {
            border-radius: 999px;
            padding: .14rem .6rem;
            font-size: .72rem;
            border: 1px solid rgba(148, 163, 184, .45);
            background: rgba(148, 163, 184, .12);
            color: #64748b;
            white-space: nowrap;
        }

        .pay-paid {
            border-color: rgba(22, 163, 74, .55);
            background: rgba(22, 163, 74, .12);
            color: #15803d;
        }

        .pay-partial {
            border-color: rgba(234, 179, 8, .55);
            background: rgba(234, 179, 8, .12);
            color: #a16207;
        }

        .pay-unpaid {
            border-color: rgba(148, 163, 184, .45);
            background: rgba(148, 163, 184, .12);
            color: #64748b;
        }

        /* method badges */
        .pm-badge {
            border-radius: 999px;
            padding: .12rem .55rem;
            font-size: .7rem;
            border: 1px solid rgba(148, 163, 184, .5);
            background: rgba(148, 163, 184, .10);
            color: #64748b;
            white-space: nowrap;
        }

        .pm-cash {
            border-color: rgba(22, 163, 74, .5);
            background: rgba(22, 163, 74, .10);
            color: #15803d;
        }

        .pm-credit {
            border-color: rgba(59, 130, 246, .55);
            background: rgba(59, 130, 246, .08);
            color: #1d4ed8;
        }

        .pm-transfer {
            border-color: rgba(234, 179, 8, .55);
            background: rgba(234, 179, 8, .10);
            color: #a16207;
        }

        /* type chips */
        .type-chip {
            border-radius: 999px;
            padding: .12rem .55rem;
            font-size: .72rem;
            border: 1px solid rgba(148, 163, 184, .45);
            background: rgba(148, 163, 184, .10);
            color: #64748b;
            white-space: nowrap;
            text-transform: uppercase;
        }

        .type-dp {
            border-color: rgba(59, 130, 246, .55);
            background: rgba(59, 130, 246, .08);
            color: #1d4ed8;
        }

        .type-payment {
            border-color: rgba(22, 163, 74, .55);
            background: rgba(22, 163, 74, .10);
            color: #15803d;
        }

        .type-dp-apply {
            border-color: rgba(234, 179, 8, .55);
            background: rgba(234, 179, 8, .10);
            color: #a16207;
        }

        .btn-pill {
            border-radius: 999px;
            padding-inline: .95rem;
        }

        @media (max-width: 768px) {
            .page-wrap {
                padding-inline: .75rem;
            }

            .po-item-name {
                font-size: .9rem;
                font-weight: 600;
            }

            .po-item-code {
                font-size: .78rem;
            }

            .card .card-body {
                padding: .75rem .85rem;
            }

            .card-header {
                padding: .6rem .85rem;
            }

            .po-mobile-card {
                border-top: 1px solid var(--line);
                padding-top: .5rem;
                margin-top: .5rem;
            }

            .po-mobile-card:first-of-type {
                border-top: none;
                padding-top: 0;
                margin-top: 0;
            }

            .po-actions .btn-action {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $isAdmin = strtolower((string)($user->role ?? '')) === 'admin';
        $canSeeMoney = $user?->isOwner() ?? false;   // hanya owner
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

        // Payment method "preferensi" PO
        $pm = $order->paymentMethod ?? null;

        // Hutang real berbasis GRN posted (dari controller show)
        $grnPostedTotal = (float) ($grnPostedTotal ?? 0);
        $paidPaymentTotal = (float) ($paidPaymentTotal ?? 0); // type=payment
        $dpTotal = (float) ($dpTotal ?? 0); // type=dp

        // DP APPLY total (buat UI)
        $dpAppliedTotal =
            (float) ($dpAppliedTotal ?? ($order->activePayments()?->where('type', 'dp_apply')->sum('amount') ?? 0));
        $dpAvailable = max(0, round($dpTotal - $dpAppliedTotal, 2));

        // outstanding hutang (should include dp_apply)
        $apOutstanding =
            (float) ($apOutstanding ?? max(0, round($grnPostedTotal - $paidPaymentTotal - $dpAppliedTotal, 2)));

        // status bayar
        $payStatus = (string) ($order->payment_status ?? 'unpaid');
        $payBadgeClass = match ($payStatus) {
            'paid' => 'pay-badge pay-paid',
            'partial' => 'pay-badge pay-partial',
            default => 'pay-badge pay-unpaid',
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

        // apply DP guard
        $canApplyDp = $canPay && $hasAp && $dpAvailable > 0.01 && $apOutstanding > 0.01;
        $maxApplyDp = max(0, round(min($dpAvailable, $apOutstanding), 2));

        // for JS/open modal routing
        $voidActionTemplate = route('purchasing.purchase_orders.payments.void', [
            'purchase_order' => $order->id,
            'payment' => '___PAYMENT___',
        ]);
    @endphp

    <div class="page-wrap py-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3 flex-column flex-md-row gap-2">
            <div class="w-100 w-md-auto">
                <h2 class="mb-0">Purchase Order</h2>
                <div class="text-muted mono">Kode: {{ $order->code }}</div>
            </div>

            <div class="d-flex flex-column flex-md-row flex-wrap gap-2 justify-content-end w-100 po-actions">
                <a href="{{ route('purchasing.purchase_orders.index') }}" class="btn btn-outline-secondary btn-sm btn-action">
                    &larr; Kembali
                </a>

                @if ($status === 'draft')
                    <a href="{{ route('purchasing.purchase_orders.edit', $order->id) }}"
                        class="btn btn-outline-primary btn-sm btn-action">
                        Edit PO
                    </a>
                @endif

             @if ($canSeeMoney && $canPay)
    @if ($hasAp)
        <button type="button" class="btn btn-primary btn-sm btn-action" data-bs-toggle="modal"
            data-bs-target="#modalAddPayment">
            + Pembayaran
        </button>

        <button type="button" class="btn btn-outline-primary btn-sm btn-action" data-bs-toggle="modal"
            data-bs-target="#modalApplyDp" @if (!$canApplyDp) disabled @endif
            title="{{ $canApplyDp ? '' : 'Offset DP tidak tersedia / hutang sudah lunas / PO cancelled' }}">
            Offset DP
        </button>
    @else
        <span class="tag mono">
            Pembayaran aktif setelah GRN <b>POSTED</b>
        </span>
    @endif

    @if ($hasPayments)
        <button type="button" class="btn btn-outline-secondary btn-sm btn-action" data-bs-toggle="collapse"
            data-bs-target="#paymentHistoryCollapse" aria-expanded="false" aria-controls="paymentHistoryCollapse">
            Riwayat
        </button>
    @endif
@endif


                @if ($user && (in_array($user->role, ['owner','admin']) || $user->isDeveloper()) && $status === 'draft')
                    @if ($poHasPrice)
                        <form action="{{ route('purchasing.purchase_orders.approve', $order->id) }}" method="POST"
                            onsubmit="return confirm('Approve PO ini? Setelah di-approve, PO tidak bisa diedit lagi.');">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm btn-action">
                                Approve PO
                            </button>
                        </form>
                    @else
                        <button type="button" class="btn btn-success btn-sm btn-action disabled"
                                style="opacity:.55;cursor:not-allowed;"
                                title="{{ $canSeeMoney ? 'Harga belum diisi, edit PO terlebih dahulu.' : 'Dokumen belum lengkap, hubungi owner.' }}">
                            Approve PO
                        </button>
                    @endif
                @endif

                @if ($user && $user->isOwner() && in_array($status, ['draft', 'approved'], true) && $grnCount === 0)
                    <form action="{{ route('purchasing.purchase_orders.cancel', $order->id) }}" method="POST"
                        onsubmit="return confirm('Batalkan PO ini? Tindakan ini tidak bisa dibatalkan.');">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm btn-action">
                            Cancel PO
                        </button>
                    </form>
                @endif

                {{-- Buat Faktur Supplier — owner + accounting, hanya jika PO approved --}}
                @if ($status === 'approved' && $user && ($user->isOwner() || in_array($user->role ?? '', ['accounting', 'developer'])))
                    @php
                        $hasSupplierInvoiceRoute = \Illuminate\Support\Facades\Route::has('purchasing.supplier_invoices.create');
                    @endphp
                    @if ($hasSupplierInvoiceRoute)
                        <a href="{{ route('purchasing.supplier_invoices.create', ['purchase_order_id' => $order->id]) }}"
                           class="btn btn-sm btn-outline-success btn-action">
                            🧾 Faktur Supplier
                        </a>
                    @endif
                @endif

                {{-- Close PO — owner only, syarat lengkap --}}
                @if ($user && $user->isOwner() && !($order->isClosed()) && ($canClose ?? false))
                    @php $hasCloseRoute = \Illuminate\Support\Facades\Route::has('purchasing.purchase_orders.close'); @endphp
                    @if ($hasCloseRoute)
                        <form action="{{ route('purchasing.purchase_orders.close', $order->id) }}" method="POST"
                              onsubmit="return confirm('Close PO ini? Pastikan semua sudah lunas dan diterima.');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-dark btn-action">✔ Close PO</button>
                        </form>
                    @endif
                @endif
            </div>
        </div>

        {{-- FLASH --}}
        @if (session('success'))
            <div class="alert alert-success py-2 small">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
        @endif

        {{-- ============================================================
        RINGKASAN PO — Status + Nominal (Tahap 4)
        ============================================================ --}}
        @php
            $isClosed    = $order->isClosed();
            $rcvStatus   = $order->received_status ?? 'not_received';
            $closeBlks   = $closeBlockers ?? [];
            $canClosePO  = ($canClose ?? false) && !$isClosed;
            $invTotal    = (float) ($invoiceTotalAmount ?? 0);
            $invPaid     = (float) ($invoiceTotalPaid ?? 0);
            $invOutstand = (float) ($invoiceOutstanding ?? 0);
            $poInvoiceList = $poInvoices ?? collect();
        @endphp
        <div class="card mb-3">
            <div class="card-body py-3 px-3">
                <div class="row g-2 align-items-center">
                    {{-- Status kolom --}}
                    <div class="col-6 col-md-3">
                        <div class="text-muted small mb-1">Status PO</div>
                        <span class="{{ $statusClass }} mono">
                            @if ($isClosed) 🔒 CLOSED
                            @else {{ strtoupper($status) }}
                            @endif
                        </span>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small mb-1">Terima Barang</div>
                        <span>{{ received_status_label($rcvStatus) }}</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small mb-1">Status Bayar</div>
                        <span class="{{ $payBadgeClass }}">
                            {{ match($payStatus) { 'paid' => '✅ Lunas', 'partial' => '🟡 Sebagian', default => '❌ Belum Bayar' } }}
                        </span>
                    </div>
                    @if ($canSeeMoney)
                    <div class="col-6 col-md-3">
                        <div class="text-muted small mb-1">Outstanding Hutang</div>
                        <span class="mono fw-semibold {{ $apOutstanding > 0 ? 'text-danger' : 'text-success' }}">
                            {{ rupiah($apOutstanding) }}
                        </span>
                    </div>
                    @endif

                    @if ($canSeeMoney)
                    {{-- Baris 2: nominal --}}
                    <div class="col-12"><hr class="my-1"></div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small mb-1">Total PO</div>
                        <div class="mono">{{ rupiah($order->grand_total) }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small mb-1">Total Invoice ({{ $poInvoiceList->count() }})</div>
                        <div class="mono">{{ rupiah($invTotal) }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small mb-1">Invoice Dibayar</div>
                        <div class="mono text-success">{{ rupiah($invPaid) }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small mb-1">Invoice Outstanding</div>
                        <div class="mono {{ $invOutstand > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                            {{ rupiah($invOutstand) }}
                        </div>
                    </div>
                    @endif

                    {{-- Baris 3: status close --}}
                    <div class="col-12"><hr class="my-1"></div>
                    <div class="col-12">
                        @if ($isClosed)
                            <span class="text-success small fw-semibold">🔒 PO sudah di-Close
                                @if ($order->closed_at) pada {{ id_date($order->closed_at) }} @endif
                            </span>
                        @elseif ($canClosePO)
                            <span class="text-success small">✅ PO siap di-Close — semua syarat terpenuhi.</span>
                        @else
                            <span class="text-muted small">
                                ⛔ Belum bisa Close PO:
                                @foreach ($closeBlks as $blk)
                                    <span class="badge bg-warning text-dark ms-1">{{ $blk }}</span>
                                @endforeach
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- WARNING: PO draft, harga belum diisi --}}
        @if ($status === 'draft' && !$poHasPrice)
            @if ($canSeeMoney)
                {{-- Owner: tahu tentang harga --}}
                <div class="alert mb-3 d-flex align-items-start gap-2"
                     style="background:var(--bs-warning-bg-subtle,#fff3cd);border:1px solid var(--bs-warning-border-subtle,#ffc107);border-radius:10px;padding:.75rem 1rem;">
                    <span style="font-size:1.15rem;line-height:1.3;">⚠️</span>
                    <div style="font-size:.875rem;">
                        <strong>Harga belum diisi.</strong>
                        Grand total PO ini masih Rp 0. Silakan edit PO dan isi harga sebelum melakukan approval.
                    </div>
                </div>
            @elseif ($isAdmin)
                {{-- Admin: tidak menyebut harga --}}
                <div class="alert mb-3 d-flex align-items-start gap-2"
                     style="background:var(--bs-warning-bg-subtle,#fff3cd);border:1px solid var(--bs-warning-border-subtle,#ffc107);border-radius:10px;padding:.75rem 1rem;">
                    <span style="font-size:1.15rem;line-height:1.3;">⚠️</span>
                    <div style="font-size:.875rem;">
                        <strong>PO ini belum bisa di-approve.</strong>
                        Hubungi owner untuk menyelesaikan dokumen ini.
                    </div>
                </div>
            @endif
        @endif

        {{-- INFO CARD --}}
        <div class="card mb-3">
            <div class="card-body row g-3">
                <div class="col-md-3 col-6">
                    <div class="text-muted small">Tanggal</div>
                    <div class="fw-semibold mono">{{ id_date($order->date) }}</div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="text-muted small">Supplier</div>
                    <div class="fw-semibold">
                        {{ optional($order->supplier)->name ?? '—' }}
                        @if ($order->supplier)
                            <div class="text-muted small mono">{{ $order->supplier->code }}</div>
                        @endif
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="text-muted small">Jenis PO</div>
                    <div class="fw-semibold">{{ po_order_type_label($order->order_type, true) }}</div>
                </div>

                @if ($canSeeMoney)
                    <div class="col-md-3 col-6">
                        <div class="text-muted small">Tipe Pembayaran</div>
                        @if ($pm)
                            @php
                                $pmModeLabel = ['cash' => 'Tunai', 'transfer' => 'Transfer (TF)', 'credit' => 'Hutang / Tempo'];
                                $pmMode = strtolower((string) ($pm->mode ?? ''));
                            @endphp
                            <div class="fw-semibold">{{ $pmModeLabel[$pmMode] ?? $pm->name }}</div>
                            <div class="text-muted small mono">{{ $pm->name }}</div>
                        @else
                            <div class="fw-semibold text-muted">—</div>
                        @endif
                    </div>
                @endif

                <div class="col-md-3 col-6">
                    <div class="text-muted small">Status PO</div>
                    <span class="{{ $statusClass }} mono">{{ strtoupper($status) }}</span>
                    @if ($grnCount > 0 && $user && ($user->isOwner() || $isAdmin))
                        <span class="tag-grn mono ms-1">GRN x{{ $grnCount }}</span>
                    @endif
                </div>

                <div class="col-md-3 col-6">
                    <div class="text-muted small">Status Terima Barang</div>
                    @php
                        $rcvStatus = $order->received_status ?? 'not_received';
                        $rcvBadge = match($rcvStatus) {
                            'fully_received' => 'badge-pill badge-posted',
                            'partial'        => 'badge-pill',
                            default          => 'badge-pill badge-draft',
                        };
                    @endphp
                    <span class="{{ $rcvBadge }}" style="font-size:.75rem;">
                        {{ received_status_label($rcvStatus) }}
                    </span>
                </div>

                @if ($canSeeMoney)
                <div class="col-md-3 col-6">
                    <div class="text-muted small">GRN Posted</div>
                    <div class="fw-semibold mono">{{ rupiah($grnPostedTotal) }}</div>
                </div>
                @endif

                @if ($canSeeMoney)
                    <div class="col-md-3 col-6">
                        <div class="text-muted small">DP</div>
                        <div class="fw-semibold mono">{{ rupiah($dpTotal) }}</div>
                        <div class="text-muted small mono">Available: {{ rupiah($dpAvailable) }}</div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="text-muted small">Paid (Pelunasan)</div>
                        <div class="fw-semibold mono">{{ rupiah($paidPaymentTotal) }}</div>
                        <div class="text-muted small mono">+ Offset: {{ rupiah($dpAppliedTotal) }}</div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="text-muted small">Sisa Hutang</div>
                        <div class="fw-bold mono">{{ rupiah($apOutstanding) }}</div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="text-muted small">Status bayar</div>
                        <span class="{{ $payBadgeClass }} mono">{{ strtoupper($payStatus) }}</span>
                        @if (!empty($order->due_date))
                            <div class="text-muted small mono mt-1">JT: {{ id_date($order->due_date) }}</div>
                        @endif
                    </div>
                @endif

                <div class="col-md-3 col-6">
                    <div class="text-muted small">Dibuat oleh</div>
                    <div class="fw-semibold">{{ optional($order->createdBy)->name ?? '—' }}</div>
                </div>

                <div class="col-12">
                    <div class="text-muted small">Catatan</div>
                    <div>{{ $order->notes ?: '—' }}</div>
                </div>
            </div>
        </div>

        {{-- RIWAYAT PEMBAYARAN (COLLAPSE) --}}
        @if ($canSeeMoney && $hasPayments)
            <div class="collapse mb-4" id="paymentHistoryCollapse">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="fw-semibold">Riwayat Pembayaran</div>
                        <div class="text-muted small mono">
                            Sisa {{ rupiah($apOutstanding) }} • DP Avail {{ rupiah($dpAvailable) }}
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 mono align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 18%;">Tanggal</th>
                                        <th style="width: 14%;">Tipe</th>
                                        <th>Metode</th>
                                        <th style="width: 18%;" class="text-end">Nominal</th>
                                        <th style="width: 18%;">Ref</th>
                                        <th style="width: 14%;" class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->payments as $p)
                                        @php
                                            $pMode = strtolower((string) ($p->paymentMethod?->mode ?? ''));
                                            $pPmClass = match ($pMode) {
                                                'cash' => 'pm-badge pm-cash',
                                                'credit' => 'pm-badge pm-credit',
                                                'transfer' => 'pm-badge pm-transfer',
                                                default => 'pm-badge',
                                            };

                                            $type = (string) ($p->type ?? '');
                                            $typeClass = match ($type) {
                                                'dp' => 'type-chip type-dp',
                                                'dp_apply' => 'type-chip type-dp-apply',
                                                default => 'type-chip type-payment',
                                            };

                                            $typeLabel = match ($type) {
                                                'dp' => 'DP',
                                                'dp_apply' => 'OFFSET',
                                                default => 'PAYMENT',
                                            };
                                        @endphp

                                        <tr @class(['text-muted' => $p->voided_at])>
                                            <td>{{ $p->date ? id_date($p->date) : '-' }}</td>

                                            <td>
                                                <span class="{{ $typeClass }}">{{ $typeLabel }}</span>
                                            </td>

                                            <td>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span>{{ $p->paymentMethod?->name ?? '-' }}</span>
                                                    @if ($p->paymentMethod)
                                                        <span class="{{ $pPmClass }} mono">
                                                            {{ strtoupper($p->paymentMethod?->mode ?? '-') }}
                                                        </span>
                                                    @endif
                                                    @if ($p->voided_at)
                                                        <span class="tag tag-status-cancelled mono">VOID</span>
                                                    @endif
                                                </div>

                                                @if ($p->cashAccount)
                                                    <div class="text-muted small mono">
                                                        {{ $p->cashAccount->code }} — {{ $p->cashAccount->name }}
                                                    </div>
                                                @else
                                                    @if (strtolower((string) ($p->paymentMethod?->mode ?? '')) === 'credit')
                                                        <div class="text-muted small">Tanpa kas/bank (tempo/credit)</div>
                                                    @endif
                                                @endif

                                                @if (!empty($p->notes))
                                                    <div class="text-muted small">{{ $p->notes }}</div>
                                                @endif
                                            </td>

                                            <td class="text-end fw-semibold">{{ rupiah($p->amount) }}</td>
                                            <td>{{ $p->ref_no ?? '—' }}</td>

                                            <td class="text-end">
                                                @if (!$p->voided_at && $canPay)
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal" data-bs-target="#modalVoidPayment"
                                                        data-payment-id="{{ $p->id }}"
                                                        data-payment-type="{{ $typeLabel }}"
                                                        data-payment-amount="{{ rupiah($p->amount) }}"
                                                        data-payment-date="{{ $p->date ? id_date($p->date) : '-' }}">
                                                        VOID
                                                    </button>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="small text-muted mt-3">
                                <div>Catatan:</div>
                                <ul class="mb-0">
                                    <li><b>DP</b> boleh dicatat walau GRN belum posted.</li>
                                    <li><b>Pembayaran (pelunasan)</b> hanya relevan kalau sudah ada GRN posted (ada hutang
                                        real).</li>
                                    <li><b>TEMPO/CREDIT</b> hanya untuk <b>DP</b>, tidak boleh untuk pelunasan.</li>
                                    <li><b>OFFSET DP</b> mengurangi hutang tanpa keluar kas/bank (Dr AP, Cr DP/Advance).
                                    </li>
                                </ul>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- GOODS RECEIPTS (GRN) — owner & admin --}}
        @if ($user && ($user->isOwner() || $isAdmin))
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="fw-semibold">Goods Receipts (GRN) terkait</div>
                @if ($order->order_type !== 'packing')
                <div class="d-flex flex-wrap gap-2">
                    @if ($status === 'approved')
                        @if ($canCreateGrn ?? true)
                            <a href="{{ route('purchasing.purchase_receipts.create_from_order', $order->id) }}"
                                class="btn btn-sm btn-outline-primary">
                                + GRN baru dari PO ini
                            </a>
                        @else
                            <span class="btn btn-sm btn-outline-secondary disabled"
                                title="Semua item PO sudah fully received">
                                ✓ Semua sudah diterima
                            </span>
                        @endif
                    @endif

                    <a href="{{ route('purchasing.purchase_receipts.index', ['po' => $order->id]) }}"
                        class="btn btn-sm btn-outline-secondary">
                        Lihat semua GRN
                    </a>
                </div>
                @else
                <div class="text-muted" style="font-size:.8rem;">PO Packing tidak memerlukan GRN</div>
                @endif
            </div>

            <div class="table-responsive d-none d-md-block">
                @if ($grnCount === 0)
                    <div class="p-3 text-muted small">Belum ada GRN untuk PO ini.</div>
                @else
                    <table class="table table-sm mb-0 mono align-middle">
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 16%;">Tanggal</th>
                                <th style="width: 18%;">No. GRN</th>
                                <th style="width: 20%;">Warehouse</th>
                                <th>Catatan</th>
                                @if ($canSeeMoney)
                                <th style="width: 16%;" class="text-end">Grand Total</th>
                                @endif
                                <th style="width: 12%;" class="text-center">Status</th>
                                <th style="width: 13%;" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($grnList as $grn)
                                @php
                                    $isPosted = ($grn->status ?? 'draft') === 'posted';
                                    $badgeStatusClass = $isPosted
                                        ? 'badge-pill badge-posted'
                                        : 'badge-pill badge-draft';
                                    $statusIcon = $isPosted ? '✅' : '⏳';
                                    $statusLabel = $isPosted ? 'POSTED' : 'DRAFT';
                                    $wh = $grn->warehouse ?? null;
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $grn->date ? id_date($grn->date) : '-' }}</td>
                                    <td>
                                        <a href="{{ route('purchasing.purchase_receipts.show', $grn->id) }}"
                                            class="text-decoration-none">
                                            {{ $grn->code ?? $grn->id }}
                                        </a>
                                    </td>
                                    <td>
                                        @if ($wh)
                                            <div class="fw-semibold">{{ $wh->code }}</div>
                                            <div class="text-muted small">{{ $wh->name }}</div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="small">{{ $grn->notes ?: '—' }}</td>
                                    @if ($canSeeMoney)
                                    <td class="text-end">{{ isset($grn->grand_total) ? rupiah($grn->grand_total) : '—' }}</td>
                                    @endif
                                    <td class="text-center">
                                        <span class="{{ $badgeStatusClass }}">{{ $statusIcon }}
                                            {{ $statusLabel }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="{{ route('purchasing.purchase_receipts.show', $grn->id) }}"
                                                class="btn btn-xs btn-outline-secondary btn-sm">Detail</a>
                                            @if (!$isPosted)
                                                <form action="{{ route('purchasing.purchase_receipts.post', $grn->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Post GRN ini? Setelah di-post, stok akan ter-update.');">
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn btn-xs btn-success btn-sm">Post</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Mobile cards --}}
            <div class="d-md-none">
                @if ($grnCount === 0)
                    <div class="p-3 text-muted small">Belum ada GRN untuk PO ini.</div>
                @else
                    <div class="p-3 pt-2">
                        @foreach ($grnList as $grn)
                            @php
                                $isPosted = ($grn->status ?? 'draft') === 'posted';
                                $badgeStatusClass = $isPosted ? 'badge-pill badge-posted' : 'badge-pill badge-draft';
                                $statusIcon = $isPosted ? '✅' : '⏳';
                                $statusLabel = $isPosted ? 'POSTED' : 'DRAFT';
                                $wh = $grn->warehouse ?? null;
                            @endphp

                            <div class="po-mobile-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold mono">
                                            <a href="{{ route('purchasing.purchase_receipts.show', $grn->id) }}"
                                                class="text-decoration-none">
                                                {{ $grn->code ?? $grn->id }}
                                            </a>
                                        </div>
                                        <div class="text-muted small mono">{{ $grn->date ? id_date($grn->date) : '-' }}
                                        </div>
                                        @if ($wh)
                                            <div class="small mt-1">
                                                <span class="fw-semibold mono">{{ $wh->code }}</span>
                                                <span class="text-muted">• {{ $wh->name }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-end">
                                        <span class="{{ $badgeStatusClass }}">{{ $statusIcon }}
                                            {{ $statusLabel }}</span>
                                    </div>
                                </div>

                                <div class="small mt-2">
                                    <div class="text-muted">Catatan</div>
                                    <div>{{ $grn->notes ?: '—' }}</div>
                                </div>

                                @if ($canSeeMoney)
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="small text-muted">Grand Total</div>
                                    <div class="mono fw-semibold">
                                        {{ isset($grn->grand_total) ? rupiah($grn->grand_total) : '—' }}</div>
                                </div>
                                @endif

                                <div class="d-flex justify-content-end gap-1 mt-2">
                                    <a href="{{ route('purchasing.purchase_receipts.show', $grn->id) }}"
                                        class="btn btn-outline-secondary btn-sm">Detail</a>

                                    @if (!$isPosted)
                                        <form action="{{ route('purchasing.purchase_receipts.post', $grn->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Post GRN ini? Setelah di-post, stok akan ter-update.');">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">Post</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        @endif {{-- end isOwner GRN --}}

        {{-- DETAIL BARANG --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold">Detail Barang</div>

            <div class="table-responsive d-none d-md-block">
                <table class="table table-sm mb-0 mono">
                    <thead>
                        <tr>
                            <th class="sticky" style="width: 5%">No</th>
                            <th class="sticky">Item</th>
                            <th class="text-end sticky" style="width: 12%">Qty</th>
                            @if ($canSeeMoney)
                                <th class="text-end sticky" style="width: 18%">Harga</th>
                                <th class="text-end sticky" style="width: 15%">Diskon</th>
                                <th class="text-end sticky" style="width: 18%">Total</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->lines as $line)
                            <tr>
                                <td class="text-center align-middle">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="po-item-name">{{ optional($line->item)->name ?? '—' }}</div>
                                    @if ($line->item)
                                        <div class="text-muted small po-item-code">{{ $line->item->code }}</div>
                                    @endif
                                </td>
                                <td class="text-end">{{ decimal_id($line->qty, 2) }}</td>
                                @if ($canSeeMoney)
                                    <td class="text-end">{{ angka($line->unit_price) }}</td>
                                    <td class="text-end">{{ angka($line->discount) }}</td>
                                    <td class="text-end fw-semibold">{{ angka($line->line_total) }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canSeeMoney ? 6 : 3 }}" class="text-center text-muted py-3">Tidak ada item</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($canSeeMoney)
                        <tfoot>
                            <tr class="table-light">
                                <th colspan="5" class="text-end">Total PO</th>
                                <th class="text-end fs-5 fw-bold">{{ rupiah($order->grand_total) }}</th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <div class="d-md-none">
                <div class="p-3 pt-2">
                    @forelse ($order->lines as $line)
                        @php $item = $line->item; @endphp
                        <div class="po-mobile-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="po-item-name">{{ $item->name ?? '—' }}</div>
                                    @if ($item)
                                        <div class="text-muted small mono">{{ $item->code }}</div>
                                    @endif
                                </div>
                                <div class="text-end mono small text-muted">No. {{ $loop->iteration }}</div>
                            </div>

                            <div class="mt-2 small">
                                <div class="d-flex justify-content-between"><span>Qty</span><span
                                        class="mono">{{ decimal_id($line->qty, 2) }}</span></div>
                                @if ($canSeeMoney)
                                    <div class="d-flex justify-content-between"><span>Harga</span><span
                                            class="mono">{{ angka($line->unit_price) }}</span></div>
                                    <div class="d-flex justify-content-between"><span>Diskon</span><span
                                            class="mono">{{ angka($line->discount) }}</span></div>
                                @endif
                            </div>

                            @if ($canSeeMoney)
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="small text-muted">Total</div>
                                    <div class="mono fw-semibold">{{ angka($line->line_total) }}</div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-muted py-3 small">Tidak ada item</div>
                    @endforelse

                    @if ($canSeeMoney)
                        <div class="mt-3 border-top pt-2 small">
                            <div class="d-flex justify-content-between mt-1 fw-bold">
                                <span>Total PO</span>
                                <span class="mono">{{ rupiah($order->grand_total) }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- FOOT --}}
        <div class="d-flex justify-content-end">
            <a href="{{ route('purchasing.purchase_orders.index') }}" class="btn btn-outline-secondary">
                ⬅️ Kembali ke daftar
            </a>
        </div>

    </div>

    @if ($canSeeMoney)
    {{-- =========================================================
    MODAL: ADD PAYMENT / DP (punya kamu, tetap, cuma sedikit touch)
========================================================= --}}
    <div class="modal fade" id="modalAddPayment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="fw-semibold">Tambah Pembayaran</div>
                        <div class="text-muted small mono">
                            GRN Posted: {{ rupiah($grnPostedTotal) }} • Sisa hutang: {{ rupiah($apOutstanding) }}
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form method="POST" action="{{ route('purchasing.purchase_orders.payments.store', $order->id) }}"
                    id="paymentForm">
                    @csrf

                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-muted mb-1">Tanggal</label>
                                <input type="text" name="date" class="form-control gf-date-input"
                                    value="{{ old('date', now()->toDateString()) }}" data-gf-date
                                    autocomplete="off" required>
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label small text-muted mb-1">Jenis</label>
                                <select name="type" class="form-select" required id="typeSelectModal">
                                    <option value="payment" @selected(old('type', 'payment') === 'payment')>Pembayaran (Pelunasan)</option>
                                    <option value="dp" @selected(old('type') === 'dp')>DP (Uang Muka)</option>
                                </select>
                              <div class="text-muted small mt-1">
    Pembayaran hanya bisa dibuat setelah GRN <b>POSTED</b>.
</div>

                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small text-muted mb-1">Metode Bayar</label>
                                <select name="payment_method_id" class="form-select" required id="pmSelectModal">
                                    @foreach ($paymentMethods ?? [] as $pmOpt)
                                        <option value="{{ $pmOpt->id }}"
                                            data-mode="{{ strtolower($pmOpt->mode ?? '') }}"
                                            data-code="{{ strtoupper($pmOpt->code ?? '') }}" @selected(old('payment_method_id', $order->payment_method_id) == $pmOpt->id)>
                                            {{ $pmOpt->name }}
                                            @if (!empty($pmOpt->mode))
                                                — {{ strtoupper($pmOpt->mode) }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="text-muted small mt-1">
                                    Pilih <b>lewat apa</b> bayarnya (Cash / Transfer / Tempo).
                                </div>
                                <div class="text-danger small mt-1 d-none" id="creditPayErrModal">
                                    Metode TEMPO/CREDIT hanya untuk DP. Untuk pelunasan, pilih CASH / TRANSFER.
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small text-muted mb-1">Akun Kas/Bank</label>

                                <div id="cashWrap" class="d-none">
                                    <select name="cash_account_id" class="form-select" id="cashSelectCash">
                                        <option value="">— Pilih Kas —</option>
                                        @if ($cash1101)
                                            <option value="{{ $cash1101->id }}" data-code="1101"
                                                @selected(old('cash_account_id') == $cash1101->id)>
                                                1101 — {{ $cash1101->name }}
                                            </option>
                                        @endif
                                    </select>
                                    <div class="text-danger small mt-1 d-none" id="cashErrModal">Untuk CASH, wajib pilih
                                        akun 1101 (Kas).</div>
                                </div>

                                <div id="bankWrap" class="d-none">
                                    <select name="cash_account_id" class="form-select" id="cashSelectBank">
                                        <option value="">— Pilih Bank/E-Wallet —</option>
                                        @foreach ($transferBanks as $acc)
                                            <option value="{{ $acc->id }}" data-code="{{ $acc->code }}"
                                                @selected(old('cash_account_id') == $acc->id)>
                                                {{ $acc->code }} — {{ $acc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="text-danger small mt-1 d-none" id="bankErrModal">Untuk TRANSFER, wajib
                                        pilih 1111/1112/1113/1114.</div>
                                </div>

                                <div id="creditWrap" class="d-none">
                                    <input type="text" class="form-control"
                                        value="Tidak perlu kas/bank (TEMPO/CREDIT)" disabled>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small text-muted mb-1">Nominal</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" name="amount" class="form-control mono" id="amountInput"
                                        placeholder="0" value="{{ old('amount') }}" required>
                                    <button type="button" class="btn btn-outline-secondary" id="btnFillRemaining">Isi
                                        sisa</button>
                                </div>
                                <div class="text-muted small mt-1">
                                    Tombol <b>Isi sisa</b> mengisi sesuai <b>sisa hutang</b> (GRN posted - paid - offset).
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small text-muted mb-1">Ref</label>
                                <input type="text" name="ref_no" class="form-control mono" placeholder="Opsional"
                                    value="{{ old('ref_no') }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label small text-muted mb-1">Catatan</label>
                                <input type="text" name="notes" class="form-control" placeholder="Opsional"
                                    value="{{ old('notes') }}">
                            </div>

                            {{-- Tahap 4: Link ke Supplier Invoice (opsional) --}}
                            @php $unpaidInvList = $unpaidInvoices ?? collect(); @endphp
                            @if ($unpaidInvList->isNotEmpty())
                            <div class="col-12">
                                <label class="form-label small text-muted mb-1">
                                    Faktur Supplier <span class="text-muted">(opsional — untuk melunasi faktur)</span>
                                </label>
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
                                <div class="text-muted small mt-1">
                                    Jika dipilih, paid_amount faktur akan diupdate otomatis.
                                </div>
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
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button class="btn btn-primary" id="btnSavePayment">Simpan</button>
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
                class="modal-content" id="applyDpForm">
                @csrf

                <div class="modal-header">
                    <div>
                        <div class="fw-semibold">Offset DP ke Hutang</div>
                        <div class="text-muted small mono">
                            DP Available: {{ rupiah($dpAvailable) }} • AP Outstanding: {{ rupiah($apOutstanding) }}
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Max Offset</span>
                            <span class="mono fw-semibold">{{ rupiah($maxApplyDp) }}</span>
                        </div>
                        <div class="text-muted mt-1">
                            Nominal akan otomatis diambil minimum dari: input, DP available, AP outstanding.
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small text-muted mb-1">Tanggal</label>
                            <input type="text" name="date" class="form-control gf-date-input"
                                value="{{ old('date', now()->toDateString()) }}" data-gf-date
                                autocomplete="off" required>
                            @error('date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6">
                            <label class="form-label small text-muted mb-1">Nominal Offset</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="amount" class="form-control mono" id="applyDpAmount"
                                    placeholder="0" value="{{ old('amount') }}" required>
                                <button type="button" class="btn btn-outline-secondary"
                                    id="btnFillMaxApplyDp">Max</button>
                            </div>
                            @error('amount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label small text-muted mb-1">Catatan (optional)</label>
                            <input type="text" name="notes" class="form-control" maxlength="255"
                                value="{{ old('notes') }}">
                            @error('notes')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    @if ($errors->any())
                        {{-- NOTE: error bag kamu masih global. Ini tetap tampil. --}}
                        <div class="text-muted small mt-3">
                            Tips: kalau error-nya terkait Offset DP, modal ini akan kebuka otomatis.
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary"
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
            <form method="POST" action="" class="modal-content" id="voidPaymentForm">
                @csrf

                <div class="modal-header">
                    <div>
                        <div class="fw-semibold">Void Pembayaran</div>
                        <div class="text-muted small mono" id="voidInfo">—</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning py-2 small">
                        Ini akan membuat jurnal <b>reversal</b> otomatis. Pastikan kamu yakin.
                    </div>

                    <label class="form-label small text-muted mb-1">Alasan Void</label>
                    <input type="text" name="reason" class="form-control" maxlength="255"
                        placeholder="contoh: salah nominal / duplikat / salah metode" value="{{ old('reason') }}"
                        required>
                    @error('reason')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">VOID</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (grnPostedTotal <= 0.0001) {
  if (btnSave) btnSave.disabled = true;
}

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
                const grnPostedTotal = {{ (float) $grnPostedTotal }};
                const typeSelect = document.getElementById('typeSelectModal');

                function fmtId(n) {
                    return new Intl.NumberFormat('id-ID', {
                        maximumFractionDigits: 0
                    }).format(Math.round(n || 0));
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

                function autoPickBankJago() {
                    if (!cashSelectBank) return;
                    if (cashSelectBank.value) return;

                    const opts = Array.from(cashSelectBank.options);
                    const jago = opts.find(o => (o.dataset.code || '') === '1111' || (o.textContent || '').includes(
                        '1111'));
                    if (jago) {
                        cashSelectBank.value = jago.value;
                        return;
                    }
                    if (cashSelectBank.options.length > 1) cashSelectBank.selectedIndex = 1;
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
                        autoPickBankJago();
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
                    amountInput.value = fmtId(remaining);
                    amountInput.focus();
                    amountInput.select?.();
                });

                amountInput?.addEventListener('focusout', function() {
                    const raw = (amountInput.value || '').toString().trim();
                    if (raw === '') return;
                    const n = Number(raw.replace(/\./g, '').replace(/,/g, '.'));
                    if (!isNaN(n)) amountInput.value = fmtId(n);
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
                    applyDpAmount.value = fmtId(maxApplyDp);
                    applyDpAmount.focus();
                    applyDpAmount.select?.();
                });

                applyDpAmount?.addEventListener('focusout', function() {
                    const raw = (applyDpAmount.value || '').toString().trim();
                    if (raw === '') return;
                    const n = Number(raw.replace(/\./g, '').replace(/,/g, '.'));
                    if (!isNaN(n)) applyDpAmount.value = fmtId(n);
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
