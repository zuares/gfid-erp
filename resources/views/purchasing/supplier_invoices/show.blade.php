@extends('layouts.app')

@section('title', 'Faktur ' . $supplierInvoice->invoice_no)

@php
    $user        = auth()->user();
    $canSeeMoney = $user?->isOwner() || in_array($user?->role ?? '', ['accounting', 'developer']);
    $isOwner     = $user?->isOwner() ?? false;
    $inv         = $supplierInvoice;
    $isDraft     = $inv->isDraft();
    $isPosted    = $inv->isPosted();
    $isVoid      = $inv->isVoid();
    $outstanding = $inv->outstanding();
    $paymentAvailable = (float) ($paymentAvailable ?? 0);
    $canPayInvoice = $isOwner
        && in_array($inv->status, ['posted', 'partial_paid'], true)
        && $paymentAvailable > \App\Models\PurchaseOrder::paymentRoundingTolerance();
    $cash1101 = collect($cashAccounts ?? [])->firstWhere('code', '1101');
    $transferBanks = collect($cashAccounts ?? [])->filter(fn ($account) => in_array((string) $account->code, ['1111', '1112', '1113', '1114'], true))->values();

    $statusLabel = match($inv->status) {
        'posted'       => 'POSTED',
        'partial_paid' => 'BAYAR SEBAGIAN',
        'paid'         => 'LUNAS',
        'void'         => 'VOID',
        default        => 'DRAFT',
    };
    $statusClass = match($inv->status) {
        'posted'       => 'tag tag-status-posted',
        'partial_paid' => 'tag tag-status-partial',
        'paid'         => 'tag tag-status-paid',
        'void'         => 'tag tag-status-void',
        default        => 'tag tag-status-draft',
    };
@endphp

@push('head')
<style>
    .page-wrap { max-width: 800px; margin-inline: auto; padding-bottom: 3rem; }
    .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono"; }

    /* status tags */
    .tag { border-radius: 999px; font-size: .75rem; padding: .15rem .7rem; border: 1px solid transparent; display: inline-block; }
    .tag-status-draft    { background: rgba(148,163,184,.12); color: #64748b; border-color: rgba(148,163,184,.5); }
    .tag-status-posted   { background: rgba(59,130,246,.10); color: #1d4ed8; border-color: rgba(59,130,246,.5); }
    .tag-status-partial  { background: rgba(234,179,8,.12); color: #a16207; border-color: rgba(234,179,8,.5); }
    .tag-status-paid     { background: rgba(22,163,74,.12); color: #15803d; border-color: rgba(22,163,74,.5); }
    .tag-status-void     { background: rgba(220,38,38,.08); color: #b91c1c; border-color: rgba(220,38,38,.5); }

    /* Info grid */
    dl.info-grid { display: grid; grid-template-columns: auto 1fr; gap: .25rem .75rem; font-size: .87rem; }
    dl.info-grid dt { color: var(--muted); font-weight: 400; white-space: nowrap; }
    dl.info-grid dd { margin: 0; }

    /* Summary card */
    .summary-row { display: flex; justify-content: space-between; padding: .3rem 0; border-bottom: 1px solid var(--line); font-size: .87rem; }
    .summary-row:last-child { border-bottom: none; font-weight: 600; font-size: .92rem; }
    .summary-row.highlight { background: rgba(59,130,246,.04); margin: 0 -.75rem; padding: .3rem .75rem; }

    /* Action buttons */
    .action-bar { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
    .btn-action { font-size: .82rem; }

    /* Warning */
    .warn-box {
        background: rgba(234,179,8,.08);
        border: 1px solid rgba(234,179,8,.4);
        border-radius: 8px;
        padding: .6rem .85rem;
        font-size: .82rem;
        color: #a16207;
    }
    .info-box {
        background: rgba(59,130,246,.06);
        border: 1px solid rgba(59,130,246,.3);
        border-radius: 8px;
        padding: .6rem .85rem;
        font-size: .82rem;
        color: #1d4ed8;
    }

    .invoice-payment-modal .modal-content { border: 0; border-radius: 16px; overflow: hidden; box-shadow: 0 18px 55px rgba(15,23,42,.2); }
    .invoice-payment-modal .modal-header { background: #f8f9ff; border-bottom: 1px solid var(--line); padding: 1rem 1.15rem; }
    .invoice-payment-modal .modal-body { padding: 1.15rem; }
    .invoice-payment-modal .modal-footer { padding: .8rem 1.15rem; border-top: 1px solid var(--line); }
    .invoice-payment-modal .payment-context { display: flex; gap: .65rem; align-items: flex-start; margin-bottom: 1rem; padding: .75rem; border: 1px solid #dbe4ff; border-radius: 10px; background: #f6f8ff; color: #334155; font-size: .78rem; line-height: 1.45; }
    .invoice-payment-modal .payment-context i { color: #4f46e5; font-size: 1rem; }
    .invoice-payment-modal .outstanding-pill { display: inline-flex; align-items: center; gap: .35rem; margin-top: .25rem; padding: .25rem .55rem; border-radius: 999px; background: #fff1f2; color: #be123c; font-size: .72rem; font-weight: 700; }
    .invoice-payment-modal .form-label { margin-bottom: .35rem; font-size: .78rem; font-weight: 600; color: #334155; }
    .invoice-payment-modal .form-control, .invoice-payment-modal .form-select { min-height: 40px; border-radius: 9px; }
    .invoice-payment-modal .form-control:focus, .invoice-payment-modal .form-select:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.13); }
    .invoice-payment-modal .amount-group .btn { min-width: 78px; }
    .payment-history-row { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .7rem 0; border-bottom: 1px solid var(--line); font-size: .82rem; }
    .payment-history-row:last-child { border-bottom: 0; padding-bottom: 0; }
    .payment-history-meta { color: var(--muted); font-size: .74rem; }
    @media (max-width: 575.98px) {
        .invoice-payment-modal .modal-dialog { margin: .5rem; }
        .invoice-payment-modal .modal-footer { flex-direction: column-reverse; align-items: stretch; gap: .5rem; }
        .invoice-payment-modal .modal-footer .btn { width: 100%; min-height: 42px; }
        .payment-history-row { align-items: flex-start; }
    }
</style>
@endpush

@section('content')
<div class="page-wrap">

    {{-- Flash --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible mb-3">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible mb-3">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <a href="{{ route('purchasing.supplier_invoices.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                ← Daftar Faktur
            </a>
            <h1 class="h5 mb-0 fw-bold">{{ $inv->invoice_no }}</h1>
            <div class="text-muted small mono">Dibuat {{ id_date($inv->invoice_date) }}</div>
        </div>
        <div class="action-bar">
            @if ($canPayInvoice)
                <button type="button" class="btn btn-sm btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#supplierInvoicePaymentModal">
                    <i class="bi bi-wallet2 me-1" aria-hidden="true"></i> Bayar Invoice
                </button>
            @endif

            @if ($isDraft && $canSeeMoney)
                <form method="POST" action="{{ route('purchasing.supplier_invoices.post', $inv->id) }}"
                      onsubmit="return confirm('Post faktur ini? Pastikan nilainya sudah benar.')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary btn-action">Post Faktur</button>
                </form>
            @endif

            @if (!$isVoid && $isOwner)
                <form method="POST" action="{{ route('purchasing.supplier_invoices.void', $inv->id) }}"
                      onsubmit="return confirm('Void faktur ini? Tindakan ini tidak bisa dibatalkan.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger btn-action">Void</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Warning: draft belum di-post --}}
    @if ($isDraft)
        <div class="warn-box mb-3">
            ⚠️ Faktur ini masih Draft. Klik <strong>Post Faktur</strong> setelah data sudah benar.
        </div>
    @endif

    {{-- Info card --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <dl class="info-grid">
                        <dt>Status</dt>
                        <dd><span class="{{ $statusClass }}">{{ $statusLabel }}</span></dd>

                        <dt>Supplier</dt>
                        <dd class="fw-semibold">{{ optional($inv->supplier)->name ?? '—' }}</dd>

                        <dt>Tanggal Faktur</dt>
                        <dd class="mono">{{ id_date($inv->invoice_date) }}</dd>

                        @if ($inv->due_date)
                        <dt>Jatuh Tempo</dt>
                        <dd class="mono {{ $isPosted && $inv->due_date < now() ? 'text-danger fw-semibold' : '' }}">
                            {{ id_date($inv->due_date) }}
                            @if ($isPosted && $outstanding > 0 && $inv->due_date < now())
                                <span class="badge bg-danger ms-1" style="font-size:.65rem;">JATUH TEMPO</span>
                            @endif
                        </dd>
                        @endif

                        @if ($inv->supplier_invoice_ref)
                        <dt>No. Faktur Supplier</dt>
                        <dd class="mono">{{ $inv->supplier_invoice_ref }}</dd>
                        @endif

                        @if ($order)
                        <dt>Purchase Order</dt>
                        <dd>
                            <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
                               class="text-decoration-none mono">{{ $order->code }}</a>
                        </dd>
                        @endif
                    </dl>
                </div>

                <div class="col-md-6">
                    <dl class="info-grid">
                        <dt>Dibuat oleh</dt>
                        <dd>{{ optional($inv->createdBy)->name ?? '—' }}</dd>

                        @if ($inv->posted_at)
                        <dt>Di-post</dt>
                        <dd class="mono">{{ id_date($inv->posted_at) }}</dd>
                        @endif

                        @if ($isVoid && $inv->voided_at)
                        <dt>Di-void</dt>
                        <dd class="mono text-danger">{{ id_date($inv->voided_at) }}</dd>
                        @endif

                        @if ($inv->notes)
                        <dt>Catatan</dt>
                        <dd>{{ $inv->notes }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>

    {{-- Ringkasan Nilai --}}
    @if ($canSeeMoney)
    <div class="card mb-3">
        <div class="card-body">
            <div class="fw-semibold small text-uppercase text-muted mb-3">Ringkasan Nilai</div>

            {{-- Referensi PO / GRN --}}
            @if ($order)
                <div class="info-box mb-3">
                    <div class="row g-2" style="font-size:.83rem;">
                        <div class="col-6">
                            <div class="text-muted">Total PO ({{ $order->code }})</div>
                            <div class="mono fw-semibold">{{ rupiah($order->grand_total) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted">GRN Posted ({{ $grnCount }} doc)</div>
                            <div class="mono fw-semibold">{{ rupiah($grnPostedTotal) }}</div>
                        </div>
                        @if ($returnTotal > 0)
                        <div class="col-6">
                            <div class="text-muted">Total Return</div>
                            <div class="mono fw-semibold text-danger">- {{ rupiah($returnTotal) }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Nilai Faktur --}}
            <div class="summary-row">
                <span class="text-muted">Subtotal Faktur</span>
                <span class="mono">{{ rupiah($inv->subtotal) }}</span>
            </div>
            @if ((float) $inv->discount_amount > 0)
            <div class="summary-row">
                <span class="text-muted">Diskon</span>
                <span class="mono text-danger">- {{ rupiah($inv->discount_amount) }}</span>
            </div>
            @endif
            @if ((float) $inv->return_deduction_amount > 0)
            <div class="summary-row">
                <span class="text-muted">Potongan Retur</span>
                <span class="mono text-danger">- {{ rupiah($inv->return_deduction_amount) }}</span>
            </div>
            @endif
            <div class="summary-row">
                <span>Total Faktur</span>
                <span class="mono">{{ rupiah($inv->total_amount) }}</span>
            </div>
            @if ($isPosted)
            <div class="summary-row">
                <span class="text-muted">Sudah Dibayar</span>
                <span class="mono text-success">{{ rupiah($inv->paid_amount) }}</span>
            </div>
            <div class="summary-row highlight">
                <span>Outstanding</span>
                <span class="mono {{ $outstanding > 0 ? 'text-danger' : 'text-success' }}">
                    {{ rupiah($outstanding) }}
                </span>
            </div>
            @endif
        </div>
    </div>

    @if ($canSeeMoney && ($paymentRows ?? collect())->isNotEmpty())
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold small text-uppercase text-muted">Riwayat Pembayaran Invoice</div>
                <span class="text-muted small">{{ $paymentRows->count() }} transaksi</span>
            </div>
            @foreach ($paymentRows as $payment)
                <div class="payment-history-row">
                    <div>
                        <div class="fw-semibold">{{ id_date($payment->date) }} · {{ $payment->paymentMethod?->name ?? '—' }}</div>
                        <div class="payment-history-meta">
                            {{ $payment->cashAccount?->code ?? '—' }} · {{ $payment->ref_no ?: 'Tanpa referensi' }}
                            @if ($payment->createdBy) · {{ $payment->createdBy->name }} @endif
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="mono fw-semibold {{ $payment->voided_at ? 'text-decoration-line-through text-muted' : 'text-success' }}">
                            {{ rupiah($payment->amount) }}
                        </div>
                        @if ($payment->voided_at)
                            <span class="badge bg-danger-subtle text-danger">VOID</span>
                        @else
                            <span class="text-muted small">Dr Hutang / Cr Kas-Bank</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- GRN warning jika tidak ada GRN --}}
    @if ($order && $grnPostedTotal <= 0)
        <div class="warn-box mb-3">
            ⚠️ PO {{ $order->code }} belum memiliki GRN Posted. Invoice tidak dapat di-post.
        </div>
    @endif

    @if ($order && $grnPostedTotal > 0)
        <div class="info-box mb-3">
            Nilai invoice bersumber dari GRN Posted dikurangi retur Posted. Potongan tidak dapat diubah manual.
        </div>
    @endif

    @endif {{-- end canSeeMoney --}}

    @if ($canPayInvoice)
    <div class="modal fade invoice-payment-modal" id="supplierInvoicePaymentModal" tabindex="-1"
         aria-labelledby="supplierInvoicePaymentTitle" aria-describedby="supplierInvoicePaymentHint" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="text-muted text-uppercase small fw-semibold">Settlement supplier</div>
                        <h2 class="modal-title h6 mb-0" id="supplierInvoicePaymentTitle">Bayar Invoice</h2>
                        <div class="outstanding-pill"><i class="bi bi-wallet2" aria-hidden="true"></i> Bisa dibayar {{ rupiah($paymentAvailable) }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup modal pembayaran"></button>
                </div>

                <form method="POST" action="{{ route('purchasing.supplier_invoices.payments.store', $inv->id) }}" id="supplierInvoicePaymentForm">
                    @csrf
                    <div class="modal-body">
                        <div class="payment-context" id="supplierInvoicePaymentHint" role="status" aria-live="polite">
                            <i class="bi bi-journal-check" aria-hidden="true"></i>
                            <span>GRN sudah mencatat hutang. Pembayaran ini hanya melunasi invoice: <strong>Dr Hutang Dagang / Cr Kas atau Bank</strong> di akuntansi lama dan akuntansi baru.</span>
                        </div>
                        @if ($paymentAvailable + \App\Models\PurchaseOrder::paymentRoundingTolerance() < $outstanding)
                            <div class="warn-box mb-3">Sebagian hutang PO sudah dibayar melalui PO/GRN. Nominal maksimal pada modal ini adalah {{ rupiah($paymentAvailable) }}.</div>
                        @endif

                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label" for="supplierInvoicePaymentDate">Tanggal</label>
                                <input type="date" class="form-control" id="supplierInvoicePaymentDate" name="date" value="{{ old('date', now()->toDateString()) }}" required>
                            </div>

                            <div class="col-6">
                                <label class="form-label" for="supplierInvoicePaymentAmount">Nominal</label>
                                <div class="input-group amount-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control mono" id="supplierInvoicePaymentAmount" name="amount" value="{{ old('amount', number_format($paymentAvailable, 0, ',', '.')) }}" data-max="{{ $paymentAvailable }}" inputmode="decimal" required>
                                    <button type="button" class="btn btn-outline-secondary" id="fillSupplierInvoiceOutstanding">Sisa</button>
                                </div>
                                <div class="invalid-feedback" id="supplierInvoicePaymentAmountError">Nominal tidak boleh melebihi outstanding invoice.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="supplierInvoicePaymentMethod">Metode pembayaran</label>
                                <select class="form-select" id="supplierInvoicePaymentMethod" name="payment_method_id" required>
                                    @foreach ($paymentMethods as $method)
                                        <option value="{{ $method->id }}" data-mode="{{ strtolower($method->mode ?? '') }}">
                                            {{ $method->name }}{{ $method->mode ? ' — '.strtoupper($method->mode) : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="supplierInvoiceCashAccount">Kas / Bank</label>
                                <select class="form-select" id="supplierInvoiceCashAccount" name="cash_account_id" required>
                                    <option value="">Pilih akun kas</option>
                                    @if ($cash1101)
                                        <option value="{{ $cash1101->id }}" data-mode="cash">1101 — {{ $cash1101->name }}</option>
                                    @endif
                                    @foreach ($transferBanks as $account)
                                        <option value="{{ $account->id }}" data-mode="transfer">{{ $account->code }} — {{ $account->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text" id="supplierInvoiceCashAccountHint">Pilih akun yang digunakan untuk mengeluarkan pembayaran.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="supplierInvoicePaymentRef">No. referensi <span class="fw-normal text-muted">(opsional)</span></label>
                                <input type="text" class="form-control mono" id="supplierInvoicePaymentRef" name="ref_no" maxlength="100" placeholder="Contoh: transfer / bukti kas">
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="supplierInvoicePaymentNotes">Catatan <span class="fw-normal text-muted">(opsional)</span></label>
                                <input type="text" class="form-control" id="supplierInvoicePaymentNotes" name="notes" maxlength="255" placeholder="Tambahkan catatan bila diperlukan">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="submitSupplierInvoicePayment"><i class="bi bi-check2 me-1" aria-hidden="true"></i> Simpan Pembayaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        (() => {
            const method = document.getElementById('supplierInvoicePaymentMethod');
            const account = document.getElementById('supplierInvoiceCashAccount');
            const amount = document.getElementById('supplierInvoicePaymentAmount');
            const fill = document.getElementById('fillSupplierInvoiceOutstanding');
            const form = document.getElementById('supplierInvoicePaymentForm');
            if (!method || !account || !amount || !form) return;

            const parseAmount = (value) => {
                const raw = String(value || '').replace(/\s/g, '');
                if (raw.includes(',')) return Number(raw.replace(/\./g, '').replace(',', '.')) || 0;
                if (/^\d{1,3}(\.\d{3})+$/.test(raw)) return Number(raw.replace(/\./g, '')) || 0;
                return Number(raw) || 0;
            };

            const syncAccounts = () => {
                const mode = method.options[method.selectedIndex]?.dataset.mode || '';
                let hasSelected = false;
                [...account.options].forEach((option) => {
                    if (!option.value) return;
                    const visible = option.dataset.mode === mode;
                    option.hidden = !visible;
                    if (visible && !hasSelected) {
                        option.selected = true;
                        hasSelected = true;
                    }
                });
                account.required = true;
                document.getElementById('supplierInvoiceCashAccountHint').textContent = mode === 'cash'
                    ? 'Pembayaran tunai akan mengurangi akun 1101 Kas.'
                    : 'Transfer akan mengurangi akun bank/ewallet yang dipilih.';
            };

            method.addEventListener('change', syncAccounts);
            fill?.addEventListener('click', () => {
                amount.value = amount.dataset.max || '';
                amount.classList.remove('is-invalid');
            });
            form.addEventListener('submit', (event) => {
                const max = Number(amount.dataset.max || 0);
                const value = parseAmount(amount.value);
                if (value <= 0 || value > max + 0.01) {
                    event.preventDefault();
                    amount.classList.add('is-invalid');
                    amount.focus();
                }
            });
            syncAccounts();
        })();
    </script>
    @endpush
    @endif

    {{-- Link PO untuk non-owner yang tetap boleh lihat --}}
    @if ($order && !$canSeeMoney)
    <div class="card mb-3">
        <div class="card-body small">
            <span class="text-muted">Purchase Order terkait:</span>
            <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
               class="mono ms-1">{{ $order->code }}</a>
        </div>
    </div>
    @endif

</div>
@endsection
