@php
    use Illuminate\Support\Facades\Route;
    $r = fn (string $name, array $p = []) => Route::has($name) ? route($name, $p) : null;
    $qty = fn ($n) => number_format((float) $n, 0, ',', '.');
@endphp

{{-- ================= PESANAN HARI INI ================= --}}
<div class="dash-sec"><i class="bi bi-bag-check"></i> Pesanan & Penjualan</div>
<div class="dash-grid">
    @include('dashboard.partials._kpi', [
        'label' => 'Pesanan masuk hari ini', 'icon' => 'bi-cart-plus', 'color' => 'blue',
        'value' => number_format($d['orders_today'], 0, ',', '.'),
        'sub' => 'Order marketplace hari ini',
        'url' => $r('marketplace.orders'), 'cta' => 'Lihat pesanan',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Perlu diproses', 'icon' => 'bi-hourglass-split', 'color' => $d['orders_processed'] > 0 ? 'amber' : 'green',
        'value' => number_format($d['orders_processed'], 0, ',', '.'),
        'sub' => 'Pesanan belum dipacking',
        'url' => $r('marketplace.orders'), 'cta' => 'Proses',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Siap kirim', 'icon' => 'bi-box-seam', 'color' => $d['orders_ready'] > 0 ? 'amber' : '',
        'value' => number_format($d['orders_ready'], 0, ',', '.'),
        'sub' => 'Sudah dipacking, tunggu kurir',
        'url' => $r('marketplace.orders'), 'cta' => 'Siapkan kirim',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Dikirim (7 hari)', 'icon' => 'bi-truck', 'color' => '',
        'value' => number_format($d['orders_shipped_7'], 0, ',', '.'),
        'sub' => 'Paket keluar sepekan',
    ])
</div>

{{-- ================= PERLU PERHATIAN ================= --}}
<div class="dash-sec"><i class="bi bi-exclamation-triangle"></i> Perlu Perhatian</div>
<div class="dash-grid">
    @include('dashboard.partials._kpi', [
        'label' => 'Data perlu diperbaiki', 'icon' => 'bi-tools', 'color' => $d['orders_issue'] > 0 ? 'red' : '',
        'value' => number_format($d['orders_issue'], 0, ',', '.'),
        'sub' => 'Pesanan tanpa resi/mapping',
        'url' => $r('marketplace.issues') ?? $r('marketplace.orders'), 'cta' => 'Perbaiki',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Barang jadi habis', 'icon' => 'bi-exclamation-octagon', 'color' => $d['stock_out_fg'] > 0 ? 'red' : '',
        'value' => number_format($d['stock_out_fg'], 0, ',', '.'),
        'sub' => 'Model kosong, bisa ganggu jualan',
        'url' => $r('inventory.stocks.items'), 'cta' => 'Cek stok',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Barang jadi siap', 'icon' => 'bi-bag-check', 'color' => 'green',
        'value' => $qty($d['fg_ready']) . ' pcs', 'small' => true,
        'sub' => 'Stok siap jual',
        'url' => $r('inventory.stocks.items'), 'cta' => 'Lihat stok',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'PO belum diterima', 'icon' => 'bi-box-arrow-in-down', 'color' => $d['po_unreceived'] > 0 ? 'amber' : '',
        'value' => number_format($d['po_unreceived'], 0, ',', '.'),
        'sub' => 'Barang beli belum datang',
        'url' => $r('purchasing.purchase_orders.index'), 'cta' => 'Cek PO',
    ])
</div>

{{-- ================= AKSI CEPAT ================= --}}
<div class="dash-sec"><i class="bi bi-lightning-charge"></i> Aksi Cepat</div>
<div class="dash-actions">
    @if($u = $r('inventory.warehouse_intelligence.index'))
        <a class="act" href="{{ $u }}"><span class="ico indigo" style="color: #4f46e5; background: #e0e7ff;"><i class="bi bi-cpu"></i></span><span class="t">Kebutuhan WH-RTS<small>Rekomendasi stok RTS</small></span></a>
    @endif

    @if($u = $r('marketplace.orders'))
        <a class="act" href="{{ $u }}"><span class="ico blue"><i class="bi bi-card-list"></i></span><span class="t">Order Marketplace<small>Kelola semua pesanan</small></span></a>
    @endif
    @if($u = $r('marketplace.sync'))
        <a class="act" href="{{ $u }}"><span class="ico green"><i class="bi bi-arrow-repeat"></i></span><span class="t">Sinkron Order<small>Tarik pesanan terbaru</small></span></a>
    @endif
    @if($u = $r('inventory.stocks.items'))
        <a class="act" href="{{ $u }}"><span class="ico amber"><i class="bi bi-box-seam"></i></span><span class="t">Cek Stok<small>Stok barang jadi</small></span></a>
    @endif
    @if($u = $r('purchasing.purchase_orders.index'))
        <a class="act" href="{{ $u }}"><span class="ico slate"><i class="bi bi-cart3"></i></span><span class="t">Pembelian<small>PO & penerimaan</small></span></a>
    @endif
</div>

{{-- ================= DAFTAR ================= --}}
<div class="dash-panels">
    @include('dashboard.partials._list_orders', ['title' => 'Pesanan belum dikirim', 'rows' => $d['list_todo'], 'link' => $r('marketplace.orders'), 'showAmount' => false])
    @include('dashboard.partials._list_stock', ['title' => 'Barang jadi menipis', 'rows' => $d['list_stock'], 'link' => $r('inventory.stocks.items')])
</div>
