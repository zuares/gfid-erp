@extends('storefront.layouts.checkout')

@section('title', 'Pesanan Diterima — Greatfit')

@push('styles')
<style>
    :root { --green: #16a34a; }
    body { background: var(--soft); }
    .wrap { width: min(640px, calc(100% - 32px)); margin: 0 auto; }

    .success-hero { padding: 40px 0 28px; text-align: center; }
    .check-icon { width: 64px; height: 64px; border-radius: 50%; background: var(--green); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; }
    .hero-title { font-size: 22px; font-weight: 900; letter-spacing: -.02em; margin-bottom: 6px; }
    .hero-sub { font-size: 13px; color: var(--mid); font-weight: 600; line-height: 1.55; max-width: 320px; margin: 0 auto; }
    .order-number { display: inline-block; margin-top: 12px; font-size: 11px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--mid); background: var(--white); border: 1px solid var(--line); border-radius: 999px; padding: 5px 14px; }

    .card { background: var(--white); border-radius: 18px; border: 1px solid var(--line); margin-bottom: 10px; overflow: hidden; }
    .card-head { padding: 14px 16px 10px; font-size: 10px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: var(--mid); border-bottom: 1px solid var(--line); }
    .card-body { padding: 14px 16px; }

    .item-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line); }
    .item-row:last-child { border-bottom: 0; padding-bottom: 0; }
    .item-img { width: 52px; height: 52px; border-radius: 10px; overflow: hidden; flex-shrink: 0; background: var(--soft); }
    .item-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .item-info { flex: 1; min-width: 0; }
    .item-name { font-size: 13px; font-weight: 700; line-height: 1.3; }
    .item-meta { font-size: 11px; color: var(--mid); font-weight: 600; margin-top: 2px; }
    .item-price { font-size: 13px; font-weight: 800; flex-shrink: 0; }

    .sum-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; font-size: 13px; font-weight: 600; border-bottom: 1px solid var(--line); }
    .sum-row:last-child { border-bottom: 0; padding-bottom: 0; }
    .sum-row.total { font-weight: 900; font-size: 15px; }
    .sum-label { color: var(--mid); }
    .sum-row.total .sum-label { color: var(--ink); }

    .addr-row { display: flex; gap: 10px; align-items: flex-start; padding: 6px 0; font-size: 13px; }
    .addr-label { font-size: 11px; font-weight: 800; color: var(--mid); width: 80px; flex-shrink: 0; padding-top: 1px; }
    .addr-val { font-weight: 600; line-height: 1.5; }

    .wa-section { padding: 20px 0 calc(20px + var(--safe)); }
    .wa-btn { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; height: 54px; border-radius: 16px; background: #25d366; color: #fff; font-size: 15px; font-weight: 900; border: none; cursor: pointer; font-family: inherit; transition: opacity .15s; text-decoration: none; }
    .wa-btn:hover { opacity: .9; }
    .wa-hint { text-align: center; font-size: 11px; color: var(--mid); font-weight: 600; margin-top: 10px; line-height: 1.6; }
    .back-link { display: block; text-align: center; margin-top: 16px; font-size: 12px; font-weight: 700; color: var(--mid); }
    .back-link:hover { color: var(--ink); }

    @@media (min-width: 720px) {
        .success-hero { padding: 56px 0 36px; }
        .check-icon { width: 72px; height: 72px; }
        .hero-title { font-size: 26px; }
        .card { border-radius: 20px; }
        .card-head { padding: 16px 20px 12px; }
        .card-body { padding: 16px 20px; }
    }
</style>
@endpush

@section('content')
<div class="wrap">
    <div class="success-hero">
        <div class="check-icon">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="hero-title">Pesanan Diterima!</div>
        <div class="hero-sub">Konfirmasi pesanan via WhatsApp agar segera diproses.</div>
        <div class="order-number">{{ $order->order_number }}</div>
    </div>

    <div class="card">
        <div class="card-head">Produk yang Dipesan</div>
        <div class="card-body">
            @foreach($order->items as $item)
            <div class="item-row">
                <div class="item-img">
                    <img src="{{ storefront_img($item['img']) }}" alt="{{ $item['name'] }}" loading="lazy">
                </div>
                <div class="item-info">
                    <div class="item-name">{{ $item['name'] }}</div>
                    <div class="item-meta">{{ $item['color'] }} · {{ $item['size'] }} · {{ $item['qty'] }}x</div>
                </div>
                <div class="item-price">Rp{{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}</div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-head">Ringkasan Biaya</div>
        <div class="card-body">
            <div class="sum-row">
                <span class="sum-label">Subtotal</span>
                <span>Rp{{ number_format($order->subtotal, 0, ',', '.') }}</span>
            </div>
            <div class="sum-row">
                <span class="sum-label">
                    Ongkir
                    @if($order->shipping_courier)
                        ({{ strtoupper($order->shipping_courier) }}
                        @if($order->shipping_service) — {{ $order->shipping_service }}@endif)
                    @endif
                </span>
                <span>{{ $order->shipping_cost > 0 ? 'Rp'.number_format($order->shipping_cost, 0, ',', '.') : 'Gratis' }}</span>
            </div>
            @if(($order->unique_code ?? 0) > 0)
            <div class="sum-row">
                <span class="sum-label">Kode unik</span>
                <span>Rp{{ number_format($order->unique_code, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="sum-row total">
                <span class="sum-label">Total</span>
                <span>Rp{{ number_format($order->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    @if($order->payment_method)
    <div class="card">
        <div class="card-head">Pembayaran</div>
        <div class="card-body">
            <div class="addr-row">
                <span class="addr-label">Metode</span>
                <span class="addr-val">{{ $order->payment_method }}</span>
            </div>
            @if($order->payment_proof_url)
            <div class="addr-row">
                <span class="addr-label">Bukti</span>
                <span class="addr-val"><a href="{{ $order->payment_proof_url }}" target="_blank" style="color:var(--green);font-weight:700;">Lihat bukti bayar →</a></span>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-head">Alamat Pengiriman</div>
        <div class="card-body">
            <div class="addr-row">
                <span class="addr-label">Penerima</span>
                <span class="addr-val">{{ $order->customer_name }} · {{ $order->customer_phone }}</span>
            </div>
            <div class="addr-row">
                <span class="addr-label">Wilayah</span>
                <span class="addr-val">{{ implode(', ', array_filter([$order->village, $order->district, $order->city, $order->province])) }}</span>
            </div>
            <div class="addr-row">
                <span class="addr-label">Alamat</span>
                <span class="addr-val">{{ $order->address_detail }}{{ $order->postal_code ? ', '.$order->postal_code : '' }}</span>
            </div>
            @if($order->address_note)
            <div class="addr-row">
                <span class="addr-label">Catatan</span>
                <span class="addr-val">{{ $order->address_note }}</span>
            </div>
            @endif
        </div>
    </div>

    <div class="wa-section">
        <a href="#" class="wa-btn" id="wa-btn">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Chat WhatsApp untuk Konfirmasi
        </a>
        <div class="wa-hint">
            Klik tombol di atas untuk mengirim detail pesanan ke WhatsApp kami.<br>
            Pesanan diproses setelah konfirmasi diterima.
        </div>
        <a href="{{ route('storefront.products') }}" class="back-link">← Lanjut Belanja</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var WA_NUMBER  = '{{ storefront_setting('branding.whatsapp_number', '6281224889319') }}';
    var ORDER_NUM  = '{{ $order->order_number }}';
    var WA_MSG     = @json($waMessage);
    var MARK_URL   = '{{ route('storefront.order.wa_click', $order->order_number) }}';
    var CSRF       = '{{ csrf_token() }}';

    function buildFallbackMsg() {
        var lines = [];
        lines.push('*Konfirmasi Pesanan Greatfit*');
        lines.push('No. Pesanan: ' + ORDER_NUM);
        lines.push('');
        lines.push('*Produk:*');
        @foreach($order->items as $item)
        lines.push('- {{ $item['name'] }} ({{ $item['color'] }}, {{ $item['size'] }}) x{{ $item['qty'] }} = Rp{{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}');
        @endforeach
        lines.push('');
        lines.push('Subtotal: Rp{{ number_format($order->subtotal, 0, ',', '.') }}');
        @if($order->shipping_cost > 0)
        lines.push('Ongkir: Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}');
        @endif
        @if(($order->unique_code ?? 0) > 0)
        lines.push('Kode unik: Rp{{ number_format($order->unique_code, 0, ',', '.') }}');
        @endif
        lines.push('*Total: Rp{{ number_format($order->total_amount, 0, ',', '.') }}*');
        lines.push('');
        lines.push('*Kirim ke:*');
        lines.push('{{ $order->customer_name }} ({{ $order->customer_phone }})');
        lines.push('{{ implode(', ', array_filter([$order->address_detail, $order->village, $order->district, $order->city, $order->province])) }}');
        @if($order->payment_method)
        lines.push('');
        lines.push('Pembayaran: {{ $order->payment_method }}');
        @endif
        return lines.join('\n');
    }

    document.getElementById('wa-btn').addEventListener('click', function (e) {
        e.preventDefault();
        var msg = WA_MSG || buildFallbackMsg();
        fetch(MARK_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' }, keepalive: true });
        window.open('https://wa.me/' + WA_NUMBER + '?text=' + encodeURIComponent(msg), '_blank');
    });
})();
</script>
@endpush
