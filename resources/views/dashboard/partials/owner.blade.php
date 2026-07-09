@php
    use Illuminate\Support\Facades\Route;
    $r = fn (string $name, array $p = []) => Route::has($name) ? route($name, $p) : null;
    $qty = fn ($n) => number_format((float) $n, 0, ',', '.');
@endphp

{{-- ================= PENJUALAN ================= --}}
<div class="dash-sec"><i class="bi bi-graph-up-arrow"></i> Penjualan</div>
<div class="dash-grid">
    @include('dashboard.partials._kpi', [
        'label' => 'Penjualan hari ini', 'icon' => 'bi-cart-check', 'color' => 'green',
        'value' => rupiah($d['sales_today_amount']),
        'sub' => $d['sales_today_count'] . ' pesanan masuk hari ini',
        'url' => $r('marketplace.orders'), 'cta' => 'Lihat pesanan',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Penjualan 7 hari', 'icon' => 'bi-calendar-week', 'color' => 'blue',
        'value' => rupiah($d['sales_7_amount']),
        'sub' => $d['sales_7_count'] . ' pesanan dalam sepekan',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Perlu diproses', 'icon' => 'bi-hourglass-split', 'color' => $d['orders_todo'] > 0 ? 'amber' : '',
        'value' => number_format($d['orders_todo'], 0, ',', '.'),
        'sub' => 'Pesanan belum dikirim',
        'url' => $r('marketplace.orders'), 'cta' => 'Proses sekarang',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Sudah dikirim (7 hari)', 'icon' => 'bi-truck', 'color' => '',
        'value' => number_format($d['orders_shipped_7'], 0, ',', '.'),
        'sub' => 'Paket keluar sepekan',
    ])
</div>

{{-- ================= BARANG & PRODUKSI ================= --}}
<div class="dash-sec"><i class="bi bi-box-seam"></i> Barang & Produksi</div>
<div class="dash-grid">
    @include('dashboard.partials._kpi', [
        'label' => 'Barang jadi siap', 'icon' => 'bi-bag-check', 'color' => 'green',
        'value' => $qty($d['fg_ready']) . ' pcs', 'small' => true,
        'sub' => 'Stok siap jual di gudang',
        'url' => $r('inventory.stocks.items'), 'cta' => 'Lihat stok',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Sedang diproduksi', 'icon' => 'bi-gear-wide-connected', 'color' => 'violet',
        'value' => $qty($d['wip_total']) . ' pcs', 'small' => true,
        'sub' => 'Cutting + jahit + finishing + packing',
        'url' => $r('production.dashboard'), 'cta' => 'Pantau produksi',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Barang jadi habis', 'icon' => 'bi-exclamation-octagon', 'color' => $d['stock_out_fg'] > 0 ? 'red' : '',
        'value' => number_format($d['stock_out_fg'], 0, ',', '.'),
        'sub' => 'Model yang stoknya 0',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Bahan baku habis', 'icon' => 'bi-droplet-half', 'color' => $d['stock_out_rm'] > 0 ? 'amber' : '',
        'value' => number_format($d['stock_out_rm'], 0, ',', '.'),
        'sub' => 'Bahan yang perlu dibeli',
    ])
</div>

{{-- ================= PEMBELIAN & MASALAH ================= --}}
<div class="dash-sec"><i class="bi bi-wallet2"></i> Pembelian & Perlu Perhatian</div>
<div class="dash-grid">
    @include('dashboard.partials._kpi', [
        'label' => 'PO belum diterima', 'icon' => 'bi-box-arrow-in-down', 'color' => $d['po_unreceived'] > 0 ? 'amber' : '',
        'value' => number_format($d['po_unreceived'], 0, ',', '.'),
        'sub' => 'Barang beli belum datang',
        'url' => $r('purchasing.purchase_orders.index'), 'cta' => 'Cek PO',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'PO belum dibayar', 'icon' => 'bi-cash-stack', 'color' => $d['po_unpaid'] > 0 ? 'amber' : '',
        'value' => number_format($d['po_unpaid'], 0, ',', '.'),
        'sub' => 'Tagihan supplier',
        'url' => $r('purchasing.purchase_orders.index'), 'cta' => 'Cek tagihan',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Barang reject', 'icon' => 'bi-x-octagon', 'color' => $d['reject_total'] > 0 ? 'red' : '',
        'value' => $qty($d['reject_total']) . ' pcs', 'small' => true,
        'sub' => 'Total barang reject/cacat',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Atur akses user', 'icon' => 'bi-shield-lock', 'color' => 'violet',
        'value' => 'Kelola', 'small' => true,
        'sub' => 'Sidebar & izin per user',
        'url' => $r('owner.access-control.index'), 'cta' => 'Buka pengaturan',
    ])
</div>

{{-- ================= DAFTAR ================= --}}
<div class="dash-panels">
    @include('dashboard.partials._list_orders', ['title' => 'Pesanan perlu diproses', 'rows' => $d['list_todo'], 'link' => $r('marketplace.orders')])
    @include('dashboard.partials._list_stock', ['title' => 'Stok kritis (perlu segera)', 'rows' => $d['list_stock'], 'link' => $r('inventory.stocks.items')])
</div>
