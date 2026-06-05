@extends('layouts.app')

@section('title', 'Shipment Analytics')

@section('content')
    <div class="container py-3">

        <h4 class="mb-3">Shipment Analytics</h4>

        {{-- FILTER --}}
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">

                    <div class="col-md-3">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                            value="{{ $filters['date_from'] }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                            value="{{ $filters['date_to'] }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Gudang</label>
                        <select name="warehouse_id" class="form-select form-select-sm">
                            <option value="">– Semua –</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected($filters['warehouse_id'] == $wh->id)>
                                    {{ $wh->code }} — {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Kurir</label>
                        <input type="text" name="shipping_method" class="form-control form-control-sm"
                            value="{{ $filters['shipping_method'] }}" placeholder="JNE, J&T, Sicepat">
                    </div>

                    <div class="col-12 d-flex justify-content-end mt-2">
                        <button class="btn btn-sm btn-outline-primary me-2">Terapkan Filter</button>
                        <a href="{{ route('sales.reports.shipment_analytics') }}"
                            class="btn btn-sm btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- CARD: AVG LEAD TIME --}}
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="mb-1">Lead Time Rata-rata</h6>
                <div class="display-6">{{ $avgLeadTime }} <small class="text-muted fs-6">hari</small></div>
            </div>
        </div>

        {{-- TABEL LEAD TIME --}}
        <div class="card mb-4">
            <div class="card-header">Detail Lead Time Invoice → Shipment</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Tgl Invoice</th>
                            <th>Shipment</th>
                            <th>Tgl Shipment</th>
                            <th class="text-end">Lead Time (Hari)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leadTimes as $lt)
                            <tr>
                                <td>{{ $lt['invoice_code'] }}</td>
                                <td>{{ $lt['invoice_date'] }}</td>
                                <td>{{ $lt['shipment_code'] }}</td>
                                <td>{{ $lt['shipment_date'] }}</td>
                                <td class="text-end fw-semibold">{{ $lt['lead_days'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- SHIPMENT PER KURIR --}}
        <div class="card mb-4">
            <div class="card-header">Jumlah Shipment per Kurir</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Kurir</th>
                            <th class="text-end">Total Shipment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($byCourier as $row)
                            <tr>
                                <td>{{ $row['shipping_method'] }}</td>
                                <td class="text-end fw-semibold">{{ $row['total'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- SHIPMENT PER TANGGAL --}}
        <div class="card">
            <div class="card-header">Shipment per Hari</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th class="text-end">Total Shipment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($byDate as $row)
                            <tr>
                                <td>{{ $row['date'] }}</td>
                                <td class="text-end fw-semibold">{{ $row['total'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
