@php
    use Illuminate\Support\Facades\Route;
    $r = fn (string $name, array $p = []) => Route::has($name) ? route($name, $p) : null;
@endphp

<div class="dash-sec"><i class="bi bi-compass"></i> Mulai dari sini</div>
<div class="dash-actions">
    @if($u = $r('inventory.stocks.items'))
        <a class="act" href="{{ $u }}"><span class="ico blue"><i class="bi bi-box-seam"></i></span><span class="t">Stok Barang<small>Lihat persediaan</small></span></a>
    @endif
    @if($u = $r('production.dashboard'))
        <a class="act" href="{{ $u }}"><span class="ico violet"><i class="bi bi-columns-gap"></i></span><span class="t">Produksi<small>Alur kerja produksi</small></span></a>
    @endif
    @if($u = $r('marketplace.orders'))
        <a class="act" href="{{ $u }}"><span class="ico green"><i class="bi bi-card-list"></i></span><span class="t">Pesanan<small>Order marketplace</small></span></a>
    @endif
    @if($u = $r('master.items.index'))
        <a class="act" href="{{ $u }}"><span class="ico slate"><i class="bi bi-collection"></i></span><span class="t">Data Master<small>Item & lainnya</small></span></a>
    @endif
</div>

<div class="dash-empty" style="margin-top:1rem;">
    <i class="bi bi-info-circle"></i>
    Gunakan menu di samping untuk membuka modul yang tersedia untukmu.
</div>
