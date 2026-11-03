@extends('layouts.app')

@section('title', 'Akuntansi Baru • Detail Akun')

@php
    $typeLabel = ['asset' => 'Aset', 'liability' => 'Liabilitas', 'equity' => 'Ekuitas', 'revenue' => 'Pendapatan', 'expense' => 'Beban'];
    $typeTone = ['asset' => 'blue', 'liability' => 'orange', 'equity' => 'purple', 'revenue' => 'green', 'expense' => 'red'];
    $money = fn ($value) => (float) $value > 0 ? 'Rp ' . number_format(abs((float) $value), 0, ',', '.') : '-';
    $uomLabel = function ($uom): string {
        return match (strtolower(trim((string) $uom))) {
            'kg', 'kgs' => 'Kg',
            'g', 'gram', 'grams' => 'Gram',
            'm', 'meter', 'meters' => 'Meter',
            'cm', 'centimeter', 'centimeters' => 'Cm',
            'pcs', 'pc', 'piece', 'pieces' => 'pcs',
            'set', 'sets' => 'Set',
            'roll', 'rolls' => 'Roll',
            'unit', 'units' => 'Unit',
            default => trim((string) $uom) !== '' ? (string) $uom : 'Unit',
        };
    };
    $qtyLabel = function ($qty, $uom) use ($uomLabel): string {
        return number_format((float) $qty, 2, ',', '.') . ' ' . $uomLabel($uom);
    };
@endphp

@push('head')
    <style>
        .account-detail-page { max-width:1100px; margin-inline:auto; }
        .account-detail-kicker { color:#64748b; font-size:.72rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
        .account-detail-title { color:#0f172a; font-size:clamp(1.35rem, 2vw, 1.8rem); font-weight:800; letter-spacing:-.035em; }
        .account-detail-subtitle { color:#64748b; }
        .account-detail-btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; min-height:40px; padding:.5rem .9rem; border:1px solid #dbe3ee; border-radius:10px; background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:750; }
        .account-detail-btn:hover { background:#f8fafc; color:#0f172a; }
        .account-detail-btn.primary { border-color:#0f172a; background:#0f172a; color:#fff; }
        .account-detail-btn.primary:hover { background:#1e293b; color:#fff; }
        .account-detail-card { border:1px solid #e2e8f0; border-radius:16px; background:#fff; box-shadow:0 5px 16px rgba(15,23,42,.04); }
        .account-detail-hero { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1.25rem; }
        .account-detail-code { color:#64748b; font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:.82rem; font-weight:800; }
        .account-detail-badge { display:inline-flex; margin-top:.65rem; border-radius:999px; padding:.3rem .6rem; font-size:.72rem; font-weight:750; }
        .blue { color:#1d4ed8; background:#eff6ff; } .orange { color:#c2410c; background:#fff7ed; } .purple { color:#7e22ce; background:#faf5ff; } .green { color:#15803d; background:#f0fdf4; } .red { color:#b91c1c; background:#fef2f2; }
        .account-detail-status { color:#166534; font-size:.78rem; font-weight:750; }
        .account-detail-status.inactive { color:#64748b; }
        .account-detail-status::before { display:inline-block; width:7px; height:7px; margin-right:.35rem; border-radius:50%; background:#22c55e; content:""; }
        .account-detail-status.inactive::before { background:#94a3b8; }
        .account-detail-balance { min-width:220px; padding:1rem; border-radius:14px; background:#f8fafc; text-align:right; }
        .account-detail-balance-label { color:#64748b; font-size:.72rem; font-weight:700; }
        .account-detail-balance-value { margin-top:.15rem; color:#0f172a; font-size:1.35rem; font-weight:850; }
        .account-detail-balance-side { color:#64748b; font-size:.76rem; font-weight:700; }
        .account-detail-stat-grid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:.75rem; padding:0 1.25rem 1.25rem; }
        .account-detail-stat { padding:.85rem 1rem; border:1px solid #e2e8f0; border-radius:12px; }
        .account-detail-stat-label { color:#64748b; font-size:.72rem; font-weight:700; }
        .account-detail-stat-value { margin-top:.25rem; color:#0f172a; font-size:1rem; font-weight:800; }
        .account-detail-section { overflow:hidden; }
        .account-detail-section-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; border-bottom:1px solid #e2e8f0; }
        .account-detail-section-title { color:#0f172a; font-size:.95rem; font-weight:800; }
        .account-detail-table { margin:0; }
        .account-detail-table th { color:#64748b; background:#f8fafc; font-size:.68rem; letter-spacing:.06em; text-transform:uppercase; white-space:nowrap; }
        .account-detail-table th, .account-detail-table td { padding:.8rem 1rem; border-color:#eef2f7; }
        .account-detail-table td { color:#334155; font-size:.82rem; }
        .account-detail-item-code { color:#0f172a; font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:.78rem; font-weight:800; }
        .account-detail-item-qty { display:block; margin-top:.15rem; color:#64748b; font-size:.72rem; font-weight:700; }
        .account-detail-item-breakdown { display:flex; flex-wrap:wrap; align-items:baseline; gap:.15rem .55rem; min-width:0; }
        .account-detail-description-meta { margin-top:.18rem; }
        .account-detail-item-line { display:inline-flex; align-items:baseline; gap:.25rem; white-space:nowrap; }
        .account-detail-item-line .account-detail-item-qty { margin-top:0; white-space:nowrap; }
        .account-detail-item-value { color:#334155; font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:.68rem; font-weight:750; white-space:nowrap; }
        .account-detail-empty { padding:2.5rem 1rem; text-align:center; color:#64748b; }
        .account-detail-audit-note { color:#64748b; font-size:.78rem; }
        .account-detail-audit-badge { display:inline-flex; border-radius:999px; padding:.25rem .5rem; font-size:.68rem; font-weight:800; }
        .account-detail-audit-badge.void { color:#b91c1c; background:#fef2f2; }
        .account-detail-audit-badge.reversal { color:#92400e; background:#fffbeb; }
        @media(max-width:700px) { .account-detail-hero { flex-direction:column; } .account-detail-balance { width:100%; text-align:left; } .account-detail-stat-grid { grid-template-columns:1fr; } .account-detail-actions { width:100%; } .account-detail-actions a { flex:1; } }
    </style>
@endpush

@section('content')
    <div class="account-detail-page d-grid gap-3">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div><div class="account-detail-kicker mb-1">Akuntansi Baru / Daftar Akun</div><h1 class="account-detail-title mb-1">Detail Akun</h1><div class="account-detail-subtitle small">Ringkasan akun dan transaksi terakhir yang sudah diposting.</div></div>
            <a class="account-detail-btn" href="{{ route('standalone-accounting.accounts.index') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i>Kembali ke daftar akun</a>
        </div>

        <section class="account-detail-card">
            <div class="account-detail-hero">
                <div><div class="account-detail-code">{{ $account->code }}</div><h2 class="account-detail-title fs-4 mt-2 mb-0">{{ $account->name }}</h2><span class="account-detail-badge {{ $typeTone[$account->type] ?? 'blue' }}">{{ $typeLabel[$account->type] ?? ucfirst($account->type) }}</span><div class="account-detail-status {{ $account->is_active ? '' : 'inactive' }} mt-2">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</div></div>
                <div class="account-detail-balance"><div class="account-detail-balance-label">Saldo saat ini</div><div class="account-detail-balance-value">{{ $money($summary['balanceAmount']) }}</div><div class="account-detail-balance-side">{{ $summary['balanceSide'] ?: 'Belum ada saldo' }}</div></div>
            </div>
            <div class="account-detail-stat-grid"><div class="account-detail-stat"><div class="account-detail-stat-label">Total debit</div><div class="account-detail-stat-value">{{ $money($summary['totalDebit']) }}</div></div><div class="account-detail-stat"><div class="account-detail-stat-label">Total kredit</div><div class="account-detail-stat-value">{{ $money($summary['totalCredit']) }}</div></div><div class="account-detail-stat"><div class="account-detail-stat-label">Pemakaian</div><div class="account-detail-stat-value">{{ $account->lines_count }} baris jurnal</div></div></div>
        </section>

        <section class="account-detail-card account-detail-section">
            <div class="account-detail-section-head"><div><div class="account-detail-section-title">Transaksi terakhir</div><div class="small text-muted">Jurnal posted dan belum di-void.</div></div><a class="account-detail-btn primary" href="{{ route('standalone-accounting.accounts.ledger', $account) }}"><i class="bi bi-journal-text" aria-hidden="true"></i>Lihat Buku Besar</a></div>
            <div class="table-responsive">
                <table class="table account-detail-table align-middle">
                    <thead>
                        <tr><th>Tanggal &amp; waktu</th><th>Referensi</th><th>Keterangan</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($recentLines as $line)
                            @php
                                $context = $recentLineContext[$line->id] ?? null;
                            @endphp
                            <tr>
                                <td><span class="d-block">{{ $line->journal->date?->format('d-m-Y') }}</span><small class="text-muted">Posting {{ $line->journal->posted_at?->format('H:i') ?: '-' }}</small></td>
                                <td>{{ $line->journal->reference ?: '-' }}</td>
                                <td>
                                    <div>{{ $line->journal->description ?: '-' }}</div>
                                    @if ($context && !empty($context['items']))
                                        <div class="account-detail-description-meta account-detail-item-breakdown">
                                            @foreach ($context['items'] as $item)
                                                <div class="account-detail-item-line">
                                                    <span class="account-detail-item-code">{{ $item['item_code'] }}</span>
                                                    <span class="account-detail-item-value">{{ $qtyLabel($item['qty'], $item['uom'] ?? 'unit') }} · {{ $money($item['value'] ?? 0) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif ($context && ($context['item_code'] !== '-' || $context['qty'] > 0.000001))
                                        <div class="account-detail-description-meta"><span class="account-detail-item-code">{{ $context['item_code'] }}</span>
                                        @if ($context['qty'] > 0.000001)<span class="account-detail-item-qty">{{ $qtyLabel($context['qty'], $context['uom'] ?? 'unit') }}</span>@endif
                                        </div>
                                    @else
                                        <div class="account-detail-description-meta"><span class="text-muted">—</span></div>
                                    @endif
                                </td>
                                <td class="text-end">{{ $money($line->debit) }}</td>
                                <td class="text-end">{{ $money($line->credit) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="account-detail-empty"><i class="bi bi-journal-x d-block fs-3 mb-2"></i><div>Belum ada transaksi pada akun ini.</div><a class="account-detail-btn primary mt-3" href="{{ route('standalone-accounting.journals.create') }}"><i class="bi bi-plus-lg" aria-hidden="true"></i>Buat Jurnal</a></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="account-detail-card account-detail-section">
            <div class="account-detail-section-head">
                <div>
                    <div class="account-detail-section-title">Riwayat void / reversal</div>
                    <div class="account-detail-audit-note">Hanya untuk audit; transaksi ini tidak dihitung ke saldo aktif.</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table account-detail-table align-middle">
                    <thead><tr><th>Status</th><th>Tanggal</th><th>Referensi</th><th>Keterangan</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr></thead>
                    <tbody>
                        @forelse ($auditLines as $line)
                            @php
                                $isReversal = str_contains((string) $line->journal->source_type, '_reversal_') || str_starts_with((string) $line->journal->description, 'Reversal:');
                            @endphp
                            <tr>
                                <td><span class="account-detail-audit-badge {{ $isReversal ? 'reversal' : 'void' }}">{{ $isReversal ? 'REVERSAL' : 'VOID' }}</span></td>
                                <td>{{ $line->journal->date?->format('d-m-Y') }}</td>
                                <td>{{ $line->journal->reference ?: '-' }}</td>
                                <td>
                                    <div>{{ $line->journal->description ?: '-' }}</div>
                                    @php
                                        $context = $auditLineContext[$line->id] ?? null;
                                    @endphp
                                    @if ($context && !empty($context['items']))
                                        <div class="account-detail-description-meta account-detail-item-breakdown">
                                            @foreach ($context['items'] as $item)
                                                <div class="account-detail-item-line">
                                                    <span class="account-detail-item-code">{{ $item['item_code'] }}</span>
                                                    <span class="account-detail-item-value">{{ $qtyLabel($item['qty'], $item['uom'] ?? 'unit') }} · {{ $money($item['value'] ?? 0) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif ($context && ($context['item_code'] !== '-' || $context['qty'] > 0.000001))
                                        <div class="account-detail-description-meta"><span class="account-detail-item-code">{{ $context['item_code'] }}</span>
                                        @if ($context['qty'] > 0.000001)<span class="account-detail-item-qty">{{ $qtyLabel($context['qty'], $context['uom'] ?? 'unit') }}</span>@endif
                                        </div>
                                    @else
                                        <div class="account-detail-description-meta"><span class="text-muted">—</span></div>
                                    @endif
                                </td>
                                <td class="text-end">{{ $money($line->debit) }}</td>
                                <td class="text-end">{{ $money($line->credit) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="account-detail-empty">Belum ada riwayat void atau reversal.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
