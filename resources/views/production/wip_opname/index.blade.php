@extends('layouts.app')

@section('title', 'WIP Opname Cutting')

@section('content')
<div class="page-wrap" style="max-width:960px;margin-inline:auto;padding:1rem 1rem 4rem;">

    <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
        <div>
            <h4 class="mb-0 fw-bold">WIP Opname — Cutting</h4>
            <p class="text-muted small mb-0">Riwayat periode stock opname bundle WIP cutting.</p>
        </div>
        @if (!$activePeriod)
            <a href="{{ route('production.wip_opname.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Buka Periode Baru
            </a>
        @else
            <a href="{{ route('production.wip_opname.show', $activePeriod) }}" class="btn btn-warning btn-sm">
                <i class="bi bi-clipboard-check me-1"></i> Lanjut Opname Aktif
            </a>
        @endif
    </div>

    @if ($activePeriod)
    <div class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-3" style="border-left:3px solid #f59e0b;">
        <i class="bi bi-lock-fill text-warning"></i>
        <span class="small">
            <strong>Transaksi cutting dibekukan</strong> — periode <code>{{ $activePeriod->code }}</code> sedang berjalan.
            Selesaikan opname untuk membuka kembali.
        </span>
    </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
    @endif

    <div class="cutting-card">
        <div class="cutting-card-body p-0">
            <table class="table table-sm align-middle mb-0">
                <thead style="background:rgba(148,163,184,.06);">
                    <tr>
                        <th class="ps-3">Kode</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th class="text-center">Bundle</th>
                        <th class="text-center">Dihitung</th>
                        <th class="text-center">Selisih</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($periods as $p)
                    @php
                        $statusColor = match($p->status) {
                            'open', 'counting'         => 'warning',
                            'pending_approval'         => 'info',
                            'approved', 'closed'       => 'success',
                            default                    => 'secondary',
                        };
                        $statusLabel = match($p->status) {
                            'open'             => 'Buka',
                            'counting'         => 'Sedang Hitung',
                            'pending_approval' => 'Menunggu Approve',
                            'approved'         => 'Disetujui',
                            'closed'           => 'Selesai',
                            default            => $p->status,
                        };
                        $total   = $p->lines()->count();
                        $counted = $p->lines()->where('is_counted', true)->count();
                        $withDiff = $p->lines()->where('is_counted', true)->whereRaw('ABS(difference) > 0.01')->count();
                    @endphp
                    <tr>
                        <td class="ps-3 mono fw-semibold">{{ $p->code }}</td>
                        <td class="small">{{ $p->date->format('d M Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} border border-{{ $statusColor }}-subtle" style="font-size:.7rem;">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="text-center mono small">{{ $total }}</td>
                        <td class="text-center mono small">{{ $counted }} / {{ $total }}</td>
                        <td class="text-center mono small {{ $withDiff > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                            {{ $withDiff > 0 ? $withDiff : '—' }}
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route('production.wip_opname.show', $p) }}" class="btn btn-sm btn-outline-primary btn-pill-sm">
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4 small">Belum ada periode opname.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-2">{{ $periods->links() }}</div>
</div>
@endsection
