@extends('layouts.app')

@section('title', 'Marketplace • Tutup Periode Keuangan')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $filters = $audit['filters'];
    $summary = $audit['statement']['summary'];
    $closing = $audit['closing'];
    $isClosed = $closing?->status === 'closed';
    $failedChecks = collect($audit['checks'])->where('pass', false)->count();
    $checkCount = count($audit['checks']);
    $selectedStore = $filters['store_id']
        ? $stores->firstWhere('id', (int) $filters['store_id'])
        : null;
    $storeLabel = $selectedStore?->name ?: 'Semua toko';
    $basisLabel = $filters['date_basis'] === 'settlement_time' ? 'tanggal pencairan' : 'tanggal order';
    $qualityFixUrl = route('marketplace.reports.financial-quality', [
        'store_id' => $filters['store_id'],
        'status' => 'incomplete',
    ]);
    $statementUrl = route('marketplace.reports.financial-statement', $filters);
    $postingUrl = route('marketplace.reports.financial-statement.posting-preview', $filters);
@endphp

@push('head')
<style>
    .mfc-page { --mfc-ink:#172033; --mfc-muted:#64748b; --mfc-border:#e2e8f0; --mfc-soft:#f8fafc; --mfc-blue:#2563eb; color:var(--mfc-ink); }
    .mfc-page .card { border:1px solid var(--mfc-border); border-radius:16px; box-shadow:0 6px 20px rgba(15,23,42,.04)!important; }
    .mfc-breadcrumb { display:flex; align-items:center; gap:.45rem; color:var(--mfc-muted); font-size:.78rem; margin-bottom:1rem; }
    .mfc-breadcrumb a { color:inherit; text-decoration:none; }
    .mfc-breadcrumb a:hover { color:var(--mfc-blue); }
    .mfc-eyebrow, .mfc-step-kicker { color:var(--mfc-blue); font-size:.7rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
    .mfc-title { font-size:clamp(1.45rem,2.4vw,2.05rem); font-weight:800; letter-spacing:-.03em; margin:.25rem 0 .4rem; }
    .mfc-lead { color:var(--mfc-muted); max-width:720px; margin:0; }
    .mfc-header-actions { display:flex; gap:.5rem; flex-wrap:wrap; justify-content:flex-end; }
    .mfc-card-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1.1rem 1.2rem; border-bottom:1px solid var(--mfc-border); }
    .mfc-card-title { font-size:1rem; font-weight:800; margin:0; }
    .mfc-card-copy { color:var(--mfc-muted); font-size:.82rem; margin:.25rem 0 0; }
    .mfc-scope { background:linear-gradient(135deg,#eff6ff 0%,#fff 58%); }
    .mfc-scope-body { padding:1.15rem 1.2rem; }
    .mfc-scope-form { display:grid; grid-template-columns:minmax(190px,1.3fr) minmax(170px,1fr) minmax(140px,.8fr) minmax(140px,.8fr) auto; gap:.75rem; align-items:end; margin-top:1rem; }
    .mfc-field-label { display:block; color:#334155; font-size:.75rem; font-weight:750; margin-bottom:.35rem; }
    .mfc-field-help { color:var(--mfc-muted); display:block; font-size:.7rem; margin-top:.3rem; }
    .mfc-scope-note { display:flex; align-items:center; gap:.45rem; color:#475569; font-size:.8rem; margin-top:1rem; }
    .mfc-status { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1.1rem 1.2rem; margin:1rem 0; border:1px solid var(--mfc-border); border-radius:16px; background:#fff; }
    .mfc-status.is-ready { border-color:#86efac; background:#f0fdf4; }
    .mfc-status.is-blocked { border-color:#fecaca; background:#fff7f7; }
    .mfc-status.is-closed { border-color:#93c5fd; background:#eff6ff; }
    .mfc-status-main { display:flex; align-items:flex-start; gap:.8rem; }
    .mfc-status-icon { display:grid; place-items:center; width:2.35rem; height:2.35rem; border-radius:12px; background:#e2e8f0; color:#475569; font-size:1.15rem; flex:0 0 auto; }
    .is-ready .mfc-status-icon { background:#dcfce7; color:#15803d; }
    .is-blocked .mfc-status-icon { background:#fee2e2; color:#b91c1c; }
    .is-closed .mfc-status-icon { background:#dbeafe; color:#1d4ed8; }
    .mfc-status-title { font-size:1.05rem; font-weight:800; margin:0; }
    .mfc-status-copy { color:#475569; font-size:.84rem; margin:.25rem 0 0; }
    .mfc-status-badge, .mfc-check-badge { border-radius:999px; font-size:.7rem; font-weight:800; padding:.35rem .65rem; white-space:nowrap; }
    .mfc-status-badge { background:#e2e8f0; color:#475569; }
    .is-ready .mfc-status-badge { background:#dcfce7; color:#166534; }
    .is-blocked .mfc-status-badge { background:#fee2e2; color:#991b1b; }
    .is-closed .mfc-status-badge { background:#dbeafe; color:#1e40af; }
    .mfc-metric { height:100%; padding:1rem 1.1rem; }
    .mfc-metric-label { color:var(--mfc-muted); font-size:.75rem; font-weight:700; }
    .mfc-metric-value { font-size:1.45rem; font-weight:850; line-height:1.15; margin-top:.35rem; }
    .mfc-metric-note { color:var(--mfc-muted); font-size:.75rem; margin-top:.3rem; }
    .mfc-checklist { list-style:none; padding:0; margin:0; }
    .mfc-check { display:flex; align-items:flex-start; gap:.85rem; padding:1rem 1.2rem; border-bottom:1px solid var(--mfc-border); }
    .mfc-check:last-child { border-bottom:0; }
    .mfc-check-number { display:grid; place-items:center; width:1.8rem; height:1.8rem; border-radius:50%; background:#e2e8f0; color:#475569; font-size:.75rem; font-weight:800; flex:0 0 auto; }
    .mfc-check.is-pass .mfc-check-number { background:#dcfce7; color:#166534; }
    .mfc-check.is-fail .mfc-check-number { background:#fee2e2; color:#991b1b; }
    .mfc-check-content { min-width:0; flex:1; }
    .mfc-check-heading { display:flex; align-items:center; flex-wrap:wrap; gap:.45rem; font-weight:800; }
    .mfc-check-badge { background:#dcfce7; color:#166534; }
    .is-fail .mfc-check-badge { background:#fee2e2; color:#991b1b; }
    .mfc-check-detail { color:var(--mfc-muted); font-size:.82rem; margin:.3rem 0 0; }
    .mfc-check-action { display:inline-flex; align-items:center; gap:.3rem; font-size:.78rem; font-weight:750; margin-top:.5rem; text-decoration:none; }
    .mfc-control-body { padding:1.1rem 1.2rem; }
    .mfc-consequence { background:#fffbeb; border:1px solid #fde68a; border-radius:12px; color:#92400e; font-size:.8rem; padding:.75rem .85rem; }
    .mfc-closed-box { background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; color:#1e40af; font-size:.8rem; padding:.75rem .85rem; }
    .mfc-audit-table th { color:#64748b; font-size:.7rem; letter-spacing:.04em; text-transform:uppercase; white-space:nowrap; }
    .mfc-audit-table td { font-size:.8rem; }
    @media (max-width: 992px) { .mfc-scope-form { grid-template-columns:repeat(2,minmax(0,1fr)); } .mfc-scope-form .mfc-submit { grid-column:span 2; } }
    @media (max-width: 576px) { .mfc-scope-form { grid-template-columns:1fr; } .mfc-scope-form .mfc-submit { grid-column:auto; } .mfc-status { align-items:flex-start; flex-direction:column; } .mfc-header-actions { justify-content:flex-start; } }
</style>
@endpush

@section('content')
<div class="mfc-page container-fluid py-4">
    <nav class="mfc-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('marketplace.orders') }}">Marketplace</a>
        <i class="bi bi-chevron-right"></i>
        <span>Keuangan</span>
        <i class="bi bi-chevron-right"></i>
        <span>Tutup periode</span>
    </nav>

    <header class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="mfc-eyebrow">Kontrol akhir laporan</div>
            <h1 class="mfc-title">Tutup periode keuangan</h1>
            <p class="mfc-lead">Pastikan data, payout, posting accounting, dan jurnal sudah benar sebelum periode dikunci.</p>
        </div>
        <div class="mfc-header-actions">
            <a class="btn btn-outline-secondary" href="{{ route('marketplace.reports.financial-statement', $filters) }}"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Lihat laporan keuangan</a>
            <a class="btn btn-outline-primary" href="{{ route('marketplace.reports.financial-statement.posting-preview', $filters) }}"><i class="bi bi-journal-check me-1"></i>Tinjau posting</a>
        </div>
    </header>

    @if (session('status'))
        <div class="alert alert-success d-flex gap-2 align-items-start"><i class="bi bi-check-circle-fill mt-1"></i><div>{{ session('status') }}</div></div>
    @endif
    @if ($errors->has('closing'))
        <div class="alert alert-danger d-flex gap-2 align-items-start"><i class="bi bi-exclamation-triangle-fill mt-1"></i><div>{{ $errors->first('closing') }}</div></div>
    @endif

    <section class="card mfc-scope mb-3" aria-labelledby="scope-title">
        <div class="mfc-scope-body">
            <div class="mfc-step-kicker">Langkah 1 dari 3</div>
            <h2 id="scope-title" class="mfc-card-title mt-1">Pilih periode yang ingin ditutup</h2>
            <p class="mfc-card-copy">Audit dan status closing akan dihitung berdasarkan pilihan di bawah. Pastikan toko dan rentang tanggal sudah sesuai.</p>
            <form method="GET" class="mfc-scope-form">
                <div>
                    <label class="mfc-field-label" for="mfc-store">Toko</label>
                    <select id="mfc-store" name="store_id" class="form-select">
                        <option value="">Semua toko</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" @selected((int) ($filters['store_id'] ?? 0) === (int) $store->id)>{{ $store->name }} · {{ strtoupper($store->channel?->code ?? '-') }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mfc-field-label" for="mfc-date-basis">Gunakan tanggal</label>
                    <select id="mfc-date-basis" name="date_basis" class="form-select">
                        <option value="ordered_at" @selected($filters['date_basis'] === 'ordered_at')>Tanggal order</option>
                        <option value="settlement_time" @selected($filters['date_basis'] === 'settlement_time')>Tanggal pencairan</option>
                    </select>
                    <span class="mfc-field-help">Menentukan order yang masuk ke periode.</span>
                </div>
                <div>
                    <label class="mfc-field-label" for="mfc-date-from">Tanggal mulai</label>
                    <input id="mfc-date-from" type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                </div>
                <div>
                    <label class="mfc-field-label" for="mfc-date-to">Tanggal akhir</label>
                    <input id="mfc-date-to" type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                </div>
                <div class="mfc-submit"><button class="btn btn-primary w-100"><i class="bi bi-arrow-repeat me-1"></i>Perbarui audit</button></div>
            </form>
            <div class="mfc-scope-note"><i class="bi bi-info-circle"></i><span>Saat ini: <strong>{{ $storeLabel }}</strong>, {{ $filters['date_from'] }}–{{ $filters['date_to'] }}, berdasarkan {{ $basisLabel }}.</span></div>
        </div>
    </section>

    @php
        $statusClass = $isClosed ? 'is-closed' : ($audit['can_close'] ? 'is-ready' : 'is-blocked');
        $statusTitle = $isClosed ? 'Periode sudah dikunci' : ($audit['can_close'] ? 'Periode siap dikunci' : 'Periode belum siap dikunci');
        $statusCopy = $isClosed
            ? 'Periode ini sudah ditutup. Perubahan pada scope yang sama akan membutuhkan proses buka kembali.'
            : ($audit['can_close'] ? 'Semua pemeriksaan lulus. Anda dapat mengunci periode ini setelah memastikan ringkasan laporan sudah benar.' : 'Selesaikan item checklist yang belum lulus sebelum periode dapat dikunci.');
        $statusBadge = $isClosed ? 'TERKUNCI' : ($audit['can_close'] ? 'SIAP' : 'TERTUNDA');
    @endphp
    <section class="mfc-status {{ $statusClass }}" aria-live="polite">
        <div class="mfc-status-main">
            <div class="mfc-status-icon"><i class="bi {{ $isClosed ? 'bi-lock-fill' : ($audit['can_close'] ? 'bi-check-lg' : 'bi-exclamation-lg') }}"></i></div>
            <div>
                <h2 class="mfc-status-title">{{ $statusTitle }}</h2>
                <p class="mfc-status-copy">{{ $statusCopy }}</p>
            </div>
        </div>
        <span class="mfc-status-badge">{{ $statusBadge }}</span>
    </section>

    <div class="row g-3 mb-4" aria-label="Ringkasan periode">
        <div class="col-sm-6 col-xl-3"><div class="card mfc-metric"><div class="mfc-metric-label">Order siap masuk laporan</div><div class="mfc-metric-value">{{ $fmt($summary['order_count']) }}</div><div class="mfc-metric-note">Dalam periode terpilih</div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card mfc-metric"><div class="mfc-metric-label">Total payout</div><div class="mfc-metric-value">Rp {{ $fmt($summary['payout']) }}</div><div class="mfc-metric-note">Nilai yang akan dikunci</div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card mfc-metric"><div class="mfc-metric-label">Status periode</div><div class="mfc-metric-value text-{{ $isClosed ? 'primary' : 'warning' }}">{{ $isClosed ? 'Terkunci' : 'Terbuka' }}</div><div class="mfc-metric-note">{{ $closing?->closed_at ? 'Dikunci ' . $closing->closed_at->format('d M Y H:i') : 'Belum dikunci' }}</div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card mfc-metric"><div class="mfc-metric-label">Pemeriksaan belum lulus</div><div class="mfc-metric-value text-{{ $failedChecks ? 'danger' : 'success' }}">{{ $failedChecks }} <span class="fs-6 fw-normal text-muted">/ {{ $checkCount }}</span></div><div class="mfc-metric-note">{{ $failedChecks ? 'Perlu ditindaklanjuti' : 'Semua pemeriksaan lulus' }}</div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-7">
            <section class="card h-100" aria-labelledby="checklist-title">
                <div class="mfc-card-header">
                    <div><div class="mfc-step-kicker">Langkah 2 dari 3</div><h2 id="checklist-title" class="mfc-card-title mt-1">Selesaikan checklist closing</h2><p class="mfc-card-copy">Setiap item harus berstatus <strong>Lulus</strong> sebelum periode bisa dikunci.</p></div>
                    <span class="badge text-bg-{{ $failedChecks ? 'danger' : 'success' }}">{{ $failedChecks ? $failedChecks . ' perlu tindakan' : 'Semua lulus' }}</span>
                </div>
                <ol class="mfc-checklist">
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
                            $actionLabel = match ($check['key']) {
                                'quality' => 'Buka pemeriksaan data',
                                'reconciliation' => 'Buka laporan keuangan',
                                'posting' => 'Tinjau posting accounting',
                                'journal' => 'Buka jurnal accounting',
                                'has_data' => 'Periksa data periode',
                                default => 'Buka halaman terkait',
                            };
                        @endphp
                        <li class="mfc-check {{ $check['pass'] ? 'is-pass' : 'is-fail' }}">
                            <div class="mfc-check-number">{{ $loop->iteration }}</div>
                            <div class="mfc-check-content">
                                <div class="mfc-check-heading"><span>{{ $check['label'] }}</span><span class="mfc-check-badge">{{ $check['pass'] ? 'Lulus' : 'Belum lulus' }}</span></div>
                                <p class="mfc-check-detail">{{ $check['detail'] }}</p>
                                @if (!$check['pass'])<a class="mfc-check-action" href="{{ $fixUrl }}">{{ $actionLabel }} <i class="bi bi-arrow-up-right"></i></a>@endif
                            </div>
                            <i class="bi {{ $check['pass'] ? 'bi-check-circle-fill text-success' : 'bi-exclamation-circle-fill text-danger' }} fs-5"></i>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        <div class="col-xl-5">
            <section class="card h-100" aria-labelledby="control-title">
                <div class="mfc-card-header"><div><div class="mfc-step-kicker">Langkah 3 dari 3</div><h2 id="control-title" class="mfc-card-title mt-1">{{ $isClosed ? 'Kelola periode terkunci' : 'Kunci periode' }}</h2><p class="mfc-card-copy">{{ $isClosed ? 'Buka kembali hanya jika ada koreksi yang memang perlu dilakukan.' : 'Mengunci angka dan mencegah posting baru pada periode ini.' }}</p></div></div>
                <div class="mfc-control-body">
                    @if ($isClosed)
                        <div class="mfc-closed-box mb-3"><i class="bi bi-lock-fill me-1"></i><strong>Periode terkunci.</strong> Posting baru pada scope yang sama akan ditolak sampai periode dibuka kembali.</div>
                        <form method="POST" action="{{ route('marketplace.reports.financial-closing.reopen', $closing) }}" onsubmit="return confirm('Buka kembali periode ini? Alasan akan dicatat di audit trail.');">
                            @csrf
                            <label class="mfc-field-label" for="mfc-reopen-reason">Alasan buka kembali <span class="text-danger">*</span></label>
                            <textarea id="mfc-reopen-reason" name="reason" class="form-control mb-2" rows="3" required placeholder="Contoh: koreksi settlement setelah sinkronisasi ulang"></textarea>
                            <button class="btn btn-outline-danger w-100"><i class="bi bi-unlock me-1"></i>Buka kembali periode</button>
                        </form>
                    @elseif ($audit['can_close'])
                        <div class="mfc-consequence mb-3"><i class="bi bi-exclamation-triangle me-1"></i><strong>Perhatian:</strong> setelah dikunci, posting pada periode atau scope yang sama akan ditolak.</div>
                        <form method="POST" action="{{ route('marketplace.reports.financial-closing.close') }}" onsubmit="return confirm('Kunci periode ini? Pastikan ringkasan laporan sudah benar.');">
                            @csrf
                            @foreach ($filters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                            <button class="btn btn-primary btn-lg w-100"><i class="bi bi-lock-fill me-1"></i>Kunci periode sekarang</button>
                        </form>
                    @else
                        <div class="alert alert-danger small mb-3"><i class="bi bi-slash-circle me-1"></i><strong>Belum bisa dikunci.</strong> Selesaikan {{ $failedChecks }} item checklist yang belum lulus terlebih dahulu.</div>
                        <button class="btn btn-secondary w-100" type="button" disabled><i class="bi bi-lock me-1"></i>Kunci periode belum tersedia</button>
                    @endif
                </div>
            </section>
        </div>
    </div>

    <section class="card" aria-labelledby="audit-trail-title">
        <div class="mfc-card-header"><div><h2 id="audit-trail-title" class="mfc-card-title">Riwayat tindakan</h2><p class="mfc-card-copy">Catatan perubahan untuk {{ $storeLabel }} · {{ $filters['date_from'] }}–{{ $filters['date_to'] }}.</p></div><i class="bi bi-clock-history fs-5 text-muted"></i></div>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0 mfc-audit-table"><thead class="table-light"><tr><th>Waktu</th><th>Tindakan</th><th>Pengguna</th><th>Alasan</th></tr></thead><tbody>
            @forelse ($audit['logs'] as $log)
                <tr><td>{{ optional($log->created_at)->format('d M Y H:i:s') }}</td><td><span class="badge text-bg-{{ $log->action === 'closed' ? 'success' : ($log->action === 'reopened' ? 'warning' : 'info') }}">{{ $log->action === 'closed' ? 'DIKUNCI' : ($log->action === 'reopened' ? 'DIBUKA KEMBALI' : strtoupper($log->action)) }}</span></td><td>{{ $log->user?->name ?? '-' }}</td><td>{{ $log->reason ?: '-' }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada tindakan pada periode ini.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
</div>
@endsection
