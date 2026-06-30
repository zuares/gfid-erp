@extends('layouts.app')

@section('title', 'WIP Opname ' . $period->code)

@push('head')
<style>
    .opname-progress-bar {
        height: 6px;
        border-radius: 999px;
        background: rgba(148,163,184,.2);
        overflow: hidden;
        margin-top: .3rem;
    }
    .opname-progress-fill {
        height: 100%;
        border-radius: 999px;
        background: #2563eb;
        transition: width .3s;
    }
    .opname-line-row td { vertical-align: middle; }
    .opname-line-row.is-counted { background: rgba(37,99,235,.03); }
    .opname-line-row.has-diff td:first-child { border-left: 3px solid #dc2626; }
    .qty-input-wrap { display: flex; align-items: center; gap: .3rem; }
    .qty-input { width: 90px; text-align: right; font-family: monospace; font-size: .85rem; }
    @media (max-width: 767.98px) {
        .hide-mobile { display: none !important; }
        .qty-input { width: 80px; }
    }
</style>
@endpush

@section('content')
<div class="page-wrap" style="max-width:960px;margin-inline:auto;padding:1rem 1rem 4rem;">

    {{-- Header --}}
    <div class="d-flex align-items-start justify-content-between gap-2 mb-3 flex-wrap">
        <div>
            <a href="{{ route('production.wip_opname.index') }}" class="text-muted small">← Kembali</a>
            <h4 class="fw-bold mb-0 mt-1 mono">{{ $period->code }}</h4>
            <span class="text-muted small">{{ $period->date->format('d M Y') }} · Dibuka oleh {{ $period->openedBy?->name }}</span>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if ($period->canEdit())
                @if ($countedLines === $totalLines && $totalLines > 0)
                    <form method="POST" action="{{ route('production.wip_opname.submit', $period) }}">
                        @csrf
                        <button class="btn btn-warning btn-sm">
                            <i class="bi bi-send me-1"></i> Kirim ke Owner
                        </button>
                    </form>
                @endif
            @endif

            @if ($period->canApprove() && (auth()->user()->isOwner() || auth()->user()->isDeveloper()))
                <form method="POST" action="{{ route('production.wip_opname.approve', $period) }}"
                      onsubmit="return confirm('Setujui opname ini dan koreksi stok bundle yang selisih?')">
                    @csrf
                    <button class="btn btn-success btn-sm">
                        <i class="bi bi-check-circle me-1"></i> Approve & Koreksi Stok
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
    @endif

    {{-- Stats --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="cutting-card h-100">
                <div class="cutting-card-body py-2 px-3">
                    <div class="text-muted" style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Total Bundle</div>
                    <div class="mono fw-bold" style="font-size:1.4rem;">{{ $totalLines }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cutting-card h-100">
                <div class="cutting-card-body py-2 px-3">
                    <div class="text-muted" style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Sudah Dihitung</div>
                    <div class="mono fw-bold" style="font-size:1.4rem;">{{ $countedLines }}</div>
                    <div class="opname-progress-bar">
                        <div class="opname-progress-fill" style="width:{{ $totalLines > 0 ? round($countedLines/$totalLines*100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cutting-card h-100">
                <div class="cutting-card-body py-2 px-3">
                    <div class="text-muted" style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Ada Selisih</div>
                    <div class="mono fw-bold {{ $withDiff > 0 ? 'text-danger' : '' }}" style="font-size:1.4rem;">{{ $withDiff }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cutting-card h-100">
                <div class="cutting-card-body py-2 px-3">
                    <div class="text-muted" style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Status</div>
                    @php
                        $statusLabel = match($period->status) {
                            'open'             => 'Buka',
                            'counting'         => 'Sedang Hitung',
                            'pending_approval' => 'Menunggu Approve',
                            'approved','closed'=> 'Selesai',
                            default            => $period->status,
                        };
                        $statusColor = match($period->status) {
                            'open','counting'          => '#f59e0b',
                            'pending_approval'         => '#2563eb',
                            'approved','closed'        => '#16a34a',
                            default                    => '#94a3b8',
                        };
                    @endphp
                    <div class="fw-bold mt-1" style="font-size:.9rem;color:{{ $statusColor }}">{{ $statusLabel }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="cutting-card">
        <div class="cutting-card-header">
            <h5>Daftar Bundle</h5>
            @if ($period->canEdit())
                <span class="text-muted small">Input qty fisik per baris</span>
            @endif
        </div>
        <div class="cutting-card-body p-0">
            <table class="table table-sm align-middle mb-0">
                <thead style="background:rgba(148,163,184,.06);">
                    <tr>
                        <th class="ps-3">Bundle</th>
                        <th class="hide-mobile">Item</th>
                        <th class="hide-mobile">Job</th>
                        <th class="text-end">Sistem (pcs)</th>
                        <th class="text-end">Fisik (pcs)</th>
                        <th class="text-end">Selisih</th>
                        @if ($period->canEdit()) <th></th> @endif
                    </tr>
                </thead>
                <tbody>
                @foreach ($lines as $line)
                    @php
                        $hasDiff = $line->is_counted && abs($line->difference ?? 0) > 0.01;
                    @endphp
                    <tr class="opname-line-row {{ $line->is_counted ? 'is-counted' : '' }} {{ $hasDiff ? 'has-diff' : '' }}">
                        <td class="ps-3 mono small fw-semibold">{{ $line->bundle_code }}</td>
                        <td class="small hide-mobile">{{ $line->item_code }}</td>
                        <td class="small text-muted hide-mobile">{{ $line->cutting_job_code }}</td>
                        <td class="text-end mono small">{{ number_format($line->qty_system, 2, ',', '.') }}</td>
                        <td class="text-end mono small {{ !$line->is_counted ? 'text-muted' : '' }}">
                            {{ $line->is_counted ? number_format($line->qty_physical, 2, ',', '.') : '—' }}
                        </td>
                        <td class="text-end mono small {{ $line->differenceClass() }}">
                            @if ($line->is_counted)
                                {{ ($line->difference >= 0 ? '+' : '') . number_format($line->difference, 2, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                        @if ($period->canEdit())
                        <td class="text-end pe-2">
                            <button type="button"
                                class="btn btn-sm btn-outline-secondary btn-pill-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#inputModal"
                                data-line-id="{{ $line->id }}"
                                data-bundle="{{ $line->bundle_code }}"
                                data-system="{{ $line->qty_system }}"
                                data-physical="{{ $line->qty_physical ?? '' }}"
                                data-notes="{{ $line->notes ?? '' }}"
                                data-url="{{ route('production.wip_opname.update_line', [$period, $line]) }}">
                                {{ $line->is_counted ? 'Edit' : 'Input' }}
                            </button>
                        </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Input Fisik --}}
@if ($period->canEdit())
<div class="modal fade" id="inputModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="input-form">
                @csrf
                <div class="modal-header py-2 px-3">
                    <h6 class="modal-title fw-bold mb-0" id="modal-bundle-label">Input Fisik</h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-3 py-3">
                    <div class="mb-1 small text-muted">Stok sistem: <span class="mono fw-bold" id="modal-system-qty"></span> pcs</div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold mb-1">Qty Fisik (pcs)</label>
                        <input type="number" name="qty_physical" id="modal-qty-physical"
                            class="form-control form-control-sm text-end mono"
                            step="0.01" min="0" required autofocus>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold mb-1">Catatan</label>
                        <input type="text" name="notes" id="modal-notes"
                            class="form-control form-control-sm" placeholder="Opsional">
                    </div>
                </div>
                <div class="modal-footer py-2 px-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('inputModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        document.getElementById('modal-bundle-label').textContent = btn.dataset.bundle;
        document.getElementById('modal-system-qty').textContent   = btn.dataset.system;
        document.getElementById('modal-qty-physical').value       = btn.dataset.physical;
        document.getElementById('modal-notes').value              = btn.dataset.notes;
        document.getElementById('input-form').action              = btn.dataset.url;
    });

    modal.addEventListener('shown.bs.modal', function () {
        document.getElementById('modal-qty-physical').select();
    });
});
</script>
@endpush
@endif

@endsection
