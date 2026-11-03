@extends('layouts.app')

@section('title', 'Akuntansi Baru • Buku Besar')

@php
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
        .account-ledger-page { max-width:1200px; margin-inline:auto; }
        .account-ledger-title { color:#0f172a; font-size:clamp(1.35rem, 2vw, 1.8rem); font-weight:800; letter-spacing:-.035em; }
        .account-ledger-code { color:#64748b; font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:.82rem; font-weight:800; }
        .account-ledger-btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; min-height:40px; padding:.5rem .9rem; border:1px solid #dbe3ee; border-radius:10px; background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:750; }
        .account-ledger-btn:hover { background:#f8fafc; color:#0f172a; }
        .account-ledger-btn.primary { border-color:#0f172a; background:#0f172a; color:#fff; }
        .account-ledger-card { overflow:hidden; border:1px solid #e2e8f0; border-radius:16px; background:#fff; box-shadow:0 5px 16px rgba(15,23,42,.04); }
        .account-ledger-filter { display:flex; align-items:end; gap:.65rem; flex-wrap:wrap; padding:1rem; }
        .account-ledger-filter .form-control { min-height:40px; border-color:#dbe3ee; border-radius:10px; }
        .account-ledger-table { margin:0; }
        .account-ledger-table th { color:#64748b; background:#f8fafc; font-size:.68rem; letter-spacing:.06em; text-transform:uppercase; white-space:nowrap; }
        .account-ledger-table th, .account-ledger-table td { padding:.8rem 1rem; border-color:#eef2f7; }
        .account-ledger-table td { color:#334155; font-size:.82rem; }
        .account-ledger-item-code { color:#0f172a; font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:.78rem; font-weight:800; }
        .account-ledger-item-qty { display:block; margin-top:.15rem; color:#64748b; font-size:.72rem; font-weight:700; }
        .account-ledger-item-breakdown { display:flex; flex-wrap:wrap; align-items:baseline; gap:.15rem .55rem; min-width:0; }
        .account-ledger-description-meta { margin-top:.18rem; }
        .account-ledger-item-line { display:inline-flex; align-items:baseline; gap:.25rem; white-space:nowrap; }
        .account-ledger-item-line .account-ledger-item-qty { margin-top:0; white-space:nowrap; }
        .account-ledger-item-value { color:#334155; font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:.68rem; font-weight:750; white-space:nowrap; }
        .account-ledger-balance { color:#0f172a; font-variant-numeric:tabular-nums; white-space:nowrap; }
        .account-ledger-summary { display:flex; justify-content:flex-end; gap:.5rem; padding:.85rem 1rem; color:#0f172a; font-size:.85rem; font-weight:800; }
        @media(max-width:640px) { .account-ledger-filter > div, .account-ledger-filter button, .account-ledger-filter a { flex:1 1 100%; } }
    </style>
@endpush

@section('content')
    <div class="account-ledger-page d-grid gap-3">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap"><div><div class="account-ledger-code mb-1">{{ $account->code }}</div><h1 class="account-ledger-title mb-1">Buku Besar — {{ $account->name }}</h1><div class="text-muted small">Riwayat debit, kredit, dan saldo berjalan akun.</div></div><a class="account-ledger-btn" href="{{ route('standalone-accounting.accounts.show', $account) }}"><i class="bi bi-arrow-left" aria-hidden="true"></i>Kembali ke detail akun</a></div>
        <div class="account-ledger-card"><form class="account-ledger-filter" method="GET" action="{{ route('standalone-accounting.accounts.ledger', $account) }}"><div><label class="form-label small fw-semibold mb-1" for="from">Dari tanggal</label><input id="from" name="from" type="date" class="form-control" value="{{ $filters['from'] }}"></div><div><label class="form-label small fw-semibold mb-1" for="to">Sampai tanggal</label><input id="to" name="to" type="date" class="form-control" value="{{ $filters['to'] }}"></div><button class="account-ledger-btn primary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i>Terapkan</button>@if ($filters['from'] || $filters['to'])<a class="account-ledger-btn" href="{{ route('standalone-accounting.accounts.ledger', $account) }}">Reset</a>@endif</form></div>
        <div class="account-ledger-card">
            <div class="table-responsive">
                <table class="table account-ledger-table align-middle">
                    <thead><tr><th>Tanggal &amp; waktu</th><th>Referensi</th><th>Keterangan</th><th class="text-end">Debit</th><th class="text-end">Kredit</th><th class="text-end">Saldo berjalan</th></tr></thead>
                    <tbody>
                        @forelse ($lines as $line)
                            @php
                                $context = $lineContext[$line->id] ?? null;
                            @endphp
                            <tr>
                                <td><span class="d-block">{{ $line->journal->date?->format('d-m-Y') }}</span><small class="text-muted">Posting {{ $line->journal->posted_at?->format('H:i') ?: '-' }}</small></td>
                                <td>{{ $line->journal->reference ?: '-' }}</td>
                                <td>
                                    <div>{{ $line->journal->description ?: '-' }}</div>
                                    @if ($context && !empty($context['items']))
                                        <div class="account-ledger-description-meta account-ledger-item-breakdown">
                                            @foreach ($context['items'] as $item)
                                                <div class="account-ledger-item-line">
                                                    <span class="account-ledger-item-code">{{ $item['item_code'] }}</span>
                                                    <span class="account-ledger-item-value">{{ $qtyLabel($item['qty'], $item['uom'] ?? 'unit') }} · {{ $money($item['value'] ?? 0) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif ($context && ($context['item_code'] !== '-' || $context['qty'] > 0.000001))
                                        <div class="account-ledger-description-meta"><span class="account-ledger-item-code">{{ $context['item_code'] }}</span>
                                        @if ($context['qty'] > 0.000001)<span class="account-ledger-item-qty">{{ $qtyLabel($context['qty'], $context['uom'] ?? 'unit') }}</span>@endif
                                        </div>
                                    @else
                                        <div class="account-ledger-description-meta"><span class="text-muted">—</span></div>
                                    @endif
                                </td>
                                <td class="text-end">{{ $money($line->debit) }}</td>
                                <td class="text-end">{{ $money($line->credit) }}</td>
                                <td class="account-ledger-balance text-end">{{ $money($line->running_balance) }}<small class="d-block text-muted">{{ $line->running_balance_side ?: 'Seimbang' }}</small></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">Belum ada transaksi pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="account-ledger-summary">Saldo akhir: {{ $money($endingBalance) }} {{ $endingBalanceSide }}</div>
        </div>
    </div>
@endsection
