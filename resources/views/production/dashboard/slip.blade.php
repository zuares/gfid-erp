@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $rp = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $tgl = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : $d;
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

        /* ---------- Base = Mobile-first ---------- */
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

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 7px 5px; text-align: left; vertical-align: top; }
        thead th { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted);
            border-bottom: 1px solid var(--line); }
        .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .cat-row td { background: #f8fafc; font-weight: 700; border-top: 1px solid var(--line); padding-top: 9px; }
        .line-row td { border-bottom: 1px solid #f1f5f9; }
        .line-row .prod-name { font-weight: 600; }
        .line-row .sku-mini { display: block; color: var(--muted); font-size: 11px; margin-top: 1px; }
        .sub-row td { font-size: 12px; color: var(--muted); border-bottom: 1px solid var(--line); padding-bottom: 9px; }
        .sub-row .num { font-weight: 700; color: var(--ink); }

        /* Hero — recap per kategori (kiri, panel) + Total Diterima (kanan, angka hijau).
           Dua focal point: kiri panel rapi bertepi, kanan angka besar hijau. */
        .hero { display: flex; flex-direction: column; gap: 16px;
            padding: 2px 0 16px; margin-bottom: 18px; border-bottom: 2px solid var(--line); }
        .hero-label { font-size: 11px; text-transform: uppercase; letter-spacing: .06em;
            color: var(--muted); font-weight: 700; margin-bottom: 8px; }

        .hero-recap { flex: 1 1 auto; min-width: 0; }
        .recap-list { border: 1px solid var(--line); border-radius: 10px; overflow: hidden; background: #fff; }
        .recap-row { display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 9px 12px; border-bottom: 1px solid var(--line); }
        .recap-row:last-child { border-bottom: 0; }
        .recap-cat { font-weight: 700; color: var(--ink); font-size: 13px; }
        .recap-qty { display: block; font-size: 11px; color: var(--muted); font-weight: 500; margin-top: 1px; }
        .recap-amt { white-space: nowrap; font-variant-numeric: tabular-nums;
            font-weight: 800; font-size: 14px; color: var(--ink); }

        .hero-total { text-align: left; }
        .hero-amount { font-size: 32px; font-weight: 800; letter-spacing: -.02em; margin-top: 2px; color: #16a34a; }
        .hero-sub { font-size: 12px; color: var(--muted); margin-top: 5px; }
        .hero-muted .hero-amount { color: var(--ink); }

        .sec-title { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: var(--muted);
            font-weight: 700; margin: 4px 0 6px; }

        .grand { margin-top: 18px; display: flex; justify-content: stretch; }
        .grand-box { width: 100%; }
        .grand-box .row { display: flex; justify-content: space-between; padding: 5px 0; }
        .grand-box .total { border-top: 2px solid var(--ink); margin-top: 4px; padding-top: 8px; font-size: 16px; font-weight: 800; }

        .sign { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 28px; }
        .sign div { text-align: center; }
        .sign .label { color: var(--muted); margin-bottom: 44px; }
        .sign .name { border-top: 1px solid var(--ink); padding-top: 6px; font-weight: 600; }

        .empty { padding: 40px; text-align: center; color: var(--muted); }
        .foot-note { margin-top: 24px; font-size: 11px; color: var(--muted); }

        .note-empty { padding: 14px; margin-bottom: 4px; text-align: center; color: var(--muted);
            background: #f8fafc; border: 1px dashed var(--line); border-radius: 8px; }

        /* ---- Perkiraan (belum disetor) — gaya redup, jelas terpisah dari upah riil ---- */
        .est { margin-top: 26px; padding-top: 18px; border-top: 1px dashed var(--line); }
        .est-head { font-weight: 800; font-size: 13px; color: var(--muted); }
        .est-head span { display: block; font-weight: 500; font-size: 11px; margin-top: 2px; }
        .est-table { margin-top: 10px; }
        .est-table .cat-row td { background: #fff7ed; }
        .est-table .num { color: var(--muted); }
        .est-total { border-top: 2px dashed var(--muted); margin-top: 4px; padding-top: 8px;
            font-size: 15px; font-weight: 800; color: var(--muted); }

        /* ---------- Tablet & desktop enhancements ---------- */
        @media (min-width: 641px) {
            body { font-size: 13px; }
            .slip-toolbar { padding: 10px 14px; }
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
        <button type="button" class="btn btn-dark" id="btnShare" onclick="shareImage()" hidden>Bagikan</button>
        <button type="button" class="btn" id="btnPng" onclick="downloadImage('png')">Unduh PNG</button>
        <button type="button" class="btn" id="btnPdf" onclick="downloadImage('pdf')">Unduh PDF</button>
        <button type="button" class="btn" onclick="window.print()">Cetak</button>
    </div>

    <div class="sheet" id="slipSheet">
        <div class="slip-head">
            <div class="slip-brand">
                {{ config('app.name', 'Produksi') }}
                <small>Slip Borongan</small>
            </div>
            <div class="slip-title">
                <b>{{ $moduleLabel }}</b>
                <span>Periode {{ $period }}</span>
            </div>
        </div>

        <dl class="slip-meta">
            <div><dt>Nama</dt><dd>{{ $employee->name }}</dd></div>
            <div><dt>Kode</dt><dd>{{ $employee->code }}</dd></div>
            <div><dt>Peran</dt><dd>{{ $role }}</dd></div>
            <div><dt>Periode</dt><dd>{{ $tgl($dateFrom) }} – {{ $tgl($dateTo) }}</dd></div>
            @if ($pickupFrom)
                <div><dt>Tgl Ambil</dt><dd>{{ $pickupFrom === $pickupTo ? $tgl($pickupFrom) : $tgl($pickupFrom) . ' – ' . $tgl($pickupTo) }}</dd></div>
            @endif
            <div><dt>Dicetak</dt><dd>{{ $printedAt->format('d-m-Y H:i') }}</dd></div>
        </dl>

        @if ($groups->isEmpty() && $estGroups->isEmpty())
            <div class="empty">Tidak ada hasil borongan pada periode ini.</div>
        @else
            {{-- Kartu utama: Total Diterima = upah setor (riil) + perkiraan belum disetor --}}
            @php
                $grandTotal = $grandAmount + $estAmount;
                if ($estAmount > 0) {
                    $heroSub = 'Setor ' . $rp($grandAmount) . ' + perkiraan ~' . $rp($estAmount);
                } else {
                    $heroSub = $fmt($grandQty) . ' pcs lolos QC · ' . $tgl($dateFrom) . ' – ' . $tgl($dateTo);
                }
            @endphp
            <div class="hero {{ $grandTotal > 0 ? '' : 'hero-muted' }}">
                @if ($recap->isNotEmpty())
                    <div class="hero-recap">
                        <div class="hero-label">Per Kategori</div>
                        <div class="recap-list">
                            @foreach ($recap as $r)
                                <div class="recap-row">
                                    <span><span class="recap-cat">{{ $r->category }}</span><span class="recap-qty">{{ $fmt($r->qty) }} pcs</span></span>
                                    <span class="recap-amt">{{ $rp($r->amount) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="hero-total">
                    <div class="hero-label">Total Diterima</div>
                    <div class="hero-amount">{{ $rp($grandTotal) }}</div>
                    <div class="hero-sub">{{ $heroSub }}</div>
                </div>
            </div>

            @if (!$groups->isEmpty())
                @php $flatLines = $groups->flatMap(fn($g) => $g->lines)->sortBy('sku')->values(); @endphp
                <div class="sec-title">Rincian hasil disetor</div>
                <table>
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="num">OK</th>
                            <th class="num">Tarif</th>
                            <th class="num">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($flatLines as $l)
                            <tr class="line-row">
                                <td><span class="prod-name">{{ $l->product_name }}</span><span class="sku-mini">{{ $l->sku }}</span></td>
                                <td class="num">{{ $fmt($l->qty) }}</td>
                                <td class="num">{{ $l->rate > 0 ? $rp($l->rate) : '–' }}</td>
                                <td class="num">{{ $rp($l->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="sub-row">
                            <td>Total</td>
                            <td class="num">{{ $fmt($grandQty) }}</td>
                            <td></td>
                            <td class="num">{{ $rp($grandAmount) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @else
                <div class="note-empty">Belum ada hasil yang disetor &amp; lolos QC pada periode ini.</div>
            @endif

            @if ($estGroups->isNotEmpty())
                <div class="est">
                    <div class="est-head">
                        Belum Disetor
                        <span>Pekerjaan yang masih dipegang (belum lolos QC). Nilai perkiraan, belum dibayar.</span>
                    </div>
                    @php $flatEst = $estGroups->flatMap(fn($g) => $g->lines)->sortBy('sku')->values(); @endphp
                    <table class="est-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="num">Sisa</th>
                                <th class="num">Tarif</th>
                                <th class="num">Perkiraan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($flatEst as $l)
                                <tr class="line-row">
                                    <td><span class="prod-name">{{ $l->product_name }}</span><span class="sku-mini">{{ $l->sku }}</span></td>
                                    <td class="num">{{ $fmt($l->qty) }}</td>
                                    <td class="num">{{ $l->rate > 0 ? $rp($l->rate) : '–' }}</td>
                                    <td class="num">~{{ $rp($l->amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="grand">
                        <div class="grand-box">
                            <div class="row"><span>Sisa Qty</span><b>{{ $fmt($estQty) }} pcs</b></div>
                            <div class="row est-total"><span>Perkiraan (belum final)</span><span>~{{ $rp($estAmount) }}</span></div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="sign">
                <div><div class="label">Diterima oleh</div><div class="name">{{ $employee->name }}</div></div>
                <div><div class="label">Disahkan oleh</div><div class="name">&nbsp;</div></div>
            </div>
        @endif

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        const FILE_NAME = @json($fileName);

        // Tombol "Bagikan" hanya muncul bila HP mendukung berbagi berkas (mis. ke WhatsApp).
        const CAN_SHARE_FILES = typeof navigator !== 'undefined' && !!navigator.canShare
            && navigator.canShare({ files: [new File([''], 't.png', { type: 'image/png' })] });
        if (CAN_SHARE_FILES) {
            const s = document.getElementById('btnShare');
            if (s) s.hidden = false;
        }

        async function captureCanvas() {
            const sheet = document.getElementById('slipSheet');
            return await html2canvas(sheet, {
                scale: 2,
                backgroundColor: '#ffffff',
                useCORS: true,
                scrollX: 0,
                scrollY: -window.scrollY,
                windowWidth: 820,
            });
        }

        function canvasToBlob(canvas) {
            return new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
        }

        async function shareImage() {
            const btn = document.getElementById('btnShare');
            if (typeof html2canvas === 'undefined' || !navigator.canShare) {
                alert('Perangkat ini belum mendukung berbagi langsung. Gunakan "Unduh PNG" lalu kirim manual.');
                return;
            }
            const label = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Menyiapkan…';
            try {
                const canvas = await captureCanvas();
                const blob = await canvasToBlob(canvas);
                const file = new File([blob], FILE_NAME + '.png', { type: 'image/png' });
                if (!navigator.canShare({ files: [file] })) {
                    throw new Error('unsupported');
                }
                await navigator.share({ files: [file], title: FILE_NAME });
            } catch (e) {
                if (e && e.name === 'AbortError') return; // user batal — abaikan
                console.error(e);
                alert('Gagal membagikan. Gunakan "Unduh PNG" lalu kirim manual.');
            } finally {
                btn.textContent = label;
                btn.disabled = false;
            }
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
