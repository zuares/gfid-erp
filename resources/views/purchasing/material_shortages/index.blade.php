@extends('layouts.app')

@section('title', 'Kekurangan Material')

@push('head')
<style>
  .page-wrap { max-width:1080px; margin-inline:auto; padding-bottom:3rem; }
  .mono { font-variant-numeric:tabular-nums; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono"; }

  /* Cards */
  .card-filter {
    background:var(--card); border:1px solid var(--line); border-radius:14px;
    padding:.75rem .95rem; margin-bottom:.85rem;
  }
  .card-table { background:var(--card); border:1px solid var(--line); border-radius:14px; overflow:hidden; }

  /* Table */
  .table thead th {
    border-bottom:1px solid var(--line);
    font-size:.72rem; text-transform:uppercase; letter-spacing:.07em;
    color:var(--muted); padding:.5rem .75rem; white-space:nowrap;
  }
  .table tbody td { vertical-align:middle; font-size:.83rem; padding:.45rem .75rem; }
  .table tbody tr:last-child td { border-bottom:none; }
  .ms-row:hover { background:rgba(59,130,246,.04); }

  /* Summary pills */
  .ms-pill {
    display:inline-flex; align-items:center; gap:.3rem;
    padding:.22rem .65rem; border:1px solid var(--line); border-radius:999px;
    font-size:.76rem; background:var(--card); white-space:nowrap;
  }
  .ms-pill strong { font-weight:700; }
  .ms-pill.danger { color:#b91c1c; border-color:rgba(220,38,38,.35); background:rgba(220,38,38,.05); }
  .ms-pill.success { color:#15803d; border-color:rgba(22,163,74,.35); background:rgba(22,163,74,.05); }

  /* Selection bar */
  .ms-sel-bar {
    padding:.6rem .85rem; border-bottom:1px solid var(--line);
    display:flex; align-items:center; justify-content:space-between; gap:.65rem;
    background:rgba(37,99,235,.03); flex-wrap:wrap;
  }
  .ms-sel-left { display:flex; align-items:center; gap:.55rem; }
  .ms-sel-right { display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; }

  /* Cell helpers */
  .ms-name { font-weight:650; line-height:1.2; }
  .ms-code { color:var(--muted); font-size:.72rem; margin-top:.1rem; }
  .ms-source { color:var(--muted); font-size:.7rem; margin-top:.18rem; max-width:360px; }
  .ms-num { font-variant-numeric:tabular-nums; white-space:nowrap; }
  .ms-shortage { color:#b91c1c; font-weight:700; }
  .ms-safe { color:#15803d; font-weight:600; }
  .ms-in { font-size:.78rem; line-height:1.5; }

  /* Filter result */
  .filter-result { color:var(--muted); font-size:.76rem; margin-top:.45rem; }

  @media (max-width:767.98px){
    .page-wrap { padding-inline:.75rem; }
    .card-filter { padding:.7rem .8rem; }
    .table thead { display:none; }
    .table, .table tbody, .table tr, .table td { display:block; width:100%; }
    .table tr { position:relative; padding:.7rem .8rem .65rem 2.7rem; border-bottom:1px solid var(--line); }
    .table td { border:0; padding:.12rem 0; text-align:left !important; }
    .table td:first-child { position:absolute; left:.75rem; top:.75rem; width:auto; }
    .table td[data-label]::before { content:attr(data-label); display:inline-block; min-width:100px; color:var(--muted); font-size:.67rem; font-weight:700; text-transform:uppercase; }
    .ms-sel-bar { flex-direction:column; align-items:stretch; }
    .ms-sel-right { width:100%; }
  }
</style>
@endpush

@section('content')
<div class="page-wrap py-3">

  {{-- HEADER --}}
  <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
    <div>
      <h2 class="mb-0">Kekurangan Material</h2>
      <div class="text-muted small">Kebutuhan produksi setelah dikurangi stok RM, PR, dan PO yang masih berjalan.</div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
      @if (Route::has('production.reconcile.index'))
        <a href="{{ route('production.reconcile.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-clipboard-data me-1"></i>Audit Produksi
        </a>
      @endif
      <a href="{{ route('purchasing.purchase_requests.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-list-ul me-1"></i>Purchase Requests
      </a>
    </div>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger py-2 small mb-3">{{ $errors->first() }}</div>
  @endif

  {{-- FILTER CARD --}}
  <div class="card-filter">
    {{-- Summary pills --}}
    <div class="d-flex flex-wrap gap-1 mb-2">
      <span class="ms-pill">Material <strong>{{ $summary['materials'] }}</strong></span>
      <span class="ms-pill danger">Kurang <strong>{{ $summary['shortage_count'] }}</strong></span>
      <span class="ms-pill success">Aman <strong>{{ $summary['covered_count'] }}</strong></span>
      <span class="ms-pill">PR berjalan <strong>{{ $summary['open_pr_count'] }}</strong></span>
    </div>

    {{-- Filter inputs --}}
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <input type="search" id="ms-search" value="{{ $q }}"
             class="form-control form-control-sm" style="max-width:175px;"
             placeholder="Cari material…" autocomplete="off" />

      <select class="form-select form-select-sm ms-filter" id="ms-status" style="max-width:155px;">
        <option value="shortage" @selected($status === 'shortage')>Yang masih kurang</option>
        <option value="safe"     @selected($status === 'safe')>Sudah aman</option>
        <option value="all"      @selected($status === 'all')>Semua material</option>
      </select>

      <select class="form-select form-select-sm ms-filter" id="ms-source" style="max-width:145px;">
        <option value="all">Semua proses</option>
        <option value="sewing">Jahit</option>
        <option value="finishing">Finishing</option>
      </select>

      <select class="form-select form-select-sm ms-filter" id="ms-stock" style="max-width:140px;">
        <option value="all">Semua stok</option>
        <option value="negative">Stok minus</option>
        <option value="empty">Stok kosong</option>
        <option value="available">Masih ada</option>
      </select>

      <select class="form-select form-select-sm ms-filter" id="ms-incoming" style="max-width:130px;">
        <option value="all">Semua PR/PO</option>
        <option value="yes">Sudah diproses</option>
        <option value="no">Belum diproses</option>
      </select>

      <button type="button" class="btn btn-sm btn-outline-secondary" id="ms-reset"
              style="font-size:.78rem;padding:.25rem .65rem;">
        <i class="bi bi-x me-1"></i>Reset
      </button>
    </div>

    <div class="filter-result" id="ms-result"></div>
  </div>

  {{-- TABLE --}}
  <form method="POST" action="{{ route('purchasing.material_shortages.purchase_request') }}" id="shortage-form">
    @csrf
    <div class="card-table">

      {{-- Selection bar (only if shortages exist) --}}
      @if ($rows->contains('has_shortage', true))
        <div class="ms-sel-bar">
          <label class="ms-sel-left mb-0">
            <input type="checkbox" class="form-check-input" id="check-all" checked>
            <span class="small fw-semibold" id="ms-count">0 material dipilih</span>
          </label>
          <div class="ms-sel-right">
            <input name="notes" class="form-control form-control-sm"
                   style="min-width:220px;" placeholder="Catatan PR (opsional)">
            <button class="btn btn-primary btn-sm" id="btn-buat-pr" type="submit">
              <i class="bi bi-plus me-1"></i>Buat PR
            </button>
          </div>
        </div>
      @endif

      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th style="width:38px;"></th>
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
                $sourceKeys  = collect(array_keys($row->sources));
                $sourceKinds = collect([
                    $sourceKeys->contains(fn ($s) => str_contains($s, 'sewing_supply'))  ? 'sewing'    : null,
                    $sourceKeys->contains(fn ($s) => str_contains($s, 'packing_supply')) ? 'finishing' : null,
                ])->filter()->join(' ');
                $stockState    = $row->stock_qty < -0.000001 ? 'negative' : (abs($row->stock_qty) <= 0.000001 ? 'empty' : 'available');
                $incomingState = ($row->open_pr_qty + $row->open_po_qty) > 0.000001 ? 'yes' : 'no';
              @endphp
              <tr class="ms-row {{ $row->has_shortage ? 'ms-row-selected' : '' }}"
                  data-search="{{ strtolower($row->code . ' ' . $row->name) }}"
                  data-status="{{ $row->has_shortage ? 'shortage' : 'safe' }}"
                  data-source="{{ $sourceKinds }}"
                  data-stock="{{ $stockState }}"
                  data-incoming="{{ $incomingState }}">
                <td>
                  @if ($row->has_shortage)
                    <input class="form-check-input ms-check" type="checkbox"
                           name="item_ids[]" value="{{ $row->item_id }}" checked>
                  @endif
                </td>
                <td>
                  <div class="ms-name">{{ $row->name }}</div>
                  <div class="ms-code mono">{{ $row->code }}</div>
                  @if (!empty($row->sources))
                    <div class="ms-source">
                      @foreach ($row->sources as $source => $qty)
                        @php
                          $label = match ($source) {
                            'WIP-CUT:sewing_supply'  => 'Kelengkapan jahit',
                            'WIP-CUT:packing_supply' => 'Kelengkapan finishing',
                            'WIP-SEW:packing_supply' => 'Finishing dari proses jahit',
                            'WIP-FIN:packing_supply' => 'Menunggu finishing',
                            default => $source,
                          };
                        @endphp
                        {{ $label }} {{ number_format($qty, 2, ',', '.') }}{{ !$loop->last ? ' · ' : '' }}
                      @endforeach
                    </div>
                  @endif
                </td>
                <td data-label="Kebutuhan" class="text-end ms-num">
                  {{ number_format($row->required_qty, 2, ',', '.') }} {{ $row->unit }}
                </td>
                <td data-label="Stok RM" class="text-end ms-num {{ $row->stock_qty < 0 ? 'text-danger fw-bold' : '' }}">
                  {{ number_format($row->stock_qty, 2, ',', '.') }} {{ $row->unit }}
                </td>
                <td data-label="Dalam Proses" class="text-end ms-in">
                  <div>PR {{ number_format($row->open_pr_qty, 2, ',', '.') }}</div>
                  <div class="text-muted">PO {{ number_format($row->open_po_qty, 2, ',', '.') }}</div>
                </td>
                <td data-label="Kekurangan" class="text-end ms-num">
                  @if ($row->has_shortage)
                    <span class="ms-shortage">{{ number_format($row->shortage_qty, 2, ',', '.') }} {{ $row->unit }}</span>
                  @else
                    <span class="ms-safe">Aman</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr id="ms-empty-db">
                <td colspan="6" class="text-center text-muted py-4">
                  Tidak ada kekurangan material.
                </td>
              </tr>
            @endforelse

            {{-- JS-controlled: hidden when rows exist in DB, shown when JS filter returns 0 --}}
            @if ($rows->isNotEmpty())
              <tr class="d-none" id="ms-empty-filter">
                <td colspan="6" class="text-center text-muted py-4">
                  Tidak ada material yang sesuai dengan filter.
                </td>
              </tr>
            @endif
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
    const rows     = Array.from(document.querySelectorAll('.ms-row'));
    const checks   = Array.from(document.querySelectorAll('.ms-check'));
    const checkAll = document.getElementById('check-all');
    const btnPR    = document.getElementById('btn-buat-pr');
    const countLbl = document.getElementById('ms-count');
    const resultLbl = document.getElementById('ms-result');
    const emptyFilter = document.getElementById('ms-empty-filter');

    const $search   = document.getElementById('ms-search');
    const $status   = document.getElementById('ms-status');
    const $source   = document.getElementById('ms-source');
    const $stock    = document.getElementById('ms-stock');
    const $incoming = document.getElementById('ms-incoming');

    function visibleChecks() {
        return checks.filter(c => !c.closest('tr')?.classList.contains('d-none'));
    }

    function syncSelection() {
        const selected       = checks.filter(c => c.checked);
        const visible        = visibleChecks();
        const visibleSel     = visible.filter(c => c.checked);
        checks.forEach(c => c.closest('tr')?.classList.toggle('table-active', c.checked));
        if (countLbl) countLbl.textContent = `${selected.length} material dipilih`;
        if (btnPR) {
            btnPR.disabled = selected.length === 0;
            btnPR.innerHTML = selected.length
                ? `<i class="bi bi-plus me-1"></i>Buat PR (${selected.length})`
                : '<i class="bi bi-plus me-1"></i>Buat PR';
        }
        if (checkAll) {
            checkAll.checked       = visible.length > 0 && visibleSel.length === visible.length;
            checkAll.indeterminate = visibleSel.length > 0 && visibleSel.length < visible.length;
        }
    }

    function applyFilters(resetSel) {
        const q = $search ? $search.value.trim().toLowerCase() : '';
        let visible = 0;

        rows.forEach(row => {
            const ok = (!q || row.dataset.search.includes(q))
                    && (!$status   || $status.value   === 'all' || row.dataset.status   === $status.value)
                    && (!$source   || $source.value   === 'all' || row.dataset.source.split(' ').includes($source.value))
                    && (!$stock    || $stock.value    === 'all' || row.dataset.stock    === $stock.value)
                    && (!$incoming || $incoming.value === 'all' || row.dataset.incoming === $incoming.value);
            row.classList.toggle('d-none', !ok);
            if (ok) visible++;
            const cb = row.querySelector('.ms-check');
            if (cb && resetSel) cb.checked = ok;
        });

        if (resultLbl) resultLbl.textContent = `${visible} material ditampilkan`;
        if (emptyFilter) emptyFilter.classList.toggle('d-none', visible !== 0);
        syncSelection();
    }

    checks.forEach(c => c.addEventListener('change', syncSelection));
    checkAll?.addEventListener('change', () => {
        visibleChecks().forEach(c => c.checked = checkAll.checked);
        syncSelection();
    });

    let timer;
    $search?.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => applyFilters(true), 120);
    });
    document.querySelectorAll('.ms-filter').forEach(el =>
        el.addEventListener('change', () => applyFilters(true))
    );
    document.getElementById('ms-reset')?.addEventListener('click', () => {
        if ($search)   $search.value   = '';
        if ($status)   $status.value   = 'shortage';
        if ($source)   $source.value   = 'all';
        if ($stock)    $stock.value    = 'all';
        if ($incoming) $incoming.value = 'all';
        applyFilters(true);
        $search?.focus();
    });

    applyFilters(true);
});
</script>
@endpush
