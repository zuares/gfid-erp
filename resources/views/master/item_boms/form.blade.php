@extends('layouts.app')

@section('title', $bom ? 'Master • Edit BOM' : 'Master • Buat BOM')

@push('head')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    /* GF BOM UI FINAL - selaras Master Items */
    .gf-category-page,
    .page-wrap {
        max-width: 1280px !important;
        width: 100% !important;
        margin: 0 auto !important;
        padding: 1rem 1.1rem 4rem !important;
        color: #0f172a !important;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
    }

    .cardx {
        border: 1px solid #e2e8f0 !important;
        border-radius: 18px !important;
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
        border-radius: 18px !important;
        background: #ffffff !important;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .05) !important;
    }

    .cardx.sub {
        margin-top: 18px !important;
        padding: 0 !important;
        background: transparent !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
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

    .bom-cost-summary {
        display: grid !important;
        grid-template-columns: minmax(0, 1.3fr) repeat(2, minmax(0, .8fr)) !important;
        gap: 8px !important;
        margin-top: 0 !important;
        padding: 12px !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        background: #f8fafc !important;
    }
    .bom-cost-summary-label { color:#64748b !important; font-size:.64rem !important; font-weight:850 !important; text-transform:uppercase !important; letter-spacing:.04em !important; }
    .bom-cost-summary-value { margin-top:2px !important; color:#0f172a !important; font-size:1rem !important; font-weight:950 !important; }
    .bom-cost-summary-note { margin-top:2px !important; color:#64748b !important; font-size:.7rem !important; }
    body[data-theme="dark"] .bom-cost-summary { background:#172554 !important; border-color:#3730a3 !important; }
    body[data-theme="dark"] .bom-cost-summary-value { color:#f8fafc !important; }
    @media(max-width:700px) { .bom-cost-summary { grid-template-columns:1fr 1fr !important; } .bom-cost-summary > :first-child { grid-column:1 / -1 !important; } }

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
        width: 100% !important;
        min-width: 0 !important;
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

    .table-bom td:nth-child(3) .inp,
    .table-bom td:nth-child(4) .inp,
    .table-bom td:nth-child(5) .inp,
    .table-bom td:nth-child(6) .inp,
    .table-bom td:nth-child(7) .inp {
        width: 100% !important;
        max-width: 100px !important;
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
            border-radius: 18px !important;
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
            border-radius: 15px !important;
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

        .table-wrap {
            max-height: none !important;
            overflow: visible !important;
            border: 0 !important;
            border-radius: 0 !important;
        }

        .table-bom,
        .table-bom tbody,
        .table-bom tbody tr,
        .table-bom tbody td {
            display: block !important;
            width: 100% !important;
        }

        .table-bom thead { display: none !important; }
        .table-bom tbody { display: grid !important; gap: 10px !important; }
        .table-bom tbody tr.line {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 8px !important;
            padding: 11px !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            background: #ffffff !important;
        }
        .table-bom tbody td {
            display: flex !important;
            min-width: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            flex-direction: column !important;
            align-items: stretch !important;
        }
        .table-bom tbody td::before {
            margin-bottom: 3px !important;
            color: #64748b !important;
            font-size: .62rem !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: .04em !important;
        }
        .table-bom tbody td:nth-child(1) { grid-column: 1 / -1 !important; }
        .table-bom tbody td:nth-child(1)::before { content: 'Baris'; }
        .table-bom tbody td:nth-child(2),
        .table-bom tbody td:nth-child(8) { grid-column: 1 / -1 !important; }
        .table-bom tbody td:nth-child(2)::before { content: 'Komponen'; }
        .table-bom tbody td:nth-child(3)::before { content: 'Kelompok'; }
        .table-bom tbody td:nth-child(4)::before { content: 'Qty'; }
        .table-bom tbody td:nth-child(5)::before { content: 'UOM'; }
        .table-bom tbody td:nth-child(6)::before { content: 'Waste'; }
        .table-bom tbody td:nth-child(7)::before { content: 'Optional'; }
        .table-bom tbody td:nth-child(8)::before { content: ''; }
        .table-bom tbody td:nth-child(8) { align-items: flex-end !important; }
        .table-bom tbody td:nth-child(8) .btnx { width: auto !important; }

        .usage-history-wrap { overflow: visible !important; }
        .usage-history-table { min-width: 0 !important; }
        .usage-history-table th:nth-child(3),
        .usage-history-table td:nth-child(3) { display: none !important; }
    }

    @media (min-width: 769px) and (max-width: 1100px) {
        .table-wrap { overflow-x: hidden !important; }
        .table-bom { table-layout: fixed !important; }
        .table-bom th:nth-child(1) { width: 5% !important; }
        .table-bom th:nth-child(2) { width: 28% !important; min-width: 0 !important; }
        .table-bom th:nth-child(3) { width: 17% !important; }
        .table-bom th:nth-child(4) { width: 14% !important; }
        .table-bom th:nth-child(5) { width: 10% !important; }
        .table-bom th:nth-child(6) { width: 10% !important; }
        .table-bom th:nth-child(7) { width: 10% !important; }
        .table-bom th:nth-child(8) { width: 6% !important; }
    }

    .bom-form-head {
        display: flex !important;
        align-items: flex-start !important;
        justify-content: space-between !important;
        gap: 12px !important;
        padding-bottom: 14px !important;
        border-bottom: 1px solid #e2e8f0 !important;
    }

    .bom-form-head .subt { margin-top: 5px !important; }

    .bom-overview {
        display: grid !important;
        grid-template-columns: minmax(0, .95fr) minmax(0, 1.35fr) !important;
        gap: 14px !important;
        margin-top: 16px !important;
    }

    .bom-overview:not(.has-estimate) { grid-template-columns: 1fr !important; }

    .bom-identity {
        padding: 14px !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        background: #f8fafc !important;
    }

    .bom-identity .grid { gap: 10px !important; }
    .bom-identity .inp,
    .bom-identity .select2-container .select2-selection--single { background: #ffffff !important; }

    .bom-usage-tooltip {
        position: relative !important;
        cursor: help !important;
        outline: none !important;
    }

    .bom-usage-tooltip::after {
        content: attr(data-tooltip) !important;
        position: absolute !important;
        z-index: 30 !important;
        left: 0 !important;
        bottom: calc(100% + 7px) !important;
        min-width: 190px !important;
        max-width: 260px !important;
        padding: 7px 9px !important;
        border-radius: 8px !important;
        background: #0f172a !important;
        color: #ffffff !important;
        font-size: .68rem !important;
        font-weight: 650 !important;
        line-height: 1.35 !important;
        white-space: normal !important;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .18) !important;
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
        transform: translateY(3px) !important;
        transition: opacity .15s ease, transform .15s ease !important;
    }

    .bom-usage-tooltip:hover::after,
    .bom-usage-tooltip:focus::after {
        opacity: 1 !important;
        visibility: visible !important;
        transform: translateY(0) !important;
    }

    .bom-use-actual {
        display: inline-flex !important;
        align-items: center !important;
        gap: 3px !important;
        margin-left: 4px !important;
        padding: 3px 7px !important;
        border: 1px solid #99f6e4 !important;
        border-radius: 6px !important;
        background: #ecfdf5 !important;
        color: #0f766e !important;
        font-size: .68rem !important;
        font-weight: 850 !important;
        line-height: 1.1 !important;
        text-decoration: none !important;
    }

    .bom-use-actual:hover,
    .bom-use-actual:focus {
        background: #ccfbf1 !important;
        border-color: #5eead4 !important;
        color: #115e59 !important;
        text-decoration: none !important;
    }

    @media (max-width: 768px) {
        .bom-form-head { align-items: stretch !important; flex-direction: column !important; }
        .bom-form-head .btnx { align-self: flex-start !important; }
        .bom-overview { grid-template-columns: 1fr !important; gap: 10px !important; }
    }

</style>

@endpush

@section('content')
@php
  $isEdit = (bool) $bom;
  $prefilledItem = $prefilledItem ?? null;
  $bomEstimate = $bomEstimate ?? null;
  $action = $isEdit ? route('master.item_boms.update', $bom) : route('master.item_boms.store');

  // controller sebaiknya kirim $rows (sudah kamu punya)
  $rows = $rows ?? old('lines') ?? [[
    'material_item_id' => null,
    'material_text' => null,
    'qty' => '',
    'uom' => 'pcs',
    'scrap_pct' => 15,
    'is_optional' => 0,
  ]];
@endphp

<div class="gf-category-page">
  <form method="post" action="{{ $action }}" class="cardx gf-bom-form" id="bom-form">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="bom-form-head">
      <div>
        <div class="h1">{{ $isEdit ? 'Edit BOM' : 'Buat BOM' }}</div>
        <div class="subt">
          {{ $isEdit ? ($bom->item->code . ' — ' . $bom->item->name) : 'Tentukan komponen dan kebutuhan material produksi.' }}
        </div>
      </div>
      <a class="btnx ghost" href="{{ route('master.item_boms.index') }}"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>

    @if($errors->any())
      <div class="mt-3 alert alert-danger">
        <div style="font-weight:950">Error:</div>
        <ul class="mb-0">
          @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
      </div>
    @endif

    <div class="bom-overview {{ $isEdit && $bomEstimate ? 'has-estimate' : '' }}">
      <div class="bom-identity">
        <div class="grid">
          <div>
            <div class="lbl">SKU Finished Goods</div>
            @if($isEdit)
              <input class="inp mono" value="{{ $bom->item->code }} — {{ $bom->item->name }}" disabled>
            @elseif($prefilledItem)
              <input type="hidden" name="item_id" value="{{ $prefilledItem->id }}">
              <input class="inp mono" value="{{ $prefilledItem->code }} — {{ $prefilledItem->name }}" disabled>
            @else
              <select id="sku_id" name="item_id" style="width:100%"></select>
            @endif
          </div>

          <div>
            <div class="lbl">Nama BOM</div>
            <input class="inp" name="name" value="{{ old('name', $bom->name ?? '') }}" placeholder="Misal: BOM C5BLK">
            <div class="rowx" style="margin-top:10px">
              <label class="rowx" style="gap:8px">
                <input type="checkbox" name="active" value="1" {{ old('active', $bom->active ?? 1) ? 'checked' : '' }}>
                <span class="lbl" style="margin:0">Aktif</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      @if($isEdit && $bomEstimate)
        <div class="bom-cost-summary" aria-label="Ringkasan estimasi HPP BOM">
          <div>
            <div class="bom-cost-summary-label">Estimasi HPP BOM</div>
            <div class="bom-cost-summary-value">{{ $bomEstimate['total'] > 0 ? 'Rp '.number_format($bomEstimate['total'], 0, ',', '.') : 'Belum tersedia' }}</div>
            <div class="bom-cost-summary-note">Per 1 {{ $bomEstimate['item_unit'] }} · optional tidak dihitung</div>
          </div>
          <div>
            <div class="bom-cost-summary-label">Komponen</div>
            <div class="bom-cost-summary-value">{{ $bomEstimate['line_count'] }}</div>
            <div class="bom-cost-summary-note">{{ $bomEstimate['required_line_count'] }} wajib · {{ $bomEstimate['optional_line_count'] }} opsional</div>
          </div>
          <div>
            <div class="bom-cost-summary-label">Status</div>
            <div class="bom-cost-summary-value">{{ $bom->active ? 'Aktif' : 'Nonaktif' }}</div>
            <div class="bom-cost-summary-note">Tidak mengubah histori</div>
          </div>
        </div>
      @endif
    </div>

    <div style="height:12px"></div>

    <div class="cardx sub">
      <div class="rowx" style="justify-content:space-between;align-items:flex-start">
        <div>
          <div class="lbl" style="margin:0">Lines</div>
          <div class="small">Qty 3 desimal · bahan baku utama di atas.</div>
        </div>
      </div>

      @if(!empty($usageByMaterial ?? []))
        {{-- ── RIWAYAT PEMAKAIAN KAIN (dari cutting job non-void) ── --}}
        <div class="mt-3" style="background:#f0fdfa;border:1px solid #99f6e4;border-radius:12px;padding:.85rem 1rem;">
          <div style="font-weight:800;font-size:.8rem;color:#0f766e;margin-bottom:.15rem;">
            🧵 Riwayat Pemakaian Kain — Cutting Job
          </div>
          <div class="small text-muted mb-2">Data aktual cutting · tambah sebagai bahan utama.</div>
          <div class="table-responsive usage-history-wrap">
            <table class="table table-sm align-middle mb-0 usage-history-table" style="font-size:.78rem;background:#fff;border-radius:8px;overflow:hidden;">
              <thead>
                <tr style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">
                  <th>Material</th>
                  <th class="text-end" style="width:130px;">Aktual Terakhir</th>
                  <th style="width:240px;">Riwayat (3 terakhir)</th>
                  <th style="width:190px;"></th>
                </tr>
              </thead>
              <tbody>
                @foreach($usageByMaterial as $umid => $u)
                  <tr>
                    <td>
                      <span class="mono fw-bold">{{ $u['material_code'] ?? ('#'.$umid) }}</span>
                      <div class="small text-muted">{{ $u['material_name'] ?? '' }}</div>
                    </td>
                    <td class="text-end mono fw-bold" style="color:#0f766e;">
                      {{ number_format($u['kg_per_pcs'], 3, ',', '') }} <span class="text-muted fw-normal">/pcs</span>
                    </td>
                    <td class="text-muted" style="font-size:.72rem;line-height:1.5;">
                      @foreach(($u['history'] ?? []) as $h)
                        <div>{{ $h['job_code'] }} ({{ $h['date'] }}) · <span class="mono">{{ number_format($h['kg_per_pcs'], 3, ',', '') }}</span></div>
                      @endforeach
                    </td>
                    @php
                      $defaultScrap = (string) ($bom?->lines
                          ?->firstWhere('usage_stage', \App\Models\ItemBomLine::STAGE_MAIN_MATERIAL)
                          ?->scrap_pct ?? 15);
                    @endphp
                    <td class="text-end">
                      <div class="d-inline-flex align-items-center gap-1 flex-wrap justify-content-end">
                        <span style="font-size:.68rem;color:#64748b;">Waste</span>
                        <input type="number" class="use-scrap-input mono" value="{{ $defaultScrap }}"
                               min="0" max="100" step="0.1"
                               style="width:58px;font-size:.72rem;padding:.15rem .3rem;border:1px solid #cbd5e1;border-radius:6px;text-align:right;">
                        <span style="font-size:.68rem;color:#64748b;">%</span>
                        <button type="button" class="btnx btn-use-material"
                                data-mid="{{ $umid }}"
                                data-code="{{ $u['material_code'] ?? '' }}"
                                data-name="{{ $u['material_name'] ?? '' }}"
                                data-kg="{{ $u['kg_per_pcs'] }}">
                          Tambah sebagai bahan utama
                        </button>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endif

      <div class="rowx" style="justify-content:flex-end;margin-top:12px">
        <button type="button" class="btnx primary" id="btn-add"><i class="bi bi-plus-lg"></i> Tambah Baris</button>
        <button type="button" class="btnx" id="btn-default-waste">Waste default 15%</button>
      </div>

      <div class="table-wrap mt-3">
        <table class="table table-hover table-sm table-bom gf-clean-table gf-sticky-table align-middle mb-0">
          <thead>
            <tr>
              <th style="width:56px">#</th>
              <th style="min-width:280px">Komponen (bahan / accessories)</th>
              <th style="width:100px">Kelompok</th>
              <th style="width:100px">Qty (raw)</th>
              <th style="width:82px">UOM</th>
              <th style="width:92px">Waste (%)</th>
              <th style="width:88px">Optional</th>
              <th style="width:76px"></th>
            </tr>
          </thead>
          <tbody id="lines">
            @foreach($rows as $i => $r)
              @php
                $mid = data_get($r,'material_item_id');
                $mtext = data_get($r,'material_text');
                $qtyRaw = data_get($r, 'qty', '');
                $qtyDisplay = ($qtyRaw === null || $qtyRaw === '')
                    ? ''
                    : number_format((float) str_replace(',', '.', (string) $qtyRaw), 3, ',', '');
              @endphp
              <tr class="line" data-i="{{ $i }}">
                <td class="mono idx" style="font-weight:950">{{ $i+1 }}</td>
                <td>
                  <select class="mat" name="lines[{{ $i }}][material_item_id]" style="width:100%">
                    @if(!empty($mid))
                      <option value="{{ $mid }}" selected>{{ $mtext ?: 'Material terpilih' }}</option>
                    @endif
                  </select>
                </td>
                <td>
                  <select class="inp mono usage-stage" name="lines[{{ $i }}][usage_stage]">
                    @foreach(\App\Models\ItemBomLine::usageStageLabels() as $value => $label)
                      <option value="{{ $value }}" {{ data_get($r,'usage_stage', \App\Models\ItemBomLine::STAGE_MAIN_MATERIAL) === $value ? 'selected' : '' }}>
                        {{ $label }}
                      </option>
                    @endforeach
                  </select>
                </td>
                <td>
                  <input class="inp mono qty"
                         inputmode="decimal"
                         data-max-decimals="3"
                         name="lines[{{ $i }}][qty]"
                         value="{{ $qtyDisplay }}"
                         placeholder="0,000">
                  <div class="bom-usage-hint" style="font-size:.68rem;line-height:1.45;margin-top:3px;"></div>
                </td>
                <td>
                  <input class="inp mono uom"
                         name="lines[{{ $i }}][uom]"
                         value="{{ (string) data_get($r,'uom','pcs') }}"
                         placeholder="pcs">
                </td>
                <td>
                  <input class="inp mono scrap"
                         type="number"
                         inputmode="decimal"
                         min="0"
                         max="100"
                         step="0.1"
                         name="lines[{{ $i }}][scrap_pct]"
                         value="{{ (string) data_get($r,'scrap_pct',15) }}"
                         placeholder="15">
                </td>
                <td>
                  <select class="inp mono" name="lines[{{ $i }}][is_optional]">
                    <option value="0" {{ (int) data_get($r,'is_optional',0)===0 ? 'selected':'' }}>No</option>
                    <option value="1" {{ (int) data_get($r,'is_optional',0)===1 ? 'selected':'' }}>Yes</option>
                  </select>
                </td>
                <td><button type="button" class="btnx danger btn-del">Hapus</button></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="rowx" style="justify-content:flex-end;margin-top:14px">
        <button class="btnx primary" type="submit"><i class="bi bi-check2"></i> Simpan</button>
        @if($isEdit)
          <a class="btnx" href="{{ route('master.item_boms.duplicate_form', ['from_item_id' => $bom->item_id]) }}">
            <i class="bi bi-files"></i> Duplikat BOM
          </a>
        @endif
      </div>

      <div class="small mt-2">Bahan baku utama boleh lebih dari satu. Satu material hanya boleh muncul sekali; jika materialnya sama, gabungkan qty-nya.</div>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function(){
  const ajaxUrl = @json(route('master.item_boms.ajax_items'));

  // Memudahkan penggantian nilai qty, UOM, waste, dan nama BOM.
  // Delegated listener tetap berlaku untuk baris BOM yang ditambahkan dinamis.
  document.addEventListener('click', function(event){
    const input = event.target.closest('#bom-form input.inp:not([type="checkbox"]):not([type="radio"])');
    if (!input || input.disabled || input.readOnly) return;

    requestAnimationFrame(function(){
      input.select();
    });
  });

  function initSkuSelect(){
    const $el = $('#sku_id');
    if(!$el.length) return;

    $el.select2({
      placeholder: 'Cari SKU...',
      allowClear: true,
      ajax: {
        url: ajaxUrl,
        dataType: 'json',
        delay: 150,
        data: params => ({ q: params.term || '', type: 'finished_good' }),
        processResults: data => data,
        cache: true
      },
      width: 'resolve'
    });
  }

  function initMaterialSelect($select){
    $select.select2({
      placeholder: 'Cari bahan / accessories...',
      allowClear: true,
      ajax: {
        url: ajaxUrl,
        dataType: 'json',
        delay: 150,
        data: params => ({ q: params.term || '', type: 'material' }),
        processResults: data => data,
        cache: true
      },
      width: 'resolve'
    });

    // auto set UOM from ajaxItems() -> unit
    $select.on('select2:select', function(e){
      const data = e.params.data || {};
      const unit = data.unit || '';
      if (!unit) return;

      const $row = $(this).closest('tr.line');
      const $uom = $row.find('input.uom');
      if ($uom.length && (!$uom.val() || $uom.val().trim() === 'pcs')) {
        $uom.val(unit);
      }
    });
  }

  function renumber(){
    $('#lines tr.line').each(function(idx){
      $(this).attr('data-i', idx);
      $(this).find('.idx').text(idx+1);

      // rename index after delete
      $(this).find('select.mat').attr('name', `lines[${idx}][material_item_id]`);
      $(this).find('select.usage-stage').attr('name', `lines[${idx}][usage_stage]`);
      $(this).find('input.qty').attr('name', `lines[${idx}][qty]`);
      $(this).find('input.uom').attr('name', `lines[${idx}][uom]`);
      $(this).find('input.scrap').attr('name', `lines[${idx}][scrap_pct]`);
      $(this).find('select[name*="[is_optional]"]').attr('name', `lines[${idx}][is_optional]`);
    });
  }

  function stageRank(value){
    return {
      main_material: 0,
      sewing_supply: 1,
      packing_supply: 2,
    }[value] ?? 99;
  }

  function reorderLines(){
    const $tbody = $('#lines');
    const rows = $tbody.children('tr.line').get();
    rows.sort(function(a, b){
      return stageRank($(a).find('select.usage-stage').val())
        - stageRank($(b).find('select.usage-stage').val());
    });
    $(rows).appendTo($tbody);
    renumber();
  }

  function ensureOneRow(){
    const $tbody = $('#lines');
    if ($tbody.find('tr.line').length === 0) addRow();
  }

  function addRow(){
    const $tbody = $('#lines');
    const i = $tbody.find('tr.line').length;
        const usageOptions = @json(\App\Models\ItemBomLine::usageStageLabels());
        const usageHtml = Object.entries(usageOptions)
          .map(([value, label]) => `<option value="${value}" ${value === 'main_material' ? 'selected' : ''}>${label}</option>`)
          .join('');

	    const html = `
	      <tr class="line" data-i="${i}">
	        <td class="mono idx" style="font-weight:950">${i+1}</td>
	        <td><select class="mat" name="lines[${i}][material_item_id]" style="width:100%"></select></td>
            <td><select class="inp mono usage-stage" name="lines[${i}][usage_stage]">${usageHtml}</select></td>
        <td><input class="inp mono qty" inputmode="decimal" data-max-decimals="3" name="lines[${i}][qty]" placeholder="0,000"><div class="bom-usage-hint" style="font-size:.68rem;line-height:1.45;margin-top:3px;"></div></td>
        <td><input class="inp mono uom" name="lines[${i}][uom]" value="pcs" placeholder="pcs"></td>
        <td><input class="inp mono scrap" type="number" inputmode="decimal" min="0" max="100" step="0.1" name="lines[${i}][scrap_pct]" value="15" placeholder="15"></td>
        <td>
          <select class="inp mono" name="lines[${i}][is_optional]">
            <option value="0" selected>No</option>
            <option value="1">Yes</option>
          </select>
        </td>
        <td><button type="button" class="btnx danger btn-del">Hapus</button></td>
      </tr>
    `;
    const $row = $(html);
    $tbody.append($row);
    initMaterialSelect($row.find('select.mat'));
    reorderLines();
  }

  $(document).on('click', '#btn-add', addRow);
  $(document).on('click', '#btn-default-waste', function(){
    $('#lines input.scrap').val('15');
    $('#lines tr.line').css('background', '#ecfdf5');
    setTimeout(() => $('#lines tr.line').css('background', ''), 900);
  });
  $(document).on('click', '.btn-del', function(){
    $(this).closest('tr').remove();
    ensureOneRow();
    renumber();
  });

  function limitDecimalPlaces(input){
    const maxDecimals = Number(input.dataset.maxDecimals || 3);
    const parts = input.value.replace(/\./g, ',').split(',');
    const whole = parts.shift() || '';
    if (!parts.length) {
      input.value = whole;
      return;
    }

    input.value = `${whole},${parts.join('').slice(0, maxDecimals)}`;
  }

  $('#lines').on('input', 'input[data-max-decimals]', function(){
    limitDecimalPlaces(this);
  });

  initSkuSelect();
  $('#lines select.mat').each(function(){ initMaterialSelect($(this)); });

  // ── Pemakaian aktual terakhir per material (dari cutting job non-void) ──
  // { material_item_id: { kg_per_pcs, job_code, date, history: [..3] } }
  const bomUsage = @json($usageByMaterial ?? []);

  function renderUsageHint($tr){
    const $hint = $tr.find('.bom-usage-hint');
    if (!$hint.length) return;

    const stage = $tr.find('select.usage-stage').val();
    const mid   = parseInt($tr.find('select.mat').val() || '0', 10);
    const u     = bomUsage[mid];

    if (stage !== 'main_material' || !u) { $hint.empty(); return; }

    const actual = Number(u.kg_per_pcs).toFixed(3).replace('.', ',');
    const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    }[char]));
    const tooltip = escapeHtml(`Aktual ${actual}/pcs · ${u.job_code}`);
    $hint.html(
      `<a href="#" class="use-actual bom-use-actual bom-usage-tooltip" tabindex="0" data-tooltip="${tooltip}" title="${tooltip}" aria-label="${tooltip}"><i class="bi bi-arrow-down-circle"></i> Pakai</a>`
    );

    $hint.find('.use-actual').off('click').on('click', function(e){
      e.preventDefault();
      // Qty BOM = aktual per pcs dibagi (1 + scrap%) — scrap dipertahankan
      const scrap = parseFloat($tr.find('input.scrap').val() || '0');
      const suggested = Number(u.kg_per_pcs) / (1 + scrap / 100);
      $tr.find('input.qty').val(suggested.toFixed(3)).trigger('input');
    });
  }

  $('#lines tr.line').each(function(){ renderUsageHint($(this)); });
  $('#lines').on('change', 'select.mat, select.usage-stage', function(){
    const $row = $(this).closest('tr.line');
    if ($(this).hasClass('usage-stage')) reorderLines();
    renderUsageHint($row);
  });

  reorderLines();

  // ── "Pakai sebagai bahan utama" dari panel riwayat pemakaian ──
  $(document).on('click', '.btn-use-material', function(){
    const mid  = parseInt(this.dataset.mid, 10);
    const kg   = parseFloat(this.dataset.kg || '0');
    const text = `${this.dataset.code} — ${this.dataset.name}`.replace(/^ — | — $/g, '');
    if (!mid || kg <= 0) return;

    // 1) Cari baris existing dengan material sama
    let $target = null;
    $('#lines tr.line').each(function(){
      if (parseInt($(this).find('select.mat').val() || '0', 10) === mid) {
        $target = $(this);
        return false;
      }
    });

    // 2) Kalau tidak ada: pakai baris kosong pertama, atau tambah baris baru.
    //    Jangan mengganti bahan baku utama lain karena satu BOM boleh punya
    //    beberapa bahan baku utama.
    if (!$target) {
      $('#lines tr.line').each(function(){
        if (!$(this).find('select.mat').val()) { $target = $(this); return false; }
      });
      if (!$target) {
        addRow();
        $target = $('#lines tr.line').last();
      }
    }

    // Pastikan material di baris target = material dari riwayat (replace jika beda)
    const $sel = $target.find('select.mat');
    if (parseInt($sel.val() || '0', 10) !== mid) {
      $sel.append(new Option(text, String(mid), true, true)).trigger('change');
    }

    // 3) Set kelompok = bahan baku utama.
    //    Scrap% diambil dari input di panel riwayat → di-set ke baris BOM,
    //    lalu qty = aktual / (1 + scrap%) sehingga qty×(1+scrap) = aktual.
    $target.find('select.usage-stage').val('main_material').trigger('change');
    const scrap = Math.max(0, parseFloat($(this).closest('td').find('.use-scrap-input').val() || '0'));
    $target.find('input.scrap').val(scrap);
    $target.find('input.qty').val((kg / (1 + scrap / 100)).toFixed(3)).trigger('input');

    const $uom = $target.find('input.uom');
    if (!$uom.val() || $uom.val() === 'pcs') $uom.val('kg');

    renderUsageHint($target);
    $target[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    $target.css('background', '#ecfdf5');
    setTimeout(() => $target.css('background', ''), 1500);
  });
})();
</script>
@endpush
