@extends('layouts.app')
@section('title', 'CRM Orders')

@push('head')
<style>
.status-badge { font-size:.68rem; font-weight:800; padding:.2rem .55rem; border-radius:999px; text-transform:uppercase; letter-spacing:.05em; }
.status-pending    { background:#fef9c3; color:#854d0e; }
.status-confirmed  { background:#dbeafe; color:#1e40af; }
.status-processing { background:#ede9fe; color:#5b21b6; }
.status-shipped    { background:#d1fae5; color:#065f46; }
.status-done       { background:#d1fae5; color:#065f46; }
.status-cancelled  { background:#fee2e2; color:#991b1b; }
.tab-pill { border:1.5px solid #e2e8f0; border-radius:999px; padding:.25rem .7rem; font-size:.72rem; font-weight:700; text-decoration:none; color:#64748b; background:#fff; white-space:nowrap; }
.tab-pill.active, .tab-pill:hover { background:#0f172a; color:#fff; border-color:#0f172a; }
.crm-table th { font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; border-bottom:1.5px solid #e8ecf0; padding:.5rem .75rem; background:#f8fafc; white-space:nowrap; }
.crm-table td { font-size:.8rem; vertical-align:middle; padding:.55rem .75rem; border-bottom:1px solid #f1f5f9; }
.crm-table tr:last-child td { border-bottom:0; }
.crm-status-form select { font-size:.68rem; font-weight:700; padding:.18rem .45rem; border:1.5px solid #e2e8f0; border-radius:8px; background:#fff; cursor:pointer; }
.aging-row td:first-child { border-left:3px solid #f97316; }
.aging-row { background:#fffbeb !important; }
.aging-badge { display:inline-flex;align-items:center;gap:.25rem;font-size:.62rem;font-weight:800;background:#fee2e2;color:#dc2626;padding:.1rem .4rem;border-radius:4px;margin-top:.2rem; }
.stat-card { background:#fff; border:1.5px solid #e8ecf0; border-radius:16px; padding:1rem 1.25rem; }
.stat-label { font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; margin-bottom:.25rem; }
.stat-value { font-size:1.5rem; font-weight:900; color:#0f172a; line-height:1.1; }
.stat-sub { font-size:.72rem; color:#64748b; margin-top:.2rem; }
.live-dot { display:inline-block; width:7px; height:7px; border-radius:50%; background:#22c55e; animation:livePulse 1.8s ease-in-out infinite; }
.live-dot.paused { background:#94a3b8; animation:none; }
@keyframes livePulse { 0%,100% { opacity:1;transform:scale(1); } 50% { opacity:.4;transform:scale(.75); } }
.flash-new { animation:flashNew .9s ease; }
@keyframes flashNew { 0% { background:#fef9c3; } 100% { background:transparent; } }
#new-order-banner { display:none; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-black mb-0" style="font-size:1.05rem;">Order Management</h5>
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:.65rem;font-weight:800;color:#16a34a;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:999px;padding:.15rem .55rem;">
                    <span class="live-dot" id="live-dot"></span> LIVE
                </span>
            </div>
            <div style="font-size:.75rem;color:#94a3b8;margin-top:2px;">
                Semua order storefront · <span id="last-updated">memuat...</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.crm.dashboard') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.75rem;">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
            @if(env('APP_DB_MODE') === 'dev' && auth()->user()?->role === 'owner')
            {{-- Seed --}}
            <form method="POST" action="{{ route('admin.crm.dev_seed') }}" id="seedForm"
                  onsubmit="return confirmSeed(event)">
                @csrf
                <input type="hidden" name="orders" id="seedOrderCount" value="60">
                <button type="submit" class="btn btn-sm fw-bold"
                        style="background:#f0f9ff;color:#0369a1;border:1.5px dashed #7dd3fc;border-radius:10px;font-size:.72rem;padding:.25rem .7rem;">
                    <i class="bi bi-database-add me-1"></i>Seed Data
                    <span style="font-size:.6rem;background:#bae6fd;color:#0c4a6e;border-radius:4px;padding:.02rem .25rem;margin-left:4px;font-weight:900;">DEV</span>
                </button>
            </form>
            {{-- Reset --}}
            <form method="POST" action="{{ route('admin.crm.dev_reset') }}"
                  onsubmit="return confirm('⚠️ RESET semua data storefront?\n\nIni akan menghapus:\n• storefront_customers\n• storefront_orders\n• storefront_visitors\n• storefront_events\n\nTidak bisa di-undo!')">
                @csrf
                <button type="submit" class="btn btn-sm fw-bold"
                        style="background:#fff0f0;color:#b91c1c;border:1.5px dashed #fca5a5;border-radius:10px;font-size:.72rem;padding:.25rem .7rem;">
                    <i class="bi bi-trash3 me-1"></i>Reset Data
                    <span style="font-size:.6rem;background:#fecaca;color:#991b1b;border-radius:4px;padding:.05rem .3rem;margin-left:4px;font-weight:900;">DEV</span>
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- New order banner --}}
    <div id="new-order-banner" class="alert d-flex align-items-center justify-content-between gap-2 py-2 mb-3"
         style="background:#f0fdf4;border:1.5px solid #22c55e;border-radius:12px;font-size:.8rem;">
        <span><i class="bi bi-bell-fill me-1" style="color:#16a34a;"></i> <strong id="new-order-text">Order baru masuk!</strong></span>
        <button onclick="location.reload()" class="btn btn-sm btn-success" style="border-radius:8px;font-size:.75rem;">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </button>
    </div>

    @if(session('dev_seed_done'))
    <div class="alert alert-info alert-dismissible fade show py-2 mb-3" style="font-size:.8rem;border-radius:12px;background:#f0f9ff;border-color:#7dd3fc;color:#0369a1;" role="alert">
        <i class="bi bi-database-check me-1"></i> {{ session('dev_seed_done') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 mb-3" style="font-size:.8rem;border-radius:12px;" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Stat cards --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-color:#fde68a;">
                <div class="stat-label" style="color:#b45309;">Pending</div>
                <div class="stat-value" id="stat-pending" style="color:#b45309;">{{ $pendingCount }}</div>
                <div class="stat-sub">perlu diproses</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Order Hari Ini</div>
                <div class="stat-value" id="stat-today">{{ $todayCount }}</div>
                <div class="stat-sub">{{ now()->format('d M Y') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-color:#bbf7d0;">
                <div class="stat-label" style="color:#16a34a;">Revenue Hari Ini</div>
                <div class="stat-value" id="stat-revenue" style="color:#16a34a;font-size:1.2rem;">Rp{{ number_format($todayRevenue) }}</div>
                <div class="stat-sub">exclude cancelled</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-color:{{ $agingCount > 0 ? '#fed7aa' : '#e8ecf0' }};">
                <div class="stat-label" style="color:{{ $agingCount > 0 ? '#c2410c' : '#94a3b8' }};">Aging >24 Jam</div>
                <div class="stat-value" id="stat-aging" style="color:{{ $agingCount > 0 ? '#c2410c' : '#94a3b8' }};">{{ $agingCount }}</div>
                <div class="stat-sub">pending belum diproses</div>
            </div>
        </div>
    </div>

    {{-- Aging alert --}}
    @if($agingCount > 0)
    <div id="aging-alert" class="alert d-flex align-items-center gap-2 py-2 mb-3"
         style="background:#fff7ed;border:1.5px solid #f97316;border-radius:12px;font-size:.8rem;">
        <i class="bi bi-exclamation-triangle-fill" style="color:#f97316;font-size:1rem;flex-shrink:0;"></i>
        <span id="aging-alert-text">
            <strong>{{ $agingCount }} order pending</strong> sudah lebih dari 24 jam belum diproses — ditandai border oranye.
        </span>
    </div>
    @else
    <div id="aging-alert" style="display:none;" class="alert d-flex align-items-center gap-2 py-2 mb-3"
         style="background:#fff7ed;border:1.5px solid #f97316;border-radius:12px;font-size:.8rem;">
        <i class="bi bi-exclamation-triangle-fill" style="color:#f97316;font-size:1rem;flex-shrink:0;"></i>
        <span id="aging-alert-text"></span>
    </div>
    @endif

    {{-- Status tabs --}}
    <div class="d-flex gap-2 flex-wrap mb-3">
        @php
            $labels = [
                ''           => 'Semua',
                'pending'    => 'Pending',
                'confirmed'  => 'Confirmed',
                'processing' => 'Processing',
                'shipped'    => 'Shipped',
                'done'       => 'Done',
                'cancelled'  => 'Cancelled',
            ];
        @endphp
        @foreach($labels as $val => $label)
        <a href="{{ route('admin.crm.orders', array_merge(request()->query(), ['status' => $val, 'page' => 1])) }}"
           class="tab-pill {{ $status === $val ? 'active' : '' }}" data-status="{{ $val }}">
            {{ $label }}
            @if($val && isset($statusCounts[$val]))
            <span class="ms-1 status-tab-count" data-for="{{ $val }}" style="font-size:.65rem;opacity:.8;">({{ $statusCounts[$val] }})</span>
            @endif
        </a>
        @endforeach
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('admin.crm.orders') }}" class="mb-3 d-flex gap-2">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="text" name="search" value="{{ $search }}" placeholder="Cari nomor order, nama, HP…"
               class="form-control form-control-sm" style="max-width:280px;border-radius:10px;font-size:.8rem;">
        <button type="submit" class="btn btn-sm btn-dark" style="border-radius:10px;font-size:.8rem;">Cari</button>
        @if($search)
        <a href="{{ route('admin.crm.orders', ['status' => $status]) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.8rem;">Reset</a>
        @endif
    </form>

    {{-- Table --}}
    <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table class="table mb-0 crm-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Tanggal</th>
                        <th>Customer</th>
                        <th>Kota</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Pembayaran</th>
                        <th>Status</th>
                        <th>WA</th>
                    </tr>
                </thead>
                <tbody id="orders-tbody">
                    @forelse($orders as $order)
                    @php
                        $agingHours = (int) \Carbon\Carbon::parse($order->created_at)->diffInHours(now());
                        $isAging    = $order->status === 'pending' && $agingHours >= 24;
                    @endphp
                    <tr class="{{ $isAging ? 'aging-row' : '' }}" data-order-id="{{ $order->id }}">
                        <td>
                            <span class="fw-bold" style="font-family:monospace;font-size:.78rem;">{{ $order->order_number }}</span>
                            @if($isAging)
                            <div class="aging-badge"><i class="bi bi-exclamation-triangle-fill"></i>{{ $agingHours >= 48 ? round($agingHours/24).'h' : $agingHours.'j' }} menunggu</div>
                            @endif
                        </td>
                        <td style="white-space:nowrap;color:#64748b;">
                            {{ \Carbon\Carbon::parse($order->created_at)->format('d M y') }}<br>
                            <span style="font-size:.7rem;">{{ \Carbon\Carbon::parse($order->created_at)->format('H:i') }}</span>
                        </td>
                        <td>
                            <div class="fw-bold" style="font-size:.8rem;">{{ $order->customer_name }}</div>
                            <div style="font-size:.72rem;color:#64748b;">{{ $order->customer_phone }}</div>
                        </td>
                        <td style="font-size:.78rem;color:#334155;text-transform:capitalize;">{{ strtolower($order->city ?? '—') }}</td>
                        <td>
                            @foreach(array_slice($order->items ?? [], 0, 2) as $item)
                            <div style="font-size:.72rem;color:#334155;">{{ $item['name'] ?? '' }} <span style="color:#94a3b8;">×{{ $item['qty'] ?? 1 }}</span></div>
                            @endforeach
                            @if(count($order->items ?? []) > 2)
                            <div style="font-size:.68rem;color:#94a3b8;">+{{ count($order->items) - 2 }} lainnya</div>
                            @endif
                        </td>
                        <td style="white-space:nowrap;font-weight:800;">Rp{{ number_format($order->total_amount) }}</td>
                        <td style="font-size:.75rem;color:#334155;">
                            {{ $order->payment_method }}<br>
                            @if($order->payment_proof_url)
                            <a href="{{ $order->payment_proof_url }}" target="_blank" style="font-size:.68rem;color:#3b82f6;">Lihat Bukti</a>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.crm.orders.status', $order) }}" class="crm-status-form d-flex align-items-center gap-1">
                                @csrf @method('PATCH')
                                <select name="status" onchange="this.form.submit()">
                                    @foreach(['pending','confirmed','processing','shipped','done','cancelled'] as $s)
                                    <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <span class="status-badge status-{{ $order->status }} mt-1 d-inline-block">{{ $order->status }}</span>
                        </td>
                        <td style="text-align:center;">
                            @if($order->wa_sent_at)
                            <i class="bi bi-check2-circle" style="color:#22c55e;font-size:1rem;" title="WA dikirim {{ \Carbon\Carbon::parse($order->wa_sent_at)->format('d M H:i') }}"></i>
                            @else
                            <i class="bi bi-circle" style="color:#cbd5e1;font-size:.9rem;" title="Belum WA"></i>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:2rem;color:#94a3b8;font-size:.85rem;">
                            Tidak ada order
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($orders->hasPages())
    <div class="mt-3 d-flex justify-content-center">
        {{ $orders->links() }}
    </div>
    @endif

</div>

@push('scripts')
<script>
function confirmSeed(e) {
    e.preventDefault();
    const n = prompt('Berapa order yang ingin dibuat? (10–200)', '60');
    if (n === null) return;
    const count = parseInt(n);
    if (isNaN(count) || count < 10 || count > 200) { alert('Angka harus antara 10–200.'); return; }
    if (!confirm(`Buat ${count} order demo? Data akan ditambahkan ke yang sudah ada.`)) return;
    document.getElementById('seedOrderCount').value = count;
    document.getElementById('seedForm').submit();
}

(function () {
    const POLL_MS      = 10000;
    const liveUrl      = '{{ route('admin.crm.orders.live') }}';
    let lastOrderId    = {{ $latestOrderId }};
    let pollTimer      = null;

    function fmtRupiah(n) {
        return 'Rp' + Number(n).toLocaleString('id-ID');
    }

    function updateStats(stats) {
        document.getElementById('stat-pending').textContent = stats.pending;
        document.getElementById('stat-today').textContent   = stats.today_count;
        document.getElementById('stat-revenue').textContent = fmtRupiah(stats.today_revenue);
        document.getElementById('stat-aging').textContent   = stats.aging;

        // Aging card color
        const agingCard = document.getElementById('stat-aging');
        agingCard.style.color = stats.aging > 0 ? '#c2410c' : '#94a3b8';

        // Aging alert banner
        const agingAlert = document.getElementById('aging-alert');
        const agingText  = document.getElementById('aging-alert-text');
        if (stats.aging > 0) {
            agingText.innerHTML = `<strong>${stats.aging} order pending</strong> sudah lebih dari 24 jam belum diproses — ditandai border oranye.`;
            agingAlert.style.display = '';
        } else {
            agingAlert.style.display = 'none';
        }

        // Status tab counts
        document.querySelectorAll('.status-tab-count').forEach(el => {
            const s = el.dataset.for;
            if (stats.status_counts[s] !== undefined) {
                el.textContent = '(' + stats.status_counts[s] + ')';
            }
        });
    }

    function checkNewOrders(latestOrderId, latestOrders) {
        if (latestOrderId > lastOrderId) {
            const newCount = latestOrders.filter(o => o.id > lastOrderId).length;
            const banner   = document.getElementById('new-order-banner');
            const text     = document.getElementById('new-order-text');
            text.textContent = newCount + ' order baru masuk!';
            banner.style.display = '';

            // Flash the first matching row if visible on page
            latestOrders.forEach(o => {
                if (o.id > lastOrderId) {
                    const row = document.querySelector(`tr[data-order-id="${o.id}"]`);
                    if (row) row.classList.add('flash-new');
                }
            });

            lastOrderId = latestOrderId;
        }
    }

    async function poll() {
        try {
            const res  = await fetch(liveUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();

            updateStats(data.stats);
            checkNewOrders(data.latest_order_id, data.latest_orders);
            document.getElementById('last-updated').textContent = 'diperbarui ' + data.fetched_at;
        } catch (e) {
            console.warn('[orders-live] poll error:', e);
        }
    }

    function start() {
        if (pollTimer) return;
        poll();
        pollTimer = setInterval(poll, POLL_MS);
        document.getElementById('live-dot')?.classList.remove('paused');
    }

    function stop() {
        clearInterval(pollTimer);
        pollTimer = null;
        document.getElementById('live-dot')?.classList.add('paused');
        document.getElementById('last-updated').textContent = 'polling dijeda';
    }

    document.addEventListener('visibilitychange', () => document.hidden ? stop() : start());
    start();
})();
</script>
@endpush
@endsection
