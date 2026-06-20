@extends('layouts.app')

@section('title', 'Kekurangan Material')

@push('head')
<style>
    .ms-page { max-width: 1080px; margin-inline: auto; padding-bottom: 3rem; }
    .ms-summary { display: flex; gap: .4rem; flex-wrap: wrap; margin-top: .5rem; }
    .ms-summary-pill { display: inline-flex; align-items: center; gap: .35rem; padding: .22rem .6rem; border: 1px solid var(--line); border-radius: 999px; background: var(--card); font-size: .76rem; }
    .ms-summary-pill strong { font-weight: 800; }
    .ms-panel { background: var(--card); border: 1px solid var(--line); border-radius: 14px; overflow: hidden; }
    .ms-filter { padding: .8rem .9rem; margin-bottom: .8rem; }
    .ms-filter-grid { display: grid; grid-template-columns: minmax(220px, 2fr) repeat(4, minmax(130px, 1fr)) auto; gap: .5rem; align-items: end; }
    .ms-filter-result { color: var(--muted); font-size: .72rem; margin-top: .45rem; }
    .ms-selection-bar { padding: .7rem .85rem; border-bottom: 1px solid var(--line); display: flex; align-items: end; justify-content: space-between; gap: .75rem; background: rgba(148,163,184,.035); }
    .ms-selection-left { display: flex; align-items: center; gap: .6rem; min-width: 0; }
    .ms-selected-count { font-size: .8rem; font-weight: 750; }
    .ms-selection-actions { display: flex; align-items: end; gap: .5rem; }
    .ms-note-field { width: min(320px, 32vw); }
    .ms-table { margin: 0; }
    .ms-table th { padding: .7rem .85rem; color: var(--muted); font-size: .69rem; text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; }
    .ms-table td { padding: .72rem .85rem; vertical-align: middle; }
    .ms-material-name { font-weight: 750; line-height: 1.2; }
    .ms-material-code { color: var(--muted); font-size: .72rem; margin-top: .1rem; }
    .ms-source { color: var(--muted); font-size: .68rem; margin-top: .2rem; max-width: 390px; }
    .ms-num { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .ms-shortage { color: #b91c1c; font-weight: 800; }
    .ms-safe { color: #15803d; font-weight: 750; }
    .ms-incoming { font-size: .78rem; }
    .ms-row-selected { background: rgba(37,99,235,.035); }
    @media (max-width: 767.98px) {
        .ms-page { padding-inline: .75rem; }
        .ms-summary { flex-wrap: nowrap; overflow-x: auto; padding-bottom: .2rem; }
        .ms-summary-pill { flex: 0 0 auto; }
        .ms-selection-bar { align-items: stretch; flex-direction: column; }
        .ms-selection-actions { width: 100%; }
        .ms-note-field { width: 100%; flex: 1; }
        .ms-selection-actions .btn { flex: 0 0 auto; }
        .ms-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .ms-filter-search { grid-column: 1 / -1; }
        .ms-table thead { display: none; }
        .ms-table, .ms-table tbody, .ms-table tr, .ms-table td { display: block; width: 100%; }
        .ms-table tr { position: relative; padding: .7rem .8rem .65rem 2.7rem; border-bottom: 1px solid var(--line); }
        .ms-table td { border: 0; padding: .13rem 0; text-align: left !important; }
        .ms-table td:first-child { position: absolute; left: .8rem; top: .8rem; width: auto; }
        .ms-table td[data-label]::before { content: attr(data-label); display: inline-block; min-width: 105px; color: var(--muted); font-size: .67rem; font-weight: 700; text-transform: uppercase; }
    }
</style>
@endpush

@section('content')
<div class="ms-page py-3">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3 flex-wrap">
        <div>
            <h2 class="mb-0">Kekurangan Material</h2>
            <div class="text-muted small">Kebutuhan produksi setelah dikurangi stok RM, PR, dan PO yang masih berjalan.</div>
            <div class="ms-summary">
                <span class="ms-summary-pill">Material <strong>{{ $summary['materials'] }}</strong></span>
                <span class="ms-summary-pill text-danger">Kurang <strong>{{ $summary['shortage_count'] }}</strong></span>
                <span class="ms-summary-pill text-success">Aman <strong>{{ $summary['covered_count'] }}</strong></span>
                <span class="ms-summary-pill">PR berjalan <strong>{{ $summary['open_pr_count'] }}</strong></span>
            </div>
        </div>
        <div class="d-flex gap-1 flex-wrap">
            @if (Route::has('production.reconcile.index'))
                <a href="{{ route('production.reconcile.index') }}" class="btn btn-sm btn-outline-secondary">Audit Produksi</a>
            @endif
            <a href="{{ route('purchasing.purchase_requests.index') }}" class="btn btn-sm btn-outline-secondary">Purchase Requests</a>
        </div>
    </div>

    @if ($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif

    <div class="ms-panel ms-filter">
        <div class="ms-filter-grid">
            <div class="ms-filter-search">
                <label class="form-label small mb-1">Cari Material</label>
                <input type="search" id="ms-filter-search" value="{{ $q }}" class="form-control form-control-sm" placeholder="Nama atau kode material" autocomplete="off">
            </div>
            <div>
                <label class="form-label small mb-1">Status</label>
                <select class="form-select form-select-sm ms-realtime-filter" id="ms-status-filter">
                    <option value="shortage" @selected($status === 'shortage')>Yang masih kurang</option>
                    <option value="safe" @selected($status === 'safe')>Sudah aman</option>
                    <option value="all" @selected($status === 'all')>Semua material</option>
                </select>
            </div>
            <div>
                <label class="form-label small mb-1">Kebutuhan Untuk</label>
                <select class="form-select form-select-sm ms-realtime-filter" id="ms-source-filter">
                    <option value="all">Semua proses</option>
                    <option value="sewing">Jahit</option>
                    <option value="finishing">Finishing / packing</option>
                </select>
            </div>
            <div>
                <label class="form-label small mb-1">Stok RM</label>
                <select class="form-select form-select-sm ms-realtime-filter" id="ms-stock-filter">
                    <option value="all">Semua kondisi</option>
                    <option value="negative">Stok minus</option>
                    <option value="empty">Stok kosong</option>
                    <option value="available">Masih tersedia</option>
                </select>
            </div>
            <div>
                <label class="form-label small mb-1">PR / PO</label>
                <select class="form-select form-select-sm ms-realtime-filter" id="ms-incoming-filter">
                    <option value="all">Semua</option>
                    <option value="yes">Sudah diproses</option>
                    <option value="no">Belum diproses</option>
                </select>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="ms-reset-filter">Reset</button>
        </div>
        <div class="ms-filter-result" id="ms-filter-result"></div>
    </div>

    <form method="POST" action="{{ route('purchasing.material_shortages.purchase_request') }}" id="shortage-pr-form">
        @csrf
        <div class="ms-panel">
            @if ($rows->contains('has_shortage', true))
                <div class="ms-selection-bar">
                    <label class="ms-selection-left mb-0">
                        <input type="checkbox" class="form-check-input" id="check-all-shortage" checked>
                        <span class="ms-selected-count" id="ms-selected-count">0 material dipilih</span>
                    </label>
                    <div class="ms-selection-actions">
                        <div class="ms-note-field">
                            <label class="form-label small mb-1">Catatan <span class="text-muted">(opsional)</span></label>
                            <input name="notes" class="form-control form-control-sm" placeholder="Informasi tambahan untuk PR">
                        </div>
                        <button class="btn btn-sm btn-primary" id="btn-create-shortage-pr" type="submit">Buat PR</button>
                    </div>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-sm ms-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px"></th>
                            <th>Material</th>
                            <th class="text-end">Kebutuhan</th>
                            <th class="text-end">Stok RM</th>
                            <th class="text-end">Dalam Proses</th>
                            <th class="text-end">Kekurangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $sourceKeys = collect(array_keys($row->sources));
                                $sourceKinds = collect([
                                    $sourceKeys->contains(fn ($source) => str_contains($source, 'sewing_supply')) ? 'sewing' : null,
                                    $sourceKeys->contains(fn ($source) => str_contains($source, 'packing_supply')) ? 'finishing' : null,
                                ])->filter()->join(' ');
                                $stockState = $row->stock_qty < -0.000001 ? 'negative' : (abs($row->stock_qty) <= 0.000001 ? 'empty' : 'available');
                                $incomingState = ($row->open_pr_qty + $row->open_po_qty) > 0.000001 ? 'yes' : 'no';
                            @endphp
                            <tr class="ms-material-row {{ $row->has_shortage ? 'ms-row-selected' : '' }}"
                                data-search="{{ strtolower($row->code . ' ' . $row->name) }}"
                                data-status="{{ $row->has_shortage ? 'shortage' : 'safe' }}"
                                data-source="{{ $sourceKinds }}"
                                data-stock="{{ $stockState }}"
                                data-incoming="{{ $incomingState }}">
                                <td>@if ($row->has_shortage)<input class="form-check-input shortage-check" type="checkbox" name="item_ids[]" value="{{ $row->item_id }}" checked>@endif</td>
                                <td>
                                    <div class="ms-material-name">{{ $row->name }}</div>
                                    <div class="ms-material-code">{{ $row->code }}</div>
                                    @if (!empty($row->sources))
                                        <div class="ms-source">
                                            @foreach ($row->sources as $source => $qty)
                                                @php
                                                    $sourceLabel = match ($source) {
                                                        'WIP-CUT:sewing_supply' => 'Kelengkapan jahit',
                                                        'WIP-CUT:packing_supply' => 'Kelengkapan finishing',
                                                        'WIP-SEW:packing_supply' => 'Finishing dari proses jahit',
                                                        'WIP-FIN:packing_supply' => 'Menunggu finishing',
                                                        default => $source,
                                                    };
                                                @endphp
                                                {{ $sourceLabel }} {{ number_format($qty, 2, ',', '.') }}{{ !$loop->last ? ' · ' : '' }}
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td data-label="Kebutuhan" class="text-end ms-num">{{ number_format($row->required_qty, 2, ',', '.') }} {{ $row->unit }}</td>
                                <td data-label="Stok RM" class="text-end ms-num {{ $row->stock_qty < 0 ? 'text-danger fw-bold' : '' }}">{{ number_format($row->stock_qty, 2, ',', '.') }} {{ $row->unit }}</td>
                                <td data-label="Dalam Proses" class="text-end ms-incoming">
                                    <div>PR {{ number_format($row->open_pr_qty, 2, ',', '.') }}</div>
                                    <div class="text-muted">PO {{ number_format($row->open_po_qty, 2, ',', '.') }}</div>
                                </td>
                                <td data-label="Kekurangan" class="text-end ms-num">
                                    @if ($row->has_shortage)<span class="ms-shortage">{{ number_format($row->shortage_qty, 2, ',', '.') }} {{ $row->unit }}</span>@else<span class="ms-safe">Aman</span>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada kekurangan material pada filter ini.</td></tr>
                        @endforelse
                        <tr class="d-none" id="ms-no-filter-results"><td colspan="6" class="text-center text-muted py-4">Tidak ada material yang sesuai dengan filter.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = Array.from(document.querySelectorAll('.ms-material-row'));
    const checks = Array.from(document.querySelectorAll('.shortage-check'));
    const checkAll = document.getElementById('check-all-shortage');
    const button = document.getElementById('btn-create-shortage-pr');
    const countLabel = document.getElementById('ms-selected-count');
    const search = document.getElementById('ms-filter-search');
    const status = document.getElementById('ms-status-filter');
    const source = document.getElementById('ms-source-filter');
    const stock = document.getElementById('ms-stock-filter');
    const incoming = document.getElementById('ms-incoming-filter');
    const result = document.getElementById('ms-filter-result');
    const noResults = document.getElementById('ms-no-filter-results');

    function visibleChecks() {
        return checks.filter(check => !check.closest('tr')?.classList.contains('d-none'));
    }

    function syncSelection() {
        const selected = checks.filter(check => check.checked);
        const visible = visibleChecks();
        const visibleSelected = visible.filter(check => check.checked);
        checks.forEach(check => check.closest('tr')?.classList.toggle('ms-row-selected', check.checked));
        if (countLabel) countLabel.textContent = `${selected.length} material dipilih`;
        if (button) {
            button.disabled = selected.length === 0;
            button.textContent = selected.length ? `Buat PR (${selected.length})` : 'Pilih material';
        }
        if (checkAll) {
            checkAll.checked = visible.length > 0 && visibleSelected.length === visible.length;
            checkAll.indeterminate = visibleSelected.length > 0 && visibleSelected.length < visible.length;
        }
    }

    function applyFilters(resetSelection = true) {
        const query = search.value.trim().toLowerCase();
        let visibleCount = 0;

        rows.forEach(row => {
            const matchesSearch = !query || row.dataset.search.includes(query);
            const matchesStatus = status.value === 'all' || row.dataset.status === status.value;
            const matchesSource = source.value === 'all' || row.dataset.source.split(' ').includes(source.value);
            const matchesStock = stock.value === 'all' || row.dataset.stock === stock.value;
            const matchesIncoming = incoming.value === 'all' || row.dataset.incoming === incoming.value;
            const visible = matchesSearch && matchesStatus && matchesSource && matchesStock && matchesIncoming;

            row.classList.toggle('d-none', !visible);
            if (visible) visibleCount++;

            const checkbox = row.querySelector('.shortage-check');
            if (checkbox && resetSelection) checkbox.checked = visible;
        });

        if (result) result.textContent = `${visibleCount} material ditampilkan`;
        noResults?.classList.toggle('d-none', visibleCount !== 0);
        syncSelection();
    }

    checks.forEach(check => check.addEventListener('change', syncSelection));
    checkAll?.addEventListener('change', function () {
        visibleChecks().forEach(check => check.checked = checkAll.checked);
        syncSelection();
    });

    let searchTimer;
    search?.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => applyFilters(true), 100);
    });
    document.querySelectorAll('.ms-realtime-filter').forEach(filter => {
        filter.addEventListener('change', () => applyFilters(true));
    });
    document.getElementById('ms-reset-filter')?.addEventListener('click', function () {
        search.value = '';
        status.value = 'shortage';
        source.value = 'all';
        stock.value = 'all';
        incoming.value = 'all';
        applyFilters(true);
        search.focus();
    });

    applyFilters(true);
});
</script>
@endpush
