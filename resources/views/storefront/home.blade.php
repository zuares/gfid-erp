<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Greatfit Store</title>
    <style>
        :root {
            --ink: #111827;
            --muted: #64748b;
            --line: #e5e7eb;
            --soft: #f8fafc;
            --brand: #0f172a;
            --accent: #16a34a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background: #fff;
        }
        a { color: inherit; text-decoration: none; }
        .wrap { width: min(1120px, calc(100% - 32px)); margin: 0 auto; }
        .nav {
            position: sticky; top: 0; z-index: 10;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--line);
        }
        .nav-inner {
            min-height: 64px;
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
        }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 950; letter-spacing: .02em; }
        .brand-mark {
            width: 36px; height: 36px; border-radius: 10px;
            background: var(--brand); color: #fff;
            display: grid; place-items: center;
            font-size: 13px; font-weight: 950;
        }
        .nav-links { display: flex; align-items: center; gap: 18px; color: var(--muted); font-size: 14px; font-weight: 750; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-height: 42px; padding: 0 16px; border-radius: 999px;
            border: 1px solid var(--line); background: #fff; font-weight: 850;
        }
        .btn-dark { background: var(--brand); border-color: var(--brand); color: #fff; }
        .hero {
            padding: 42px 0 28px;
            background:
                linear-gradient(180deg, #f8fafc 0%, #fff 78%);
        }
        .hero-grid {
            display: grid; grid-template-columns: 1.05fr .95fr; gap: 28px; align-items: center;
        }
        .eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 7px 11px; border-radius: 999px;
            background: #ecfdf5; color: #166534;
            font-size: 12px; font-weight: 900;
            text-transform: uppercase; letter-spacing: .08em;
        }
        h1 {
            margin: 16px 0 12px;
            font-size: clamp(34px, 5vw, 62px);
            line-height: .98;
            letter-spacing: -.03em;
        }
        .lead {
            margin: 0;
            max-width: 620px;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.65;
        }
        .hero-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 22px; }
        .hero-card {
            min-height: 430px;
            border-radius: 22px;
            background:
                linear-gradient(135deg, rgba(15,23,42,.04), rgba(22,163,74,.08)),
                #f1f5f9;
            border: 1px solid rgba(15,23,42,.08);
            padding: 18px;
            display: grid;
            align-content: end;
            overflow: hidden;
            position: relative;
        }
        .hero-card::before {
            content: "";
            position: absolute; inset: 28px 28px auto auto;
            width: 210px; height: 210px; border-radius: 999px;
            background: rgba(15,23,42,.08);
        }
        .mock-product {
            position: relative;
            border-radius: 18px;
            background: #fff;
            border: 1px solid rgba(15,23,42,.08);
            box-shadow: 0 22px 60px rgba(15,23,42,.13);
            padding: 18px;
        }
        .mock-img {
            aspect-ratio: 4 / 3;
            border-radius: 14px;
            background:
                linear-gradient(160deg, #111827 0 45%, #334155 45% 100%);
            display: grid; place-items: center;
            color: #fff; font-size: 42px; font-weight: 950;
        }
        .mock-row { display: flex; justify-content: space-between; gap: 12px; margin-top: 14px; align-items: center; }
        .mock-title { font-weight: 950; }
        .mock-price { font-weight: 950; color: var(--accent); }
        .section { padding: 32px 0; }
        .section-head { display: flex; justify-content: space-between; gap: 16px; align-items: end; margin-bottom: 16px; }
        .section-title { margin: 0; font-size: 26px; letter-spacing: -.02em; }
        .section-sub { margin: 4px 0 0; color: var(--muted); }
        .grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
        .product {
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
        }
        .product-img {
            aspect-ratio: 1 / 1;
            background: #f1f5f9;
            display: grid; place-items: center;
            color: #0f172a; font-size: 28px; font-weight: 950;
        }
        .product-body { padding: 12px; }
        .product-name { font-weight: 900; line-height: 1.25; }
        .product-meta { margin-top: 4px; color: var(--muted); font-size: 13px; }
        .product-bottom { display: flex; justify-content: space-between; gap: 10px; align-items: center; margin-top: 10px; }
        .price { font-weight: 950; }
        .pill {
            padding: 5px 9px; border-radius: 999px;
            background: #ecfdf5; color: #166534;
            font-size: 12px; font-weight: 900;
        }
        .features {
            display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px;
        }
        .feature {
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
            background: var(--soft);
        }
        .feature b { display: block; margin-bottom: 6px; }
        .feature p { margin: 0; color: var(--muted); line-height: 1.55; font-size: 14px; }
        .footer { border-top: 1px solid var(--line); padding: 22px 0; color: var(--muted); font-size: 14px; }
        @media (max-width: 860px) {
            .hero-grid { grid-template-columns: 1fr; }
            .hero-card { min-height: 320px; }
            .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .features { grid-template-columns: 1fr; }
            .nav-links a:not(.btn) { display: none; }
        }
        @media (max-width: 520px) {
            .wrap { width: min(100% - 24px, 1120px); }
            .hero { padding-top: 28px; }
            .grid { grid-template-columns: 1fr; }
            .hero-actions .btn { flex: 1 1 100%; }
        }
    </style>
</head>
<body>
    <header class="nav">
        <div class="wrap nav-inner">
            <a href="{{ route('storefront.home') }}" class="brand">
                <span class="brand-mark">GF</span>
                <span>Greatfit Store</span>
            </a>
            <nav class="nav-links">
                <a href="#produk">Produk</a>
                <a href="#keunggulan">Keunggulan</a>
                <a class="btn" href="{{ route('login', [], false) }}">Login Admin</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="wrap hero-grid">
                <div>
                    <span class="eyebrow">Dummy Storefront</span>
                    <h1>Daily wear yang rapi, nyaman, dan siap kirim.</h1>
                    <p class="lead">
                        Website toko sederhana untuk preview katalog Greatfit. Data produk masih dummy,
                        nanti bisa disambungkan ke master item, stok, marketplace, dan checkout.
                    </p>
                    <div class="hero-actions">
                        <a class="btn btn-dark" href="#produk">Lihat Produk</a>
                        <a class="btn" href="https://wa.me/6281200000000">Chat WhatsApp</a>
                    </div>
                </div>
                <div class="hero-card">
                    <div class="mock-product">
                        <div class="mock-img">SJR</div>
                        <div class="mock-row">
                            <div>
                                <div class="mock-title">Jogger Pendek Basic</div>
                                <div class="product-meta">Best seller dummy</div>
                            </div>
                            <div class="mock-price">Rp 89.000</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="produk">
            <div class="wrap">
                <div class="section-head">
                    <div>
                        <h2 class="section-title">Produk Pilihan</h2>
                        <p class="section-sub">Dummy katalog untuk tampilan awal toko.</p>
                    </div>
                    <a class="btn" href="#">Lihat Semua</a>
                </div>
                <div class="grid">
                    @foreach ([
                        ['code' => 'SJR', 'name' => 'Jogger Pendek Basic', 'meta' => '30 warna dummy', 'price' => 'Rp 89.000'],
                        ['code' => 'LJR', 'name' => 'Jogger Panjang Basic', 'meta' => 'Bahan adem', 'price' => 'Rp 119.000'],
                        ['code' => 'CRG', 'name' => 'Jogger Pendek Cargo', 'meta' => 'Kantong cargo', 'price' => 'Rp 99.000'],
                        ['code' => 'LBP', 'name' => 'Baggy Pants', 'meta' => 'Loose fit', 'price' => 'Rp 129.000'],
                    ] as $item)
                        <article class="product">
                            <div class="product-img">{{ $item['code'] }}</div>
                            <div class="product-body">
                                <div class="product-name">{{ $item['name'] }}</div>
                                <div class="product-meta">{{ $item['meta'] }}</div>
                                <div class="product-bottom">
                                    <span class="price">{{ $item['price'] }}</span>
                                    <span class="pill">Ready</span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="section" id="keunggulan">
            <div class="wrap features">
                <div class="feature">
                    <b>Produksi Sendiri</b>
                    <p>Dummy copy untuk menjelaskan produk dibuat dan dikontrol langsung oleh tim Greatfit.</p>
                </div>
                <div class="feature">
                    <b>Stok Terpantau</b>
                    <p>Nanti bisa disambungkan ke stok barang jadi agar katalog lebih akurat.</p>
                </div>
                <div class="feature">
                    <b>Siap Marketplace</b>
                    <p>Struktur ini bisa dikembangkan ke checkout, WhatsApp order, atau sinkron marketplace.</p>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="wrap">
            © {{ now()->year }} Greatfit Store. Dummy storefront.
        </div>
    </footer>
</body>
</html>
