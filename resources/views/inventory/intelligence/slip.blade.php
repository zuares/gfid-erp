@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $groups = $rows->groupBy('category')
        ->map(fn($g, $cat) => (object) [
            'category' => $cat ?: '-',
            'lines' => $g->sortByDesc('suggested_qty')->values(),
            'qty' => (float) $g->sum('suggested_qty'),
        ])
        ->sortByDesc('qty')
        ->values();
    $statusLabel = [
        'stockout' => 'Stockout',
        'kritis' => 'Kritis',
        'menipis' => 'Menipis',
        'sehat' => 'Sehat',
        'no_demand' => 'Tanpa demand',
    ];
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $fileName }}</title>
    <style>
        :root { --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; }
        * { box-sizing: border-box; -webkit-text-size-adjust: 100%; }

        body { margin: 0; background: #f1f5f9; color: var(--ink);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 13.5px; line-height: 1.45; }

        .slip-toolbar { position: sticky; top: 0; z-index: 10; display: flex; flex-wrap: wrap; gap: 8px;
            justify-content: flex-end; align-items: center; padding: 10px 12px; background: rgba(241, 245, 249, .92);
            backdrop-filter: saturate(180%) blur(6px); border-bottom: 1px solid var(--line); }
        .btn { font: inherit; font-weight: 600; padding: .6rem 1rem; border-radius: 999px; border: 1px solid var(--line);
            background: #fff; color: var(--ink); cursor: pointer; text-decoration: none; line-height: 1;
            flex: 1 1 auto; text-align: center; }
        .btn:disabled { opacity: .55; cursor: progress; }
        .btn-dark { background: var(--ink); color: #fff; border-color: var(--ink); }
        .btn-spacer { display: none; }

        .sheet { margin: 12px; background: #fff; padding: 18px 16px;
            border: 1px solid var(--line); border-radius: 10px; }

        .slip-head { display: flex; flex-direction: column; gap: 6px;
            border-bottom: 2px solid var(--ink); padding-bottom: 14px; margin-bottom: 18px; }
        .slip-brand { font-size: 18px; font-weight: 800; letter-spacing: -.01em; }
        .slip-brand small { display: block; font-size: 11px; font-weight: 600; color: var(--muted); letter-spacing: .02em; }
        .slip-title { text-align: left; }
        .slip-title b { font-size: 15px; }
        .slip-title span { display: block; font-size: 11.5px; color: var(--muted); }

        .slip-meta { display: grid; grid-template-columns: 1fr; gap: 2px; margin-bottom: 18px; }
        .slip-meta div { display: flex; justify-content: space-between; gap: 12px; padding: 3px 0; border-bottom: 1px dotted var(--line); }
        .slip-meta dt { color: var(--muted); }
        .slip-meta dd { margin: 0; font-weight: 600; text-align: right; }

        .hero { display: flex; flex-direction: column; gap: 16px;
            padding: 2px 0 16px; margin-bottom: 18px; border-bottom: 2px solid var(--line); }
        .hero-label { font-size: 11px; text-transform: uppercase; letter-spacing: .06em;
            color: var(--muted); font-weight: 700; margin-bottom: 8px; }
        .hero-total { text-align: left; }
        .hero-amount { font-size: 32px; font-weight: 800; letter-spacing: -.02em; margin-top: 2px; color: var(--ink); }
        .hero-sub { font-size: 12px; color: var(--muted); margin-top: 5px; }
        .hero-recap { flex: 1 1 auto; min-width: 0; }
        .recap-list { border: 1px solid var(--line); border-radius: 10px; overflow: hidden; background: #fff; }
        .recap-row { display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 9px 12px; border-bottom: 1px solid var(--line); }
        .recap-row:last-child { border-bottom: 0; }
        .recap-cat { font-weight: 700; color: var(--ink); font-size: 13px; }
        .recap-amt { white-space: nowrap; font-variant-numeric: tabular-nums; font-weight: 800; font-size: 14px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 7px 5px; text-align: left; vertical-align: top; }
        thead th { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted);
            border-bottom: 1px solid var(--line); }
        .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .cat-row td { background: #f8fafc; font-weight: 700; border-top: 1px solid var(--line); padding-top: 9px; }
        .line-row td { border-bottom: 1px solid #f1f5f9; }
        .line-row .prod-name { font-weight: 600; }
        .line-row .sku-mini { display: block; color: var(--muted); font-size: 11px; margin-top: 1px; }
        .saran { font-weight: 800; }
        .st { font-size: 11px; font-weight: 700; }
        .st-stockout, .st-kritis { color: #b91c1c; }
        .st-menipis { color: #b45309; }

        .grand { margin-top: 18px; display: flex; justify-content: stretch; }
        .grand-box { width: 100%; }
        .grand-box .total { border-top: 2px solid var(--ink); margin-top: 4px; padding-top: 8px;
            font-size: 16px; font-weight: 800; display: flex; justify-content: space-between; }

        .sign { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 28px; }
        .sign div { text-align: center; }
        .sign .label { color: var(--muted); margin-bottom: 44px; }
        .sign .name { border-top: 1px solid var(--ink); padding-top: 6px; font-weight: 600; }

        .empty { padding: 40px; text-align: center; color: var(--muted); }
        .foot-note { margin-top: 24px; font-size: 11px; color: var(--muted); }

        @media (min-width: 641px) {
            body { font-size: 13px; }
            .btn { flex: 0 0 auto; padding: .55rem 1rem; }
            .btn-spacer { display: block; flex: 1 1 auto; }
            .sheet { max-width: 760px; margin: 20px auto; padding: 32px 36px; border-radius: 12px; }
            .slip-head { flex-direction: row; justify-content: space-between; align-items: flex-start; gap: 12px; }
            .slip-title { text-align: right; }
            .slip-meta { grid-template-columns: 1fr 1fr; gap: 6px 24px; }
            th, td { padding: 7px 8px; }
            .hero { flex-direction: row; align-items: center; justify-content: space-between; gap: 28px; }
            .hero-recap { max-width: 56%; }
            .hero-total { text-align: right; flex: 0 0 auto; }
            .grand { justify-content: flex-end; }
            .grand-box { width: auto; min-width: 280px; }
            .sign { grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 40px; }
            .sign .label { margin-bottom: 52px; }
        }

        @media print {
            body { background: #fff; }
            .slip-toolbar { display: none; }
            .sheet { margin: 0; border: 0; border-radius: 0; max-width: none; padding: 0; }
            @page { margin: 16mm; }
        }
    </style>
</head>

<body>
    <div class="slip-toolbar">
        <span class="btn-spacer"></span>
        <button type="button" class="btn" id="btnPng" onclick="downloadImage('png')">Unduh PNG</button>
        <button type="button" class="btn" id="btnPdf" onclick="downloadImage('pdf')">Unduh PDF</button>
        <button type="button" class="btn btn-dark" onclick="window.print()">Cetak</button>
    </div>

    <div class="sheet" id="slipSheet">
        <div class="slip-head">
            <div class="slip-brand">
                {{ config('app.name', 'Inventory') }}
                <small>Saran Produksi</small>
            </div>
            <div class="slip-title">
                <b>Draft Saran Produksi</b>
                <span>Target cover {{ 21 }} hari</span>
            </div>
        </div>

        <dl class="slip-meta">
            <div><dt>Kategori</dt><dd>{{ $categoryLabel ?? 'Semua' }}</dd></div>
            <div><dt>SKU</dt><dd>{{ $itemLabel ?? 'Semua' }}</dd></div>
            <div><dt>Jumlah SKU</dt><dd>{{ $fmt($skuCount) }}</dd></div>
            <div><dt>Dicetak</dt><dd>{{ $printedAt->format('d-m-Y H:i') }}</dd></div>
        </dl>

        @if ($rows->isEmpty())
            <div class="empty">Tidak ada saran produksi untuk filter ini.<br>Semua SKU tercukupi (saran 0).</div>
        @else
            <div class="hero">
                @if ($groups->count() > 1)
                    <div class="hero-recap">
                        <div class="hero-label">Per Kategori</div>
                        <div class="recap-list">
                            @foreach ($groups as $g)
                                <div class="recap-row">
                                    <span class="recap-cat">{{ $g->category }}</span>
                                    <span class="recap-amt">{{ $fmt($g->qty) }} pcs</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="hero-total">
                    <div class="hero-label">Total Saran Produksi</div>
                    <div class="hero-amount">{{ $fmt($totalSuggested) }} pcs</div>
                    <div class="hero-sub">{{ $fmt($skuCount) }} SKU perlu diproduksi</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th class="num">Ready</th>
                        <th class="num">WIP</th>
                        <th class="num">Cover</th>
                        <th class="num">F30</th>
                        <th class="num">Saran</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groups as $g)
                        <tr class="cat-row">
                            <td>{{ $g->category }}</td>
                            <td class="num" colspan="4"></td>
                            <td class="num">{{ $fmt($g->qty) }}</td>
                        </tr>
                        @foreach ($g->lines as $l)
                            <tr class="line-row">
                                <td>
                                    <span class="prod-name">{{ $l->product }}</span>
                                    <span class="sku-mini">{{ $l->sku }}
                                        @if (in_array($l->status, ['stockout', 'kritis', 'menipis'], true))
                                            · <span class="st st-{{ $l->status }}">{{ $statusLabel[$l->status] ?? $l->status }}</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="num">{{ $fmt($l->ready) }}</td>
                                <td class="num">{{ $fmt($l->wip) }}</td>
                                <td class="num">{{ $l->cover_days === null ? '–' : $fmt($l->cover_days, 1) }}</td>
                                <td class="num">{{ $fmt($l->forecast_30) }}</td>
                                <td class="num saran">{{ $fmt($l->suggested_qty) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>

            <div class="grand">
                <div class="grand-box">
                    <div class="total"><span>Total Saran</span><span>{{ $fmt($totalSuggested) }} pcs</span></div>
                </div>
            </div>

            <div class="sign">
                <div><div class="label">Disiapkan oleh</div><div class="name">&nbsp;</div></div>
                <div><div class="label">Disetujui oleh</div><div class="name">&nbsp;</div></div>
            </div>

            <div class="foot-note">
                Saran = maks(0, 21 × laju jual − ready − WIP). F30 = laju jual × 30. Angka mengikuti data realtime saat dicetak.
            </div>
        @endif
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        const FILE_NAME = @json($fileName);

        async function captureCanvas() {
            const sheet = document.getElementById('slipSheet');
            return await html2canvas(sheet, {
                scale: 2, backgroundColor: '#ffffff', useCORS: true,
                scrollX: 0, scrollY: -window.scrollY, windowWidth: 820,
            });
        }

        async function downloadImage(kind) {
            const btn = document.getElementById(kind === 'pdf' ? 'btnPdf' : 'btnPng');
            const others = [document.getElementById('btnPng'), document.getElementById('btnPdf')];
            if (typeof html2canvas === 'undefined' || (kind === 'pdf' && !window.jspdf)) {
                alert('Gagal memuat pustaka unduhan. Gunakan tombol Cetak lalu "Simpan sebagai PDF".');
                return;
            }
            const label = btn.textContent;
            others.forEach(b => b.disabled = true);
            btn.textContent = 'Memproses…';
            try {
                const canvas = await captureCanvas();
                if (kind === 'png') {
                    const a = document.createElement('a');
                    a.href = canvas.toDataURL('image/png');
                    a.download = FILE_NAME + '.png';
                    a.click();
                } else {
                    const { jsPDF } = window.jspdf;
                    const pdf = new jsPDF({ unit: 'pt', format: 'a4', compress: true });
                    const margin = 24;
                    const pw = pdf.internal.pageSize.getWidth();
                    const ph = pdf.internal.pageSize.getHeight();
                    const img = canvas.toDataURL('image/png');
                    const iw = pw - margin * 2;
                    const ih = canvas.height * iw / canvas.width;
                    const pageH = ph - margin * 2;
                    let position = margin;
                    let heightLeft = ih;
                    pdf.addImage(img, 'PNG', margin, position, iw, ih);
                    heightLeft -= pageH;
                    while (heightLeft > 0) {
                        position -= pageH;
                        pdf.addPage();
                        pdf.addImage(img, 'PNG', margin, position, iw, ih);
                        heightLeft -= pageH;
                    }
                    pdf.save(FILE_NAME + '.pdf');
                }
            } catch (e) {
                console.error(e);
                alert('Gagal membuat berkas. Coba lagi atau gunakan tombol Cetak.');
            } finally {
                btn.textContent = label;
                others.forEach(b => b.disabled = false);
            }
        }
    </script>
</body>

</html>
