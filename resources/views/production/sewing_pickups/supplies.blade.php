{{-- resources/views/production/sewing_pickups/supplies.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Kelengkapan Jahit ' . $pickup->code)

@push('head')
<style>
    .sp-page  { max-width:680px; margin-inline:auto; padding:.85rem .75rem 5rem; }
    .sp-card  { background:var(--card); border:1px solid var(--line); border-radius:14px;
                box-shadow:0 6px 20px rgba(15,23,42,.07); margin-bottom:.75rem; overflow:hidden; }
    .sp-info  { padding:.75rem 1rem; border-bottom:1px solid var(--line); }
    .sp-info h1 { margin:0; font-size:1rem; font-weight:900; }
    .sp-sub   { color:var(--muted); font-size:.78rem; margin-top:.15rem; }
    .mono     { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-variant-numeric:tabular-nums; }

    /* Bundle section */
    .bun-head  { display:flex; justify-content:space-between; align-items:center;
                 padding:.6rem 1rem; background:rgba(148,163,184,.07); border-bottom:1px solid rgba(148,163,184,.12); }
    .bun-code  { font-size:1.05rem; font-weight:900; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; }
    .bun-qty   { font-size:.78rem; font-weight:700; color:var(--muted); }

    /* Supply rows */
    .sup-row   { display:grid; grid-template-columns:1fr auto; align-items:center; gap:.75rem;
                 padding:.55rem 1rem; border-bottom:1px solid rgba(148,163,184,.1); transition:background .12s; }
    .sup-row:last-child { border-bottom:none; }
    .sup-row.is-ok     { background:rgba(22,163,74,.04); }
    .sup-row.is-short  { background:rgba(239,68,68,.04); }
    .sup-name  { font-size:.8rem; font-weight:700; }
    .sup-need  { font-size:.68rem; color:var(--muted); margin-top:.08rem; }
    .sup-input { width:72px; text-align:right; font-weight:800; font-size:.82rem;
                 border-radius:8px; padding:.25rem .4rem;
                 border:1px solid rgba(148,163,184,.4); background:var(--card); color:var(--text); }
    .sup-input:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 2px rgba(37,99,235,.14); }
    .sup-input.is-ok    { border-color:rgba(22,163,74,.5); }
    .sup-input.is-short { border-color:rgba(239,68,68,.45); }
    .sup-input:disabled { opacity:.45; cursor:not-allowed; }
    .sup-row.is-locked  { opacity:.6; }
    .sup-row.is-locked .js-row-tap { cursor:default; }

    /* Footer */
    .sp-footer { position:sticky; bottom:.75rem; display:flex; justify-content:space-between;
                 gap:.5rem; margin-top:.5rem; }
    .sp-footer .btn { border-radius:999px; }
    @media (max-width:575px) {
        .sp-footer { flex-direction:column-reverse; }
        .sp-footer .btn { width:100%; }
    }
</style>
@endpush

@section('content')
<div class="sp-page">
    @if (session('success'))
        <div class="alert alert-success py-2 mb-3">{{ session('success') }}</div>
    @endif

    {{-- Header card --}}
    <div class="sp-card">
        <div class="sp-info" style="display:flex; justify-content:space-between; align-items:flex-start; gap:.5rem;">
            <div>
                <h1>Kelengkapan Jahit</h1>
                <div class="sp-sub mono">
                    {{ $pickup->code }}
                    @if ($pickup->operator)
                        · {{ $pickup->operator->code }} — {{ $pickup->operator->name }}
                    @endif
                    · {{ optional($pickup->date)->format('d/m/Y') }}
                    @if (($filterLineId ?? 0) > 0)
                        <a href="{{ request()->fullUrlWithoutQuery(['line_id']) }}"
                           style="font-size:.68rem; color:var(--muted); text-decoration:none; margin-left:.25rem;">semua ✕</a>
                    @endif
                </div>
            </div>
            <a href="{{ route('production.sewing.pickups.show', $pickup) }}"
               class="btn btn-sm btn-outline-secondary" style="border-radius:999px; flex-shrink:0;">Detail</a>
        </div>
    </div>

    @php $redirectTo = request('redirect_to', ''); @endphp

    <form method="post" action="{{ route('production.sewing.pickups.supplies.update', $pickup) }}" id="supply-form">
        @csrf
        @method('PUT')
        @if ($redirectTo)
            <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
        @endif

        {{-- Hidden aggregate inputs — diisi JS sebelum submit --}}
        @foreach ($pickup->supplyLines as $sl)
            <input type="hidden"
                   id="agg-{{ $sl->id }}"
                   name="supplies[{{ $sl->id }}][issued_pcs]"
                   value="{{ old("supplies.{$sl->id}.issued_pcs", (int)($sl->issued_pcs ?? 0)) }}">
        @endforeach

        @php
            // Lookup: supply line id → required_pcs (total pickup level)
            $slRequiredPcs = $pickup->supplyLines->pluck('required_pcs', 'id')->map(fn($v) => (float) $v)->toArray();
        @endphp

        @if ($bundleRequirements->isEmpty())
            <div class="sp-card">
                <div style="padding:1rem; color:var(--muted); font-size:.85rem;">
                    Tidak ada kelengkapan jahit dari BOM untuk pickup ini.
                </div>
            </div>
        @else
            @php $anyIncomplete = false; @endphp
            @foreach ($bundleRequirements as $bidx => $bundle)
                @php
                    // Filter: hanya tampilkan material yang belum lengkap di level pickup
                    $incompleteMaterials = collect($bundle['materials'])->filter(function ($mat) use ($supplyLineIdByMaterial, $slRequiredPcs, $issuedPcsBySupplyLine) {
                        $slId       = $supplyLineIdByMaterial[$mat['material_item_id']] ?? null;
                        $required   = $slId ? ($slRequiredPcs[$slId] ?? 0) : 0;
                        $issued     = $slId ? ($issuedPcsBySupplyLine[$slId] ?? 0) : 0;
                        return !($required > 0 && $issued >= $required);
                    })->values();
                @endphp

                @if ($incompleteMaterials->isEmpty())
                    @continue {{-- semua material bundle ini sudah lengkap, skip card --}}
                @endif
                @php $anyIncomplete = true; @endphp

            <div class="sp-card" data-bundle-idx="{{ $bidx }}">
                {{-- Bundle header --}}
                <div class="bun-head">
                    <span class="bun-code">{{ $bundle['code'] }}</span>
                    <span class="bun-qty">{{ number_format($bundle['qty'], 0, ',', '.') }} pcs</span>
                </div>

                {{-- Materials (hanya yang belum lengkap) --}}
                @foreach ($incompleteMaterials as $mat)
                    @php
                        $slId   = $supplyLineIdByMaterial[$mat['material_item_id']] ?? null;
                        $reqPcs = (int) $mat['required_pcs'];
                    @endphp
                    <div class="sup-row js-sup-row"
                         data-sl-id="{{ $slId }}"
                         data-req="{{ $reqPcs }}">
                        <div class="js-row-tap" style="cursor:pointer; min-width:0;">
                            <div class="sup-name">{{ $mat['name'] }}</div>
                            <div class="sup-need mono">{{ number_format($reqPcs, 0, ',', '.') }} pcs</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:.35rem; flex-shrink:0;">
                            <button type="button" class="js-unlock-btn"
                                    style="display:none; font-size:.65rem; color:var(--muted); background:none;
                                           border:1px solid rgba(148,163,184,.35); border-radius:999px;
                                           padding:.1rem .4rem; cursor:pointer; white-space:nowrap;"
                                    title="Ubah nilai">✎</button>
                            <input type="number" step="1" min="0" max="{{ $reqPcs }}" inputmode="numeric"
                                   class="sup-input js-sup-input"
                                   name="line_supplies[{{ $bundle['id'] }}][{{ $mat['material_item_id'] }}][issued_pcs]"
                                   data-sl-id="{{ $slId }}"
                                   data-req="{{ $reqPcs }}"
                                   value="0"
                                   placeholder="{{ $reqPcs }}">
                            <input type="hidden"
                                   name="line_supplies[{{ $bundle['id'] }}][{{ $mat['material_item_id'] }}][required_qty]"
                                   value="{{ (float) ($mat['required_qty'] ?? 0) }}">
                            <input type="hidden"
                                   name="line_supplies[{{ $bundle['id'] }}][{{ $mat['material_item_id'] }}][qty_per_pcs]"
                                   value="{{ (float) ($mat['qty_per_pcs'] ?? 0) }}">
                        </div>
                    </div>
                @endforeach
            </div>
            @endforeach

            @if (!$anyIncomplete)
            <div class="sp-card">
                <div style="padding:1rem; color:var(--ok, #16a34a); font-size:.85rem; font-weight:700;">
                    ✓ Semua kelengkapan jahit sudah terpenuhi.
                </div>
            </div>
            @endif
        @endif

        <div class="sp-footer">
            <a href="{{ $redirectTo ?: route('production.sewing.pickups.show', $pickup) }}"
               class="btn btn-outline-secondary btn-sm">
                {{ $redirectTo ? '← Kembali Setor Jahit' : 'Detail Pickup' }}
            </a>
            <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const issuedPcsBySupplyLine = @json($issuedPcsBySupplyLine);

    // inputsBySlId: slId → [visible input elements]
    // otherContribution: slId → issued dari bundle yang tidak ditampilkan (kasus filter line_id)
    const inputsBySlId      = {};
    const otherContribution = {};

    document.querySelectorAll('.js-sup-input').forEach(inp => {
        const slId = inp.dataset.slId;
        if (!slId) return;
        if (!inputsBySlId[slId]) inputsBySlId[slId] = [];
        inputsBySlId[slId].push(inp);
    });

    // Distribusi + hitung sisa untuk bundle yang tidak tampil
    Object.entries(issuedPcsBySupplyLine).forEach(([slId, totalIssued]) => {
        const inputs = inputsBySlId[slId] || [];
        let remaining = Math.round(totalIssued);
        inputs.forEach(inp => {
            const req = parseInt(inp.dataset.req || '0');
            const fill = Math.min(remaining, req);
            inp.value = fill;
            remaining -= fill;
            const row = inp.closest('.js-sup-row');
            syncRow(row, fill, req);
            if (fill >= req && req > 0) lockRow(row, inp);
        });
        // Sisa = kontribusi bundle lain yang tidak ditampilkan
        otherContribution[slId] = Math.max(remaining, 0);
        aggregateSlId(slId);
    });

    // ── Input manual → clamp + sync + aggregate
    document.querySelectorAll('.js-sup-input').forEach(inp => {
        inp.addEventListener('input', function () {
            const req = parseInt(this.dataset.req || '0');
            const val = Math.max(0, Math.min(parseInt(this.value || '0') || 0, req));
            if (parseInt(this.value) !== val) this.value = val;
            syncRow(this.closest('.js-sup-row'), val, req);
            aggregateSlId(this.dataset.slId);
        });
    });

    // ── Klik area teks → toggle 0 / penuh (skip jika terkunci)
    document.querySelectorAll('.js-row-tap').forEach(tap => {
        tap.addEventListener('click', function (e) {
            e.stopPropagation();
            const row = this.closest('.js-sup-row');
            const inp = row?.querySelector('.js-sup-input');
            if (!inp || inp.disabled) return;   // skip baris terkunci
            const req = parseInt(inp.dataset.req || '0');
            const cur = parseInt(inp.value || '0') || 0;
            inp.value = cur >= req ? 0 : req;
            syncRow(row, parseInt(inp.value), req);
            aggregateSlId(inp.dataset.slId);
        });
    });

    // ── Tombol ✎ → buka kunci baris
    document.querySelectorAll('.js-unlock-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const row = this.closest('.js-sup-row');
            const inp = row?.querySelector('.js-sup-input');
            if (!inp) return;
            inp.disabled = false;
            inp.focus();
            row.dataset.locked = '0';
            this.style.display = 'none';
            row.classList.remove('is-locked');
        });
    });

    function lockRow(row, inp) {
        if (!row || !inp) return;
        inp.disabled = true;
        row.dataset.locked = '1';
        row.classList.add('is-locked');
        const btn = row.querySelector('.js-unlock-btn');
        if (btn) btn.style.display = '';
    }

    function syncRow(row, val, req) {
        if (!row) return;
        const isOk    = val >= req && req > 0;
        const isShort = val > 0 && !isOk;
        row.classList.toggle('is-ok',    isOk);
        row.classList.toggle('is-short', isShort);
        const inp = row.querySelector('.js-sup-input');
        if (inp) {
            inp.classList.toggle('is-ok',    isOk);
            inp.classList.toggle('is-short', isShort);
        }
    }

    function aggregateSlId(slId) {
        if (!slId) return;
        const inputs = inputsBySlId[slId] || [];
        const visibleTotal = inputs.reduce((sum, i) => sum + (parseInt(i.value || '0') || 0), 0);
        const other = otherContribution[slId] || 0;
        const hidden = document.getElementById('agg-' + slId);
        if (hidden) hidden.value = visibleTotal + other;
    }
});
</script>
@endpush
