{{-- resources/views/production/sewing_pickups/supplies.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Kelengkapan Jahit ' . $pickup->code)

@push('head')
    <style>
        .supply-page { max-width: 920px; margin-inline: auto; padding: .85rem .75rem 4rem; }
        .cardx { background: var(--card); border: 1px solid var(--line); border-radius: 14px; box-shadow: 0 10px 28px rgba(15,23,42,.08); }
        .card-section { padding: .95rem 1rem; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; flex-wrap: wrap; }
        .head h1 { margin: 0; font-size: 1.02rem; font-weight: 900; }
        .sub { color: var(--muted); font-size: .82rem; margin-top: .2rem; }
        .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono"; }
        .supply-list { display: grid; gap: .65rem; }
        .supply-row { border: 1px solid rgba(148,163,184,.28); border-radius: 13px; padding: .78rem; display: grid; grid-template-columns: 1fr 140px; gap: .85rem; align-items: center; }
        .supply-row.is-short { border-color: rgba(239,68,68,.38); background: rgba(239,68,68,.045); }
        .code { font-weight: 900; color: #2563eb; }
        .name { color: var(--muted); font-size: .78rem; margin-top: .08rem; }
        .chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .45rem; }
        .chip { border-radius: 999px; border: 1px solid rgba(148,163,184,.35); padding: .12rem .5rem; font-size: .72rem; color: var(--muted); background: rgba(148,163,184,.08); }
        .chip.short { color: #dc2626; border-color: rgba(239,68,68,.35); background: rgba(239,68,68,.08); font-weight: 800; }
        .issued-input { text-align: right; font-weight: 900; border-radius: 12px; }
        .footer-actions { position: sticky; bottom: .75rem; display: flex; justify-content: space-between; gap: .5rem; margin-top: 1rem; }
        .footer-actions .btn { border-radius: 999px; }
        @media (max-width: 575.98px) {
            .supply-row { grid-template-columns: 1fr; }
            .footer-actions { flex-direction: column-reverse; }
            .footer-actions .btn { width: 100%; }
        }
    </style>
@endpush

@section('content')
    <div class="supply-page">
        @if (session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif

        <div class="cardx mb-3">
            <div class="card-section">
                <div class="head">
                    <div>
                        <h1>Isi Kelengkapan Jahit</h1>
                        <div class="sub">
                            <span class="mono">{{ $pickup->code }}</span>
                            @if ($pickup->operator)
                                • {{ $pickup->operator->code }} — {{ $pickup->operator->name }}
                            @endif
                            • {{ optional($pickup->date)->format('d/m/Y') }}
                        </div>
                    </div>
                    <a href="{{ route('production.sewing.pickups.show', $pickup) }}" class="btn btn-sm btn-outline-secondary">
                        Detail Pickup
                    </a>
                </div>
            </div>
        </div>

        @php $redirectTo = request('redirect_to', ''); @endphp
        <form method="post" action="{{ route('production.sewing.pickups.supplies.update', $pickup) }}" id="supply-form">
            @csrf
            @method('PUT')
            @if ($redirectTo)
                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
            @endif

            <div class="cardx">
                <div class="card-section">
                    @if ($pickup->supplyLines->isEmpty())
                        <div class="text-muted small">Tidak ada kelengkapan jahit dari BOM untuk pickup ini.</div>
                    @else
                        <div class="supply-list">
                            @foreach ($pickup->supplyLines as $line)
                                @php
                                    $requiredPcs  = (float) ($line->required_pcs ?? 0);
                                    $issuedPcs    = (float) ($line->issued_pcs ?? 0);
                                    $requiredQty  = (float) ($line->required_qty ?? 0);
                                    $stockSnap    = (float) ($line->stock_available_snapshot ?? 0);
                                    $uom          = $line->uom ?: $line->material?->unit;

                                    // Stok cukup jika snapshot >= required
                                    $stockOk = $requiredQty <= 0 || $stockSnap >= ($requiredQty - 0.0001);

                                    // Auto-fill Dibawa jika belum pernah diisi DAN stok cukup
                                    $defaultPcs = $issuedPcs > 0
                                        ? (int) $issuedPcs
                                        : ($stockOk && $requiredPcs > 0 ? (int) $requiredPcs : '');

                                    $shortPcs = max($requiredPcs - max($issuedPcs, $stockOk ? $requiredPcs : 0), 0);
                                    // shortage hanya dari issued actual (bukan auto-fill)
                                    $shortPcsDisplay = max($requiredPcs - $issuedPcs, 0);
                                @endphp
                                <div class="supply-row {{ !$stockOk && $shortPcsDisplay > 0.0001 ? 'is-short' : '' }}"
                                     data-required-pcs="{{ $requiredPcs }}"
                                     data-stock-ok="{{ $stockOk ? '1' : '0' }}">
                                    <div>
                                        <div class="mono code">{{ $line->material?->code ?? 'ITEM-' . $line->material_item_id }}</div>
                                        <div class="name">{{ $line->material?->name }}</div>
                                        <div class="chips">
                                            <span class="chip">Butuh <span class="mono">{{ number_format($requiredPcs, 0, ',', '.') }}</span> pcs</span>
                                            @if ($stockOk)
                                                <span class="chip" style="color:#16a34a; border-color:rgba(22,163,74,.35); background:rgba(22,163,74,.08);">
                                                    Stok cukup ✓
                                                </span>
                                            @else
                                                <span class="chip">Stok <span class="mono">{{ number_format(floor($stockSnap / max($requiredQty / max($requiredPcs,1), 0.00001)), 0, ',', '.') }}</span> pcs</span>
                                                <span class="chip short js-shortage" style="{{ $shortPcsDisplay > 0.0001 ? '' : 'display:none;' }}">
                                                    Kurang <span class="mono js-shortage-value">{{ number_format($shortPcsDisplay, 0, ',', '.') }}</span> pcs
                                                </span>
                                            @endif
                                            <span class="chip">BOM <span class="mono">{{ number_format($requiredQty, 4, ',', '.') }}</span> {{ $uom }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="small text-muted mb-1 d-block">
                                            Dibawa (pcs)
                                            @if ($stockOk && $issuedPcs == 0)
                                                <span style="color:#16a34a; font-size:.7rem;">· auto</span>
                                            @endif
                                        </label>
                                        <input type="number"
                                            min="0"
                                            step="1"
                                            inputmode="numeric"
                                            name="supplies[{{ $line->id }}][issued_pcs]"
                                            value="{{ old("supplies.{$line->id}.issued_pcs", $defaultPcs) }}"
                                            class="form-control form-control-sm issued-input js-issued-input {{ $stockOk ? 'border-success' : '' }}"
                                            placeholder="0">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="footer-actions">
                <a href="{{ $redirectTo ?: route('production.sewing.pickups.show', $pickup) }}" class="btn btn-outline-secondary btn-sm">
                    {{ $redirectTo ? '← Kembali Setor Jahit' : 'Detail Pickup' }}
                </a>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Kelengkapan</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-issued-input').forEach(input => {
                input.addEventListener('input', function () {
                    const row = input.closest('.supply-row');
                    const required = parseFloat(row?.dataset.requiredPcs || '0') || 0;
                    const issued = Math.max(parseFloat(input.value || '0') || 0, 0);
                    const short = Math.max(required - issued, 0);
                    const stockOk = row?.dataset.stockOk === '1';
                    const badge = row?.querySelector('.js-shortage');
                    const value = row?.querySelector('.js-shortage-value');

                    // is-short hanya jika stok memang tidak cukup DAN issued kurang
                    const reallyShort = !stockOk && short > 0.0001;
                    row?.classList.toggle('is-short', reallyShort);
                    if (badge) badge.style.display = reallyShort ? '' : 'none';
                    if (value) value.textContent = short.toLocaleString('id-ID', { maximumFractionDigits: 0 });
                });
            });
        });
    </script>
@endpush
