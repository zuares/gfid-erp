import re

with open('resources/views/production/sewing_returns/create.blade.php', 'r') as f:
    create_html = f.read()

# Extract CSS from create.blade.php
style_match = re.search(r'<style>(.*?)</style>', create_html, re.DOTALL)
css_content = style_match.group(1) if style_match else ""

with open('resources/views/production/qc/sewing_edit.blade.php', 'r') as f:
    edit_html = f.read()

new_edit_html = """{{-- resources/views/production/qc/sewing_edit.blade.php --}}
@extends('layouts.app')

@section('title', 'QC Jahit · ' . $sewingReturn->code)

@push('head')
<style>
""" + css_content + """
    .page-wrap { max-width: 980px; margin: 0 auto; padding: 14px 12px 96px; }
    
    @media(max-width:991.98px) {
        .page-wrap { padding-bottom: calc(var(--bottom-nav-h) + 130px + var(--vv-kbd)); }
        body.keyboard-open .page-wrap { padding-bottom: calc(14rem + var(--vv-kbd)); }
    }
</style>
@endpush

@section('content')
@php
    $statusLabel = $hasQcSewing ? 'QC Selesai' : 'Belum QC';
    $totalBundles = count($rows);
    $totalIn = 0;
    $totalOk = 0;
    $totalReject = 0;

    foreach ($rows as $idx => $row) {
        $totalIn += (float) $row['qty_max'];
        $totalOk += (float) old("results.{$idx}.qty_ok", $row['qty_ok']);
        $totalReject += (float) old("results.{$idx}.qty_reject", $row['qty_reject']);
    }
@endphp

<div class="page-wrap">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($hasQcSewing)
        <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
            QC Jahit untuk Setor Jahit ini sudah pernah diinput. Simpan ulang akan menimpa hasil QC sebelumnya.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="panel mb-2">
        <div class="panel-h">
            <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                <div>
                    <div class="h-title">QC Jahit</div>
                    <div class="text-muted small mt-1">Setor Jahit: {{ $sewingReturn->code }}</div>
                </div>
                <div class="return-head-actions">
                    <a href="{{ route('production.qc.index', ['stage' => 'sewing']) }}"
                       class="btn btn-sm btn-outline-secondary"
                       style="border-radius:8px;">
                        Kembali
                    </a>
                    <a href="{{ route('production.sewing.returns.show', $sewingReturn) }}"
                       class="btn btn-sm btn-outline-success"
                       style="border-radius:8px;">
                        Lihat Setor
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <form id="sewing-qc-form" method="POST" action="{{ route('production.qc.sewing.update', $sewingReturn) }}">
            @csrf
            @method('PUT')
            
            <div class="panel-b">
                <div class="meta">
                    <div class="row align-items-end return-filter-row">
                        <div class="col-5 col-lg-2">
                            <label class="form-label form-label-sm">Tanggal QC</label>
                            <input type="date" name="qc_date"
                                   class="form-control form-control-sm @error('qc_date') is-invalid @enderror"
                                   value="{{ old('qc_date', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-7 col-lg-3">
                            <label class="form-label form-label-sm">Operator QC</label>
                            <input type="hidden" name="operator_id" value="{{ $loginOperator?->id }}">
                            <div class="form-control form-control-sm mono" style="background: rgba(148,163,184,.1);">
                                {{ $loginOperator?->name ?? '(Tidak ditemukan)' }}
                            </div>
                        </div>
                        <div class="col-lg-3 d-none d-lg-block">
                            <label class="form-label form-label-sm">Status QC</label>
                            <div class="form-control form-control-sm mono" style="background: rgba(148,163,184,.1);">
                                {{ $statusLabel }}
                            </div>
                        </div>
                        <div class="col-lg-4 d-none d-lg-block text-end">
                            <label class="form-label form-label-sm">Tanggal Setor</label>
                            <div class="form-control form-control-sm mono" style="background: rgba(148,163,184,.1);">
                                {{ $sewingReturn->date?->format('d/m/Y') ?? '-' }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="mini-kpi-row kpi-4">
                        <div class="mini-kpi">
                            <span class="lbl">Bundle</span>
                            <span class="val mono">{{ number_format($totalBundles, 0, ',', '.') }}</span>
                        </div>
                        <div class="mini-kpi">
                            <span class="lbl">Masuk</span>
                            <span class="val mono">
                                <span>{{ number_format($totalIn, 0, ',', '.') }}</span>
                                <span class="unit">pcs</span>
                            </span>
                        </div>
                        <div class="mini-kpi is-main">
                            <span class="lbl">OK</span>
                            <span class="val mono" style="color: #16a34a;">
                                <span id="kpi-ok">{{ number_format($totalOk, 0, ',', '.') }}</span>
                                <span class="unit">pcs</span>
                            </span>
                        </div>
                        <div class="mini-kpi is-main" style="background: rgba(185, 28, 28, .06);">
                            <span class="lbl">Reject</span>
                            <span class="val mono" style="color: #b91c1c;">
                                <span id="kpi-reject">{{ number_format($totalReject, 0, ',', '.') }}</span>
                                <span class="unit">pcs</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="list" id="list-byop">
                    @if(empty($rows))
                        <div class="text-center py-4 text-muted">Tidak ada bundle yang bisa di-QC pada Setor Jahit ini.</div>
                    @else
                        @foreach($rows as $i => $row)
                            <div class="cardx mono fin-item"
                                 data-idx="{{ $i }}"
                                 data-max="{{ $row['qty_max'] }}">
                                <div class="cardx-h">
                                    <div class="cardx-left">
                                        <div>
                                            <div class="code">{{ $row['bundle_code'] }}</div>
                                            <div class="meta-inline">
                                                <span class="dot">•</span>
                                                <span class="truncate">{{ $row['item_code'] }}</span>
                                                @if($row['cutting_job_code'])
                                                    <span class="dot">•</span>
                                                    <span class="truncate text-muted">{{ $row['cutting_job_code'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="right-metrics card-metrics">
                                        <div class="metric-main">
                                            <span class="lbl">Masuk</span>
                                            <span class="val">{{ number_format($row['qty_max'], 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="cardx-b">
                                    <div class="grid2">
                                        <div class="field">
                                            <label>OK</label>
                                            <input type="number" step="1" min="0" max="{{ $row['qty_max'] }}"
                                                   inputmode="numeric"
                                                   class="form-control form-control-sm qty ok num-input select-all-on-focus qty-ok"
                                                   name="results[{{ $i }}][qty_ok]"
                                                   data-idx="{{ $i }}"
                                                   value="{{ old("results.{$i}.qty_ok", $row['qty_ok']) }}" placeholder="0"
                                                   oninput="syncReject(this, {{ $i }}, {{ $row['qty_max'] }})">
                                        </div>
                                        <div class="field">
                                            <label>Reject</label>
                                            <input type="number" step="1" min="0" max="{{ $row['qty_max'] }}"
                                                   inputmode="numeric"
                                                   class="form-control form-control-sm qty rj num-input select-all-on-focus qty-reject"
                                                   name="results[{{ $i }}][qty_reject]"
                                                   id="reject_{{ $i }}"
                                                   data-idx="{{ $i }}"
                                                   value="{{ old("results.{$i}.qty_reject", $row['qty_reject']) }}" placeholder="0"
                                                   oninput="syncOk(this, {{ $i }}, {{ $row['qty_max'] }})">
                                        </div>
                                    </div>
                                    
                                    <div class="field notes qcs-mobile-reason {{ (float) old("results.{$i}.qty_reject", $row['qty_reject']) > 0 ? 'is-show' : '' }}" style="margin-top: .35rem;">
                                        <label>Alasan Reject</label>
                                        <select name="results[{{ $i }}][reject_reason]" class="form-select form-select-sm" style="border-radius: 12px;">
                                            <option value="">- Pilih Alasan -</option>
                                            <option value="Reject Jahit" {{ old("results.{$i}.reject_reason", $row['reject_reason']) == 'Reject Jahit' ? 'selected' : '' }}>Reject Jahit</option>
                                            <option value="Reject Bahan" {{ old("results.{$i}.reject_reason", $row['reject_reason']) == 'Reject Bahan' ? 'selected' : '' }}>Reject Bahan</option>
                                            @if(old("results.{$i}.reject_reason", $row['reject_reason']) && !in_array(old("results.{$i}.reject_reason", $row['reject_reason']), ['Reject Jahit', 'Reject Bahan']))
                                                <option value="{{ old("results.{$i}.reject_reason", $row['reject_reason']) }}" selected>{{ old("results.{$i}.reject_reason", $row['reject_reason']) }}</option>
                                            @endif
                                        </select>
                                    </div>
                                    
                                    <input type="hidden" name="results[{{ $i }}][sewing_return_line_id]" value="{{ $row['sewing_return_line_id'] }}">
                                    <input type="hidden" name="results[{{ $i }}][bundle_id]" value="{{ $row['bundle_id'] }}">
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
                
                <div class="fab-wrap" id="fab-wrap">
                    <a href="{{ route('production.sewing.returns.show', $sewingReturn) }}"
                       class="btn btn-sm btn-outline-secondary fab-back">←</a>
                    <button type="submit" class="btn btn-sm btn-success fab-save">
                        Simpan QC
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function initVV() {
        if (!window.visualViewport) return;
        const vv = window.visualViewport;
        const set = () => {
            const kbd = Math.max(0, (window.innerHeight - vv.height - vv.offsetTop));
            document.documentElement.style.setProperty('--vv-kbd', `${kbd}px`);
        };
        vv.addEventListener('resize', set);
        vv.addEventListener('scroll', set);
        set();
    })();

    function numberValue(input) {
        return parseFloat(input.value) || 0;
    }

    function syncReject(okInput, idx, max) {
        const ok = numberValue(okInput);
        const rejectField = document.getElementById('reject_' + idx);
        const suggestedReject = Math.max(0, max - ok);

        if (rejectField) {
            rejectField.value = suggestedReject;
        }

        toggleReason(idx);
        updateSummary();
    }

    function syncOk(rejectInput, idx, max) {
        const reject = numberValue(rejectInput);
        const okInputs = document.querySelectorAll('.qty-ok');
        const okField = okInputs[idx];
        const suggestedOk = Math.max(0, max - reject);

        if (okField) {
            okField.value = suggestedOk;
        }

        toggleReason(idx);
        updateSummary();
    }

    function toggleReason(idx) {
        const rejectField = document.getElementById('reject_' + idx);
        const reasonWrap = rejectField?.closest('.cardx-b')?.querySelector('.notes');

        if (!reasonWrap || !rejectField) return;

        if (numberValue(rejectField) > 0) {
            reasonWrap.classList.add('is-show');
        } else {
            reasonWrap.classList.remove('is-show');
        }
    }

    function updateSummary() {
        const okInputs = document.querySelectorAll('.qty-ok');
        const rejectInputs = document.querySelectorAll('.qty-reject');
        
        let totalOk = 0;
        let totalReject = 0;
        
        okInputs.forEach(el => totalOk += numberValue(el));
        rejectInputs.forEach(el => totalReject += numberValue(el));

        document.getElementById('kpi-ok').textContent = new Intl.NumberFormat('id-ID').format(totalOk);
        document.getElementById('kpi-reject').textContent = new Intl.NumberFormat('id-ID').format(totalReject);
    }
    
    // auto select all on focus
    document.querySelectorAll('.select-all-on-focus').forEach(input => {
        input.addEventListener('focus', function() {
            this.select();
        });
    });
</script>
@endpush
"""

with open('resources/views/production/qc/sewing_edit.blade.php', 'w') as f:
    f.write(new_edit_html)
