@extends('layouts.app')

@section('title', 'Sales Reports • Penjualan & Performa Produk')

@php
  $fmtInt = fn($n) => number_format((float)$n, 0, ',', '.');
  $fmtMoney = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
  $rangeLabel = $fromDate.' s/d '.$toDate;
@endphp

@push('head')
<style>
  /* =========================================================
     GFID ENTERPRISE REPORT STYLE (LIGHT/DARK FRIENDLY)
  ========================================================= */
  :root{
    --r: 16px;
    --r2: 20px;

    /* fallback jika layout app kamu belum define */
    --g-bg: var(--bg, #f6f7fb);
    --g-card: var(--card, #ffffff);
    --g-tx: var(--text, #0f172a);
    --g-muted: var(--muted, #64748b);

    --g-line: var(--line, rgba(148,163,184,.22));
    --g-soft: rgba(148, 163, 184, .10);
    --g-soft2: rgba(148, 163, 184, .06);

    --g-accent: var(--accent, #2563eb);
    --g-success: #16a34a;

    --g-shadow: 0 14px 34px rgba(15,23,42,.10), 0 0 0 1px rgba(15,23,42,.03);
    --g-shadow2: 0 10px 26px rgba(15,23,42,.08), 0 0 0 1px rgba(15,23,42,.03);
  }

  /* Dark mode fallback (kalau app pakai data-theme="dark") */
  html[data-theme="dark"]{
    --g-bg: #0b1220;
    --g-card: #0f172a;
    --g-tx: #e5e7eb;
    --g-muted: #94a3b8;
    --g-line: rgba(148,163,184,.18);
    --g-soft: rgba(148,163,184,.10);
    --g-soft2: rgba(148,163,184,.06);
    --g-shadow: 0 16px 44px rgba(0,0,0,.35), 0 0 0 1px rgba(255,255,255,.03);
    --g-shadow2: 0 12px 30px rgba(0,0,0,.28), 0 0 0 1px rgba(255,255,255,.03);
  }

  .g-wrap{
    max-width: 1180px;
    margin: 0 auto;
    padding: 18px 14px 28px;
  }

  /* Header */
  .g-head{
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 12px;
  }

  .g-title{
    font-size: 20px;
    font-weight: 900;
    letter-spacing: -.01em;
    color: var(--g-tx);
    line-height: 1.15;
  }

  .g-sub{
    color: var(--g-muted);
    font-size: 13px;
    margin-top: 6px;
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    align-items:center;
  }

  .g-chip{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding: 6px 10px;
    border-radius: 999px;
    border: 1px solid var(--g-line);
    background: var(--g-soft2);
    color: var(--g-muted);
    font-size: 12px;
    font-weight: 800;
  }

  .g-chip b{ color: var(--g-tx); font-weight: 900; }

  /* Card */
  .g-card{
    background: var(--g-card);
    border: 1px solid var(--g-line);
    border-radius: var(--r2);
    box-shadow: var(--g-shadow2);
  }

  .g-pad{ padding: 14px; }

  /* Toolbar / Filters */
  .g-toolbar{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:flex-end;
    justify-content:flex-end;
  }

  .g-field{
    display:flex;
    flex-direction:column;
    gap:6px;
    min-width: 170px;
  }
  .g-field label{
    font-size: 12px;
    color: var(--g-muted);
    font-weight: 800;
  }
  .g-input{
    height: 40px;
    border-radius: 14px;
    border: 1px solid var(--g-line);
    background: var(--g-card);
    color: var(--g-tx);
    padding: 0 12px;
    outline: none;
  }
  .g-input:focus{
    border-color: rgba(37,99,235,.55);
    box-shadow: 0 0 0 4px rgba(37,99,235,.12);
  }

  .g-btn{
    height: 40px;
    border-radius: 14px;
    padding: 0 12px;
    border: 1px solid var(--g-line);
    background: var(--g-soft2);
    color: var(--g-tx);
    font-weight: 900;
    display:inline-flex;
    align-items:center;
    gap:8px;
    text-decoration:none;
    user-select:none;
    transition: transform .06s ease, background .12s ease;
  }
  .g-btn:active{ transform: scale(.98); }
  .g-btn.primary{
    background: rgba(37,99,235,.14);
    border-color: rgba(37,99,235,.28);
  }
  .g-btn.ghost{
    background: transparent;
  }

  /* KPI */
  .g-kpis{
    display:grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 10px;
    margin-top: 12px;
  }
  @media (max-width: 920px){
    .g-kpis{ grid-template-columns: repeat(2, minmax(0,1fr)); }
  }
  @media (max-width: 560px){
    .g-kpis{ grid-template-columns: 1fr; }
    .g-field{ min-width: 140px; }
  }

  .g-kpi{
    padding: 14px;
    border-radius: var(--r2);
    border: 1px solid var(--g-line);
    background: linear-gradient(180deg, var(--g-soft2), transparent);
  }
  .g-kpi .k{
    color: var(--g-muted);
    font-size: 12px;
    font-weight: 900;
    letter-spacing: .02em;
    text-transform: uppercase;
  }
  .g-kpi .v{
    margin-top: 6px;
    font-weight: 950;
    font-size: 18px;
    color: var(--g-tx);
    font-variant-numeric: tabular-nums;
  }
  .g-kpi .hint{
    margin-top: 6px;
    color: var(--g-muted);
    font-size: 12px;
  }

  /* Grid */
  .g-grid{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 12px;
  }
  @media (max-width: 980px){
    .g-grid{ grid-template-columns: 1fr; }
  }

  .g-sec-title{
    font-weight: 950;
    color: var(--g-tx);
    letter-spacing: -.01em;
  }
  .g-sec-sub{
    color: var(--g-muted);
    font-size: 12px;
    margin-top: 4px;
  }

  /* Table wrapper */
  .g-tablewrap{
    margin-top: 10px;
    border: 1px solid var(--g-line);
    border-radius: var(--r2);
    overflow: hidden;
    background: var(--g-card);
  }
  .g-table-scroll{
    max-height: 460px;
    overflow: auto;
  }

  table{
    width: 100%;
    border-collapse: collapse;
  }
  thead th{
    position: sticky;
    top: 0;
    z-index: 2;
    background: var(--g-card);
    border-bottom: 1px solid var(--g-line);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--g-muted);
    padding: 11px 12px;
    white-space: nowrap;
  }
  tbody td{
    padding: 11px 12px;
    border-bottom: 1px solid var(--g-line);
    font-size: 13px;
    color: var(--g-tx);
    vertical-align: top;
  }
  tbody tr:hover td{
    background: var(--g-soft2);
  }
  .right{ text-align:right; font-variant-numeric: tabular-nums; }
  .code{ font-weight: 950; }
  .name{ color: var(--g-muted); font-size: 12px; margin-top: 2px; }
  .uom{ color: var(--g-muted); font-size: 12px; font-weight: 900; }

  /* Tabs */
  .g-tabs{
    display:flex;
    gap:8px;
    flex-wrap: wrap;
    margin-top: 10px;
  }
  .g-tab{
    border: 1px solid var(--g-line);
    background: var(--g-soft2);
    color: var(--g-tx);
    border-radius: 999px;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 950;
    cursor: pointer;
    display:inline-flex;
    align-items:center;
    gap:8px;
  }
  .g-tab.active{
    background: rgba(22,163,74,.12);
    border-color: rgba(22,163,74,.24);
  }

  /* Small badge */
  .g-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding: 4px 8px;
    border-radius: 999px;
    border: 1px solid var(--g-line);
    background: var(--g-soft2);
    color: var(--g-muted);
    font-size: 12px;
    font-weight: 950;
  }
</style>
@endpush

@section('content')
<div class="g-wrap">
  <div class="g-head">
    <div>
      <div class="g-title">Penjualan & Performa Produk</div>
      <div class="g-sub">
        <span class="g-chip"><i class="bi bi-calendar3"></i> <b>{{ $rangeLabel }}</b></span>
        <span class="g-chip"><i class="bi bi-database"></i> source <b>daily_item_sales</b></span>
        <span class="g-chip"><i class="bi bi-shield-check"></i> read-only report</span>
      </div>
    </div>

    <form method="get" class="g-toolbar">
      <div class="g-field">
        <label>Dari</label>
        <input class="g-input" type="date" name="from" value="{{ $fromDate }}">
      </div>
      <div class="g-field">
        <label>Sampai</label>
        <input class="g-input" type="date" name="to" value="{{ $toDate }}">
      </div>

      <button class="g-btn primary" type="submit">
        <i class="bi bi-funnel"></i> Terapkan
      </button>

      <a class="g-btn ghost" href="{{ route('sales.reports.sales_performance.index') }}">
        <i class="bi bi-arrow-counterclockwise"></i> Reset
      </a>
    </form>
  </div>

  {{-- KPI --}}
  <div class="g-card g-pad">
    <div class="g-kpis">
      <div class="g-kpi">
        <div class="k">Total Qty</div>
        <div class="v">{{ $fmtInt($kpi->total_qty ?? 0) }}</div>
        <div class="hint">Akumulasi qty terjual pada range</div>
      </div>
      <div class="g-kpi">
        <div class="k">Total Value</div>
        <div class="v">{{ $fmtMoney($kpi->total_value ?? 0) }}</div>
        <div class="hint">Akumulasi nilai penjualan</div>
      </div>
      <div class="g-kpi">
        <div class="k">Jumlah Hari</div>
        <div class="v">{{ $fmtInt($daysCount ?? 1) }}</div>
        <div class="hint">Dipakai untuk ADS (avg/day)</div>
      </div>
      <div class="g-kpi">
        <div class="k">Rata-rata Value/Hari</div>
        @php
          $avgValuePerDay = ($daysCount ?? 1) > 0 ? ((float)($kpi->total_value ?? 0) / (float)$daysCount) : 0;
        @endphp
        <div class="v">{{ $fmtMoney($avgValuePerDay) }}</div>
        <div class="hint">Total value ÷ jumlah hari</div>
      </div>
    </div>
  </div>

  <div class="g-grid">
    {{-- LEFT: Tren harian --}}
    <div class="g-card g-pad">
      <div>
        <div class="g-sec-title">Tren Harian</div>
        <div class="g-sec-sub">Ringkasan qty & value per tanggal.</div>
      </div>

      <div class="g-tablewrap">
        <div class="g-table-scroll">
          <table>
            <thead>
              <tr>
                <th style="width: 140px;">Tanggal</th>
                <th class="right">Qty</th>
                <th class="right">Value</th>
              </tr>
            </thead>
            <tbody>
              @forelse($daily as $d)
                <tr>
                  <td>
                    <div class="code">{{ $d->date }}</div>
                    <div class="name">Daily aggregate</div>
                  </td>
                  <td class="right">{{ $fmtInt($d->qty) }}</td>
                  <td class="right">{{ $fmtMoney($d->value) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" style="padding:14px; color:var(--g-muted);">
                    Tidak ada data pada range ini.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- RIGHT: Performa produk --}}
    <div class="g-card g-pad">
      <div>
        <div class="g-sec-title">Performa Produk</div>
        <div class="g-sec-sub">Top SKU berdasarkan Qty / Value / ADS (avg per hari).</div>
      </div>

      <div class="g-tabs">
        <button class="g-tab active" type="button" data-tab="qty">
          <i class="bi bi-bar-chart-line"></i> Top Qty
          <span class="g-badge">50</span>
        </button>

        <button class="g-tab" type="button" data-tab="value">
          <i class="bi bi-cash-stack"></i> Top Value
          <span class="g-badge">50</span>
        </button>

        <button class="g-tab" type="button" data-tab="ads">
          <i class="bi bi-speedometer2"></i> ADS
          <span class="g-badge">50</span>
        </button>
      </div>

      {{-- Tab: Qty --}}
      <div id="tab-qty" class="g-tablewrap">
        <div class="g-table-scroll">
          <table>
            <thead>
              <tr>
                <th>Item</th>
                <th class="right">Qty</th>
                <th class="right">Value</th>
              </tr>
            </thead>
            <tbody>
              @forelse($topByQty as $r)
                <tr>
                  <td>
                    <div class="code">{{ $r->code }}</div>
                    <div class="name">{{ $r->name }}</div>
                  </td>
                  <td class="right">
                    <span class="code">{{ $fmtInt($r->qty) }}</span>
                    <span class="uom">{{ $r->unit ?? '' }}</span>
                  </td>
                  <td class="right">{{ $fmtMoney($r->value) }}</td>
                </tr>
              @empty
                <tr><td colspan="3" style="padding:14px; color:var(--g-muted);">Tidak ada data.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- Tab: Value --}}
      <div id="tab-value" class="g-tablewrap" style="display:none;">
        <div class="g-table-scroll">
          <table>
            <thead>
              <tr>
                <th>Item</th>
                <th class="right">Qty</th>
                <th class="right">Value</th>
              </tr>
            </thead>
            <tbody>
              @forelse($topByValue as $r)
                <tr>
                  <td>
                    <div class="code">{{ $r->code }}</div>
                    <div class="name">{{ $r->name }}</div>
                  </td>
                  <td class="right">
                    <span class="code">{{ $fmtInt($r->qty) }}</span>
                    <span class="uom">{{ $r->unit ?? '' }}</span>
                  </td>
                  <td class="right">{{ $fmtMoney($r->value) }}</td>
                </tr>
              @empty
                <tr><td colspan="3" style="padding:14px; color:var(--g-muted);">Tidak ada data.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- Tab: ADS --}}
      <div id="tab-ads" class="g-tablewrap" style="display:none;">
        <div class="g-table-scroll">
          <table>
            <thead>
              <tr>
                <th>Item</th>
                <th class="right">Total Qty</th>
                <th class="right">ADS</th>
              </tr>
            </thead>
            <tbody>
              @forelse($ads as $r)
                <tr>
                  <td>
                    <div class="code">{{ $r->code }}</div>
                    <div class="name">{{ $r->name }}</div>
                  </td>
                  <td class="right">
                    <span class="code">{{ $fmtInt($r->total_qty) }}</span>
                    <span class="uom">{{ $r->unit ?? '' }}</span>
                  </td>
                  <td class="right">
                    <span class="code">{{ number_format((float)$r->ads, 2, ',', '.') }}</span>
                    <span class="uom">/hari</span>
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" style="padding:14px; color:var(--g-muted);">Tidak ada data.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (function(){
    const btns = document.querySelectorAll('.g-tab');
    const tabs = {
      qty: document.getElementById('tab-qty'),
      value: document.getElementById('tab-value'),
      ads: document.getElementById('tab-ads'),
    };

    function show(key){
      Object.values(tabs).forEach(el => el.style.display = 'none');
      if (tabs[key]) tabs[key].style.display = '';
    }

    btns.forEach(b => b.addEventListener('click', () => {
      btns.forEach(x => x.classList.remove('active'));
      b.classList.add('active');
      show(b.getAttribute('data-tab'));
    }));
  })();
</script>
@endpush
