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

        .pm-hybrid {
            border-color: rgba(234, 179, 8, .55);
            background: rgba(234, 179, 8, .10);
            color: #a16207;
        }

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

        $status = $order->status;
        $statusClass = match ($status) {
            'approved' => 'tag tag-status-approved',
            'cancelled' => 'tag tag-status-cancelled',
            default => 'tag tag-status-draft',
        };

        $grnList = $order->purchaseReceipts ?? collect();
        $grnCount = $grnList->count();

        $pm = $order->paymentMethod ?? null;
        $pmMode = strtolower((string) ($pm->mode ?? ''));
        $pmBadgeClass = match ($pmMode) {
            'cash' => 'pm-badge pm-cash',
            'credit' => 'pm-badge pm-credit',
            'hybrid' => 'pm-badge pm-hybrid',
            default => 'pm-badge',
        };

        $paid = (float) ($order->paid_amount ?? 0);
        $grand = (float) ($order->grand_total ?? 0);
        $balance = max(0, $grand - $paid);

        $payStatus = (string) ($order->payment_status ?? 'unpaid');
        $payBadgeClass = match ($payStatus) {
            'paid' => 'pay-badge pay-paid',
            'partial' => 'pay-badge pay-partial',
            default => 'pay-badge pay-unpaid',
        };

        $canPay = $order->status !== 'cancelled';
        $hasPayments = ($order->payments?->count() ?? 0) > 0;

        // ✅ strict list sesuai permintaan kamu
        $cashAccountsCol = collect($cashAccounts ?? []);
        $cash1101 = $cashAccountsCol->firstWhere('code', '1101');

        $transferBankCodes = ['1111', '1112', '1113', '1114'];
        $transferBanks = $cashAccountsCol
            ->filter(fn($a) => in_array((string) ($a->code ?? ''), $transferBankCodes, true))
            ->values();
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

                @if ($order->status === 'draft')
                    <a href="{{ route('purchasing.purchase_orders.edit', $order->id) }}"
                        class="btn btn-outline-primary btn-sm btn-action">
                        Edit PO
                    </a>
                @endif

                @if ($canPay)
                    <button type="button" class="btn btn-primary btn-sm btn-action" data-bs-toggle="modal"
                        data-bs-target="#modalAddPayment">
                        + Pembayaran
                    </button>

                    @if ($hasPayments)
                        <button type="button" class="btn btn-outline-secondary btn-sm btn-action" data-bs-toggle="collapse"
                            data-bs-target="#paymentHistoryCollapse" aria-expanded="false"
                            aria-controls="paymentHistoryCollapse">
                            Riwayat
                        </button>
                    @endif
                @endif

                @if ($user && $user->role === 'owner' && $order->status === 'draft')
                    <form action="{{ route('purchasing.purchase_orders.approve', $order->id) }}" method="POST"
                        onsubmit="return confirm('Approve PO ini? Setelah di-approve, PO tidak bisa diedit lagi.');">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm btn-action">
                            Approve PO
                        </button>
                    </form>
                @endif

                @if ($user && $user->role === 'owner' && in_array($order->status, ['draft', 'approved'], true) && $grnCount === 0)
                    <form action="{{ route('purchasing.purchase_orders.cancel', $order->id) }}" method="POST"
                        onsubmit="return confirm('Batalkan PO ini? Tindakan ini tidak bisa dibatalkan.');">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm btn-action">
                            Cancel PO
                        </button>
                    </form>
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
                    <div class="text-muted small">Metode bayar</div>
                    @if ($pm)
                        <div class="fw-semibold">{{ $pm->name }}</div>
                        <div class="mt-1">
                            <span class="{{ $pmBadgeClass }} mono">{{ strtoupper($pm->mode ?? '-') }}</span>
                            @if (!empty($pm->code))
                                <span class="text-muted small mono ms-1">{{ $pm->code }}</span>
                            @endif
                        </div>
                    @else
                        <div class="fw-semibold text-muted">—</div>
                    @endif
                </div>

                <div class="col-md-3 col-6">
                    <div class="text-muted small">Status PO</div>
                    <span class="{{ $statusClass }} mono">{{ strtoupper($order->status) }}</span>
                    @if ($grnCount > 0)
                        <span class="tag-grn mono ms-1">GRN x{{ $grnCount }}</span>
                    @endif
                </div>

                <div class="col-md-3 col-6">
                    <div class="text-muted small">Status bayar</div>
                    <span class="{{ $payBadgeClass }} mono">{{ strtoupper($payStatus) }}</span>
                    @if (!empty($order->due_date))
                        <div class="text-muted small mono mt-1">JT: {{ id_date($order->due_date) }}</div>
                    @endif
                </div>

                <div class="col-md-3 col-6">
                    <div class="text-muted small">Paid</div>
                    <div class="fw-semibold mono">{{ rupiah($paid) }}</div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="text-muted small">Sisa</div>
                    <div class="fw-bold mono">{{ rupiah($balance) }}</div>
                </div>

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
        @if ($hasPayments)
            <div class="collapse mb-4" id="paymentHistoryCollapse">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="fw-semibold">Riwayat Pembayaran</div>
                        <div class="text-muted small mono">Sisa {{ rupiah($balance) }}</div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 mono align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 18%;">Tanggal</th>
                                        <th style="width: 10%;">Tipe</th>
                                        <th>Metode</th>
                                        <th style="width: 18%;" class="text-end">Nominal</th>
                                        <th style="width: 18%;">Ref</th>
                                        <th style="width: 12%;" class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->payments as $p)
                                        <tr @class(['text-muted' => $p->voided_at])>
                                            <td>{{ $p->date ? id_date($p->date) : '-' }}</td>
                                            <td class="fw-semibold text-uppercase">{{ $p->type }}</td>
                                            <td>
                                                {{ $p->paymentMethod?->name ?? '-' }}
                                                @if ($p->cashAccount)
                                                    <div class="text-muted small mono">
                                                        {{ $p->cashAccount->code }} — {{ $p->cashAccount->name }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-end fw-semibold">{{ rupiah($p->amount) }}</td>
                                            <td>{{ $p->ref_no ?? '—' }}</td>
                                            <td class="text-end">
                                                @if (!$p->voided_at && $canPay)
                                                    <form method="POST"
                                                        action="{{ route('purchasing.purchase_orders.payments.void', [$order->id, $p->id]) }}"
                                                        onsubmit="return confirm('VOID pembayaran ini?')" class="d-inline">
                                                        @csrf
                                                        <button class="btn btn-sm btn-outline-danger">VOID</button>
                                                    </form>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
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

        {{-- GOODS RECEIPTS (GRN) --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="fw-semibold">Goods Receipts (GRN) terkait</div>
                <div class="d-flex flex-wrap gap-2">
                    @if ($order->status === 'approved')
                        <a href="{{ route('purchasing.purchase_receipts.create_from_order', $order->id) }}"
                            class="btn btn-sm btn-outline-primary">
                            + GRN baru dari PO ini
                        </a>
                    @endif

                    <a href="{{ route('purchasing.purchase_receipts.index', ['po' => $order->id]) }}"
                        class="btn btn-sm btn-outline-secondary">
                        Lihat semua GRN
                    </a>
                </div>
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
                                <th style="width: 16%;" class="text-end">Grand Total</th>
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
                                    <td class="text-end">{{ isset($grn->grand_total) ? rupiah($grn->grand_total) : '—' }}
                                    </td>
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

                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="small text-muted">Grand Total</div>
                                    <div class="mono fw-semibold">
                                        {{ isset($grn->grand_total) ? rupiah($grn->grand_total) : '—' }}</div>
                                </div>

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
                            <th class="text-end sticky" style="width: 18%">Harga</th>
                            <th class="text-end sticky" style="width: 15%">Diskon</th>
                            <th class="text-end sticky" style="width: 18%">Total</th>
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
                                <td class="text-end">{{ angka($line->unit_price) }}</td>
                                <td class="text-end">{{ angka($line->discount) }}</td>
                                <td class="text-end fw-semibold">{{ angka($line->line_total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Tidak ada item</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Subtotal</th>
                            <th class="text-end">{{ rupiah($order->subtotal) }}</th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-end">Diskon</th>
                            <th class="text-end">{{ rupiah($order->discount) }}</th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-end">
                                PPN @if ($order->tax_percent)
                                    ({{ angka($order->tax_percent) }}%)
                                @endif
                            </th>
                            <th class="text-end">{{ rupiah($order->tax_amount) }}</th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-end">Ongkir</th>
                            <th class="text-end">{{ rupiah($order->shipping_cost) }}</th>
                        </tr>
                        <tr class="table-light">
                            <th colspan="5" class="text-end">Grand Total</th>
                            <th class="text-end fs-5 fw-bold">{{ rupiah($order->grand_total) }}</th>
                        </tr>
                    </tfoot>
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
                                <div class="d-flex justify-content-between"><span>Harga</span><span
                                        class="mono">{{ angka($line->unit_price) }}</span></div>
                                <div class="d-flex justify-content-between"><span>Diskon</span><span
                                        class="mono">{{ angka($line->discount) }}</span></div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="small text-muted">Total</div>
                                <div class="mono fw-semibold">{{ angka($line->line_total) }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-3 small">Tidak ada item</div>
                    @endforelse

                    <div class="mt-3 border-top pt-2 small">
                        <div class="d-flex justify-content-between"><span>Subtotal</span><span
                                class="mono">{{ rupiah($order->subtotal) }}</span></div>
                        <div class="d-flex justify-content-between"><span>Diskon</span><span
                                class="mono">{{ rupiah($order->discount) }}</span></div>
                        <div class="d-flex justify-content-between"><span>PPN @if ($order->tax_percent)
                                    ({{ angka($order->tax_percent) }}%)
                                @endif
                            </span><span class="mono">{{ rupiah($order->tax_amount) }}</span></div>
                        <div class="d-flex justify-content-between"><span>Ongkir</span><span
                                class="mono">{{ rupiah($order->shipping_cost) }}</span></div>
                        <div class="d-flex justify-content-between mt-1 fw-bold"><span>Grand Total</span><span
                                class="mono">{{ rupiah($order->grand_total) }}</span></div>
                    </div>
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

    {{-- ===========================
        MODAL: ADD PAYMENT (STRICT BANK LIST)
    =========================== --}}
    <div class="modal fade" id="modalAddPayment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="fw-semibold">Tambah Pembayaran</div>
                        <div class="text-muted small mono">Sisa: {{ rupiah($balance) }}</div>
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
                                <input type="date" name="date" class="form-control"
                                    value="{{ old('date', now()->toDateString()) }}" required>
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label small text-muted mb-1">Jenis</label>
                                <select name="type" class="form-select" required id="typeSelectModal">
                                    <option value="payment" @selected(old('type', 'payment') === 'payment')>Pembayaran</option>
                                    <option value="dp" @selected(old('type') === 'dp')>DP</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small text-muted mb-1">Metode</label>
                                <select name="payment_method_id" class="form-select" required id="pmSelectModal">
                                    @foreach ($paymentMethods ?? [] as $pmOpt)
                                        <option value="{{ $pmOpt->id }}"
                                            data-mode="{{ strtolower($pmOpt->mode ?? '') }}"
                                            data-code="{{ strtoupper($pmOpt->code ?? '') }}" @selected(old('payment_method_id', $order->payment_method_id) == $pmOpt->id)>
                                            {{ $pmOpt->name }}@if (!empty($pmOpt->mode))
                                                — {{ strtoupper($pmOpt->mode) }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- ✅ STRICT CASH/BANK OPTIONS --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label small text-muted mb-1">Akun Kas/Bank</label>

                                {{-- cash select (1101 only) --}}
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
                                    <div class="text-muted small mt-1">CASH: gunakan Kas Tunai (1101).</div>
                                </div>

                                {{-- transfer select (1111..1114 only) --}}
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
                                    <div class="text-danger small mt-1 d-none" id="bankErrModal">
                                        Untuk <span class="fw-semibold">TRANSFER</span>, wajib pilih salah satu:
                                        1111/1112/1113/1114.
                                    </div>
                                    <div class="text-muted small mt-1" id="bankHintModal" style="display:none;"></div>
                                </div>

                                {{-- credit/tempo --}}
                                <div id="creditWrap" class="d-none">
                                    <input type="text" class="form-control"
                                        value="Tidak perlu kas/bank (TEMPO/CREDIT)" disabled>
                                    <div class="text-muted small mt-1">Hutang dicatat saat GRN diposting.</div>
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

                            <div class="col-12">
                                <div class="alert alert-warning py-2 small mb-0 d-none" id="pmWarn">
                                    Metode <span class="fw-semibold">CREDIT/TEMPO</span> tidak membutuhkan kas/bank.
                                    Hutang dicatat saat <span class="fw-semibold">GRN diposting</span>.
                                </div>
                            </div>

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

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const pm = document.getElementById('pmSelectModal');
                const btnSave = document.getElementById('btnSavePayment');
                const warn = document.getElementById('pmWarn');

                const cashWrap = document.getElementById('cashWrap');
                const bankWrap = document.getElementById('bankWrap');
                const creditWrap = document.getElementById('creditWrap');

                const cashSelectCash = document.getElementById('cashSelectCash');
                const cashSelectBank = document.getElementById('cashSelectBank');

                const bankErr = document.getElementById('bankErrModal');
                const bankHint = document.getElementById('bankHintModal');

                const amountInput = document.getElementById('amountInput');
                const btnFill = document.getElementById('btnFillRemaining');
                const remaining = {{ (float) $balance }};

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
                    if (code.includes('TRF') || code.includes('TRANSFER')) return 'transfer';
                    if (code.includes('TEMPO') || code.includes('CREDIT')) return 'credit';
                    return '';
                }

                function showOnly(which) {
                    cashWrap?.classList.toggle('d-none', which !== 'cash');
                    bankWrap?.classList.toggle('d-none', which !== 'bank');
                    creditWrap?.classList.toggle('d-none', which !== 'credit');
                }

                function setBankError(show) {
                    if (!bankErr) return;
                    bankErr.classList.toggle('d-none', !show);
                }

                function setBankHint(text) {
                    if (!bankHint) return;
                    bankHint.style.display = text ? 'block' : 'none';
                    bankHint.textContent = text || '';
                }

                function autoPickCash() {
                    if (!cashSelectCash) return;
                    if (!cashSelectCash.value) {
                        // pilih option kedua (1101) kalau ada
                        if (cashSelectCash.options.length > 1) cashSelectCash.selectedIndex = 1;
                    }
                }

                function autoPickBankJago() {
                    if (!cashSelectBank) return;
                    if (cashSelectBank.value) return;

                    // cari yang 1111 dulu
                    const opts = Array.from(cashSelectBank.options);
                    const jago = opts.find(o => (o.dataset.code || '') === '1111' || (o.textContent || '').includes(
                        '1111'));
                    if (jago) {
                        cashSelectBank.value = jago.value;
                        return;
                    }

                    // fallback: option pertama setelah placeholder
                    if (cashSelectBank.options.length > 1) cashSelectBank.selectedIndex = 1;
                }

                function validateTransfer() {
                    const mode = detectMode();
                    if (mode !== 'transfer') {
                        setBankError(false);
                        if (btnSave) btnSave.disabled = false;
                        return true;
                    }

                    const ok = !!(cashSelectBank && cashSelectBank.value);
                    setBankError(!ok);
                    if (btnSave) btnSave.disabled = !ok;
                    return ok;
                }

                function syncModeUI() {
                    const mode = detectMode();

                    warn?.classList.toggle('d-none', mode !== 'credit');

                    if (mode === 'credit') {
                        showOnly('credit');
                        // pastikan select tidak ngirim nilai (remove selection)
                        if (cashSelectCash) cashSelectCash.value = '';
                        if (cashSelectBank) cashSelectBank.value = '';
                        setBankHint(null);
                        setBankError(false);
                        if (btnSave) btnSave.disabled = false;
                        return;
                    }

                    if (mode === 'transfer') {
                        showOnly('bank');
                        autoPickBankJago();
                        setBankHint('TRANSFER: pilih bank/ewallet (default: 1111 Bank Jago).');
                        validateTransfer();
                        return;
                    }

                    // default cash
                    showOnly('cash');
                    autoPickCash();
                    setBankHint(null);
                    setBankError(false);
                    if (btnSave) btnSave.disabled = false;
                }

                // Fill remaining
                btnFill?.addEventListener('click', function() {
                    if (!amountInput) return;
                    amountInput.value = fmtId(remaining);
                    amountInput.focus();
                    amountInput.select?.();
                });

                // format nominal on blur
                amountInput?.addEventListener('focusout', function() {
                    const raw = (amountInput.value || '').toString().trim();
                    if (raw === '') return;
                    const n = Number(raw.replace(/\./g, '').replace(/,/g, '.'));
                    if (!isNaN(n)) amountInput.value = fmtId(n);
                });

                pm?.addEventListener('change', syncModeUI);
                cashSelectBank?.addEventListener('change', validateTransfer);

                syncModeUI();

                @if ($errors->any())
                    const addModalEl = document.getElementById('modalAddPayment');
                    if (addModalEl && window.bootstrap) {
                        new bootstrap.Modal(addModalEl).show();
                    }
                    const hist = document.getElementById('paymentHistoryCollapse');
                    if (hist && window.bootstrap) {
                        new bootstrap.Collapse(hist, {
                            toggle: true
                        });
                    }
                @endif
            });
        </script>
    @endpush
@endsection
