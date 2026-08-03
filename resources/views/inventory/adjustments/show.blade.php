{{-- resources/views/inventory/adjustments/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail Inventory Adjustment • ' . $adjustment->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(96, 165, 250, .14) 0, rgba(45, 212, 191, .10) 26%, #f9fafb 60%)
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .30);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06), 0 0 0 1px rgba(15, 23, 42, .03)
        }

        .hero-shell {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(37, 99, 235, .08), rgba(14, 165, 233, .04));
        }

        body[data-theme="dark"] .hero-shell {
            background: linear-gradient(135deg, rgba(30, 41, 59, .9), rgba(15, 23, 42, .88));
        }

        .hero-title {
            font-size: 1.15rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: .2rem;
        }

        .hero-sub {
            font-size: .86rem;
            color: #64748b;
            max-width: 62ch;
        }

        body[data-theme="dark"] .hero-sub {
            color: #9ca3af;
        }

        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-top: .85rem;
        }

        .hero-meta-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .24rem .55rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .28);
            background: rgba(255, 255, 255, .55);
            font-size: .74rem;
            color: #475569;
        }

        body[data-theme="dark"] .hero-meta-pill {
            background: rgba(15, 23, 42, .45);
            color: #cbd5e1;
        }

        .hero-action {
            min-width: min(360px, 100%);
            padding: .9rem;
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .18);
            background: rgba(255, 255, 255, .55);
        }

        body[data-theme="dark"] .hero-action {
            background: rgba(15, 23, 42, .5);
            border-color: rgba(51, 65, 85, .75);
        }

        .hero-action-note {
            margin-top: .5rem;
            font-size: .78rem;
            color: #64748b;
            line-height: 1.35;
        }

        body[data-theme="dark"] .hero-action-note {
            color: #94a3b8;
        }

        .badge-status {
            font-size: .7rem;
            padding: .18rem .5rem;
            border-radius: 999px;
            font-weight: 700
        }

        .badge-status--draft {
            background: rgba(148, 163, 184, .2);
            color: #475569
        }

        .badge-status--pending {
            background: rgba(234, 179, 8, .18);
            color: #854d0e
        }

        .badge-status--approved {
            background: rgba(22, 163, 74, .18);
            color: #15803d
        }

        .badge-status--void {
            background: rgba(248, 113, 113, .18);
            color: #b91c1c
        }

        .badge-approver {
            font-size: .7rem;
            padding: .18rem .55rem;
            border-radius: 999px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            border: 1px solid rgba(148, 163, 184, .45);
            background: rgba(15, 23, 42, .02);
            color: #0f172a
        }

        body[data-theme="dark"] .badge-approver {
            background: rgba(15, 23, 42, .8);
            border-color: rgba(148, 163, 184, .7);
            color: rgba(226, 232, 240, .96)
        }

        .badge-approver--owner {
            border-color: rgba(22, 163, 74, .7);
            background: rgba(22, 163, 74, .1);
            color: #166534
        }

        body[data-theme="dark"] .badge-approver--owner {
            background: rgba(22, 163, 74, .22);
            color: rgba(187, 247, 208, .98)
        }

        .badge-approver--admin {
            border-color: rgba(59, 130, 246, .7);
            background: rgba(59, 130, 246, .1);
            color: #1d4ed8
        }

        body[data-theme="dark"] .badge-approver--admin {
            background: rgba(59, 130, 246, .22);
            color: rgba(191, 219, 254, .98)
        }

        .badge-approver-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: currentColor
        }

        .pill-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8
        }

        .text-mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Courier New", monospace
        }

        .table-wrap {
            margin-top: .75rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .24);
            overflow-x: auto;
            overflow-y: auto;
            max-height: 420px;
            background: rgba(248, 250, 252, .92)
        }

        body[data-theme="dark"] .table-wrap {
            background: rgba(15, 23, 42, .92);
            border-color: rgba(51, 65, 85, .9)
        }

        .table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
            white-space: nowrap;
            background: #fff !important
        }

        body[data-theme="dark"] .table thead th {
            background: #0f172a !important;
            color: #e5e7eb
        }

        .diff-plus {
            color: #16a34a
        }

        .diff-minus {
            color: #dc2626
        }

        .badge-dir {
            font-size: .7rem;
            padding: .16rem .5rem;
            border-radius: 999px;
            white-space: nowrap;
            font-weight: 700
        }

        .badge-dir--in {
            background: rgba(22, 163, 74, .18);
            color: #15803d
        }

        .badge-dir--out {
            background: rgba(248, 113, 113, .18);
            color: #b91c1c
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem
        }

        .summary-card {
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 12px;
            padding: .65rem .75rem;
            background: rgba(2, 6, 23, .02)
        }

        body[data-theme="dark"] .summary-card {
            background: rgba(15, 23, 42, .35);
            border-color: rgba(51, 65, 85, .7)
        }

        .summary-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem
        }

        .summary-value {
            margin-top: .35rem;
            font-weight: 800;
            font-size: 1.02rem
        }

        .summary-sub {
            font-size: .8rem;
            color: #64748b
        }

        body[data-theme="dark"] .summary-sub {
            color: rgba(148, 163, 184, .92)
        }

        .net-pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-size: .72rem;
            font-weight: 800;
            padding: .18rem .5rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .35)
        }

        .ref-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .18rem .5rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .35);
            font-size: .78rem;
            background: rgba(15, 23, 42, .02)
        }

        body[data-theme="dark"] .ref-chip {
            background: rgba(15, 23, 42, .55)
        }

        .ref-chip b {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
            font-variant-numeric: tabular-nums
        }

        .action-bar {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: center;
        }

        .btn-pill {
            border-radius: 999px;
        }

        .btn-post {
            border: none;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            box-shadow: 0 10px 22px rgba(37, 99, 235, .25);
        }

        .btn-post:hover {
            filter: brightness(1.03);
        }

        @media (max-width:767.98px) {
            .page-wrap {
                padding-inline: .5rem
            }

            .summary-grid {
                grid-template-columns: 1fr
            }

            .table thead {
                display: none
            }

            .table tbody tr {
                display: block;
                border-bottom: 1px solid rgba(148, 163, 184, .25);
                padding: .45rem .75rem
            }

            .table tbody tr:last-child {
                border-bottom: none
            }

            .table tbody td {
                display: flex;
                justify-content: space-between;
                gap: .75rem;
                padding: .18rem 0;
                border-top: none;
                font-size: .86rem
            }

            .table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #64748b
            }

            .table-wrap {
                max-height: none !important;
                overflow-y: visible !important
            }
        }
    </style>
@endpush

@section('content')
    @php
        use App\Models\InventoryAdjustment;
        use App\Models\StockOpname;
        use App\Models\CuttingJob;

        $statusClass = match ($adjustment->status) {
            InventoryAdjustment::STATUS_DRAFT => 'badge-status badge-status--draft',
            InventoryAdjustment::STATUS_PENDING => 'badge-status badge-status--pending',
            InventoryAdjustment::STATUS_APPROVED => 'badge-status badge-status--approved',
            InventoryAdjustment::STATUS_VOID => 'badge-status badge-status--void',
            default => 'badge-status badge-status--draft',
        };

        $user = auth()->user();
        $isOwnerAdmin = $user && in_array($user->role ?? null, ['owner', 'admin'], true);

        // ==== DETEKSI OVERPRODUCTION ====
        $isOverproduction =
            $adjustment->reason === 'Cutting Overproduction Adjustment' ||
            $adjustment->source_type === CuttingJob::class;

        $sourceLabel = null;
        $sourceUrl = null;

        if ($adjustment->source_type === StockOpname::class && $adjustment->source) {
            $sourceLabel = 'Stock Opname • ' . $adjustment->source->code;
            $sourceUrl = route('inventory.stock_opnames.show', $adjustment->source);
        } elseif ($adjustment->source_type === CuttingJob::class && $adjustment->source) {
            // overproduction: source adalah CuttingJob
            $sourceLabel = 'Cutting Job • ' . ($adjustment->source->code ?? '#' . $adjustment->source_id);
            $sourceUrl = route('production.cutting_jobs.show', $adjustment->source);
        } elseif (
            $adjustment->reference_type === \App\Models\SewingPickup::class
            && $adjustment->reference_id
        ) {
            $sourcePickup = \App\Models\SewingPickup::find($adjustment->reference_id);
            if ($sourcePickup) {
                $sourceLabel = 'Pickup Jahit • ' . $sourcePickup->code;
                $sourceUrl = route('production.sewing.pickups.show', $sourcePickup);
            }
        }

        $canApprove = $isOwnerAdmin && method_exists($adjustment, 'canApprove') && $adjustment->canApprove();
        $canVoid = $isOwnerAdmin && in_array($adjustment->status, [InventoryAdjustment::STATUS_DRAFT, InventoryAdjustment::STATUS_PENDING], true);
        $canPostDraft = $isOwnerAdmin && $adjustment->status === InventoryAdjustment::STATUS_DRAFT;
        $showReasonBlock = (bool) $adjustment->reason && !in_array($adjustment->reason, ['Adjustment Manual Draft', 'Draft Adjustment Manual'], true);

        $summary = $summary ?? [
            'total_in_qty' => 0,
            'total_out_qty_abs' => 0,
            'total_in_value' => 0,
            'total_out_value' => 0,
            'net_value' => 0,
        ];

        $summaryFmt = $summaryFmt ?? [
            'total_in_value' => 'Rp 0',
            'total_out_value' => 'Rp 0',
            'net_value' => 'Rp 0',
        ];

        $lineTotals = $lineTotals ?? [];

        $isApproved = $adjustment->status === InventoryAdjustment::STATUS_APPROVED;

        $net = (float) ($summary['net_value'] ?? 0);
        $netClass = $net >= 0 ? 'diff-plus' : 'diff-minus';
        $netPillText = $net >= 0 ? 'NET MASUK' : 'NET KELUAR';

        $totalInQty = (float) ($summary['total_in_qty'] ?? 0);
        $totalOutQtyAbs = (float) ($summary['total_out_qty_abs'] ?? 0);
        $netQtyRaw = $totalInQty - $totalOutQtyAbs;
        $netQtySign = $netQtyRaw > 0 ? '+' : ($netQtyRaw < 0 ? '-' : '');
        $netQtyAbs = abs($netQtyRaw);

        $totalInVal = (float) ($summary['total_in_value'] ?? 0);
        $totalOutVal = (float) ($summary['total_out_value'] ?? 0);

        // Badge Approved by
        $approverRole = $adjustment->approver?->role ?? null;
        $approverBadgeText = null;
        $approverBadgeClass = 'badge-approver';

        if ($adjustment->status === InventoryAdjustment::STATUS_APPROVED && $approverRole) {
            if ($approverRole === 'owner') {
                $approverBadgeText = 'Approved by Owner';
                $approverBadgeClass .= ' badge-approver--owner';
            } elseif ($approverRole === 'admin') {
                $approverBadgeText = 'Approved by Admin';
                $approverBadgeClass .= ' badge-approver--admin';
            } else {
                $approverBadgeText = 'Approved by ' . ucfirst($approverRole);
            }
        }

        // Bundle ref (kalau ada)
        $hasAnyBundleRef = false;
        foreach ($adjustment->lines as $ln) {
            if (!empty($ln->cutting_job_bundle_id)) {
                $hasAnyBundleRef = true;
                break;
            }
        }
    @endphp

    <div class="page-wrap">
        {{-- HERO --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <div class="hero-shell">
                    <div class="flex-grow-1">
                        <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-sm btn-link px-0 mb-2">← Kembali ke daftar</a>

                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="{{ $statusClass }}">{{ ucfirst($adjustment->status) }}</span>

                            @if ($approverBadgeText)
                                <span class="{{ $approverBadgeClass }}">
                                    <span class="badge-approver-dot"></span>
                                    {{ $approverBadgeText }}
                                </span>
                            @endif

                            @if ($isOverproduction)
                                <span class="ref-chip" title="Dokumen koreksi stok untuk overproduction cutting">
                                    ⚙️ <b>Cutting Overproduction</b>
                                </span>
                            @endif
                        </div>

                        <div class="hero-title">Inventory Adjustment • {{ $adjustment->code }}</div>
                        <div class="hero-sub">
                            {{ $isOverproduction ? 'Dokumen koreksi stok untuk hasil cutting lebih (overproduction).' : 'Dokumen penyesuaian stok gudang.' }}
                            @if ($adjustment->status === InventoryAdjustment::STATUS_DRAFT)
                                Draft ini belum mempengaruhi stok sampai di-post.
                            @endif
                        </div>

                        <div class="hero-meta">
                            <span class="hero-meta-pill">Gudang: <b>{{ $adjustment->warehouse?->code ?? '-' }}</b></span>
                            <span class="hero-meta-pill">Tanggal: <b>{{ $adjustment->date?->format('d M Y') ?? '-' }}</b></span>
                            <span class="hero-meta-pill">Dibuat: <b>{{ $adjustment->creator?->name ?? '-' }}</b></span>
                            @if ($sourceLabel)
                                <span class="hero-meta-pill">Sumber: <b>{{ \Illuminate\Support\Str::limit($sourceLabel, 32) }}</b></span>
                            @endif
                        </div>
                    </div>

                    <div class="hero-action">
                        <div class="pill-label">Aksi</div>
                        <div class="action-bar" style="justify-content:flex-start; margin-top:.45rem;">
                            @if ($canPostDraft)
                                <form action="{{ route('inventory.adjustments.post', $adjustment) }}" method="POST"
                                    onsubmit="return confirm('POST draft ini? Stok akan dikoreksi sesuai baris yang tersimpan.');">
                                    @csrf
                                    <button class="btn btn-sm text-white btn-post btn-pill">POST & Eksekusi Stok</button>
                                </form>
                            @endif

                            @if ($canApprove)
                                <form action="{{ route('inventory.adjustments.approve', $adjustment) }}" method="POST"
                                    onsubmit="return confirm('Approve adjustment ini? Stok akan dikoreksi sesuai baris di bawah.');">
                                    @csrf
                                    <button class="btn btn-sm btn-success btn-pill">Approve</button>
                                </form>
                            @endif

                            @if ($canVoid)
                                <form action="{{ route('inventory.adjustments.void', $adjustment) }}" method="POST"
                                    onsubmit="return confirm('Batalkan draft/pending ini?');">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger btn-pill">Batalkan</button>
                                </form>
                            @endif
                        </div>

                        <div class="hero-action-note">
                            @if ($adjustment->status === InventoryAdjustment::STATUS_DRAFT)
                                Simpan dulu sebagai draft, lalu post dari halaman detail saat sudah siap.
                            @elseif ($adjustment->status === InventoryAdjustment::STATUS_PENDING)
                                Menunggu approval Owner atau Admin.
                            @elseif ($adjustment->status === InventoryAdjustment::STATUS_APPROVED)
                                Sudah diposting dan tercatat di kartu stok.
                            @else
                                Dokumen sudah dibatalkan.
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ALERT STATUS --}}
        @if ($adjustment->status === InventoryAdjustment::STATUS_DRAFT)
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:.82rem;">
                <strong>Draft tersimpan.</strong>
                Dokumen belum mempengaruhi stok sampai kamu klik <b>POST</b> di kanan atas.
            </div>
        @elseif ($adjustment->status === InventoryAdjustment::STATUS_PENDING)
            <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:.82rem;">
                <strong>Status Pending.</strong> Stok <b>belum</b> dikoreksi. Menunggu approval Owner.
            </div>
        @elseif ($adjustment->status === InventoryAdjustment::STATUS_APPROVED)
            <div class="alert alert-success py-2 px-3 mb-3" style="font-size:.82rem;">
                Stok gudang sudah dikoreksi dan tercatat di kartu stok.
            </div>
        @elseif ($adjustment->status === InventoryAdjustment::STATUS_VOID)
            <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.82rem;">
                Dokumen ini berstatus <b>VOID</b> / dibatalkan.
            </div>
        @endif

        {{-- INFO HEADER + SUMMARY --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="pill-label">Gudang</div>
                        <div class="fw-semibold">{{ $adjustment->warehouse?->code ?? '-' }}</div>
                        <div class="text-muted" style="font-size:.86rem;">{{ $adjustment->warehouse?->name ?? '-' }}</div>
                    </div>

                    <div class="col-md-4">
                        <div class="pill-label">Sumber</div>
                        @if ($sourceLabel)
                            <a href="{{ $sourceUrl }}">{{ $sourceLabel }}</a>
                        @else
                            <span class="text-muted">Manual</span>
                        @endif

                        <div class="pill-label mt-3">Tanggal</div>
                        <div>{{ $adjustment->date?->format('d M Y') ?? '-' }}</div>
                    </div>

                    <div class="col-md-4">
                        <div class="pill-label">Dibuat</div>
                        <div>{{ $adjustment->creator?->name ?? '-' }}</div>
                        <small
                            class="text-muted d-block">{{ $adjustment->created_at?->format('d M Y H:i') ?? '-' }}</small>

                        @if ($adjustment->approved_by)
                            <div class="pill-label mt-3">Approved</div>
                            <div>{{ $adjustment->approver?->name ?? '-' }}</div>
                            <small
                                class="text-muted d-block">{{ $adjustment->approved_at?->format('d M Y H:i') ?? '-' }}</small>
                        @endif
                    </div>
                </div>

                {{-- REASON / NOTES --}}
                @if ($showReasonBlock || $adjustment->notes)
                    <hr>
                    <div class="row g-3 mb-2">
                        @if ($showReasonBlock)
                            <div class="{{ $adjustment->notes ? 'col-md-6' : 'col-12' }}">
                                <div class="pill-label">Ringkasan</div>
                                <div>{{ $adjustment->reason }}</div>
                            </div>
                        @endif

                        @if ($adjustment->notes)
                            <div class="{{ $showReasonBlock ? 'col-md-6' : 'col-12' }}">
                                <div class="pill-label">Catatan</div>
                                <div>{!! nl2br(e($adjustment->notes)) !!}</div>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($hasAnyBundleRef)
                    <hr>
                    <div class="text-muted" style="font-size:.82rem;">
                        Ada baris yang punya referensi <b>Bundle Cutting</b> (audit trail).
                    </div>
                @endif

                <hr>

                {{-- RINGKASAN --}}
                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-title">
                            <div class="pill-label">Ringkasan Qty</div>
                            <span class="net-pill {{ $netClass }}">{{ $netPillText }}</span>
                        </div>

                        <div class="summary-value text-mono">
                            <span class="diff-plus">+{{ number_format($totalInQty, 2) }}</span>
                            <span class="mx-2" style="color:#94a3b8;">|</span>
                            <span class="diff-minus">-{{ number_format($totalOutQtyAbs, 2) }}</span>
                        </div>

                        <div class="summary-sub mt-2">
                            Net Qty:
                            <span
                                class="text-mono {{ $netClass }}">{{ $netQtySign }}{{ number_format($netQtyAbs, 2) }}</span>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-title">
                            <div class="pill-label">Nilai Masuk</div>
                            <span class="badge-dir badge-dir--in">MASUK</span>
                        </div>

                        <div class="summary-value text-mono diff-plus">+{{ $summaryFmt['total_in_value'] }}</div>

                        <div class="summary-sub mt-1">
                            {{ $totalInVal == 0 ? 'Belum ada nilai masuk' : 'Total nilai stok yang bertambah' }}
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-title">
                            <div class="pill-label">Nilai Keluar</div>
                            <span class="badge-dir badge-dir--out">KELUAR</span>
                        </div>

                        <div class="summary-value text-mono diff-minus">-{{ $summaryFmt['total_out_value'] }}</div>

                        <div class="summary-sub mt-1">
                            {{ $totalOutVal == 0 ? 'Belum ada nilai keluar' : 'Total nilai stok yang berkurang' }}
                        </div>

                        <div class="summary-sub mt-2">
                            Net Nilai: <span class="text-mono {{ $netClass }}">{{ $summaryFmt['net_value'] }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- DETAIL LINES --}}
        <div class="card card-main">
            <div class="card-body">
                <div class="d-flex align-items-end justify-content-between gap-2 mb-2">
                    <div>
                        <h2 class="h6 mb-1">Detail Baris Adjustment</h2>
                        @if ($adjustment->status === InventoryAdjustment::STATUS_DRAFT)
                            <div class="text-muted" style="font-size:.82rem;">
                                Alasan wajib diisi per baris sebelum dokumen di-post.
                            </div>
                        @endif
                        @if ($isOverproduction)
                            <div class="text-muted" style="font-size:.82rem;">
                                Overproduction biasanya <b>hanya baris MASUK (IN)</b>. Setelah klik <b>POST</b>, stok akan
                                bertambah.
                            </div>
                        @endif
                    </div>

                    @if ($isOverproduction && $adjustment->status === InventoryAdjustment::STATUS_DRAFT)
                        <a href="{{ route('production.cutting_overproduction.create', ['cutting_job_id' => $adjustment->source_id]) }}"
                            class="btn btn-sm btn-outline-primary btn-pill">
                            Edit Qty (buat draft baru)
                        </a>
                    @endif
                </div>

                <div class="table-wrap" id="adj-lines-wrap">
                    <table class="table table-sm mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width:42px;">#</th>
                                <th>Item</th>
                                <th class="text-center" style="width:88px;">Arah</th>
                                <th class="text-end" style="width:110px;">Qty Selisih</th>
                                <th class="text-end" style="width:140px;">HPP / Unit</th>
                                <th class="text-end" style="width:170px;">Nilai</th>
                                <th class="text-end" style="width:120px;">Sebelum</th>
                                <th class="text-end" style="width:120px;">Sesudah</th>
                                @if ($hasAnyBundleRef)
                                    <th class="text-center" style="width:90px;">Bundle</th>
                                @endif
                                <th>Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($adjustment->lines as $i => $line)
                                @php
                                    $dir = $line->direction === 'out' ? 'out' : 'in';
                                    $qtySigned = (float) ($line->qty_change ?? 0);
                                    $qtyAbs = abs($qtySigned);

                                    $t = $lineTotals[$line->id] ?? null;
                                    $unitCostFmt = $t['unit_cost_fmt'] ?? '-';
                                    $valueFmt = $t['value_fmt'] ?? 'Rp 0';

                                    $bundleRef = $line->cutting_job_bundle_id ?? null;
                                @endphp

                                <tr>
                                    <td data-label="#">{{ $i + 1 }}</td>

                                    <td data-label="Item">
                                        <b>{{ $line->item?->code ?? '-' }}</b>
                                        <div class="text-muted" style="font-size:.83rem;">
                                            {{ $line->item?->name ?? '' }}
                                        </div>
                                    </td>

                                    <td data-label="Arah" class="text-center">
                                        <span class="badge-dir {{ $dir === 'in' ? 'badge-dir--in' : 'badge-dir--out' }}">
                                            {{ $dir === 'in' ? 'Masuk' : 'Keluar' }}
                                        </span>
                                    </td>

                                    <td data-label="Qty Selisih"
                                        class="text-end text-mono {{ $dir === 'in' ? 'diff-plus' : 'diff-minus' }}">
                                        {{ $dir === 'in' ? '+' : '-' }}{{ number_format($qtyAbs, 2) }}
                                    </td>

                                    <td data-label="HPP / Unit" class="text-end text-mono">
                                        {{ $unitCostFmt }}
                                        @if (!$isApproved)
                                            <div style="font-size:.74rem; color:#9ca3af;">estimasi</div>
                                        @endif
                                    </td>

                                    <td data-label="Nilai"
                                        class="text-end text-mono {{ $dir === 'in' ? 'diff-plus' : 'diff-minus' }}">
                                        {{ $dir === 'in' ? '+' : '-' }}{{ $valueFmt }}
                                    </td>

                                    <td data-label="Sebelum" class="text-end text-mono">
                                        {{ is_null($line->qty_before) ? '-' : number_format($line->qty_before, 2) }}
                                    </td>

                                    <td data-label="Sesudah" class="text-end text-mono">
                                        {{ is_null($line->qty_after) ? '-' : number_format($line->qty_after, 2) }}
                                    </td>

                                    @if ($hasAnyBundleRef)
                                        <td data-label="Bundle" class="text-center text-mono">
                                            {{ $bundleRef ? '#' . $bundleRef : '-' }}
                                        </td>
                                    @endif

                                    <td data-label="Alasan" style="font-size:.82rem;">
                                        {{ $line->notes }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $hasAnyBundleRef ? 10 : 9 }}" class="text-center py-3 text-muted">
                                        Tidak ada baris adjustment.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setAdjMaxHeight7Rows();
            window.addEventListener('resize', debounce(setAdjMaxHeight7Rows, 150));
        });

        function debounce(fn, wait) {
            let t = null;
            return function() {
                clearTimeout(t);
                t = setTimeout(fn, wait || 150);
            };
        }

        function setAdjMaxHeight7Rows() {
            const wrap = document.getElementById('adj-lines-wrap');
            if (!wrap) return;
            if (window.matchMedia("(max-width: 767.98px)").matches) return;

            const table = wrap.querySelector('table');
            const thead = table ? table.querySelector('thead') : null;
            const firstRow = table ? table.querySelector('tbody tr') : null;
            if (!firstRow) return;

            const rowH = firstRow.getBoundingClientRect().height || 38;
            const headH = thead ? (thead.getBoundingClientRect().height || 34) : 34;

            const maxH = Math.ceil((rowH * 7) + headH + 8);
            wrap.style.maxHeight = maxH + 'px';
            wrap.style.overflowY = 'auto';
        }
    </script>
@endpush
