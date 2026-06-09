@extends('layouts.app')

@section('title','Master • BOM SKU')

@push('head')

<style>
    /* GF BOM UI FINAL - selaras Master Items */
    .gf-category-page,
    .page-wrap {
        max-width: 1180px !important;
        margin: 0 auto !important;
        padding: 16px 12px 28px !important;
        color: #0f172a !important;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
    }

    .cardx {
        border: 1px solid #e2e8f0 !important;
        border-radius: 24px !important;
        background: #ffffff !important;
        box-shadow: 0 16px 42px rgba(15, 23, 42, .07) !important;
        padding: 16px !important;
        overflow: hidden !important;
    }

    .cardx.gf-category-head,
    .gf-bom-form {
        margin-bottom: 14px !important;
        padding: 18px !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 24px !important;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 58%, #f1f5f9 100%) !important;
        box-shadow: 0 16px 42px rgba(15, 23, 42, .07) !important;
    }

    .cardx.sub {
        margin-top: 14px !important;
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 24px !important;
        box-shadow: 0 16px 42px rgba(15, 23, 42, .07) !important;
    }

    .rowx {
        display: flex !important;
        gap: 10px !important;
        flex-wrap: wrap !important;
        align-items: center !important;
    }

    .h1 {
        color: #0f172a !important;
        font-size: 1.34rem !important;
        font-weight: 950 !important;
        letter-spacing: -.05em !important;
        line-height: 1.1 !important;
        margin: 0 !important;
    }

    .subt,
    .small {
        color: #64748b !important;
        font-size: .82rem !important;
        font-weight: 650 !important;
        line-height: 1.45 !important;
    }

    .chip {
        display: inline-flex !important;
        align-items: center !important;
        gap: 7px !important;
        padding: 6px 10px !important;
        border-radius: 999px !important;
        background: #f1f5f9 !important;
        border: 1px solid #e2e8f0 !important;
        color: #334155 !important;
        font-size: .72rem !important;
        font-weight: 900 !important;
    }

    .dot {
        width: 7px !important;
        height: 7px !important;
        border-radius: 999px !important;
        background: #0f172a !important;
        display: inline-block !important;
    }

    .mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace !important;
        letter-spacing: -.02em !important;
    }

    .hr {
        height: 1px !important;
        background: #e2e8f0 !important;
        border: 0 !important;
        margin: 14px 0 !important;
    }

    .lbl {
        font-size: .72rem !important;
        font-weight: 900 !important;
        color: #334155 !important;
        margin-bottom: 5px !important;
        text-transform: uppercase !important;
        letter-spacing: .045em !important;
    }

    .grid {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 12px !important;
    }

    .inp,
    .form-control,
    .form-select {
        border-radius: 14px !important;
        border: 1px solid #e2e8f0 !important;
        min-height: 38px !important;
        color: #0f172a !important;
        font-size: .84rem !important;
        font-weight: 650 !important;
        background: #ffffff !important;
        box-shadow: none !important;
    }

    .inp:focus,
    .form-control:focus,
    .form-select:focus {
        border-color: #94a3b8 !important;
        box-shadow: 0 0 0 .22rem rgba(15, 23, 42, .08) !important;
    }

    .btnx,
    .btn {
        border-radius: 999px !important;
        font-weight: 850 !important;
        letter-spacing: -.01em !important;
        min-height: 34px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        text-decoration: none !important;
    }

    .btnx {
        padding: 8px 13px !important;
        border: 1px solid #cbd5e1 !important;
        background: rgba(255,255,255,.78) !important;
        color: #475569 !important;
    }

    .btnx:hover {
        background: #f8fafc !important;
        color: #0f172a !important;
        box-shadow: 0 10px 22px rgba(15, 23, 42, .08) !important;
    }

    .btnx.primary,
    .btnx.success,
    .btn-primary {
        color: #ffffff !important;
        background: linear-gradient(135deg, #0f172a, #334155) !important;
        border-color: transparent !important;
        box-shadow: 0 12px 24px rgba(15, 23, 42, .12) !important;
    }

    .btnx.ghost {
        background: #ffffff !important;
        color: #64748b !important;
        border-color: #e2e8f0 !important;
    }

    .btnx.danger,
    .btn-outline-danger {
        color: #991b1b !important;
        background: #fee2e2 !important;
        border-color: #fecaca !important;
    }

    .alert-successx,
    .alert-success {
        margin-top: 14px !important;
        border-radius: 16px !important;
        padding: 10px 12px !important;
        background: #dcfce7 !important;
        color: #166534 !important;
        border: 1px solid #bbf7d0 !important;
        font-size: .82rem !important;
        font-weight: 800 !important;
    }

    .alert,
    .alert-danger {
        border-radius: 16px !important;
        font-size: .82rem !important;
    }

    .table-wrap {
        max-height: 68vh !important;
        overflow: auto !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 18px !important;
        background: #ffffff !important;
    }

    .table-bom,
    .gf-clean-table {
        min-width: 860px !important;
        margin: 0 !important;
        background: #ffffff !important;
        color: #0f172a !important;
        font-size: .82rem !important;
    }

    .table-bom thead th,
    .gf-clean-table thead th,
    .gf-sticky-table thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 8 !important;
        background: #f8fafc !important;
        color: #64748b !important;
        font-size: .7rem !important;
        text-transform: uppercase !important;
        letter-spacing: .045em !important;
        font-weight: 900 !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: 12px 10px !important;
        white-space: nowrap !important;
    }

    .table-bom td,
    .gf-clean-table td {
        border-color: #eef2f7 !important;
        padding: 12px 10px !important;
        vertical-align: middle !important;
        color: #0f172a !important;
    }

    .table-bom tbody tr:hover td,
    .gf-clean-table tbody tr:hover td {
        background: #f8fbff !important;
    }

    .badge {
        border-radius: 999px !important;
        padding: 6px 10px !important;
        font-weight: 850 !important;
    }

    .pagination {
        margin: 0 !important;
        gap: 4px !important;
    }

    .pagination .page-link {
        border-radius: 11px !important;
        border-color: #e2e8f0 !important;
        color: #475569 !important;
        font-size: .78rem !important;
        font-weight: 700 !important;
    }

    .pagination .active .page-link,
    .pagination .page-item.active .page-link {
        color: #ffffff !important;
        background: #0f172a !important;
        border-color: #0f172a !important;
    }

    .select2-container {
        width: 100% !important;
    }

    .select2-container .select2-selection--single {
        height: 40px !important;
        border-radius: 14px !important;
        border: 1px solid #e2e8f0 !important;
        display: flex !important;
        align-items: center !important;
        background: #ffffff !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 40px !important;
        color: #0f172a !important;
        font-size: .84rem !important;
        font-weight: 650 !important;
        padding-left: 12px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
        right: 8px !important;
    }

    .select2-dropdown {
        border: 1px solid #e2e8f0 !important;
        border-radius: 14px !important;
        overflow: hidden !important;
        box-shadow: 0 18px 46px rgba(15, 23, 42, .14) !important;
    }

    .select2-results__option {
        font-size: .84rem !important;
        padding: 8px 12px !important;
    }

    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background: #0f172a !important;
        color: #ffffff !important;
    }

    .gf-live-filter-wrap {
        position: relative !important;
    }

    .gf-live-filter-indicator {
        position: absolute !important;
        right: 12px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        display: none !important;
        color: #334155 !important;
        background: rgba(255,255,255,.88) !important;
        padding-left: 8px !important;
        font-size: .72rem !important;
        font-weight: 850 !important;
    }

    .gf-live-filter-indicator.is-show {
        display: inline-flex !important;
    }

    @media (max-width: 992px) {
        .cardx.gf-category-head,
        .gf-bom-form {
            border-radius: 22px !important;
        }

        .grid {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 768px) {
        .gf-category-page,
        .page-wrap {
            padding: 12px 10px 24px !important;
        }

        .cardx,
        .cardx.gf-category-head,
        .gf-bom-form {
            border-radius: 20px !important;
            padding: 14px !important;
        }

        .rowx {
            align-items: stretch !important;
        }

        .btnx,
        .btn {
            width: auto;
        }

        form.rowx {
            display: grid !important;
            grid-template-columns: 1fr !important;
        }

        form.rowx > div {
            width: 100% !important;
        }

        form.rowx .btnx {
            width: 100% !important;
        }
    }

    .gf-bom-head-layout {
        display: flex !important;
        align-items: flex-start !important;
        gap: 13px !important;
        min-width: 0 !important;
    }

    .gf-bom-head-icon {
        width: 48px !important;
        height: 48px !important;
        flex: 0 0 48px !important;
        border-radius: 17px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        color: #ffffff !important;
        background: linear-gradient(135deg, #0f172a, #334155) !important;
        box-shadow: 0 14px 28px rgba(15, 23, 42, .18) !important;
        font-size: 1.22rem !important;
    }

    .gf-bom-head-content {
        min-width: 0 !important;
    }

    @media (max-width: 768px) {
        .gf-bom-head-icon {
            width: 42px !important;
            height: 42px !important;
            flex-basis: 42px !important;
            border-radius: 15px !important;
            font-size: 1.08rem !important;
        }
    }

</style>

@endpush

@section('content')
<div class="gf-category-page">

  <div class="cardx gf-category-head">
    <div class="rowx" style="justify-content:space-between;align-items:flex-start">
      <div class="gf-bom-head-layout">
        <div class="gf-bom-head-icon"><i class="bi bi-diagram-3"></i></div>
        <div class="gf-bom-head-content">
          <div class="h1">BOM per SKU</div>
          <div class="subt">1 SKU = 1 BOM (paling cepat jalan). Material ambil dari <span class="mono">items</span>.</div>

        <div class="rowx" style="margin-top:10px">
          <span class="chip"><span class="dot"></span> GFID • BOM SKU</span>
          <span class="chip">Total: <span class="mono">{{ $boms->total() }}</span></span>
          </div>
        </div>
      </div>

      <div class="rowx">
        <a class="btnx" href="{{ route('master.item_boms.import_form') }}"><i class="bi bi-upload"></i> Import CSV</a>
        <a class="btnx" href="{{ route('master.item_boms.duplicate_form') }}"><i class="bi bi-files"></i> Duplicate BOM</a>
        <a class="btnx primary" href="{{ route('master.item_boms.create') }}"><i class="bi bi-plus-lg"></i> BOM Baru</a>
      </div>
    </div>

    <div class="hr"></div>

    <form class="rowx" method="get" style="align-items:flex-end">
      <div style="flex:1;min-width:240px">
        <div class="lbl">Cari SKU</div>
        <input class="inp mono" name="q" value="{{ $q }}" placeholder="Cari SKU (code / nama)..." / autofocus>
        <div class="small" style="margin-top:6px">Contoh: <span class="mono">C5BLK</span>, <span class="mono">J3MST</span>, <span class="mono">K1NVY</span></div>
      </div>
      <div class="rowx">
        <button class="btnx success" type="submit"><i class="bi bi-funnel"></i> Cari</button>
        <a class="btnx ghost" href="{{ route('master.item_boms.index') }}">Reset</a>
      </div>
    </form>
  </div>

  <div style="height:12px"></div>

  @if(session('success'))
    <div class="alert-successx">{{ session('success') }}</div>
    <div style="height:12px"></div>
  @endif

  <div class="cardx sub">
    <div class="table-wrap">
      <table class="table table-hover table-sm table-bom gf-clean-table gf-sticky-table align-middle mb-0">
        <thead>
          <tr>
            <th style="width:56px">#</th>
            <th style="width:170px">SKU</th>
            <th>Nama</th>
            <th style="width:120px">Status</th>
            <th style="width:140px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($boms as $i => $b)
            <tr>
              <td class="mono" style="font-weight:950">{{ ($boms->currentPage()-1)*$boms->perPage() + $i + 1 }}</td>
              <td class="mono" style="font-weight:950">{{ $b->item->code }}</td>
              <td>{{ $b->item->name }}</td>
              <td>
                @if($b->active)
                  <span class="badge bg-success">Active</span>
                @else
                  <span class="badge bg-secondary">Off</span>
                @endif
              </td>
              <td>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('master.item_boms.edit',$b) }}">Edit</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-muted">Belum ada BOM.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      {{ $boms->links() }}
    </div>
  </div>

</div>
@endsection


@push('scripts')
{{-- GF_BOM_AUTO_FOCUS_SEARCH --}}

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.querySelector('input[name="q"]');

    if (!searchInput) return;

    // Fokus otomatis saat masuk halaman
    setTimeout(function () {
        searchInput.focus();

        // Cursor langsung di akhir teks kalau ada query sebelumnya
        const value = searchInput.value || '';
        searchInput.setSelectionRange(value.length, value.length);
    }, 120);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[method="get"]');
    if (!form || form.dataset.gfRealtime === '1') return;

    form.dataset.gfRealtime = '1';

    const input = form.querySelector('input[name="q"]');
    const submitBtn = form.querySelector('button[type="submit"]');
    let timer = null;
    let submitting = false;

    if (!input) return;

    let wrap = input.closest('.gf-live-filter-wrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.className = 'gf-live-filter-wrap';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);
    }

    let indicator = document.createElement('span');
    indicator.className = 'gf-live-filter-indicator';
    indicator.textContent = 'filter...';
    wrap.appendChild(indicator);

    function submitLive(delay = 450) {
        clearTimeout(timer);

        timer = setTimeout(function () {
            if (submitting) return;
            submitting = true;

            indicator.classList.add('is-show');

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Cari';
            }

            if (String(input.value || '').trim() === '') {
                input.disabled = true;
            }

            form.requestSubmit ? form.requestSubmit() : form.submit();
        }, delay);
    }

    input.setAttribute('autocomplete', 'off');

    input.addEventListener('input', function () {
        submitLive(450);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            submitLive(0);
        }
    });
});
</script>
@endpush

