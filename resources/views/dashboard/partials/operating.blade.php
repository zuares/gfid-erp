@php
    use Illuminate\Support\Facades\Route;
    $r = fn (string $name, array $p = []) => Route::has($name) ? route($name, $p) : null;
    $qty = fn ($n) => number_format((float) $n, 0, ',', '.');
@endphp

{{-- ================= ALUR PRODUKSI ================= --}}
<div class="dash-sec"><i class="bi bi-diagram-3"></i> Barang di Setiap Tahap (pcs)</div>
<div class="dash-grid">
    @include('dashboard.partials._kpi', [
        'label' => 'Sedang dipotong', 'icon' => 'bi-scissors', 'color' => 'blue',
        'value' => $qty($d['wip_cut']), 'small' => true, 'sub' => 'Tahap cutting',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Sedang dijahit', 'icon' => 'bi-person-workspace', 'color' => 'violet',
        'value' => $qty($d['wip_sew']), 'small' => true, 'sub' => 'Di tangan penjahit',
        'url' => $r('production.dashboard'), 'cta' => 'Pantau jahit',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Sedang finishing', 'icon' => 'bi-magic', 'color' => 'amber',
        'value' => $qty($d['wip_fin']), 'small' => true, 'sub' => 'Tahap finishing',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Sedang packing', 'icon' => 'bi-box', 'color' => 'green',
        'value' => $qty($d['wip_pack']), 'small' => true, 'sub' => 'Siap dibungkus',
    ])
</div>

{{-- ================= HASIL & MASALAH ================= --}}
<div class="dash-sec"><i class="bi bi-clipboard-check"></i> Hasil & Perlu Perhatian</div>
<div class="dash-grid">
    @include('dashboard.partials._kpi', [
        'label' => 'Barang jadi siap', 'icon' => 'bi-bag-check', 'color' => 'green',
        'value' => $qty($d['fg_ready']) . ' pcs', 'small' => true,
        'sub' => 'Sudah selesai, siap kirim',
        'url' => $r('inventory.stocks.items'), 'cta' => 'Lihat stok',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Barang reject', 'icon' => 'bi-x-octagon', 'color' => $d['reject_total'] > 0 ? 'red' : '',
        'value' => $qty($d['reject_total']) . ' pcs', 'small' => true,
        'sub' => 'Cacat / perlu perbaikan',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Bahan baku habis', 'icon' => 'bi-droplet-half', 'color' => $d['rm_low'] > 0 ? 'amber' : '',
        'value' => number_format($d['rm_low'], 0, ',', '.'),
        'sub' => 'Bahan yang stoknya 0',
    ])
    @include('dashboard.partials._kpi', [
        'label' => 'Permintaan stok', 'icon' => 'bi-cart-check', 'color' => $d['stock_req_open'] > 0 ? 'amber' : '',
        'value' => number_format($d['stock_req_open'], 0, ',', '.'),
        'sub' => 'Menunggu diproses',
        'url' => $r('rts.stock-requests.index'), 'cta' => 'Buka permintaan',
    ])
</div>

{{-- ================= AKSI CEPAT ================= --}}
<div class="dash-sec"><i class="bi bi-lightning-charge"></i> Aksi Cepat</div>
<div class="dash-actions">
    @if($u = $r('production.dashboard'))
        <a class="act" href="{{ $u }}"><span class="ico violet"><i class="bi bi-columns-gap"></i></span><span class="t">Dashboard Produksi<small>Prioritas & alur kerja</small></span></a>
    @endif
    @if($u = $r('inventory.stocks.items'))
        <a class="act" href="{{ $u }}"><span class="ico blue"><i class="bi bi-box-seam"></i></span><span class="t">Cek Stok<small>Semua gudang</small></span></a>
    @endif
    @if($u = $r('inventory.stock_opnames.index'))
        <a class="act" href="{{ $u }}"><span class="ico amber"><i class="bi bi-clipboard-data"></i></span><span class="t">Stok Opname<small>Hitung ulang stok</small></span></a>
    @endif
    @if($u = $r('rts.stock-requests.index'))
        <a class="act" href="{{ $u }}"><span class="ico green"><i class="bi bi-cart-check"></i></span><span class="t">Permintaan Stok<small>Minta barang ke gudang</small></span></a>
    @endif
</div>

{{-- ================= DAFTAR ================= --}}
<div class="dash-panels">
    @include('dashboard.partials._list_stock', ['title' => 'Sedang dijahit (terbanyak)', 'rows' => $d['list_sew'], 'link' => $r('production.dashboard')])
    @include('dashboard.partials._list_stock', ['title' => 'Bahan baku menipis', 'rows' => $d['list_rm'], 'link' => $r('inventory.stocks.items')])
</div>
