@extends('layouts.app')

@section('title', 'Marketplace • Closing & Audit')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $filters = $audit['filters'];
    $summary = $audit['statement']['summary'];
    $closing = $audit['closing'];
    $qualityFixUrl = route('marketplace.reports.financial-quality', [
        'store_id' => $filters['store_id'],
        'status' => 'incomplete',
    ]);
    $statementUrl = route('marketplace.reports.financial-statement', $filters);
    $postingUrl = route('marketplace.reports.financial-statement.posting-preview', $filters);
@endphp

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <div class="text-uppercase text-muted small fw-semibold">Marketplace · Owner control</div>
            <h4 class="mb-1">Closing & Audit Keuangan</h4>
            <p class="text-muted mb-0">Checklist akhir periode untuk memastikan data, rekonsiliasi, posting, dan jurnal sudah siap dikunci.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('marketplace.reports.financial-statement', $filters) }}"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Financial statement</a>
            <a class="btn btn-outline-secondary" href="{{ route('marketplace.reports.financial-statement.posting-preview', $filters) }}"><i class="bi bi-journal-check me-1"></i> Review posting</a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i>{{ session('status') }}</div>
    @endif
    @if ($errors->has('closing'))
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i>{{ $errors->first('closing') }}</div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3"><label class="form-label small fw-semibold">Toko</label><select name="store_id" class="form-select"><option value="">Semua toko</option>@foreach ($stores as $store)<option value="{{ $store->id }}" @selected((int) ($filters['store_id'] ?? 0) === (int) $store->id)>{{ $store->name }} · {{ strtoupper($store->channel?->code ?? '-') }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Basis tanggal</label><select name="date_basis" class="form-select"><option value="ordered_at" @selected($filters['date_basis'] === 'ordered_at')>Tanggal order</option><option value="settlement_time" @selected($filters['date_basis'] === 'settlement_time')>Tanggal cair</option></select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Dari</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}"></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Sampai</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}"></div>
                <div class="col-md-auto"><button class="btn btn-outline-primary"><i class="bi bi-funnel me-1"></i> Terapkan</button></div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Scope</div><div class="fw-semibold">{{ $filters['date_from'] }} s/d {{ $filters['date_to'] }}</div><div class="small text-muted">{{ $filters['store_id'] ? 'Store #' . $filters['store_id'] : 'Semua toko' }} · {{ $filters['date_basis'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Order ready</div><div class="fs-5 fw-bold">{{ $fmt($summary['order_count']) }}</div><div class="small text-muted">Payout Rp {{ $fmt($summary['payout']) }}</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm h-100 border-{{ $closing?->status === 'closed' ? 'success' : 'warning' }}"><div class="card-body"><div class="text-muted small">Status periode</div><div class="fs-5 fw-bold text-{{ $closing?->status === 'closed' ? 'success' : 'warning' }}">{{ $closing?->status === 'closed' ? 'CLOSED' : 'OPEN' }}</div><div class="small text-muted">{{ $closing?->closed_at ? 'Closed ' . $closing->closed_at->format('d M Y H:i') : 'Belum dikunci' }}</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm h-100 border-{{ $audit['can_close'] ? 'success' : 'danger' }}"><div class="card-body"><div class="text-muted small">Audit decision</div><div class="fs-5 fw-bold text-{{ $audit['can_close'] ? 'success' : 'danger' }}">{{ $audit['can_close'] ? 'READY TO CLOSE' : 'BLOCKED' }}</div><div class="small text-muted">{{ collect($audit['checks'])->where('pass', false)->count() }} checklist belum pass</div></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Checklist closing</div>
                <div class="list-group list-group-flush">
                    @foreach ($audit['checks'] as $check)
                        @php
                            $fixUrl = match ($check['key']) {
                                'quality' => $qualityFixUrl,
                                'posting' => $audit['checks'][0]['pass'] ? $postingUrl : $qualityFixUrl,
                                'reconciliation' => $statementUrl,
                                'journal' => route('accounting.journals.index', ['from' => $filters['date_from'], 'to' => $filters['date_to']]),
                                'has_data' => $qualityFixUrl,
                                default => $statementUrl,
                            };
                        @endphp
                        <div class="list-group-item d-flex gap-3 align-items-start"><i class="bi {{ $check['pass'] ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }} fs-5"></i><div><div class="fw-semibold">{{ $check['label'] }}</div><div class="small text-muted">{{ $check['detail'] }}</div>@if (!$check['pass'])<a href="{{ $fixUrl }}" class="small fw-semibold">Perbaiki / buka halaman terkait <i class="bi bi-arrow-up-right"></i></a>@endif</div></div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Kontrol periode</div>
                <div class="card-body">
                    @if ($closing?->status === 'closed')
                        <div class="alert alert-success small"><i class="bi bi-lock-fill me-1"></i>Periode locked. Posting baru pada scope overlap akan ditolak.</div>
                        <form method="POST" action="{{ route('marketplace.reports.financial-closing.reopen', $closing) }}" onsubmit="return confirm('Reopen periode ini? Tindakan akan dicatat di audit log.');">
                            @csrf
                            <label class="form-label small fw-semibold">Alasan reopen</label>
                            <textarea name="reason" class="form-control mb-2" rows="2" required placeholder="Contoh: koreksi settlement setelah upload ulang"></textarea>
                            <button class="btn btn-outline-danger w-100"><i class="bi bi-unlock me-1"></i> Reopen periode</button>
                        </form>
                    @elseif ($audit['can_close'])
                        <div class="alert alert-warning small"><i class="bi bi-exclamation-lock me-1"></i>Setelah close, posting pada periode/scope overlap akan dikunci.</div>
                        <form method="POST" action="{{ route('marketplace.reports.financial-closing.close') }}" onsubmit="return confirm('Close periode ini? Pastikan seluruh checklist sudah benar.');">
                            @csrf
                            @foreach ($filters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                            <button class="btn btn-primary w-100"><i class="bi bi-lock-fill me-1"></i> Close periode</button>
                        </form>
                    @else
                        <div class="alert alert-danger small mb-0"><i class="bi bi-slash-circle me-1"></i>Close belum diizinkan. Selesaikan item checklist yang merah.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Audit trail scope</div>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Waktu</th><th>Aksi</th><th>User</th><th>Alasan</th></tr></thead><tbody>
            @forelse ($audit['logs'] as $log)
                <tr><td>{{ optional($log->created_at)->format('d M Y H:i:s') }}</td><td><span class="badge text-bg-{{ $log->action === 'closed' ? 'success' : ($log->action === 'reopened' ? 'warning' : 'info') }}">{{ strtoupper($log->action) }}</span></td><td>{{ $log->user?->name ?? '-' }}</td><td>{{ $log->reason ?: '-' }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada aktivitas audit untuk scope ini.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>
</div>
@endsection
