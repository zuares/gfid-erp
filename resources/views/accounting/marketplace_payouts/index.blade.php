@extends('layouts.app')

@section('title', 'Accounting • Penerimaan Marketplace')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $statusLabels = ['draft' => 'Draft', 'posted' => 'Tercatat', 'void' => 'Dibatalkan'];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .mp-page { display: grid; gap: 1rem; }
        .mp-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            min-height: 40px; padding: .55rem .95rem; border-radius: 999px;
            border: 1px solid rgba(15,23,42,.10); background: #fff;
            color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850;
        }
        .mp-btn:hover { background: #f8fafc; color: #0f172a; }
        .mp-btn-primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .mp-btn-primary:hover { background: #1e293b; color: #fff; }
        .mp-kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: .75rem; }
        .mp-kpi { border: 1px solid rgba(15,23,42,.08); border-radius: 12px; background: #fff; padding: .85rem .95rem; }
        .mp-kpi-label { color: #64748b; font-size: .68rem; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; }
        .mp-kpi-value { margin-top: .18rem; color: #0f172a; font-size: 1.25rem; font-weight: 950; line-height: 1.15; }
        .mp-kpi-note { margin-top: .2rem; color: #94a3b8; font-size: .74rem; }
        .mp-filter {
            display: grid;
            grid-template-columns: minmax(120px,.7fr) minmax(130px,.7fr) minmax(140px,.8fr) minmax(140px,.8fr) auto;
            gap: .55rem; align-items: end;
        }
        .mp-filter .form-control, .mp-filter .form-select {
            min-height: 40px; border-radius: 999px; border-color: rgba(15,23,42,.12);
            font-size: .84rem; font-weight: 700; box-shadow: none;
        }
        .mp-table-wrap { max-height: calc(100vh - 340px); overflow: auto; }
        .mp-table th, .mp-table td { vertical-align: middle; }
        .mp-click-row { cursor: pointer; }
        .mp-click-row:hover td { background: #f8fafc; }
        .mp-status {
            display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px;
            padding: .22rem .6rem; font-size: .74rem; font-weight: 850;
            border: 1px solid transparent; white-space: nowrap;
        }
        .mp-status::before { content: ''; width: 7px; height: 7px; border-radius: 999px; background: currentColor; }
        .mp-status-draft  { color: #b45309; background: #fef3c7; border-color: #fde68a; }
        .mp-status-posted { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
        .mp-status-void   { color: #b91c1c; background: #fee2e2; border-color: #fecaca; }
        .mp-num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 900; color: #0f172a; }
        .mp-empty { text-align: center; color: #64748b; padding: 2.4rem 1rem; }
        @media (max-width: 768px) {
            .mp-kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .mp-filter { grid-template-columns: 1fr 1fr; }
        }
    </style>
@endpush

@section('content')
<div class="mp-page">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="mb-0 fw-black">Penerimaan Marketplace</h5>
            <div class="text-muted" style="font-size:.8rem">Pencatatan disbursement / settlement dari marketplace</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($shopeeStores->isNotEmpty())
                <button type="button" class="mp-btn" data-bs-toggle="modal" data-bs-target="#importShopeeModal">
                    Import Shopee
                </button>
            @endif
            <a href="{{ route('accounting.marketplace-payouts.create') }}" class="mp-btn mp-btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                Tambah Penerimaan
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('message'))
        <div class="alert alert-{{ session('status') === 'ok' ? 'success' : 'danger' }} py-2 mb-0">
            {{ session('message') }}
        </div>
    @endif

    {{-- KPI --}}
    <div class="mp-kpi-grid">
        <div class="mp-kpi">
            <div class="mp-kpi-label">Total Dokumen</div>
            <div class="mp-kpi-value">{{ $summary['total_docs'] }}</div>
        </div>
        <div class="mp-kpi">
            <div class="mp-kpi-label">Total Nilai</div>
            <div class="mp-kpi-value">Rp {{ $fmt($summary['total_amount']) }}</div>
        </div>
        <div class="mp-kpi">
            <div class="mp-kpi-label">Sudah Diposting</div>
            <div class="mp-kpi-value">Rp {{ $fmt($summary['posted_amount']) }}</div>
            <div class="mp-kpi-note">Masuk ke jurnal</div>
        </div>
        <div class="mp-kpi">
            <div class="mp-kpi-label">Draft / Batal</div>
            <div class="mp-kpi-value">{{ $summary['draft_docs'] }} / {{ $summary['void_docs'] }}</div>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" class="mp-filter">
        <div>
            <label class="form-label fw-bold" style="font-size:.75rem">Status</label>
            <select name="status" class="form-select">
                <option value="">Semua</option>
                @foreach(['draft'=>'Draft','posted'=>'Tercatat','void'=>'Dibatalkan'] as $val => $label)
                    <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label fw-bold" style="font-size:.75rem">Marketplace</label>
            <select name="marketplace" class="form-select">
                <option value="">Semua</option>
                @foreach($marketplaceNames as $name)
                    <option value="{{ $name }}" @selected(request('marketplace') === $name)>{{ $name }}</option>
                @endforeach
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
        <div class="d-flex gap-2 align-items-end">
            <button type="submit" class="mp-btn mp-btn-primary">Filter</button>
            @if(request()->hasAny(['status','marketplace','from','to']))
                <a href="{{ route('accounting.marketplace-payouts.index') }}" class="mp-btn">Reset</a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="card border-0 shadow-sm p-0">
        <div class="mp-table-wrap">
            <table class="table table-sm mp-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Marketplace</th>
                        <th>Toko</th>
                        <th>Referensi</th>
                        <th>Akun Bank</th>
                        <th class="text-end">Jumlah</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payouts as $p)
                        <tr class="mp-click-row" onclick="location.href='{{ route('accounting.marketplace-payouts.show', $p) }}'">
                            <td>
                                <div style="font-weight:800; font-variant-numeric:tabular-nums">
                                    {{ $p->date->format('d M Y') }}
                                </div>
                            </td>
                            <td style="font-weight:850">{{ $p->marketplace_name }}</td>
                            <td>
                                <div style="font-weight:800">{{ $p->store?->name ?? '-' }}</div>
                                @if($p->store?->code)
                                    <div style="color:#94a3b8; font-size:.74rem">{{ $p->store->code }}</div>
                                @endif
                            </td>
                            <td style="color:#64748b; font-size:.82rem">{{ $p->reference ?: '-' }}</td>
                            <td>
                                <div style="font-weight:760">{{ $p->bankAccount?->name ?? '-' }}</div>
                                <div style="color:#94a3b8; font-size:.74rem">{{ $p->bankAccount?->code }}</div>
                            </td>
                            <td class="mp-num">Rp {{ $fmt($p->amount) }}</td>
                            <td>
                                <span class="mp-status mp-status-{{ $p->status }}">
                                    {{ $statusLabels[$p->status] ?? $p->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="mp-empty">Belum ada penerimaan marketplace.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $payouts->withQueryString()->links() }}

</div>

@if($shopeeStores->isNotEmpty())
<div class="modal fade" id="importShopeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Import Pencairan Shopee</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('accounting.marketplace-payouts.import-shopee') }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info py-2" style="font-size:.8rem">
                        Pilih satu atau beberapa toko. Setiap toko memakai akun bank tujuannya sendiri.
                        Hanya transaksi pencairan wallet Shopee yang diimpor sebagai Draft.
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-bold mb-0" style="font-size:.8rem">Toko Shopee</label>
                            <button type="button" class="btn btn-link btn-sm p-0" id="selectAllShopeeStores">
                                Pilih semua toko
                            </button>
                        </div>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:42px"></th>
                                        <th>Toko</th>
                                        <th style="min-width:220px">Akun Bank Tujuan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($shopeeStores as $store)
                                        <tr>
                                            <td>
                                                <input
                                                    class="form-check-input shopee-store-toggle"
                                                    type="checkbox"
                                                    name="stores[{{ $store->id }}][enabled]"
                                                    value="1"
                                                    @checked($loop->first)
                                                >
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $store->name }}</div>
                                                @if($store->code)
                                                    <div class="text-muted" style="font-size:.72rem">{{ $store->code }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <select
                                                    name="stores[{{ $store->id }}][bank_account_id]"
                                                    class="form-select form-select-sm shopee-store-bank"
                                                >
                                                    <option value="">-- Pilih Akun --</option>
                                                    @foreach($bankAccounts as $acc)
                                                        <option value="{{ $acc->id }}">{{ $acc->code }} – {{ $acc->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="text-muted mt-2" style="font-size:.74rem">
                            Transaksi yang sama pada toko yang sama tetap tidak dibuat dua kali. Jika akun bank berbeda,
                            transaksi akan dilewati dan dilaporkan sebagai konflik akun bank.
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold" style="font-size:.8rem">Dari</label>
                            <input type="date" name="from" class="form-control" value="{{ now()->subDays(14)->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold" style="font-size:.8rem">Sampai</label>
                            <input type="date" name="to" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="mp-btn" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="mp-btn mp-btn-primary">Ambil dari Shopee</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('importShopeeModal');
            if (!modal) return;

            const syncStoreRow = function (checkbox) {
                const bankSelect = checkbox.closest('tr')?.querySelector('.shopee-store-bank');
                if (bankSelect) bankSelect.disabled = !checkbox.checked;
            };

            const toggles = Array.from(modal.querySelectorAll('.shopee-store-toggle'));
            toggles.forEach(function (checkbox) {
                syncStoreRow(checkbox);
                checkbox.addEventListener('change', function () {
                    syncStoreRow(checkbox);
                });
            });

            document.getElementById('selectAllShopeeStores')?.addEventListener('click', function () {
                const shouldSelectAll = toggles.some(function (checkbox) {
                    return !checkbox.checked;
                });

                toggles.forEach(function (checkbox) {
                    checkbox.checked = shouldSelectAll;
                    syncStoreRow(checkbox);
                });
            });
        });
    </script>
@endpush
@endsection
