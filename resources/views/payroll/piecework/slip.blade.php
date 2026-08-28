@extends('layouts.app')

@section('title', 'Slip Payroll • ' . ($moduleLabel ?? ucfirst($module ?? '')))

@push('head')
    <style>
        .slip-page { max-width: 860px; margin: 0 auto; padding: 1rem .85rem 3rem }
        .slip-actions { display:flex; justify-content:space-between; align-items:center; gap:.75rem; margin-bottom:.85rem }
        .slip-action-btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; min-height:36px; padding:.45rem .7rem; border:1px solid rgba(148,163,184,.3); border-radius:9px; background:var(--card); color:var(--text); font-size:.78rem; font-weight:700; text-decoration:none }
        .slip-action-btn.primary { border-color:var(--accent); background:var(--accent); color:#fff }
        .slip-card { overflow:hidden; background:var(--card); border:1px solid rgba(148,163,184,.28); border-radius:14px; box-shadow:0 10px 28px rgba(15,23,42,.07) }
        .slip-inner { padding:1.35rem 1.5rem 1.5rem }
        .slip-brand { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem }
        .slip-brand-mark { display:flex; align-items:center; gap:.65rem }
        .slip-brand-mark img { width:34px; height:34px; object-fit:contain }
        .slip-brand-name { color:var(--text); font-size:.95rem; font-weight:900; letter-spacing:.02em }
        .slip-brand-sub { margin-top:.1rem; color:var(--muted); font-size:.68rem }
        .slip-document { text-align:right }
        .slip-eyebrow { color:var(--accent); font-size:.68rem; font-weight:900; letter-spacing:.11em }
        .slip-number { margin-top:.22rem; color:var(--muted); font-size:.72rem; font-variant-numeric:tabular-nums }
        .slip-divider { height:1px; margin:1.1rem 0; background:rgba(148,163,184,.22) }
        .slip-heading { display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; margin-bottom:1rem }
        .slip-title { margin:0; color:var(--text); font-size:1.28rem; font-weight:900; letter-spacing:-.025em }
        .slip-subtitle { margin-top:.25rem; color:var(--muted); font-size:.78rem }
        .slip-status { display:inline-flex; align-items:center; min-height:25px; padding:.25rem .55rem; border:1px solid rgba(16,185,129,.3); border-radius:999px; color:rgba(16,185,129,1); font-size:.65rem; font-weight:850; letter-spacing:.06em }
        .slip-status.draft { border-color:rgba(245,158,11,.35); color:rgba(217,119,6,1) }
        .slip-info { display:grid; grid-template-columns:repeat(4,1fr); gap:.65rem; margin-bottom:1rem }
        .slip-info-item { min-width:0; padding:.65rem .7rem; border:1px solid rgba(148,163,184,.16); border-radius:9px; background:color-mix(in srgb,var(--card) 94%,var(--accent-soft) 6%) }
        .slip-info-label { color:var(--muted); font-size:.64rem; font-weight:750; letter-spacing:.04em; text-transform:uppercase }
        .slip-info-value { overflow:hidden; margin-top:.22rem; color:var(--text); font-size:.79rem; font-weight:800; text-overflow:ellipsis; white-space:nowrap }
        .slip-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:.65rem; margin-bottom:1.25rem }
        .slip-stat { padding:.75rem .8rem; border-radius:10px; background:rgba(148,163,184,.09) }
        .slip-stat-label { color:var(--muted); font-size:.67rem; font-weight:750 }
        .slip-stat-value { margin-top:.25rem; color:var(--text); font-size:1rem; font-weight:900; font-variant-numeric:tabular-nums }
        .slip-stat.total { background:color-mix(in srgb,var(--accent-soft) 24%,var(--card) 76%) }
        .slip-stat.total .slip-stat-value { color:var(--accent) }
        .slip-section-title { margin-bottom:.55rem; color:var(--text); font-size:.78rem; font-weight:850 }
        .slip-table-wrap { overflow-x:auto; border:1px solid rgba(148,163,184,.2); border-radius:10px }
        .slip-table { width:100%; min-width:590px; border-collapse:separate; border-spacing:0; font-size:.78rem }
        .slip-table th { padding:.65rem .7rem; border-bottom:1px solid rgba(148,163,184,.2); background:rgba(148,163,184,.08); color:var(--muted); font-size:.64rem; font-weight:850; letter-spacing:.07em; text-align:left; text-transform:uppercase; white-space:nowrap }
        .slip-table td { padding:.68rem .7rem; border-bottom:1px solid rgba(148,163,184,.13); color:var(--text); vertical-align:middle }
        .slip-table tbody tr:last-child td { border-bottom:0 }
        .slip-table tfoot td { padding:.78rem .7rem; border-top:2px solid rgba(148,163,184,.25); background:rgba(148,163,184,.06); font-weight:850 }
        .slip-right { text-align:right }
        .slip-mono { font-variant-numeric:tabular-nums }
        .slip-date-day { font-weight:800; line-height:1.25 }
        .slip-date-value { margin-top:.12rem; color:var(--muted); font-size:.7rem }
        .slip-attendance { display:inline-flex; align-items:center; padding:.25rem .48rem; border-radius:999px; background:rgba(148,163,184,.12); color:var(--muted); font-size:.68rem; font-weight:800 }
        .slip-attendance.hadir { background:rgba(16,185,129,.11); color:rgba(5,150,105,1) }
        .slip-attendance.libur { background:rgba(148,163,184,.14); color:var(--muted) }
        .slip-total { display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; margin-top:1.2rem; padding:.95rem 0 0; border-top:2px solid var(--text) }
        .slip-total-label { color:var(--muted); font-size:.7rem; font-weight:850; letter-spacing:.08em; text-transform:uppercase }
        .slip-total-note { margin-top:.2rem; color:var(--muted); font-size:.7rem }
        .slip-total-value { color:var(--text); font-size:1.35rem; font-weight:950; letter-spacing:-.025em; text-align:right; white-space:nowrap }
        .slip-signatures { display:grid; grid-template-columns:1fr 1fr; gap:3rem; margin-top:2.2rem; padding-top:1rem; border-top:1px dashed rgba(148,163,184,.35) }
        .slip-signature { color:var(--muted); font-size:.72rem }
        .slip-signature.right { text-align:right }
        .slip-signature-space { height:48px }
        .slip-signature-name { color:var(--text); font-weight:800 }
        .slip-printed { margin-top:1.3rem; color:var(--muted); font-size:.65rem; text-align:center }

        @media (max-width:640px) {
            .slip-page { padding:.65rem .55rem 2rem }
            .slip-inner { padding:1rem .85rem 1.1rem }
            .slip-brand { align-items:center }
            .slip-brand-mark img { width:29px; height:29px }
            .slip-brand-name { font-size:.82rem }
            .slip-brand-sub, .slip-number { font-size:.61rem }
            .slip-eyebrow { font-size:.58rem }
            .slip-title { font-size:1.05rem }
            .slip-heading { align-items:flex-start; flex-direction:column; gap:.55rem }
            .slip-info, .slip-stats { grid-template-columns:repeat(2,1fr) }
            .slip-info-item, .slip-stat { padding:.55rem .6rem }
            .slip-stat-value { font-size:.9rem }
            .slip-total-value { font-size:1.12rem }
            .slip-signatures { gap:1rem }
        }

        @media print {
            .no-print { display:none !important }
            body { background:#fff !important }
            .slip-page { max-width:100%; padding:0 }
            .slip-card { border:1px solid #b8b8b8; border-radius:0; box-shadow:none }
            .slip-inner { padding:1rem }
            .slip-table-wrap { overflow:visible }
            .slip-table { min-width:0 }
        }
    </style>
@endpush

@section('content')
    @php
        $isDaily = $module === 'daily';
        $periodStart = \Carbon\Carbon::parse($period->period_start)->locale('id');
        $periodEnd = \Carbon\Carbon::parse($period->period_end)->locale('id');
        $statusLabel = $period->status === 'final' ? 'FINAL' : 'DRAFT';
        $statusClass = $period->status === 'final' ? '' : 'draft';
        $backUrl = $isDaily
            ? route('payroll.daily.show', ['period' => $period])
            : route('payroll.piecework.show', ['module' => $module, 'period' => $period]);
        $roleLabel = match ($employee?->role) {
            'operating' => 'Operator',
            'admin' => 'Admin',
            'supervisor' => 'Supervisor',
            default => $employee?->role ?: '-',
        };
        $attendanceLabels = [
            'pending' => 'Belum diisi',
            'hadir' => 'Hadir',
            'setengah_hari' => 'Setengah Hari',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'libur' => 'Libur',
        ];
        $presentCount = $isDaily ? $lines->where('attendance_status', 'hadir')->count() : null;
        $holidayCount = $isDaily ? $lines->where('attendance_status', 'libur')->count() : null;
        $pendingCount = $isDaily ? $lines->where('attendance_status', 'pending')->count() : null;
        $otherAttendanceCount = $isDaily
            ? $lines->whereNotIn('attendance_status', ['hadir', 'libur', 'pending'])->count()
            : null;
        $documentLabel = $isDaily ? 'SLIP GAJI HARIAN' : 'SLIP PAYROLL BORONGAN';
    @endphp

    <div class="slip-page">
        <div class="slip-actions no-print">
            <a class="slip-action-btn" href="{{ $backUrl }}">← Kembali</a>
            <button class="slip-action-btn primary" type="button" onclick="window.print()">
                <i class="bi bi-printer" aria-hidden="true"></i> Cetak Slip
            </button>
        </div>

        <article class="slip-card">
            <div class="slip-inner">
                <header class="slip-brand">
                    <div class="slip-brand-mark">
                        <img src="{{ asset('images/logo-mark.svg') }}" alt="{{ config('app.name', 'Greatfit') }}">
                        <div>
                            <div class="slip-brand-name">{{ config('app.name', 'Greatfit') }}</div>
                            <div class="slip-brand-sub">Payroll &amp; Operasional</div>
                        </div>
                    </div>
                    <div class="slip-document">
                        <div class="slip-eyebrow">{{ $documentLabel }}</div>
                        <div class="slip-number">No. #{{ $period->id }} · {{ $statusLabel }}</div>
                    </div>
                </header>

                <div class="slip-divider"></div>

                <section class="slip-heading">
                    <div>
                        <h1 class="slip-title">{{ $isDaily ? 'Slip Gaji Harian' : ($moduleLabel ?? ucfirst($module)) }}</h1>
                        <div class="slip-subtitle">Dokumen penghasilan operator untuk periode yang dipilih</div>
                    </div>
                    <span class="slip-status {{ $statusClass }}">{{ $statusLabel }}</span>
                </section>

                <section class="slip-info" aria-label="Informasi slip">
                    <div class="slip-info-item"><div class="slip-info-label">Nama Operator</div><div class="slip-info-value">{{ $employee?->name ?? '-' }}</div></div>
                    <div class="slip-info-item"><div class="slip-info-label">Kode Operator</div><div class="slip-info-value">{{ $employee?->code ?? '-' }}</div></div>
                    <div class="slip-info-item"><div class="slip-info-label">Jabatan</div><div class="slip-info-value">{{ $roleLabel }}</div></div>
                    <div class="slip-info-item"><div class="slip-info-label">Periode</div><div class="slip-info-value">{{ $periodStart->format('d/m/Y') }} – {{ $periodEnd->format('d/m/Y') }}</div></div>
                </section>

                @if ($isDaily)
                    <section class="slip-stats" aria-label="Ringkasan kehadiran">
                        <div class="slip-stat"><div class="slip-stat-label">Hari Dibayar</div><div class="slip-stat-value">{{ rtrim(rtrim(number_format((float) $totalQty, 2, ',', '.'), '0'), ',') }}</div></div>
                        <div class="slip-stat"><div class="slip-stat-label">Hadir</div><div class="slip-stat-value">{{ number_format((int) $presentCount, 0, ',', '.') }} hari</div></div>
                        <div class="slip-stat"><div class="slip-stat-label">Libur</div><div class="slip-stat-value">{{ number_format((int) $holidayCount, 0, ',', '.') }} hari</div></div>
                        <div class="slip-stat total"><div class="slip-stat-label">Total Penghasilan</div><div class="slip-stat-value">{{ number_format((float) $totalAmount, 0, ',', '.') }}</div></div>
                    </section>
                @endif

                <section>
                    <div class="slip-section-title">{{ $isDaily ? 'Rincian Kehadiran' : 'Rincian Payroll' }}</div>
                    <div class="slip-table-wrap">
                        @if ($isDaily)
                            <table class="slip-table">
                                <thead><tr><th>Tanggal</th><th>Status</th><th class="slip-right">Tarif / Hari</th><th class="slip-right">Hari Dibayar</th><th class="slip-right">Jumlah</th></tr></thead>
                                <tbody>
                                    @foreach ($lines as $line)
                                        @php
                                            $workDate = \Carbon\Carbon::parse($line->work_date)->locale('id');
                                            $attendanceStatus = $line->attendance_status ?: 'pending';
                                        @endphp
                                        <tr>
                                            <td><div class="slip-date-day">{{ $workDate->translatedFormat('l') }}</div><div class="slip-date-value">{{ $workDate->format('d/m/Y') }}</div></td>
                                            <td><span class="slip-attendance {{ $attendanceStatus }}">{{ $attendanceLabels[$attendanceStatus] ?? ucfirst($attendanceStatus) }}</span></td>
                                            <td class="slip-right slip-mono">{{ number_format((float) ($line->rate_per_day ?: $line->rate_per_pcs), 0, ',', '.') }}</td>
                                            <td class="slip-right slip-mono">{{ rtrim(rtrim(number_format((float) $line->attendance_factor, 2, ',', '.'), '0'), ',') }}</td>
                                            <td class="slip-right slip-mono" style="font-weight:850">{{ number_format((float) $line->amount, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot><tr><td colspan="3">Total Diterima</td><td class="slip-right slip-mono">{{ rtrim(rtrim(number_format((float) $totalQty, 2, ',', '.'), '0'), ',') }}</td><td class="slip-right slip-mono">{{ number_format((float) $totalAmount, 0, ',', '.') }}</td></tr></tfoot>
                            </table>
                        @else
                            <table class="slip-table">
                                <thead><tr><th>Kategori</th><th>Item</th><th class="slip-right">{{ $module === 'sewing' ? 'Qty Ambil' : 'Qty OK' }}</th><th class="slip-right">Rate</th><th class="slip-right">Amount</th></tr></thead>
                                <tbody>
                                    @foreach ($lines as $line)
                                        <tr><td>{{ $line->category?->name ?? '-' }}</td><td>{{ $line->item?->name ?? '-' }}</td><td class="slip-right slip-mono">{{ number_format((float) $line->total_qty_ok, 2, ',', '.') }}</td><td class="slip-right slip-mono">{{ number_format((float) $line->rate_per_pcs, 0, ',', '.') }}</td><td class="slip-right slip-mono" style="font-weight:850">{{ number_format((float) $line->amount, 0, ',', '.') }}</td></tr>
                                    @endforeach
                                </tbody>
                                <tfoot><tr><td colspan="2">Total Diterima</td><td class="slip-right slip-mono">{{ number_format((float) $totalQty, 2, ',', '.') }}</td><td></td><td class="slip-right slip-mono">{{ number_format((float) $totalAmount, 0, ',', '.') }}</td></tr></tfoot>
                            </table>
                        @endif
                    </div>
                </section>

                @if ($isDaily && ($pendingCount > 0 || $otherAttendanceCount > 0))
                    <div class="slip-subtitle" style="margin-top:.65rem">Catatan: {{ $pendingCount > 0 ? $pendingCount . ' hari belum diisi' : '' }}{{ $pendingCount > 0 && $otherAttendanceCount > 0 ? ', ' : '' }}{{ $otherAttendanceCount > 0 ? $otherAttendanceCount . ' hari berstatus khusus' : '' }}.</div>
                @endif

                <div class="slip-total"><div><div class="slip-total-label">Total Dibayarkan</div><div class="slip-total-note">Nominal setelah perhitungan payroll periode ini</div></div><div class="slip-total-value">{{ number_format((float) $totalAmount, 0, ',', '.') }}</div></div>

                <div class="slip-signatures">
                    <div class="slip-signature">Diterima oleh,<div class="slip-signature-space"></div><div class="slip-signature-name">{{ $employee?->name ?? '........................' }}</div></div>
                    <div class="slip-signature right">Disetujui oleh,<div class="slip-signature-space"></div><div class="slip-signature-name">{{ auth()->user()->name ?? '........................' }}</div></div>
                </div>
                <div class="slip-printed">Dicetak pada {{ id_datetime(now()) }}</div>
            </div>
        </article>
    </div>
@endsection
