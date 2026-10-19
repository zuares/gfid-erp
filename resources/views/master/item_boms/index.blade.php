@extends('layouts.app')

@section('title','Master • BOM SKU')

@push('head')
<style>
    :root{
        --bom-accent:#334155;
        --bom-accent-2:#1f2937;
        --bom-border:rgba(148,163,184,.18);
        --bom-border-strong:rgba(148,163,184,.30);
        --bom-muted:#64748b;
    }
    .page-wrap{ max-width:1280px; width:100%; margin-inline:auto; padding:1rem 1.1rem 4rem; background:transparent!important; color:#0f172a; }

    .card-main{
        background: var(--card);
        border-radius: 18px;
        border: 1px solid var(--bom-border);
        box-shadow: 0 10px 28px rgba(15,23,42,.045);
        overflow:hidden;
    }
    body[data-theme="dark"] .card-main{
        border-color: #334155;
        box-shadow: none;
    }

    .bom-topbar{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:.6rem;
        flex-wrap:wrap;
        padding:.2rem 0 1rem;
        margin:0 0 1rem;
        border-bottom:1px solid rgba(148,163,184,.22);
    }
    body[data-theme="dark"] .page-wrap{ color:#e5e7eb; }
    body[data-theme="dark"] .bom-topbar{ border-color:#334155; }
    .title{ font-weight: 750; font-size:1.45rem; letter-spacing:-.03em; margin:0; }
    .sub{ color:var(--bom-muted); font-size:.78rem; }
    body[data-theme="dark"] .sub{ color:#9ca3af; }

    .controls{ display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
    .bom-filter{ padding:0; background:transparent; border:0; border-radius:0; }
    .filter-label{ font-size:.8rem; color:#6b7280; }
    body[data-theme="dark"] .filter-label{ color:#9ca3af; }
    .filter-input{ border-radius:10px; padding-left:.75rem; font-size:.84rem; min-height:40px; border:1.5px solid #111827; box-shadow:0 0 0 2px rgba(15,23,42,.04); }
    .btn-pill{ border-radius:10px; padding-inline:.85rem; min-height:40px; box-shadow:none!important; font-weight:600; }
    .btn-bom-primary{ background:var(--bom-accent)!important; border-color:var(--bom-accent)!important; color:#fff!important; }
    .btn-bom-primary:hover{ background:var(--bom-accent-2)!important; border-color:var(--bom-accent-2)!important; color:#fff!important; }
    .btn-bom-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
    .btn-bom-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }
    body[data-theme="dark"] .btn-bom-outline { color: #cbd5e1!important; }
    body[data-theme="dark"] .btn-bom-outline:hover { color: #f8fafc!important; background: rgba(148,163,184,.15)!important; }

    .table-list{ margin-bottom:0; font-size:.76rem; }
    .table-list thead th{
        border-bottom-width:1px;
        font-size:.68rem;
        text-transform:none;
        letter-spacing:0;
        color:#64748b;
        background: var(--card,#fff);
        padding:.7rem .7rem;
        white-space:nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    body[data-theme="dark"] .table-list thead th{
        background: rgba(15, 23, 42, 0.98);
        color:#9ca3af;
        border-bottom-color: rgba(30, 64, 175, 0.6);
    }
    .table-list tbody td{
        vertical-align:middle;
        border-top-color: rgba(148, 163, 184, 0.16);
        padding:.72rem .7rem;
    }
    body[data-theme="dark"] .table-list tbody td{ border-top-color: rgba(51, 65, 85, 0.85); }

    .code-link{ font-weight:700; text-decoration:none; color:inherit; }
    .code-link:hover{ text-decoration:underline; }
    .muted{ font-size:.82rem; color:#6b7280; }
    body[data-theme="dark"] .muted{ color:#9ca3af; }
    .bom-name{ font-weight:600; }
    .bom-item-code{ font-family:monospace; font-weight:750; letter-spacing:.01em; }
    .bom-item-name{ color:#64748b; font-size:.76rem; margin-top:.12rem; }
    body[data-theme="dark"] .bom-item-name{ color:#9ca3af; }
    .bom-structure{ color:#475569; font-size:.74rem; line-height:1.5; }
    .bom-structure strong{ color:#1e293b; font-weight:700; }
    body[data-theme="dark"] .bom-structure{ color:#cbd5e1; }
    body[data-theme="dark"] .bom-structure strong{ color:#f8fafc; }
    .bom-actual{ color:#64748b; font-size:.7rem; margin-top:.18rem; }
    body[data-theme="dark"] .bom-actual{ color:#9ca3af; }
    .usage-status{ font-size:.67rem; font-weight:650; margin-left:.35rem; }
    .usage-over{ color:#b91c1c; }
    .usage-under{ color:#15803d; }
    .usage-ok{ color:#64748b; }

    .badge-status{
        border-radius:7px; padding:.16rem .48rem;
        font-size:.68rem; letter-spacing:0; text-transform:none;
        border:1px solid transparent;
        display:inline-flex; align-items:center; gap:.35rem;
        white-space:nowrap;
    }
    .badge-status::before{ content:''; width:7px; height:7px; border-radius:999px; display:inline-block; }

    .st-active{ background: rgba(34, 197, 94, 0.10); color:#166534; border-color: rgba(34, 197, 94, 0.30); }
    .st-active::before{ background: rgba(34, 197, 94, 0.95); }
    .st-inactive{ background: rgba(148, 163, 184, 0.10); color:#475569; border-color: rgba(148, 163, 184, 0.30); }
    .st-inactive::before{ background: rgba(100, 116, 139, 0.95); }
    .st-warning{ background: #fef3c7; color: #92400e; border-color: #fde68a; }

    body[data-theme="dark"] .st-active{ background: rgba(34, 197, 94, 0.20); color:#dcfce7; border-color: rgba(34, 197, 94, 0.55); }
    body[data-theme="dark"] .st-inactive{ background: rgba(148, 163, 184, 0.18); color:#cbd5e1; border-color: rgba(148, 163, 184, 0.55); }

    .empty{ padding:2.2rem 1.25rem; text-align:center; color:#64748b; }
    body[data-theme="dark"] .empty{ color:#9ca3af; }
    .divider{ height:1px; background: rgba(148, 163, 184, 0.20); }
    body[data-theme="dark"] .divider{ background: rgba(51, 65, 85, 0.85); }
    .flash-clean{ border-radius:8px; padding:.62rem .75rem; font-size:.84rem; border:1px solid rgba(148,163,184,.25); }
    
    .gf-live-filter-wrap { position: relative !important; }
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
    body[data-theme="dark"] .gf-live-filter-indicator { background: rgba(15, 23, 42, .88)!important; color:#cbd5e1!important; }
    .gf-live-filter-indicator.is-show { display: inline-flex !important; }

    @media (max-width: 768px) {
        .page-wrap{ padding:.65rem .55rem 4rem; }
        .bom-topbar{ padding:.2rem 0 .85rem; }
        .title{ font-size:1.12rem; }
        .sub{ display:none; }
        .controls{ width:100%; align-items:stretch; }
        .controls form{ flex:1 1 100%; }
        .controls .btn,
        .controls form button{ min-height:40px; }
        .bom-filter{ padding:11px; }
        .bom-filter > div:first-child,
        .bom-filter > div:last-child{ flex-basis:100% !important; width:100%; }
        .bom-filter > div:last-child{ justify-content:stretch; }
        .bom-filter > div:last-child .btn{ flex:1 1 auto; }
        .table-responsive{ overflow:visible; }
        .table-list thead{ display:none; }
        .table-list,
        .table-list tbody,
        .table-list tr,
        .table-list td{ display:block; width:100%; }
        .table-list tbody tr{
            padding:.66rem;
            border-top:1px solid rgba(148,163,184,.16);
        }
        .table-list tbody td{
            border:0;
            padding:0;
            margin-bottom:.35rem;
        }
        .table-list tbody td.mobile-hide{ display:none; }
        .bom-row-main{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:.75rem;
        }
        .bom-row-action{
            margin-top:.55rem;
        }
        .bom-row-action .btn{
            width:100%;
            min-height:38px;
        }
    }
</style>
@endpush

@section('content')
<div class="page-wrap">
    @if(session('success'))
        <div class="flash-clean alert alert-success mb-2">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash-clean alert alert-danger mb-2">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="flash-clean alert alert-danger mb-2">{{ $errors->first() }}</div>
    @endif

    <div class="bom-topbar">
        <div>
            <div class="title">BOM per SKU</div>
            <div class="sub">Kelola komponen produksi per SKU.</div>
        </div>

        <div class="controls">
            <a href="{{ route('master.item_boms.import_form') }}" class="btn btn-sm btn-bom-outline btn-pill">
                <i class="bi bi-upload"></i> Import
            </a>
            <a href="{{ route('master.item_boms.duplicate_form') }}" class="btn btn-sm btn-bom-outline btn-pill">
                <i class="bi bi-files"></i> Duplikat
            </a>
            <a href="{{ route('master.item_boms.create') }}" class="btn btn-sm btn-bom-primary btn-pill">
                <i class="bi bi-plus-lg"></i> BOM Baru
            </a>
        </div>
    </div>

    <form method="get" class="bom-filter d-flex gap-2 flex-wrap mb-3 align-items-end">
        <div style="flex: 1 1 240px;">
            <div class="position-relative gf-live-filter-wrap">
                <i class="bi bi-search position-absolute" style="left: 10px; top: 50%; transform: translateY(-50%); color: var(--bom-muted); font-size: 0.8rem;"></i>
                <input type="search" name="q" class="form-control form-control-sm filter-input w-100"
                    value="{{ request('q') }}" placeholder="Cari SKU, nama item, atau nama BOM..." autocomplete="off" autofocus
                    style="padding-left: 32px; font-family: monospace; font-size: 0.88rem;">
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-bom-primary btn-pill" style="display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-funnel"></i> Cari
            </button>
            @if(request('q'))
                <a href="{{ route('master.item_boms.index') }}" class="btn btn-sm btn-bom-outline btn-pill" style="display: inline-flex; align-items: center;">Reset</a>
            @endif
        </div>
    </form>

    <div class="card card-main">
        <div class="card-body p-0">
            @if ($boms->count() === 0)
                <div class="empty">
                    @if($q !== '')
                        BOM dengan kata kunci <b>{{ $q }}</b> tidak ditemukan.
                        <div class="mt-1"><a href="{{ route('master.item_boms.index') }}">Hapus pencarian</a> atau buat BOM baru.</div>
                    @else
                        Belum ada BOM.
                        <div class="mt-1">Klik <b>BOM Baru</b> untuk membuat BOM.</div>
                    @endif
                </div>
            @else
                <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                    <table class="table table-hover align-middle table-list">
                        <thead>
                            <tr>
                                <th style="width: 44px;" class="text-center">#</th>
                                <th style="min-width: 240px;">SKU / Item</th>
                                <th style="width: 220px;">Struktur BOM</th>
                                <th class="text-center" style="width: 100px;">Status</th>
                                <th class="text-end" style="width: 140px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($boms as $i => $b)
                                <tr>
                                    <td class="text-center text-muted mobile-hide">{{ ($boms->currentPage()-1)*$boms->perPage() + $i + 1 }}</td>
                                    <td>
                                        <div class="bom-row-main">
                                            <div>
                                                <div class="bom-item-code">{{ $b->item->code }}</div>
                                                <div class="bom-item-name">{{ $b->item->name }}</div>
                                            </div>
                                            <span class="badge-status {{ $b->active ? 'st-active' : 'st-inactive' }} d-md-none">{{ $b->active ? 'Active' : 'Off' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="bom-structure">
                                            <strong>{{ (int) $b->main_material_lines_count }}</strong> utama
                                            <span>·</span>
                                            <strong>{{ (int) $b->sewing_supply_lines_count }}</strong> jahit
                                            <span>·</span>
                                            <strong>{{ (int) $b->packing_supply_lines_count }}</strong> packing
                                            <span class="muted">({{ (int) $b->lines_count }} total)</span>
                                        </div>
                                        @php $ub = $bomUsageBadges[$b->id] ?? null; @endphp
                                        @if($ub)
                                            <div class="bom-actual">
                                                Aktual <span style="font-family: monospace;" class="fw-semibold">{{ number_format($ub['kg_per_pcs'], 3, ',', '') }}</span>/pcs · {{ $ub['job_code'] }}
                                                @if($ub['status'] === 'over')
                                                    <span class="usage-status usage-over" title="Standar BOM (maks): {{ number_format($ub['std_max'], 3, ',', '') }} kg/pcs">· melebihi standar</span>
                                                @elseif($ub['status'] === 'under')
                                                    <span class="usage-status usage-under">· lebih hemat</span>
                                                @elseif($ub['status'] === 'ok')
                                                    <span class="usage-status usage-ok">· sesuai standar</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center mobile-hide">
                                        @if($b->active)
                                            <span class="badge-status st-active">Active</span>
                                        @else
                                            <span class="badge-status st-inactive">Off</span>
                                        @endif
                                    </td>
                                    <td class="text-end bom-row-action">
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            <a href="{{ route('master.item_boms.edit', $b) }}" class="btn btn-sm btn-bom-outline btn-pill px-3 fw-bold" style="font-size: 0.78rem;">
                                                Edit
                                            </a>
                                            <form method="post" action="{{ route('master.item_boms.destroy', $b) }}" class="js-delete-bom-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm px-2 py-1 shadow-none border-0 text-danger" style="background: transparent;">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="divider"></div>

                <div class="p-3 d-flex justify-content-between align-items-center flex-wrap">
                    <div class="muted">
                        Menampilkan {{ $boms->firstItem() }}–{{ $boms->lastItem() }} dari {{ number_format($boms->total(), 0, ',', '.') }} BOM
                    </div>
                    <div>
                        {{ $boms->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.querySelector('input[name="q"]');
    if (!searchInput) return;

    setTimeout(function () {
        searchInput.focus();
        const value = searchInput.value || '';
        try { searchInput.setSelectionRange(value.length, value.length); } catch (e) {}
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-delete-bom-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const submit = () => form.submit();

            if (window.Swal) {
                Swal.fire({
                    title: 'Hapus BOM?',
                    text: 'Semua line material pada BOM ini ikut terhapus.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#dc2626',
                }).then(result => {
                    if (result.isConfirmed) submit();
                });
                return;
            }

            if (confirm('Hapus BOM ini? Semua line material ikut terhapus.')) {
                submit();
            }
        });
    });
});
</script>
@endpush
