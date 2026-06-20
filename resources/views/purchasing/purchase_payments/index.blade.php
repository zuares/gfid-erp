@extends('layouts.app')

@section('title', 'Pembayaran Supplier')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $typeLabels = ['dp' => 'DP', 'payment' => 'Pelunasan', 'dp_apply' => 'Offset DP'];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .pp-page { display: grid; gap: 1rem; }
        .pp-btn {
            display: inline-flex; align-items: center; gap: .45rem;
            min-height: 40px; padding: .55rem .95rem; border-radius: 999px;
            border: 1px solid rgba(15,23,42,.10); background: #fff;
            color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850;
            cursor: pointer;
        }
        .pp-btn:hover { background: #f8fafc; color: #0f172a; }
        .pp-btn-primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .pp-btn-primary:hover { background: #1e293b; color: #fff; }
        .pp-kpi-grid { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: .75rem; }
        .pp-kpi { border: 1px solid rgba(15,23,42,.08); border-radius: 12px; background: #fff; padding: .85rem .95rem; }
        .pp-kpi-label { color: #64748b; font-size: .68rem; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; }
        .pp-kpi-value { margin-top: .18rem; color: #0f172a; font-size: 1.25rem; font-weight: 950; line-height: 1.15; }
        .pp-kpi-note { margin-top: .2rem; color: #94a3b8; font-size: .74rem; }
        .pp-filter {
            display: grid;
            grid-template-columns: minmax(160px,1.2fr) minmax(110px,.7fr) minmax(130px,.8fr) minmax(130px,.8fr) minmax(110px,.7fr) auto;
            gap: .55rem; align-items: end;
        }
        .pp-filter .form-control, .pp-filter .form-select {
            min-height: 40px; border-radius: 999px; border-color: rgba(15,23,42,.12);
            font-size: .84rem; font-weight: 700; box-shadow: none;
        }
        .pp-table-wrap { max-height: calc(100vh - 340px); overflow: auto; }
        .pp-table th, .pp-table td { vertical-align: middle; }
        .pp-click-row { cursor: pointer; }
        .pp-click-row:hover td { background: #f8fafc; }
        .pp-badge {
            display: inline-flex; align-items: center; border-radius: 999px;
            padding: .18rem .55rem; font-size: .73rem; font-weight: 850; white-space: nowrap;
        }
        .pp-badge-dp       { background: #eff6ff; color: #1d4ed8; }
        .pp-badge-payment  { background: #dcfce7; color: #166534; }
        .pp-badge-dp_apply { background: #f3e8ff; color: #7e22ce; }
        .pp-badge-voided   { background: #fee2e2; color: #b91c1c; }
        .pp-num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 900; }
        .pp-empty { text-align: center; color: #64748b; padding: 2.4rem 1rem; }

        /* Modal */
        .pp-modal-label { font-size: .8rem; font-weight: 850; color: #334155; margin-bottom: .3rem; }
        .pp-modal .form-control, .pp-modal .form-select { border-radius: 8px; font-size: .88rem; }
        .pp-po-card {
            border: 1px solid rgba(15,23,42,.10); border-radius: 10px; padding: .75rem 1rem;
            cursor: pointer; transition: border-color .15s, background .15s;
        }
        .pp-po-card:hover, .pp-po-card.selected { border-color: #0f172a; background: #f8fafc; }
        .pp-po-card.selected { border-color: #2563eb; background: #eff6ff; }
        .pp-po-outstanding { font-variant-numeric: tabular-nums; font-weight: 900; color: #dc2626; }

        @media (max-width: 768px) {
            .pp-kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .pp-filter { grid-template-columns: 1fr 1fr; }
        }
    </style>
@endpush

@section('content')
<div class="pp-page">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="mb-0 fw-black">Pembayaran Supplier</h5>
            <div class="text-muted" style="font-size:.8rem">Jurnal: Dr 2101 Hutang Dagang / Cr Bank</div>
        </div>
        <button class="pp-btn pp-btn-primary" data-bs-toggle="modal" data-bs-target="#modalBayar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Bayar Supplier
        </button>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="alert alert-success py-2 mb-0">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger py-2 mb-0">{{ session('error') }}</div>
    @endif

    {{-- KPI --}}
    <div class="pp-kpi-grid">
        <div class="pp-kpi">
            <div class="pp-kpi-label">Total Transaksi</div>
            <div class="pp-kpi-value">{{ $summary['count'] }}</div>
            <div class="pp-kpi-note">periode filter aktif</div>
        </div>
        <div class="pp-kpi">
            <div class="pp-kpi-label">Total Pelunasan</div>
            <div class="pp-kpi-value">Rp {{ $fmt($summary['total_payment']) }}</div>
        </div>
        <div class="pp-kpi">
            <div class="pp-kpi-label">Total DP</div>
            <div class="pp-kpi-value">Rp {{ $fmt($summary['total_dp']) }}</div>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" class="pp-filter">
        <div>
            <label class="form-label fw-bold" style="font-size:.75rem">Supplier</label>
            <select name="supplier_id" class="form-select">
                <option value="">Semua</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label fw-bold" style="font-size:.75rem">Tipe</label>
            <select name="type" class="form-select">
                <option value="">Semua</option>
                <option value="payment" @selected(request('type') === 'payment')>Pelunasan</option>
                <option value="dp"      @selected(request('type') === 'dp')>DP</option>
                <option value="dp_apply" @selected(request('type') === 'dp_apply')>Offset DP</option>
            </select>
        </div>
        <div>
            <label class="form-label fw-bold" style="font-size:.75rem">Dari</label>
            <input type="date" name="from" class="form-control" value="{{ request('from') }}">
        </div>
        <div>
            <label class="form-label fw-bold" style="font-size:.75rem">Sampai</label>
            <input type="date" name="to" class="form-control" value="{{ request('to') }}">
        </div>
        <div>
            <label class="form-label fw-bold" style="font-size:.75rem">Status</label>
            <select name="voided" class="form-select">
                <option value="no" @selected(request('voided','no') === 'no')>Aktif</option>
                <option value="yes" @selected(request('voided') === 'yes')>Void</option>
            </select>
        </div>
        <div class="d-flex gap-2 align-items-end">
            <button type="submit" class="pp-btn pp-btn-primary">Filter</button>
            @if(request()->hasAny(['supplier_id','type','from','to','voided']))
                <a href="{{ route('purchasing.purchase_payments.index') }}" class="pp-btn">Reset</a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="card border-0 shadow-sm p-0">
        <div class="pp-table-wrap">
            <table class="table table-sm pp-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Supplier</th>
                        <th>PO</th>
                        <th>Tipe</th>
                        <th>Metode</th>
                        <th>Akun</th>
                        <th class="text-end">Jumlah</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $pay)
                        <tr class="{{ $pay->voided_at ? 'text-muted' : '' }}">
                            <td style="font-weight:800; font-variant-numeric:tabular-nums; white-space:nowrap">
                                {{ \Carbon\Carbon::parse($pay->date)->format('d M Y') }}
                            </td>
                            <td style="font-weight:760">
                                {{ $pay->purchaseOrder?->supplier?->name ?? '-' }}
                            </td>
                            <td>
                                <a href="{{ route('purchasing.purchase_orders.show', $pay->purchaseOrder) }}"
                                   style="font-size:.82rem; color:#2563eb; text-decoration:none; font-weight:700">
                                    {{ $pay->purchaseOrder?->code ?? '-' }}
                                </a>
                            </td>
                            <td>
                                <span class="pp-badge pp-badge-{{ $pay->voided_at ? 'voided' : $pay->type }}">
                                    {{ $pay->voided_at ? 'VOID' : ($typeLabels[$pay->type] ?? $pay->type) }}
                                </span>
                            </td>
                            <td style="font-size:.82rem">{{ $pay->paymentMethod?->name ?? '-' }}</td>
                            <td style="font-size:.82rem; color:#64748b">{{ $pay->cashAccount?->name ?? '-' }}</td>
                            <td class="pp-num {{ $pay->voided_at ? '' : 'text-dark' }}">
                                Rp {{ $fmt($pay->amount) }}
                            </td>
                            <td>
                                @if(!$pay->voided_at)
                                    <form method="POST"
                                          action="{{ route('purchasing.purchase_orders.payments.void', [$pay->purchaseOrder, $pay]) }}"
                                          onsubmit="return confirm('VOID pembayaran ini?')">
                                        @csrf
                                        <button type="submit" class="pp-btn"
                                            style="min-height:30px; padding:.2rem .6rem; font-size:.75rem; color:#dc2626; border-color:#fca5a5">
                                            Void
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="pp-empty">Belum ada pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $payments->withQueryString()->links() }}

</div>

{{-- Modal Bayar --}}
<div class="modal fade pp-modal" id="modalBayar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-black">Bayar Supplier</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="display:grid; gap:1rem">

                {{-- Step 1: Pilih PO --}}
                <div>
                    <label class="pp-modal-label">Pilih PO yang akan dibayar</label>
                    <input type="text" id="poSearch" class="form-control mb-2" placeholder="Cari kode PO atau nama supplier...">
                    <div id="poList" style="display:grid; gap:.5rem; max-height:240px; overflow:auto">
                        @forelse($openPos as $po)
                            @php
                                $outstanding = max(0, (float)$po->grand_total - (float)$po->paid_amount);
                            @endphp
                            <div class="pp-po-card" data-po-id="{{ $po->id }}"
                                 data-po-code="{{ $po->code }}"
                                 data-supplier="{{ $po->supplier?->name }}"
                                 data-outstanding="{{ $outstanding }}"
                                 onclick="selectPo(this)">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div style="font-weight:850; font-size:.88rem">{{ $po->code }}</div>
                                        <div style="font-size:.78rem; color:#64748b">{{ $po->supplier?->name }} · {{ \Carbon\Carbon::parse($po->date)->format('d M Y') }}</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="pp-po-outstanding" style="font-size:.9rem">Rp {{ $fmt($outstanding) }}</div>
                                        <div style="font-size:.72rem; color:#94a3b8">outstanding</div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="pp-empty" style="padding:1rem">Tidak ada PO dengan hutang outstanding.</div>
                        @endforelse
                    </div>
                </div>

                {{-- Step 2: Form Bayar --}}
                <form id="payForm" method="POST" action="" style="display:none">
                    @csrf
                    <input type="hidden" name="type" value="payment">
                    <div style="background:#f8fafc; border-radius:10px; padding:.85rem 1rem; margin-bottom:.75rem">
                        <div id="selectedPoInfo" style="font-size:.85rem; font-weight:760; color:#0f172a"></div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:.75rem">
                        <div>
                            <label class="pp-modal-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div>
                            <label class="pp-modal-label">Jumlah <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-radius:8px 0 0 8px; font-size:.82rem; font-weight:700">Rp</span>
                                <input type="text" name="amount" id="payAmount" class="form-control" placeholder="0"
                                       style="border-radius:0 8px 8px 0" required>
                            </div>
                        </div>
                        <div>
                            <label class="pp-modal-label">Metode Bayar <span class="text-danger">*</span></label>
                            <select name="payment_method_id" id="payMethod" class="form-select" required onchange="updateCashAccount(this)">
                                <option value="">-- Pilih --</option>
                                @foreach($paymentMethods->whereIn('mode', ['cash','transfer']) as $pm)
                                    <option value="{{ $pm->id }}" data-mode="{{ $pm->mode }}"
                                            data-default-account="{{ $pm->default_cash_account_id }}">
                                        {{ $pm->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="pp-modal-label">Bayar dari Akun <span class="text-danger">*</span></label>
                            <select name="cash_account_id" id="payCashAccount" class="form-select" required>
                                <option value="">-- Pilih akun --</option>
                                @foreach($cashAccounts as $acc)
                                    <option value="{{ $acc->id }}" data-code="{{ $acc->code }}">
                                        {{ $acc->code }} – {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="pp-modal-label">No. Referensi</label>
                            <input type="text" name="ref_no" class="form-control" placeholder="opsional">
                        </div>
                        <div>
                            <label class="pp-modal-label">Catatan</label>
                            <input type="text" name="notes" class="form-control" placeholder="opsional">
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-end mt-3 pt-3 border-top">
                        <button type="button" class="pp-btn" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="pp-btn pp-btn-primary">Simpan Pembayaran</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let selectedPoId = null;
const fmt = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');

function selectPo(el) {
    document.querySelectorAll('.pp-po-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    selectedPoId = el.dataset.poId;

    const route = '{{ url("/purchasing/purchase-orders") }}/' + selectedPoId + '/payments';
    document.getElementById('payForm').action = route;

    const outstanding = parseFloat(el.dataset.outstanding);
    document.getElementById('payAmount').value = Math.round(outstanding);
    document.getElementById('selectedPoInfo').innerHTML =
        '<strong>' + el.dataset.poCode + '</strong> — ' + el.dataset.supplier +
        ' &nbsp;|&nbsp; Outstanding: <span style="color:#dc2626;font-weight:900">' + fmt(outstanding) + '</span>';

    document.getElementById('payForm').style.display = 'block';
}

function updateCashAccount(sel) {
    const opt = sel.selectedOptions[0];
    const defaultId = opt?.dataset.defaultAccount;
    if (defaultId) {
        document.getElementById('payCashAccount').value = defaultId;
    }
}

// Search PO
document.getElementById('poSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.pp-po-card').forEach(card => {
        const text = (card.dataset.poCode + ' ' + card.dataset.supplier).toLowerCase();
        card.style.display = text.includes(q) ? '' : 'none';
    });
});
</script>
@endpush

@endsection
