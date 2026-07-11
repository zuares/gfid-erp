<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Resi Darurat - {{ $order->channel_order_id }}</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        @page { margin: 0; size: 100mm 150mm; }
        * { box-sizing: border-box; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
        body { margin: 0; padding: 10px; background: #e2e8f0; display: flex; justify-content: center; }
        
        .awb-wrapper { width: 100mm; min-height: 150mm; background: #fff; position: relative; padding-top: 10px; }
        
        /* Tiny repeating tracking numbers at top/bottom/sides */
        .tiny-track { display: flex; justify-content: space-around; font-size: 8px; font-weight: bold; color: #000; padding: 0 10px; margin-bottom: 1px; letter-spacing: 0.5px; }
        .tiny-track-vertical { writing-mode: vertical-rl; text-orientation: mixed; font-size: 8px; font-weight: bold; color: #000; position: absolute; top: 25px; bottom: 25px; display: flex; justify-content: space-around; letter-spacing: 0.5px; }
        .tiny-left { left: 4px; }
        .tiny-right { right: 4px; }

        .awb-main { border: 3px solid #000; margin: 0 16px; display: flex; flex-direction: column; position: relative; }
        
        /* Row 1: Header */
        .row-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px dotted #000; padding: 4px 8px; height: 35px; }
        .logo-shopee { display: flex; align-items: center; gap: 5px; font-size: 20px; font-weight: 900; letter-spacing: -1px; color: #ea580c; }
        .logo-shopee-icon { background: #ea580c; color: #fff; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; border-radius: 4px; font-size: 16px; margin-right: 2px; }
        .header-center { font-size: 28px; font-weight: 900; letter-spacing: 1px; flex: 1; text-align: center; }
        .logo-spx { font-size: 28px; font-weight: 900; font-style: italic; color: #ea580c; letter-spacing: -1.5px; }

        /* Row 2: Routing & Top Barcode */
        .row-routing { display: flex; border-bottom: 3px dotted #000; height: 95px; }
        .routing-code { width: 33%; border-right: 2px solid #000; display: flex; align-items: center; justify-content: center; font-size: 42px; font-weight: 900; letter-spacing: -1px; }
        .routing-barcode { width: 67%; display: flex; flex-direction: column; padding: 4px; }
        .routing-barcode-top { display: flex; height: 24px; margin-bottom: 2px; }
        .rb-box-left { width: 45%; border: 2px solid #000; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 900; }
        .rb-box-right { width: 55%; border: 2px solid #000; border-left: none; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 900; }
        .routing-barcode-svg { flex: 1; display: flex; align-items: center; justify-content: center; overflow: hidden; padding-top: 2px; }
        .routing-barcode-svg svg { width: 100%; height: 60px; }

        /* Row 3: Addresses */
        .row-address { padding: 6px 8px; font-size: 11px; min-height: 80px; display: flex; flex-direction: column; justify-content: space-between; border-bottom: 3px solid #000; position: relative; overflow: hidden; }
        .address-cols { display: flex; margin-bottom: 5px; z-index: 2; position: relative; }
        .address-col-left { width: 60%; padding-right: 8px; }
        .address-col-right { width: 40%; }
        .address-title { margin-bottom: 3px; font-size: 12px; font-weight: 900; }
        .home-badge { display: inline-block; border: 1.5px solid #000; width: 40px; height: 16px; margin-bottom: 3px; }
        .address-text { line-height: 1.2; text-transform: uppercase; font-size: 10px; }
        
        .city-boxes { display: flex; gap: 4px; margin-top: 5px; z-index: 2; position: relative; }
        .city-box { border: 1.5px solid #000; padding: 4px; font-size: 11px; text-transform: uppercase; text-align: center; flex: 1; font-weight: normal; }

        .watermark-cod { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 120px; font-weight: 900; color: rgba(0,0,0,0.1); z-index: 1; }

        /* Row 4: Order Info & Barcodes */
        .row-info { padding: 6px 8px; font-size: 13px; }
        .info-grid { display: flex; justify-content: space-between; margin-bottom: 2px; }
        .info-grid strong { font-weight: 900; }
        .info-pesanan { font-size: 13px; font-weight: 900; margin-top: 2px; }

        .row-barcodes { display: flex; padding: 4px 8px 8px 8px; align-items: center; justify-content: space-between; }
        .main-barcode { width: 65%; display: flex; justify-content: flex-start; }
        .main-barcode svg { width: 100%; height: 50px; }
        .qr-code { width: 30%; display: flex; justify-content: flex-end; padding-right: 5px; }
        #qrcode img { width: 65px; height: 65px; }

        /* ITEMS TABLE OUTSIDE AWB MAIN */
        .items-section { padding: 5px 16px; font-size: 11px; margin-top: 2px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .items-table th { text-align: left; border-bottom: 3px solid #000; border-top: 3px solid #000; padding: 4px 0; font-weight: 900; }
        .items-table td { padding: 4px 2px; vertical-align: top; }
        .item-name { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.2; }
        .items-message { font-weight: 900; margin-top: 5px; font-size: 12px; }

        .tiny-track-bottom { display: flex; justify-content: space-around; font-size: 8px; font-weight: bold; color: #000; padding: 15px 10px; letter-spacing: 0.5px; }

        .print-btn { position: fixed; bottom: 20px; right: 20px; padding: 12px 24px; background: #2563eb; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.2); z-index: 999; }
        
        @media print {
            body { background: #fff; padding: 0; }
            .awb-wrapper { margin: 0; padding-top: 5px; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>

@php
    $raw = $order->raw_json ?? [];
    $package = $raw['package_list'][0] ?? [];
    $recipient = $raw['recipient_address'] ?? [];
    
    $buyerName = $recipient['name'] ?? $order->buyer_name ?? 'N/A';
    $buyerPhone = $recipient['phone'] ?? $order->buyer_phone ?? 'N/A';
    $buyerAddress = $recipient['full_address'] ?? $order->shipping_address ?? 'N/A';
    
    $city = $recipient['city'] ?? $order->shipping_city ?? '';
    $district = $recipient['district'] ?? '';
    $town = $recipient['town'] ?? '';
    
    $courier = $order->shipping_carrier ?? 'ECO';
    $awbText = $awb !== 'N/A' ? $awb : ($order->shipping_awb_no ?? 'NO-RESI');
    $orderSn = $order->channel_order_id;
    
    $sortingCode = $package['sorting_group'] ?? 'BU - 70.'; // Default to visual match if empty
    if ($sortingCode === '') $sortingCode = 'BU - 70.';
    
    $sortingCodeTop = 'SMJ-B-02'; // Static visual match for routing area code
    
    $weight = ($package['parcel_chargeable_weight_gram'] ?? 350) . ' gr';
    $cod = ($raw['cod'] ?? false) ? 'Ya' : 'Tidak';
    $shipBy = isset($raw['ship_by_date']) ? date('d-m-Y', $raw['ship_by_date']) : date('d-m-Y');
    
    $storePhone = $store->credential('phone', '6283175763512');
    $storeCity = $store->region ?? 'KAB. BANDUNG';
@endphp

<div class="awb-wrapper">
    <!-- Top tiny tracks -->
    <div class="tiny-track">
        <span>{{ $awbText }}</span>
        <span>{{ $awbText }}</span>
        <span>{{ $awbText }}</span>
    </div>
    
    <!-- Left vertical tracks -->
    <div class="tiny-track-vertical tiny-left">
        <span>{{ $awbText }}</span><span>{{ $awbText }}</span>
    </div>
    
    <!-- Right vertical tracks -->
    <div class="tiny-track-vertical tiny-right">
        <span>{{ $awbText }}</span><span>{{ $awbText }}</span>
    </div>

    <div class="awb-main">
        <!-- Row 1: Header -->
        <div class="row-header">
            <div class="logo-shopee">
                <div class="logo-shopee-icon">S</div>
                Shopee
            </div>
            <div class="header-center">{{ strtoupper(explode(' ', $courier)[0]) }}</div>
            <div class="logo-spx">SPX</div>
        </div>

        <!-- Row 2: Routing & Barcode Top -->
        <div class="row-routing">
            <div class="routing-code">
                {{ $sortingCode }}
            </div>
            <div class="routing-barcode">
                <div class="routing-barcode-top">
                    <div class="rb-box-left">{{ $sortingCodeTop }}</div>
                    <div class="rb-box-right">Resi:{{ $awbText }}</div>
                </div>
                <div class="routing-barcode-svg">
                    <svg id="barcode-top"></svg>
                </div>
            </div>
        </div>

        <!-- Row 3: Addresses -->
        <div class="row-address">
            @if($cod === 'Ya')
            <div class="watermark-cod">COD</div>
            @endif
            
            <div class="address-cols">
                <div class="address-col-left">
                    <div class="address-title">Penerima: {{ $buyerName }}</div>
                    <div class="home-badge"></div>
                    <div class="address-text">{{ $buyerAddress }}</div>
                </div>
                <div class="address-col-right">
                    <div class="address-title">Pengirim: {{ $store->name }}</div>
                    <div class="address-text" style="margin-top: 5px;">{{ $storePhone }}<br>{{ strtoupper($storeCity) }}</div>
                </div>
            </div>
            
            <div class="city-boxes">
                <div class="city-box">{{ strtoupper($city) }}</div>
                <div class="city-box">{{ strtoupper($district) }}</div>
                <div class="city-box">{{ strtoupper($town) }}</div>
            </div>
        </div>
        
        <!-- Row 4: Order Info -->
        <div class="row-info">
            <div class="info-grid">
                <span><strong>Berat:</strong> &nbsp;{{ $weight }}</span>
                <span><strong>COD Cek Dulu:</strong> {{ $cod }}</span>
            </div>
            <div class="info-grid">
                <span><strong>Batas Kirim:</strong> {{ $shipBy }}</span>
            </div>
            <div class="info-pesanan">No.Pesanan: {{ $orderSn }}</div>
        </div>

        <!-- Row 5: Main Barcode & QR -->
        <div class="row-barcodes">
            <div class="main-barcode">
                <svg id="barcode-main"></svg>
            </div>
            <div class="qr-code" id="qrcode"></div>
        </div>
    </div>
    
    <!-- OUTSIDE MAIN BORDER: Row 6: Items -->
    <div class="items-section">
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th style="width:50%">Nama Produk</th>
                    <th style="width:20%">SKU</th>
                    <th style="width:20%">Variasi</th>
                    <th style="width:5%; text-align:center;">Qty</th>
                </tr>
            </thead>
            <tbody>
                @php $i = 1; @endphp
                @if($order->items && $order->items->count() > 0)
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $i++ }}</td>
                        <td><div class="item-name">{{ $item->item_name }}</div></td>
                        <td>{{ $item->model_sku ?? $item->item_sku }}</td>
                        <td>{{ $item->variant_name }}</td>
                        <td style="text-align:center;">{{ $item->qty }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr><td colspan="5">Data produk tidak tersedia</td></tr>
                @endif
            </tbody>
        </table>
        
        <div class="items-message">
            Pesan: ({{ $orderSn }}) ({{ $awbText }})
        </div>
        
        <div class="tiny-track-bottom">
            <span>{{ $awbText }}</span>
            <span>{{ $awbText }}</span>
            <span>{{ $awbText }}</span>
        </div>
    </div>
</div>

<button class="print-btn" onclick="window.print()">🖨️ Cetak Resi Darurat</button>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const awb = "{{ $awbText }}";
        const orderSn = "{{ $orderSn }}";

        // Generate Barcodes
        if (awb && awb !== 'NO-RESI') {
            JsBarcode("#barcode-top", awb, {
                format: "CODE128",
                width: 2.2,
                height: 60,
                displayValue: false,
                margin: 0
            });
        }

        if (orderSn) {
            JsBarcode("#barcode-main", orderSn, {
                format: "CODE128",
                width: 2.0,
                height: 55,
                displayValue: false,
                margin: 0
            });
            
            // Generate QR Code
            new QRCode(document.getElementById("qrcode"), {
                text: orderSn,
                width: 65,
                height: 65,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.L
            });
        }
        
        // Auto print after a short delay
        setTimeout(() => {
            window.print();
        }, 800);
    });
</script>

</body>
</html>
