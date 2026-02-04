@extends('layouts.app')

@section('title', 'FG Receipt • ' . $order->code)

@push('head')
<style>
    .cardx { border:1px solid #e5e7eb; border-radius:14px; padding:14px; background:#fff; }
    .muted { color:#6b7280; font-size:13px; }
    .grid2 { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
    .btnx { border-radius:10px; padding:.45rem .75rem; }
    .table-sm td, .table-sm th { padding:.45rem .5rem; }
    @media (max-width: 768px){ .grid2 { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="container">

    <div class="mb-3">
        <h3 class="mb-1">FG Receipt</h3>
        <div class="muted">Untuk Production Order: <strong>{{ $order->code }}</strong></div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Error:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('production.receipts.store', $order) }}" class="cardx">
        @csrf

        <div class="grid2 mb-3">
            <div>
                <label class="form-label">Tanggal</label>
                <input type="date" name="date" class="form-control"
                       value="{{ old('date', now()->toDateString()) }}" required>
            </div>
            <div>
                <label class="form-label">Gudang</label>
                <div class="muted">
                    <div>From: <strong>{{ $wip->code }}</strong> — {{ $wip->name }}</div>
                    <div>To: <strong>{{ $fg->code }}</strong> — {{ $fg->name }}</div>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Catatan</label>
            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h5 class="mb-0">Items (FG)</h5>
                <div class="muted">Tambah baris FG + qty good. (Lot opsional)</div>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm btnx" onclick="addRow()">+ Add Row</button>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle" id="linesTable">
                <thead>
                    <tr>
                        <th style="width:55%">Item FG</th>
                        <th style="width:15%">Qty Good</th>
                        <th style="width:20%">Lot ID (opsional)</th>
                        <th style="width:10%"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="lines[0][item_id]" class="form-select" required>
                                <option value="">— pilih item —</option>
                                @foreach($items as $it)
                                    <option value="{{ $it->id }}">{{ $it->code }} — {{ $it->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0.01" name="lines[0][qty_good]" class="form-control" required>
                        </td>
                        <td>
                            <input type="number" step="1" min="1" name="lines[0][lot_id]" class="form-control" placeholder="(optional)">
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-outline-danger btn-sm btnx" onclick="removeRow(this)">×</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('production.orders.show', $order) }}" class="btn btn-light btnx">Back</a>
            <button type="submit" class="btn btn-success btnx">Save Draft</button>
        </div>

        <div class="muted mt-2">
            Draft belum mengubah stok. Setelah draft dibuat, buka PO lalu klik <strong>POST</strong> di receipt untuk memutasi stok.
        </div>
    </form>

</div>

<script>
    let rowIndex = 1;

    function addRow(){
        const tbody = document.querySelector('#linesTable tbody');
        const tr = document.createElement('tr');

        tr.innerHTML = `
            <td>
                <select name="lines[${rowIndex}][item_id]" class="form-select" required>
                    <option value="">— pilih item —</option>
                    @foreach($items as $it)
                        <option value="{{ $it->id }}">{{ $it->code }} — {{ $it->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" step="0.01" min="0.01" name="lines[${rowIndex}][qty_good]" class="form-control" required>
            </td>
            <td>
                <input type="number" step="1" min="1" name="lines[${rowIndex}][lot_id]" class="form-control" placeholder="(optional)">
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-outline-danger btn-sm btnx" onclick="removeRow(this)">×</button>
            </td>
        `;
        tbody.appendChild(tr);
        rowIndex++;
    }

    function removeRow(btn){
        const tbody = document.querySelector('#linesTable tbody');
        if (tbody.querySelectorAll('tr').length <= 1) return;
        btn.closest('tr').remove();
    }
</script>
@endsection
