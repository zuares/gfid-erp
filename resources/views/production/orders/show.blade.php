{{-- resources/views/production/orders/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Production Order • ' . $order->code)

@push('head')
<style>
    :root { --bd:#e5e7eb; --muted:#6b7280; --soft:#fafafa; }
    .muted { color:var(--muted); font-size:13px; }
    .cardx { border:1px solid var(--bd); border-radius:14px; padding:14px; background:#fff; }

    .kpi{ display:flex; gap:12px; flex-wrap:wrap; }
    .kpi .box{ min-width:210px; border:1px solid var(--bd); border-radius:14px; padding:12px; background:#fff; }
    .kpi .label{ font-size:12px; color:var(--muted); }
    .kpi .value{ font-size:20px; font-weight:800; line-height:1.2; }
    .kpi .hint{ color:var(--muted); font-size:12px; margin-top:6px; }

    .table thead th { font-size:12px; color:var(--muted); font-weight:700; }
    .table-sm td, .table-sm th { padding:.45rem .55rem; }
    .section-title{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px; }
    .section-title h4{ margin:0; }
    .soft-row{ background:var(--soft); }

    .progress{ background:#f3f4f6; border-radius:999px; overflow:hidden; }
    .progress-bar{ background:#111827; }

    .nav-tabs .nav-link { border-radius:12px 12px 0 0; }
    .tab-pane{ padding-top:12px; }

    .chip{
        display:inline-flex; align-items:center; gap:6px;
        padding:6px 10px; border-radius:999px;
        border:1px solid var(--bd); background:#fff;
        font-size:12px; white-space:nowrap;
    }

    @media (max-width:768px){ .kpi .box{ min-width:46%; } }
</style>
@endpush

@section('content')
<div class="container">

    {{-- Flash --}}
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Error:</strong>
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="mb-1">{{ $order->code }}</h2>
            <div class="muted">
                Tanggal: <strong>{{ $order->order_date }}</strong>
                • Status:
                @php
                    $s = strtolower((string)$order->status);
                    $cls = $s === 'done' ? 'bg-success'
                         : ($s === 'in_progress' ? 'bg-warning text-dark'
                         : ($s === 'cancelled' ? 'bg-danger' : 'bg-secondary'));
                @endphp
                <span class="badge {{ $cls }}">{{ strtoupper($s) }}</span>
            </div>
            <div class="muted mt-1">WIP live: <strong>{{ $wipWh->code }}</strong></div>
        </div>

        <div class="d-flex gap-2 flex-wrap justify-content-end">
            <a href="{{ route('production.issues.create', $order) }}" class="btn btn-outline-primary btn-sm">+ Issue Material</a>
            <a href="{{ route('production.receipts.create', $order) }}" class="btn btn-outline-success btn-sm">+ Receive FG</a>
        </div>
    </div>

    {{-- KPIs (unit-correct) --}}
    <div class="kpi mb-4">
        <div class="box">
            <div class="label">RM Issued to WIP (POSTED)</div>
            <div class="value">{{ $rmIssuedToWip }}</div>
            <div class="hint">Unit: material (meter/pcs RM). Tidak dibandingkan dengan FG.</div>
        </div>

        <div class="box">
            <div class="label">RM Stock in WIP (LIVE)</div>
            <div class="value">{{ $rmStockInWipLive }}</div>
            <div class="hint">Sumber: inventory_stocks ({{ $wipWh->code }}, type=material).</div>
        </div>

        <div class="box">
            <div class="label">FG Received (POSTED)</div>
            <div class="value">{{ $fgReceivedTotal }}</div>
            <div class="hint">Unit: finished goods (pcs).</div>
        </div>

        <div class="box">
            <div class="label">FG Remaining</div>
            <div class="value">{{ $fgRemainingTotal }}</div>
            <div class="hint">Target ({{ $fgTargetTotal }}) − Received ({{ $fgReceivedTotal }}).</div>
        </div>
    </div>

    {{-- Top WIP RM Items --}}
    <div class="cardx mb-4">
        <div class="section-title">
            <h4>🧱 Top WIP RM Items</h4>
            <span class="muted">Top 10 qty • {{ $wipWh->code }}</span>
        </div>

        @php
            $countWipFabric = (int)($countWipFabric ?? 0);
            $countWipAccessories = (int)($countWipAccessories ?? 0);
            $qtyWipFabric = (float)($qtyWipFabric ?? 0);
            $qtyWipAccessories = (float)($qtyWipAccessories ?? 0);
        @endphp

        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-fabric" data-bs-toggle="tab" data-bs-target="#pane-fabric" type="button" role="tab">
                    Fabric
                    <span class="badge bg-light text-dark ms-1" style="border:1px solid #e5e7eb">{{ $countWipFabric }}</span>
                    <span class="badge bg-dark ms-1">{{ $qtyWipFabric }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-acc" data-bs-toggle="tab" data-bs-target="#pane-acc" type="button" role="tab">
                    Accessories
                    <span class="badge bg-light text-dark ms-1" style="border:1px solid #e5e7eb">{{ $countWipAccessories }}</span>
                    <span class="badge bg-dark ms-1">{{ $qtyWipAccessories }}</span>
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="pane-fabric" role="tabpanel">
                @if(($topWipFabric ?? collect())->count() === 0)
                    <div class="text-muted">Tidak ada Fabric di WIP (qty ≠ 0).</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Kategori</th>
                                    <th class="text-end">Qty (Live)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topWipFabric as $r)
                                    <tr>
                                        <td>
                                            <div><strong>{{ $r->code ?? ('ID#'.$r->item_id) }}</strong></div>
                                            <div class="muted">{{ $r->name ?? '-' }}</div>
                                        </td>
                                        <td class="muted">{{ $r->category_name ?? '-' }}</td>
                                        <td class="text-end">
                                            <span class="badge bg-light text-dark" style="border:1px solid #e5e7eb">{{ $r->qty }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="tab-pane fade" id="pane-acc" role="tabpanel">
                @if(($topWipAccessories ?? collect())->count() === 0)
                    <div class="text-muted">Tidak ada Accessories di WIP (qty ≠ 0).</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Kategori</th>
                                    <th class="text-end">Qty (Live)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topWipAccessories as $r)
                                    <tr>
                                        <td>
                                            <div><strong>{{ $r->code ?? ('ID#'.$r->item_id) }}</strong></div>
                                            <div class="muted">{{ $r->name ?? '-' }}</div>
                                        </td>
                                        <td class="muted">{{ $r->category_name ?? '-' }}</td>
                                        <td class="text-end">
                                            <span class="badge bg-light text-dark" style="border:1px solid #e5e7eb">{{ $r->qty }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="muted mt-2">
            Badge putih = jumlah item, badge hitam = total qty. Filter: <code>items.type = material</code>.
        </div>
    </div>

    {{-- Target vs Received (FG) --}}
    <div class="cardx mb-4">
        <div class="section-title">
            <h4>🎯 Target vs Received (FG)</h4>
            <span class="muted">Output unit (pcs FG)</span>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-end">Target</th>
                        <th class="text-end">Received (POSTED)</th>
                        <th class="text-end">Remaining</th>
                        <th style="width:160px">Progress</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($targets as $line)
                        @php
                            $itemId = $line->item_id;
                            $target = (float)($targetByItem[$itemId] ?? $line->qty_target);
                            $rcv = (float)($receivedByItem[$itemId] ?? 0);
                            $rem = max(0, $target - $rcv);
                            $pct = $target > 0 ? min(100, round(($rcv / $target) * 100)) : 0;
                        @endphp
                        <tr>
                            <td>
                                <div><strong>{{ $line->item->code ?? ('ID#'.$itemId) }}</strong></div>
                                <div class="muted">{{ $line->item->name ?? '-' }}</div>
                            </td>
                            <td class="text-end">{{ $target }}</td>
                            <td class="text-end">{{ $rcv }}</td>
                            <td class="text-end">
                                @if($rem <= 0) <span class="badge bg-success">DONE</span>
                                @else {{ $rem }} @endif
                            </td>
                            <td>
                                <div class="progress" style="height:10px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $pct }}%;"></div>
                                </div>
                                <div class="muted mt-1">{{ $pct }}%</div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada target line.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Progress (log only) --}}
    <div class="cardx mb-4">
        <div class="section-title">
            <h4>🛠️ Progress Produksi</h4>
            <span class="muted">Log saja (tanpa stok)</span>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <span class="chip">CUT: <strong>{{ $activities['cut'] ?? 0 }}</strong></span>
            <span class="chip">SEW: <strong>{{ $activities['sew'] ?? 0 }}</strong></span>
            <span class="chip">FIN: <strong>{{ $activities['fin'] ?? 0 }}</strong></span>
        </div>
        <div class="muted mt-2">Stok berubah hanya saat POST Issue/Receipt.</div>
    </div>

    {{-- Activity Log --}}
    <div class="cardx mb-4">
        <div class="section-title">
            <h4>📝 Activity Log (CUT / SEW / FIN)</h4>
            <span class="muted">Input cepat</span>
        </div>

        <form method="POST" action="{{ route('production.activities.store', $order) }}" class="mb-3">
            @csrf
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Proses</label>
                    <select name="process" class="form-select" required>
                        <option value="cut" @selected(old('process')==='cut')>CUT</option>
                        <option value="sew" @selected(old('process')==='sew')>SEW</option>
                        <option value="fin" @selected(old('process')==='fin')>FIN</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Qty</label>
                    <input type="number" step="0.01" min="0.01" name="qty" value="{{ old('qty') }}" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Notes (opsional)</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="mis: line A / shift malam">
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-dark">+ Add Log</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Proses</th>
                        <th class="text-end">Qty</th>
                        <th>Notes</th>
                        <th class="text-end" style="width:90px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activityRows as $row)
                        @php
                            $label = strtoupper($row->process);
                            $badge = $row->process === 'cut'
                                ? 'bg-primary'
                                : ($row->process === 'sew' ? 'bg-warning text-dark' : 'bg-success');
                        @endphp
                        <tr>
                            <td>{{ $row->date }}</td>
                            <td><span class="badge {{ $badge }}">{{ $label }}</span></td>
                            <td class="text-end">{{ $row->qty }}</td>
                            <td class="muted">{{ $row->notes }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('production.activities.destroy', $row) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('Hapus activity log ini?')">Del</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada activity log.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Issue List --}}
    <div class="cardx mb-4">
        <div class="section-title">
            <h4>📌 Issue List (RM → WIP)</h4>
            <a href="{{ route('production.issues.create', $order) }}" class="btn btn-outline-primary btn-sm">+ Issue Material</a>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th class="text-end">Total Qty</th>
                        <th class="text-end" style="width:140px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($issues as $iss)
                        @php $totalQty = $iss->lines->sum('qty'); $isPosted = $iss->status === 'posted'; @endphp
                        <tr>
                            <td>
                                <strong>{{ $iss->code }}</strong>
                                @if($iss->notes)<div class="muted">{{ \Illuminate\Support\Str::limit($iss->notes, 70) }}</div>@endif
                            </td>
                            <td>{{ $iss->date }}</td>
                            <td><span class="badge {{ $isPosted ? 'bg-success' : 'bg-secondary' }}">{{ $isPosted ? 'POSTED' : 'DRAFT' }}</span></td>
                            <td class="text-end">{{ $totalQty }}</td>
                            <td class="text-end">
                                @if(!$isPosted)
                                    <form method="POST" action="{{ route('production.issues.post', $iss) }}" style="display:inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success"
                                                onclick="return confirm('POST issue ini? Stok WH-RM akan berkurang dan WIP-PROD bertambah.')">POST</button>
                                    </form>
                                @else <span class="text-muted">—</span> @endif
                            </td>
                        </tr>
                        <tr class="soft-row">
                            <td colspan="5">
                                <div class="muted mb-1">Lines:</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($iss->lines as $ln)
                                        <span class="chip">
                                            {{ $ln->item->code ?? ('ID#'.$ln->item_id) }}
                                            <strong>{{ $ln->qty }}</strong>
                                            @if($ln->lot_id) <span class="muted">lot {{ $ln->lot_id }}</span> @endif
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada issue material.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Receipt List --}}
    <div class="cardx mb-4">
        <div class="section-title">
            <h4>✅ FG Receipt List (WIP → FG)</h4>
            <a href="{{ route('production.receipts.create', $order) }}" class="btn btn-outline-success btn-sm">+ Receive FG</a>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th class="text-end">Total Qty</th>
                        <th class="text-end" style="width:140px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $rcv)
                        @php $totalQty = $rcv->lines->sum('qty_good'); $isPosted = $rcv->status === 'posted'; @endphp
                        <tr>
                            <td>
                                <strong>{{ $rcv->code }}</strong>
                                @if($rcv->notes)<div class="muted">{{ \Illuminate\Support\Str::limit($rcv->notes, 70) }}</div>@endif
                            </td>
                            <td>{{ $rcv->date }}</td>
                            <td><span class="badge {{ $isPosted ? 'bg-success' : 'bg-secondary' }}">{{ $isPosted ? 'POSTED' : 'DRAFT' }}</span></td>
                            <td class="text-end">{{ $totalQty }}</td>
                            <td class="text-end">
                                @if(!$isPosted)
                                    <form method="POST" action="{{ route('production.receipts.post', $rcv) }}" style="display:inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success"
                                                onclick="return confirm('POST receipt ini? Stok WIP akan berkurang dan WH-FG bertambah.')">POST</button>
                                    </form>
                                @else <span class="text-muted">—</span> @endif
                            </td>
                        </tr>
                        <tr class="soft-row">
                            <td colspan="5">
                                <div class="muted mb-1">Lines:</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($rcv->lines as $ln)
                                        <span class="chip">
                                            {{ $ln->item->code ?? ('ID#'.$ln->item_id) }}
                                            <strong>{{ $ln->qty_good }}</strong>
                                            @if($ln->lot_id) <span class="muted">lot {{ $ln->lot_id }}</span> @endif
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada FG receipt.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
