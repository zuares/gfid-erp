@extends('layouts.app')
@section('title', 'Import Order Marketplace')
@push('head') @include('admin.crm.marketplace._styles') @endpush

@section('content')
<div class="container-fluid py-3">
    @include('admin.crm.marketplace._nav')
    <div class="row justify-content-center"><div class="col-xl-8"><div class="mpcrm-card">
        <h5 class="fw-bold mb-1">Import order marketplace</h5>
        <p class="text-secondary mb-4" style="font-size:.8rem;">Gunakan export order marketplace seperti file Shopee terlampir. Satu order boleh memiliki beberapa baris item.</p>
        @if(isset($errors) && $errors->any())<div class="alert alert-danger" style="font-size:.8rem;border-radius:12px;">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('admin.crm.marketplace.import.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3"><label class="form-label fw-bold" style="font-size:.8rem;">Toko marketplace</label><select name="store_id" class="form-select" required><option value="">Pilih toko</option>@foreach($stores as $store)<option value="{{ $store->id }}" @selected(old('store_id') == $store->id)>{{ $store->name }} · {{ $store->channel?->name ?? '-' }}</option>@endforeach</select><div class="form-text">Order identity menggunakan toko + channel + No. Pesanan.</div></div>
            <div class="mb-4"><label class="form-label fw-bold" style="font-size:.8rem;">File order</label><input type="file" name="files[]" class="form-control" accept=".xlsx,.xls,.csv" multiple required><div class="form-text">Pilih satu atau beberapa file sekaligus. Format .xlsx, .xls, atau .csv; maksimal 20 MB per file, sampai 20 file.</div></div>
            <div class="p-3 mb-4" style="background:#f8fafc;border-radius:12px;font-size:.78rem;color:#475569;"><div class="fw-bold text-dark mb-1"><i class="bi bi-shield-check me-1"></i>Idempotent dan tanpa order redundan</div><div>Import ulang file yang sama akan meng-update order yang sama. Item order diganti dengan snapshot terbaru dalam satu transaksi. Customer dicari berdasarkan nomor telepon yang sudah dinormalisasi.</div></div>
            <div class="d-flex justify-content-end gap-2"><a href="{{ route('admin.crm.marketplace.dashboard') }}" class="btn btn-outline-secondary">Batal</a><button class="btn btn-primary"><i class="bi bi-upload me-1"></i>Import sekarang</button></div>
        </form>
    </div></div></div>

    <div class="row justify-content-center mt-3"><div class="col-xl-8"><div class="mpcrm-card p-0 overflow-hidden">
        <div class="d-flex justify-content-between align-items-center gap-2 p-3 border-bottom">
            <div><div class="fw-bold">Riwayat import terakhir</div><div class="mpcrm-sub">File yang baru diproses dari CRM Marketplace.</div></div>
            <span class="mpcrm-pill" style="background:#eff6ff;color:#1d4ed8;">{{ $recentImports->count() }} terakhir</span>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 mpcrm-table">
                <thead><tr><th>File</th><th>Toko</th><th>Status</th><th>Order</th><th>Waktu</th></tr></thead>
                <tbody>
                @forelse($recentImports as $batch)
                    @php
                        $status = strtolower((string) $batch->status);
                        $statusStyle = match ($status) {
                            'completed' => ['#dcfce7', '#166534', 'Selesai'],
                            'failed', 'error' => ['#fee2e2', '#991b1b', 'Gagal'],
                            default => ['#fef3c7', '#92400e', ucfirst($status ?: 'Diproses')],
                        };
                    @endphp
                    <tr>
                        <td><div class="fw-semibold" style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $batch->source_file }}">{{ $batch->source_file }}</div><div class="mpcrm-sub">{{ number_format((int) $batch->total_rows) }} baris · {{ number_format((int) $batch->items_parsed) }} item</div></td>
                        <td>{{ $batch->store?->name ?: '-' }}</td>
                        <td><span class="mpcrm-pill" style="background:{{ $statusStyle[0] }};color:{{ $statusStyle[1] }};">{{ $statusStyle[2] }}</span></td>
                        <td class="fw-bold">{{ number_format((int) $batch->shipments_parsed) }}<div class="mpcrm-sub">{{ number_format((int) $batch->inserted_shipments) }} baru · {{ number_format((int) $batch->updated_shipments) }} update</div></td>
                        <td>{{ optional($batch->created_at)->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-4">Belum ada riwayat import.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div></div></div>
</div>
@endsection
