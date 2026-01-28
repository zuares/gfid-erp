@extends('layouts.app')

@section('title', 'Marketplace • Laporan • Payout')

@php
  $fmtRp = fn($n) => 'Rp ' . number_format((float)($n ?? 0), 0, ',', '.');
  $fmtInt = fn($n) => number_format((int)($n ?? 0), 0, ',', '.');
  $fmtPct = fn($n) => number_format((float)($n ?? 0), 2, ',', '.') . '%';

  $sumGross = (float)($summary->gross_subtotal ?? 0);
  $sumFee   = (float)($summary->platform_fee_total ?? 0);
  $sumRef   = (float)($summary->refund_total ?? 0);
  $sumNet   = (float)($summary->net_payout_actual ?? 0);
  $sumCogs  = (float)($summary->cogs_total ?? 0);
  $sumGProf = (float)($summary->gross_profit ?? ($sumGross - $sumCogs));
  $sumNProf = (float)($summary->net_profit ?? ($sumGProf - $sumFee - $sumRef));

  $gm = $sumGross > 0 ? ($sumGProf / $sumGross * 100) : 0;
  $nm = $sumGross > 0 ? ($sumNProf / $sumGross * 100) : 0;

  // Forecast: profit_unreleased = unreleased_gross * realised_net_margin
  $netMarginReal = $sumGross > 0 ? ($sumNProf / $sumGross) : 0;
@endphp

@push('head')
<style>
  :root{
    --r:14px;
    --b: rgba(148,163,184,.22);
    --muted:#6b7280;
    --card: var(--bs-body-bg, #fff);
    --tx: #0f172a;
    --shadow: 0 10px 24px rgba(15,23,42,.08), 0 0 0 1px rgba(15,23,42,.03);
  }

  .wrap{ max-width: 1180px; margin:0 auto; padding: 14px 14px 90px; }
  .cardx{ background: var(--card); border:1px solid var(--b); border-radius: var(--r); box-shadow: var(--shadow); }
  .pad{ padding: 12px; }

  .head{ display:flex; justify-content:space-between; gap:12px; align-items:flex-start; margin-bottom: 10px; }
  .h1{ font-size: 18px; font-weight: 900; color: var(--tx); margin:0; }
  .sub{ color: var(--muted); font-size: 13px; line-height: 1.45; }

  .chip{
    display:inline-flex; gap:6px; align-items:center;
    padding: 4px 10px;
    border-radius: 999px;
    border: 1px solid var(--b);
    background: rgba(148,163,184,.06);
    font-size: 12px;
    color: var(--tx);
    white-space: nowrap;
  }
  .chip.ok{ background: rgba(34,197,94,.10); border-color: rgba(34,197,94,.22); color: #166534; }
  .chip.warn{ background: rgba(245,158,11,.12); border-color: rgba(245,158,11,.22); color: #92400e; }

  .filters{ display:grid; grid-template-columns: 1.2fr 1fr 1fr 1fr 1fr 1fr auto auto; gap:10px; }
  @media (max-width: 980px){ .filters{ grid-template-columns: 1fr 1fr; } }

  .inp{
    width:100%;
    padding: 10px 10px;
    border: 1px solid var(--b);
    border-radius: 12px;
    background: transparent;
    color: var(--tx);
    font-size: 14px;
  }
  .btnx{
    padding: 10px 12px;
    border-radius: 12px;
    border: 1px solid var(--b);
    background: rgba(59,130,246,.10);
    color: #1d4ed8;
    cursor: pointer;
    font-weight: 900;
    text-decoration:none;
    display:inline-flex;
    justify-content:center;
    align-items:center;
    gap:8px;
  }
  .btnx.ghost{ background: transparent; color: var(--tx); }

  .tabs{ display:flex; gap:8px; flex-wrap:wrap; margin-top: 10px; }
  .tab{
    display:inline-flex; align-items:center; gap:8px;
    padding: 8px 10px;
    border-radius: 999px;
    border: 1px solid var(--b);
    background: rgba(148,163,184,.06);
    color: var(--tx);
    font-weight: 900;
    text-decoration: none;
    font-size: 13px;
  }
  .tab.active{
    background: rgba(59,130,246,.12);
    border-color: rgba(59,130,246,.25);
    color: #1d4ed8;
  }

  .kgrid{ display:grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap:12px; margin-top: 12px; }
  @media (max-width: 980px){ .kgrid{ grid-template-columns: repeat(2, minmax(0,1fr)); } }
  .kpi{ padding: 12px; }
  .kpi .lab{ color: var(--muted); font-size: 12px; }
  .kpi .val{ font-size: 18px; font-weight: 900; margin-top: 3px; color: var(--tx); }
  .kpi .mini{ color: var(--muted); font-size: 12px; margin-top: 6px; }

  .sec{ margin-top: 14px; }
  .sec .st{ font-size: 14px; font-weight: 900; color: var(--tx); margin-bottom: 4px; }
  .sec .ss{ color: var(--muted); font-size: 12px; }

  .tablewrap{ overflow:auto; border-radius: 12px; border: 1px solid var(--b); }
  table{ width:100%; border-collapse: collapse; font-size: 13px; min-width: 980px; }
  th,td{ padding: 10px; border-bottom: 1px solid var(--b); vertical-align: top; }
  th{
    text-align:left; color: var(--muted);
    font-weight: 900; font-size: 12px;
    background: rgba(148,163,184,.06);
    text-transform: uppercase;
    letter-spacing: .06em;
  }
  tr:hover td{ background: rgba(148,163,184,.04); }
  .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
  .num{ text-align:right; white-space:nowrap; }
  .b{ font-weight: 900; }

  .tabpane{ display:none; }
  .tabpane.active{ display:block; }

  details.info{
    border: 1px solid var(--b);
    border-radius: 12px;
    padding: 10px 12px;
    background: rgba(148,163,184,.04);
  }
  details.info summary{
    cursor:pointer;
    font-weight: 900;
    color: var(--tx);
    font-size: 13px;
  }
  details.info .sub{ margin-top: 8px; }

  .row1{ display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:space-between; }

  .fxgrid{ display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px; }
  @media (max-width: 980px){ .fxgrid{ grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="wrap">

  {{-- Header --}}
  <div class="head">
    <div>
      <h1 class="h1">Laporan Payout Marketplace</h1>
      <div class="sub">
        Acuan tanggal: <b>paid_at</b>.
        Urutan baca tabel: <b>Gross → COGS → G.Profit → Fee → Refund → N.Profit → Net</b>.
      </div>
    </div>

    <div class="chip">
      Range: <b>{{ $filters['from'] }}</b> → <b>{{ $filters['to'] }}</b>
    </div>
  </div>

  {{-- Filters + Tabs --}}
  <div class="cardx pad">
    <form method="GET" action="{{ route('marketplace.reports.payout.index') }}">
      <div class="filters">
        <select class="inp" name="store_id">
          <option value="0">Semua Toko</option>
          @foreach($stores as $s)
            <option value="{{ $s->id }}" @selected((int)$filters['store_id'] === (int)$s->id)>{{ $s->name }}</option>
          @endforeach
        </select>

        <select class="inp" name="channel">
          <option value="">Semua Channel</option>
          @foreach($channels as $c)
            <option value="{{ $c }}" @selected($filters['channel'] === $c)>{{ strtoupper($c) }}</option>
          @endforeach
        </select>

        <select class="inp" name="status">
          <option value="" @selected($filters['status']==='')>Semua Status</option>
          <option value="released" @selected($filters['status']==='released')>Released</option>
          <option value="unreleased" @selected($filters['status']==='unreleased')>Unreleased</option>
        </select>

        <select class="inp" name="group">
          <option value="day" @selected($filters['group']==='day')>Harian</option>
          <option value="month" @selected($filters['group']==='month')>Bulanan</option>
        </select>

        <input class="inp" type="date" name="from" value="{{ $filters['from'] }}">
        <input class="inp" type="date" name="to" value="{{ $filters['to'] }}">

        <button class="btnx" type="submit">Terapkan</button>
        <a class="btnx ghost" href="{{ route('marketplace.reports.payout.index') }}">Reset</a>
      </div>
    </form>

    <div style="height:10px"></div>

    <div class="tabs" role="tablist" aria-label="Tabs payout">
      <a href="#ringkasan" class="tab active" data-tab="ringkasan">Ringkasan</a>
      <a href="#per-toko" class="tab" data-tab="per-toko">Per Toko</a>
      <a href="#per-sku" class="tab" data-tab="per-sku">Per SKU</a>
    </div>

    <div style="height:10px"></div>

    <details class="info">
      <summary>Definisi singkat (klik)</summary>
      <div class="sub">
        <div><b>Gross</b> = subtotal penjualan.</div>
        <div><b>COGS</b> = total HPP snapshot (line).</div>
        <div><b>G.Profit</b> = Gross − COGS.</div>
        <div><b>N.Profit</b> = G.Profit − Fee − Refund.</div>
        <div><b>Net</b> = Net Payout (uang diterima).</div>
      </div>
    </details>
  </div>

  {{-- TAB: Ringkasan --}}
  <div class="tabpane active" id="tab-ringkasan">

    {{-- KPI 1 --}}
    <div class="kgrid">
      <div class="cardx kpi">
        <div class="lab">Order</div>
        <div class="val">{{ $fmtInt($summary->orders ?? 0) }}</div>
        <div class="mini">total invoice marketplace</div>
      </div>

      <div class="cardx kpi">
        <div class="lab">Gross</div>
        <div class="val">{{ $fmtRp($sumGross) }}</div>
        <div class="mini">subtotal penjualan</div>
      </div>

      <div class="cardx kpi">
        <div class="lab">COGS</div>
        <div class="val">{{ $fmtRp($sumCogs) }}</div>
        <div class="mini">HPP total</div>
      </div>

      <div class="cardx kpi">
        <div class="lab">G.Profit</div>
        <div class="val">{{ $fmtRp($sumGProf) }}</div>
        <div class="mini">GM: <b>{{ $fmtPct($gm) }}</b></div>
      </div>
    </div>

    {{-- KPI 2 --}}
    <div class="kgrid">
      <div class="cardx kpi">
        <div class="lab">Fee</div>
        <div class="val">{{ $fmtRp($sumFee) }}</div>
      </div>

      <div class="cardx kpi">
        <div class="lab">Refund</div>
        <div class="val">{{ $fmtRp($sumRef) }}</div>
      </div>

      <div class="cardx kpi">
        <div class="lab">N.Profit</div>
        <div class="val">{{ $fmtRp($sumNProf) }}</div>
        <div class="mini">NM: <b>{{ $fmtPct($nm) }}</b></div>
      </div>

      <div class="cardx kpi">
        <div class="lab">Net (Payout)</div>
        <div class="val">{{ $fmtRp($sumNet) }}</div>
        <div class="mini">uang diterima</div>
      </div>
    </div>

    {{-- Timeline --}}
    <div class="sec">
      <div class="st">Timeline ({{ $filters['group'] }})</div>
      <div class="ss">Kolom: Gross → COGS → G.Profit → Fee → Refund → N.Profit → Net.</div>
    </div>

    <div class="cardx pad">
      <div class="tablewrap">
        <table>
          <thead>
            <tr>
              <th>Periode</th>
              <th class="num">Order</th>
              <th class="num">Gross</th>
              <th class="num">COGS</th>
              <th class="num">G.Profit</th>
              <th class="num">Fee</th>
              <th class="num">Refund</th>
              <th class="num">N.Profit</th>
              <th class="num">Net</th>
            </tr>
          </thead>
          <tbody>
            @forelse($timeline as $t)
              @php
                $tgross = (float)($t->gross_subtotal ?? 0);
                $tcogs  = (float)($t->cogs_total ?? 0);
                $tgprof = (float)($t->gross_profit ?? ($tgross - $tcogs));
                $tfee   = (float)($t->platform_fee_total ?? 0);
                $tref   = (float)($t->refund_total ?? 0);
                $tnprof = (float)($t->net_profit ?? ($tgprof - $tfee - $tref));
                $tnet   = (float)($t->net_payout_actual ?? 0);
              @endphp
              <tr>
                <td class="mono">{{ $t->grp }}</td>
                <td class="num">{{ $fmtInt($t->orders ?? 0) }}</td>
                <td class="num">{{ $fmtRp($tgross) }}</td>
                <td class="num">{{ $fmtRp($tcogs) }}</td>
                <td class="num b">{{ $fmtRp($tgprof) }}</td>
                <td class="num">{{ $fmtRp($tfee) }}</td>
                <td class="num">{{ $fmtRp($tref) }}</td>
                <td class="num b">{{ $fmtRp($tnprof) }}</td>
                <td class="num b">{{ $fmtRp($tnet) }}</td>
              </tr>
            @empty
              <tr><td colspan="9" class="sub">Tidak ada data.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div style="height:10px"></div>
      <div class="row1">
        <span class="chip warn">Gross Unreleased: <b>{{ $fmtRp($summary->gross_unreleased ?? 0) }}</b></span>
        <span class="chip warn">Fee Unreleased: <b>{{ $fmtRp($summary->fee_unreleased ?? 0) }}</b></span>
        <span class="chip warn">Refund Unreleased: <b>{{ $fmtRp($summary->refund_unreleased ?? 0) }}</b></span>
        <span class="chip warn">Net Unreleased: <b>{{ $fmtRp($summary->net_unreleased ?? 0) }}</b></span>
      </div>
    </div>

    {{-- Forecast Unreleased -> Profit --}}
    <div class="sec">
      <div class="st">Forecast: “kalau unreleased X → profit Y”</div>
      <div class="ss">
        Pakai net margin realised: <b>{{ $fmtPct($netMarginReal * 100) }}</b>
        (profit_unreleased = unreleased_gross × net_margin).
      </div>
    </div>

    <div class="cardx pad">
      <div class="fxgrid">
        <div>
          <div class="sub" style="margin-bottom:6px;">Unreleased Gross (Rp)</div>
          <input id="fxUnreleased" class="inp" type="text"
                 value="{{ number_format((float)($summary->gross_unreleased ?? 0), 0, ',', '.') }}">
        </div>

        <div>
          <div class="sub" style="margin-bottom:6px;">Estimasi Profit dari Unreleased</div>
          <div class="chip ok" style="padding:10px 12px; font-size:14px;">
            <span id="fxProfit">Rp 0</span>
          </div>
        </div>

        <div>
          <div class="sub" style="margin-bottom:6px;">Estimasi Total Net Profit</div>
          <div class="chip" style="padding:10px 12px; font-size:14px;">
            <b id="fxTotal">Rp 0</b>
          </div>
        </div>
      </div>
    </div>

    {{-- Loss Days --}}
    <div class="sec">
      <div class="st">Hari Rugi (N.Profit < 0)</div>
      <div class="ss">Di range ini, hari yang N.Profit negatif.</div>
    </div>

    <div class="cardx pad">
      <div class="tablewrap">
        <table style="min-width: 900px;">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th class="num">Order</th>
              <th class="num">Gross</th>
              <th class="num">COGS</th>
              <th class="num">Fee</th>
              <th class="num">Refund</th>
              <th class="num">N.Profit</th>
              <th class="num">Net</th>
            </tr>
          </thead>
          <tbody>
            @forelse(($lossDays ?? collect()) as $t)
              <tr>
                <td class="mono">{{ $t->grp }}</td>
                <td class="num">{{ $fmtInt($t->orders ?? 0) }}</td>
                <td class="num">{{ $fmtRp($t->gross_subtotal ?? 0) }}</td>
                <td class="num">{{ $fmtRp($t->cogs_total ?? 0) }}</td>
                <td class="num">{{ $fmtRp($t->platform_fee_total ?? 0) }}</td>
                <td class="num">{{ $fmtRp($t->refund_total ?? 0) }}</td>
                <td class="num b">{{ $fmtRp($t->net_profit ?? 0) }}</td>
                <td class="num">{{ $fmtRp($t->net_payout_actual ?? 0) }}</td>
              </tr>
            @empty
              <tr><td colspan="8" class="sub">Tidak ada hari rugi di range ini.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Unreleased per Date --}}
    <div class="sec">
      <div class="st">Unreleased per Tanggal</div>
      <div class="ss">Order sudah paid tapi released_at masih null.</div>
    </div>

    <div class="cardx pad">
      <div class="tablewrap">
        <table style="min-width: 920px;">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th class="num">Order</th>
              <th class="num">Gross</th>
              <th class="num">Fee</th>
              <th class="num">Refund</th>
              <th class="num">N.Profit (estimasi)</th>
            </tr>
          </thead>
          <tbody>
            @forelse(($unreleasedByDate ?? collect()) as $u)
              @php
                $ugross = (float)($u->gross_unreleased ?? 0);
                // belum ada cogs per unreleased-date di query ini -> pakai estimasi net margin realised
                $uNProfEst = $ugross * $netMarginReal;
              @endphp
              <tr>
                <td class="mono">{{ $u->d }}</td>
                <td class="num">{{ $fmtInt($u->orders ?? 0) }}</td>
                <td class="num b">{{ $fmtRp($ugross) }}</td>
                <td class="num">{{ $fmtRp($u->fee_unreleased ?? 0) }}</td>
                <td class="num">{{ $fmtRp($u->refund_unreleased ?? 0) }}</td>
                <td class="num b">{{ $fmtRp($uNProfEst) }}</td>
              </tr>
            @empty
              <tr><td colspan="6" class="sub">Tidak ada order unreleased di range ini.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="sub" style="margin-top:8px;">
        Catatan: N.Profit (estimasi) di tabel ini pakai <b>net margin realised</b> karena unreleased belum ada Net Payout actual.
      </div>
    </div>

    {{-- Latest invoices --}}
    <div class="sec">
      <div class="st">Invoice Terakhir</div>
      <div class="ss">Maks 200 baris untuk audit cepat.</div>
    </div>

    <div class="cardx pad">
      <div class="tablewrap">
        <table style="min-width: 1120px;">
          <thead>
            <tr>
              <th>Paid</th>
              <th>Toko</th>
              <th>Channel</th>
              <th>Order No</th>
              <th>Status</th>
              <th class="num">Gross</th>
              <th class="num">Fee</th>
              <th class="num">Refund</th>
              <th class="num">Net</th>
              <th>Released</th>
            </tr>
          </thead>
          <tbody>
            @forelse($invoices as $inv)
              @php $isReleased = !empty($inv->released_at); @endphp
              <tr>
                <td>{{ optional($inv->paid_at)->format('Y-m-d H:i') }}</td>
                <td><b>{{ $inv->store?->name ?? ('#'.$inv->store_id) }}</b></td>
                <td class="mono">{{ strtoupper($inv->channel ?? '-') }}</td>
                <td class="mono">{{ $inv->channel_order_no }}</td>
                <td>
                  @if($isReleased)
                    <span class="chip ok">released</span>
                  @else
                    <span class="chip warn">unreleased</span>
                  @endif
                  <span class="chip">{{ $inv->marketplace_status ?? '-' }}</span>
                </td>
                <td class="num">{{ $fmtRp($inv->subtotal) }}</td>
                <td class="num">{{ $fmtRp($inv->platform_fee_total) }}</td>
                <td class="num">{{ $fmtRp($inv->refund_total) }}</td>
                <td class="num b">{{ $fmtRp($inv->net_payout_actual) }}</td>
                <td>{{ $isReleased ? \Carbon\Carbon::parse($inv->released_at)->format('Y-m-d') : '-' }}</td>
              </tr>
            @empty
              <tr><td colspan="10" class="sub">Tidak ada data.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </div>

  {{-- TAB: Per Toko --}}
  <div class="tabpane" id="tab-per-toko">
    <div class="sec">
      <div class="st">Ranking per Toko</div>
      <div class="ss">Urut berdasarkan N.Profit terbesar.</div>
    </div>

    <div class="cardx pad">
      <div class="tablewrap">
        <table style="min-width: 1200px;">
          <thead>
            <tr>
              <th>#</th>
              <th>Toko</th>
              <th class="num">Order</th>
              <th class="num">Gross</th>
              <th class="num">COGS</th>
              <th class="num">G.Profit</th>
              <th class="num">Fee</th>
              <th class="num">Refund</th>
              <th class="num">N.Profit</th>
              <th class="num">Net</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rankStores as $i => $r)
              <tr>
                <td>{{ $i + 1 }}</td>
                <td><b>{{ $r->store_name }}</b> <span class="sub">(#{{ $r->store_id }})</span></td>
                <td class="num">{{ $fmtInt($r->orders ?? 0) }}</td>
                <td class="num">{{ $fmtRp($r->gross_subtotal ?? 0) }}</td>
                <td class="num">{{ $fmtRp($r->cogs_total ?? 0) }}</td>
                <td class="num b">{{ $fmtRp($r->gross_profit ?? 0) }}</td>
                <td class="num">{{ $fmtRp($r->platform_fee_total ?? 0) }}</td>
                <td class="num">{{ $fmtRp($r->refund_total ?? 0) }}</td>
                <td class="num b">{{ $fmtRp($r->net_profit ?? 0) }}</td>
                <td class="num b">{{ $fmtRp($r->net_payout_actual ?? 0) }}</td>
              </tr>
            @empty
              <tr><td colspan="10" class="sub">Tidak ada data.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- TAB: Per SKU --}}
  <div class="tabpane" id="tab-per-sku">
    <div class="sec">
      <div class="st">Performa per SKU</div>
      <div class="ss">Fee/Refund/Net dialokasikan proporsional dari gross invoice.</div>
    </div>

    <div class="cardx pad">
      <div class="tablewrap">
        <table style="min-width: 1320px;">
          <thead>
            <tr>
              <th>#</th>
              <th>SKU</th>
              <th>Nama</th>
              <th class="num">Qty</th>
              <th class="num">Gross</th>
              <th class="num">COGS</th>
              <th class="num">G.Profit</th>
              <th class="num">Fee</th>
              <th class="num">Refund</th>
              <th class="num">N.Profit</th>
              <th class="num">Net</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rankSkus as $i => $r)
              <tr>
                <td>{{ $i + 1 }}</td>
                <td class="mono"><b>{{ $r->item_code }}</b></td>
                <td>{{ $r->item_name }}</td>
                <td class="num">{{ $fmtInt($r->qty_total ?? 0) }}</td>
                <td class="num">{{ $fmtRp($r->gross_alloc ?? 0) }}</td>
                <td class="num">{{ $fmtRp($r->cogs_alloc ?? 0) }}</td>
                <td class="num b">{{ $fmtRp($r->gross_profit ?? 0) }}</td>
                <td class="num">{{ $fmtRp($r->fee_alloc ?? 0) }}</td>
                <td class="num">{{ $fmtRp($r->refund_alloc ?? 0) }}</td>
                <td class="num b">{{ $fmtRp($r->net_profit ?? 0) }}</td>
                <td class="num b">{{ $fmtRp($r->net_alloc ?? 0) }}</td>
              </tr>
            @empty
              <tr><td colspan="11" class="sub">Tidak ada data.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div style="height:10px"></div>
      <div class="sub">
        Catatan: proporsional dipakai karena fee/refund biasanya tidak detail per item di file income.
      </div>
    </div>
  </div>

</div>

@push('scripts')
<script>
(function(){
  // ---------------------------
  // Tabs
  // ---------------------------
  function setActive(tab){
    document.querySelectorAll('.tab').forEach(a => a.classList.toggle('active', a.dataset.tab === tab));
    document.querySelectorAll('.tabpane').forEach(p => p.classList.remove('active'));
    const pane = document.getElementById('tab-' + tab);
    if (pane) pane.classList.add('active');

    const hash = tab === 'ringkasan' ? '#ringkasan' : (tab === 'per-toko' ? '#per-toko' : '#per-sku');
    if (location.hash !== hash) history.replaceState(null, '', hash);
  }

  function initTabs(){
    let tab = 'ringkasan';
    if (location.hash === '#per-toko') tab = 'per-toko';
    if (location.hash === '#per-sku') tab = 'per-sku';

    document.querySelectorAll('.tab').forEach(a => {
      a.addEventListener('click', function(e){
        e.preventDefault();
        setActive(this.dataset.tab);
      });
    });

    setActive(tab);
  }

  // ---------------------------
  // Forecast widget
  // ---------------------------
  const NET_MARGIN = {{ (float)$netMarginReal }};
  const REALISED_NPROFIT = {{ (float)$sumNProf }};

  function parseIdr(s){
    if(!s) return 0;
    s = (''+s).replace(/[^\d]/g,'');
    return s ? parseInt(s,10) : 0;
  }
  function fmtIdr(n){
    n = Math.round(n || 0);
    return 'Rp ' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }
  function formatInput(el){
    const v = parseIdr(el.value);
    el.value = v.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return v;
  }
  function recalcForecast(){
    const input = document.getElementById('fxUnreleased');
    if(!input) return;

    const unreleased = parseIdr(input.value);
    const profitUnrel = unreleased * NET_MARGIN;
    const total = REALISED_NPROFIT + profitUnrel;

    const p = document.getElementById('fxProfit');
    const t = document.getElementById('fxTotal');
    if (p) p.textContent = fmtIdr(profitUnrel);
    if (t) t.textContent = fmtIdr(total);
  }

  document.addEventListener('DOMContentLoaded', function(){
    initTabs();

    const input = document.getElementById('fxUnreleased');
    if (input){
      formatInput(input);
      recalcForecast();
      input.addEventListener('input', recalcForecast);
      input.addEventListener('blur', function(){ formatInput(input); recalcForecast(); });
    }
  });
})();
</script>
@endpush
@endsection
