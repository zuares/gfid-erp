@extends('layouts.app')

@section('title', 'Buka Periode WIP Opname')

@section('content')
<div class="page-wrap" style="max-width:720px;margin-inline:auto;padding:1rem 1rem 4rem;">

    <div class="mb-3">
        <a href="{{ route('production.wip_opname.index') }}" class="text-muted small">← Kembali</a>
        <h4 class="fw-bold mb-0 mt-1">Buka Periode WIP Opname Cutting</h4>
    </div>

    @if ($activePeriod)
        <div class="alert alert-danger">
            Masih ada periode aktif: <strong>{{ $activePeriod->code }}</strong>.
            Selesaikan dulu sebelum membuka yang baru.
        </div>
    @else

    <div class="cutting-card mb-3">
        <div class="cutting-card-header">
            <h5>Preview Bundle yang Akan Di-opname</h5>
            <span class="badge-soft">{{ $bundles->count() }} bundle</span>
        </div>
        <div class="cutting-card-body p-0" style="max-height:340px;overflow-y:auto;">
            @if ($bundles->isEmpty())
                <div class="text-center text-muted py-4 small">Tidak ada bundle WIP cutting saat ini.</div>
            @else
            <table class="table table-sm align-middle mb-0">
                <thead style="position:sticky;top:0;background:var(--card);z-index:1;">
                    <tr>
                        <th class="ps-3">Bundle</th>
                        <th>Item</th>
                        <th>Job</th>
                        <th class="text-end pe-3">Stok WIP</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($bundles as $b)
                    <tr>
                        <td class="ps-3 mono small">{{ $b->bundle_code }}</td>
                        <td class="small">{{ $b->finishedItem?->code }}</td>
                        <td class="small text-muted">{{ $b->cuttingJob?->code }}</td>
                        <td class="text-end pe-3 mono small fw-bold">{{ number_format($b->cut_wip_qty, 2, ',', '.') }} pcs</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    @if ($bundles->isNotEmpty())
    <div class="cutting-card">
        <div class="cutting-card-header"><h5>Konfirmasi Buka Periode</h5></div>
        <div class="cutting-card-body">
            <div class="alert alert-warning py-2 small mb-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                Setelah dibuka, <strong>semua transaksi cutting baru akan diblokir</strong> sampai opname selesai dan disetujui.
            </div>

            <form method="POST" action="{{ route('production.wip_opname.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Catatan (opsional)</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="2"
                        placeholder="Alasan opname, shift, nama tim, dll.">{{ old('notes') }}</textarea>
                </div>
                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('production.wip_opname.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-lock me-1"></i> Buka & Bekukan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @endif
</div>
@endsection
