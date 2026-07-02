@extends('layouts.app')
@section('title', 'Prospects – Cart Abandonment')

@push('head')
<style>
.tab-pill { border:1.5px solid #e2e8f0; border-radius:999px; padding:.25rem .7rem; font-size:.72rem; font-weight:700; text-decoration:none; color:#64748b; background:#fff; white-space:nowrap; }
.tab-pill.active, .tab-pill:hover { background:#0f172a; color:#fff; border-color:#0f172a; }
.crm-table th { font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; border-bottom:1.5px solid #e8ecf0; padding:.5rem .75rem; background:#f8fafc; white-space:nowrap; }
.crm-table td { font-size:.8rem; vertical-align:middle; padding:.55rem .75rem; border-bottom:1px solid #f1f5f9; }
.crm-table tr:last-child td { border-bottom:0; }
.stat-card { background:#fff; border:1.5px solid #e8ecf0; border-radius:16px; padding:1rem 1.25rem; }
.stat-label { font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; margin-bottom:.25rem; }
.stat-value { font-size:1.5rem; font-weight:900; color:#0f172a; line-height:1.1; }
.stat-sub { font-size:.72rem; color:#64748b; margin-top:.2rem; }
.urgency-hot  { display:inline-flex;align-items:center;gap:.2rem;font-size:.6rem;font-weight:800;background:#fee2e2;color:#dc2626;padding:.1rem .38rem;border-radius:5px; }
.urgency-warm { display:inline-flex;align-items:center;gap:.2rem;font-size:.6rem;font-weight:800;background:#fef3c7;color:#b45309;padding:.1rem .38rem;border-radius:5px; }
.urgency-cold { display:inline-flex;align-items:center;gap:.2rem;font-size:.6rem;font-weight:800;background:#f1f5f9;color:#64748b;padding:.1rem .38rem;border-radius:5px; }
.blast-bar { position:fixed;bottom:1.25rem;left:50%;transform:translateX(-50%);background:#0f172a;color:#fff;border-radius:16px;padding:.65rem 1.25rem;display:flex;align-items:center;gap:1rem;z-index:1050;box-shadow:0 4px 24px rgba(0,0,0,.25);white-space:nowrap;transition:opacity .2s; }
.blast-bar.hidden { opacity:0;pointer-events:none; }
.live-dot { display:inline-block;width:7px;height:7px;border-radius:50%;background:#22c55e;animation:livePulse 1.8s ease-in-out infinite; }
.live-dot.paused { background:#94a3b8;animation:none; }
@keyframes livePulse { 0%,100%{opacity:1;transform:scale(1);}50%{opacity:.4;transform:scale(.75);} }
.flash-new { animation:flashNew .9s ease; }
@keyframes flashNew { 0%{background:#fef9c3;}100%{background:transparent;} }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-black mb-0" style="font-size:1.05rem;">Cart Abandonment</h5>
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:.65rem;font-weight:800;color:#16a34a;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:999px;padding:.15rem .55rem;">
                    <span class="live-dot" id="live-dot"></span> LIVE
                </span>
            </div>
            <div style="font-size:.75rem;color:#94a3b8;margin-top:2px;">
                Visitor add to cart tapi belum order · <span id="last-updated">memuat...</span>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ route('admin.crm.dashboard') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.75rem;">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
            <a href="{{ route('admin.crm.prospects.export', array_merge(request()->query())) }}"
               class="btn btn-sm btn-success fw-bold" style="border-radius:10px;font-size:.75rem;">
                <i class="bi bi-download me-1"></i> Export CSV
            </a>
            @if(env('APP_DB_MODE') === 'dev' && auth()->user()?->role === 'owner')
            <form method="POST" action="{{ route('admin.crm.dev_seed_abandoned') }}" id="seedAbandonedForm"
                  onsubmit="return confirmSeedAbandoned(event)">
                @csrf
                <input type="hidden" name="abandoned" id="seedAbandonedCount" value="20">
                <button type="submit" class="btn btn-sm fw-bold"
                        style="background:#f0f9ff;color:#0369a1;border:1.5px dashed #7dd3fc;border-radius:10px;font-size:.72rem;padding:.25rem .7rem;">
                    <i class="bi bi-cart-x me-1"></i>Seed Abandonment
                    <span style="font-size:.6rem;background:#bae6fd;color:#0c4a6e;border-radius:4px;padding:.02rem .25rem;margin-left:4px;font-weight:900;">DEV</span>
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Dev seed flash --}}
    @if(session('dev_seed_done'))
    <div class="alert alert-dismissible fade show py-2 mb-3"
         style="font-size:.8rem;border-radius:12px;background:#f0f9ff;border:1.5px solid #7dd3fc;color:#0369a1;" role="alert">
        <i class="bi bi-cart-check me-1"></i> {{ session('dev_seed_done') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- New prospect banner --}}
    <div id="new-prospect-banner" style="display:none;"
         class="alert d-flex align-items-center justify-content-between gap-2 py-2 mb-3"
         style="background:#fffbeb;border:1.5px solid #f59e0b;border-radius:12px;font-size:.8rem;">
        <span><i class="bi bi-cart-x me-1" style="color:#b45309;"></i> <strong id="new-prospect-text">Prospect baru ditemukan!</strong></span>
        <button onclick="location.reload()" class="btn btn-sm btn-warning fw-bold" style="border-radius:8px;font-size:.75rem;">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </button>
    </div>

    {{-- Stat cards --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Total Abandonment</div>
                <div class="stat-value" id="stat-total">{{ $totalCount }}</div>
                <div class="stat-sub">{{ $days }} hari terakhir</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-color:#bbf7d0;">
                <div class="stat-label" style="color:#16a34a;">Ada Nomor HP</div>
                <div class="stat-value" id="stat-phone" style="color:#16a34a;">{{ $withPhoneCount }}</div>
                <div class="stat-sub">bisa di-blast WA</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-color:#fecaca;">
                <div class="stat-label" style="color:#dc2626;">🔥 Panas (&lt;2 jam)</div>
                <div class="stat-value" id="stat-hot" style="color:#dc2626;">{{ $hotCount }}</div>
                <div class="stat-sub">baru saja abandon</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-color:#d1fae5;">
                <div class="stat-label" style="color:#065f46;">Potensi Nilai</div>
                <div class="stat-value" id="stat-value" style="color:#065f46;font-size:1.15rem;">
                    Rp{{ number_format($potentialTotal) }}
                </div>
                <div class="stat-sub">dari semua keranjang</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="d-flex gap-2 flex-wrap align-items-center mb-3">
        @foreach([1=>'Hari ini', 7=>'7 Hari', 30=>'30 Hari', 90=>'90 Hari'] as $d => $label)
        <a href="{{ route('admin.crm.prospects', array_merge(request()->query(), ['days' => $d])) }}"
           class="tab-pill {{ $days == $d ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
        <div class="ms-auto">
            <form method="GET" action="{{ route('admin.crm.prospects') }}" class="d-flex align-items-center gap-2">
                <input type="hidden" name="days" value="{{ $days }}">
                <div class="form-check form-switch mb-0 d-flex align-items-center gap-2" style="font-size:.78rem;">
                    <input class="form-check-input" type="checkbox" role="switch" id="onlyId"
                           name="only_identified" value="1"
                           {{ $onlyId ? 'checked' : '' }} onchange="this.form.submit()">
                    <label class="form-check-label fw-bold" for="onlyId">Hanya yang ada HP</label>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table class="table mb-0 crm-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" style="cursor:pointer;"></th>
                        <th>#</th>
                        <th>Nama / Token</th>
                        <th>No. HP</th>
                        <th>Kota</th>
                        <th>Isi Keranjang</th>
                        <th style="text-align:right;">Potensi</th>
                        <th>Urgensi</th>
                        <th>Terakhir Cart</th>
                        <th>Blast WA</th>
                    </tr>
                </thead>
                <tbody id="prospects-tbody">
                    @forelse($prospects as $p)
                    <tr data-token="{{ substr($p->visitor_token, 0, 16) }}">
                        <td>
                            @if($p->phone)
                            <input type="checkbox" class="prospect-cb" value="{{ $p->wa_phone }}"
                                   data-name="{{ $p->name ?? 'Kak' }}"
                                   data-product="{{ $p->last_product }}"
                                   style="cursor:pointer;">
                            @endif
                        </td>
                        <td style="color:#94a3b8;font-size:.72rem;">{{ $loop->index + 1 }}</td>
                        <td>
                            @if($p->name)
                            <div class="fw-bold" style="font-size:.8rem;">{{ $p->name }}</div>
                            <div style="font-size:.65rem;font-family:monospace;color:#94a3b8;">{{ substr($p->visitor_token, 0, 16) }}…</div>
                            @else
                            <div style="font-size:.72rem;font-family:monospace;color:#94a3b8;">{{ substr($p->visitor_token, 0, 20) }}…</div>
                            <span style="font-size:.62rem;background:#f1f5f9;color:#64748b;border-radius:4px;padding:.05rem .3rem;">Anon</span>
                            @endif
                        </td>
                        <td>
                            @if($p->phone)
                            <a href="https://wa.me/{{ $p->wa_phone }}" target="_blank"
                               style="font-size:.8rem;font-weight:700;color:#16a34a;text-decoration:none;">
                                <i class="bi bi-whatsapp me-1"></i>{{ $p->phone }}
                            </a>
                            @else
                            <span style="color:#cbd5e1;font-size:.75rem;">—</span>
                            @endif
                        </td>
                        <td style="font-size:.78rem;color:#334155;text-transform:capitalize;">
                            {{ $p->city ? strtolower($p->city) : '—' }}
                        </td>
                        <td style="max-width:220px;">
                            @forelse($p->cart_items as $item)
                            <div style="font-size:.72rem;line-height:1.4;padding:.1rem 0;{{ !$loop->last ? 'border-bottom:1px dashed #f1f5f9;margin-bottom:.2rem;' : '' }}">
                                <div class="fw-bold" style="color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;" title="{{ $item['name'] ?? '-' }}">{{ $item['name'] ?? '-' }}</div>
                                <div style="color:#64748b;">
                                    @if(!empty($item['color'])) <span>{{ $item['color'] }}</span> @endif
                                    @if(!empty($item['size'])) · <span>{{ $item['size'] }}</span> @endif
                                    @if(!empty($item['qty'])) · <span class="fw-bold text-dark">×{{ $item['qty'] }}</span> @endif
                                    @if(!empty($item['price'])) · <span style="color:#16a34a;">Rp{{ number_format($item['price']) }}</span> @endif
                                </div>
                            </div>
                            @empty
                            <span style="color:#cbd5e1;font-size:.75rem;">—</span>
                            @endforelse
                        </td>
                        <td style="text-align:right;white-space:nowrap;font-weight:800;font-size:.78rem;">
                            @if($p->potential_value > 0)
                            Rp{{ number_format($p->potential_value) }}
                            @else
                            <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>
                            @if($p->urgency === 'hot')
                            <span class="urgency-hot">🔥 Baru</span>
                            @elseif($p->urgency === 'warm')
                            <span class="urgency-warm">☀️ Hangat</span>
                            @else
                            <span class="urgency-cold">❄️ Dingin</span>
                            @endif
                        </td>
                        <td style="font-size:.72rem;color:#64748b;white-space:nowrap;">
                            {{ \Carbon\Carbon::parse($p->last_cart_at)->diffForHumans() }}<br>
                            <span style="font-size:.65rem;">{{ \Carbon\Carbon::parse($p->last_cart_at)->format('d M H:i') }}</span>
                        </td>
                        <td>
                            @if($p->phone)
                            @php
                                $prodText = $p->last_product !== '-' ? "tertarik dengan produk *{$p->last_product}*" : "sudah memasukkan produk ke keranjang";
                                $sapaan   = $p->name ?? 'Kak';
                                $msg      = urlencode("Halo {$sapaan}, kami dari Garuda Fashion Indonesia 👋\n\nKami lihat kamu {$prodText} tapi belum menyelesaikan pembelian. Apakah ada yang bisa kami bantu? 😊\n\nKlik link berikut untuk lanjut belanja: " . config('app.url'));
                            @endphp
                            <a href="https://wa.me/{{ $p->wa_phone }}?text={{ $msg }}" target="_blank"
                               class="btn btn-sm" style="background:#25d366;color:#fff;border-radius:8px;font-size:.68rem;font-weight:700;padding:.2rem .5rem;">
                                <i class="bi bi-whatsapp"></i> Blast
                            </a>
                            @else
                            <span style="color:#cbd5e1;font-size:.72rem;">No HP</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" style="text-align:center;padding:2.5rem;color:#94a3b8;font-size:.85rem;">
                            Tidak ada prospect untuk periode ini
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-2" style="font-size:.72rem;color:#94a3b8;">
        <i class="bi bi-info-circle me-1"></i>
        Hanya visitor add to cart tanpa order. Export CSV hanya menyertakan yang ada nomor HP.
        Urgensi: 🔥 &lt;2j · ☀️ 2-24j · ❄️ &gt;24j
    </div>

    {{-- Floating blast bar --}}
    <div class="blast-bar hidden" id="blastBar">
        <span><strong id="blastCount">0</strong> prospect dipilih</span>
        <button onclick="openBlastModal()"
                class="btn btn-sm fw-bold" style="background:#25d366;color:#fff;border-radius:10px;font-size:.78rem;">
            <i class="bi bi-whatsapp me-1"></i> Blast WA Terpilih
        </button>
        <button onclick="clearAll()" class="btn btn-sm btn-outline-light" style="border-radius:10px;font-size:.75rem;">Batal</button>
    </div>

    {{-- Blast modal --}}
    <div class="modal fade" id="blastModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content" style="border-radius:16px;">
                <div class="modal-header" style="border-bottom:1.5px solid #f1f5f9;">
                    <h6 class="modal-title fw-bold">Blast WA — <span id="modalCount">0</span> Prospects</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="blastList" style="max-height:65vh;overflow-y:auto;"></div>
                </div>
                <div class="modal-footer" style="border-top:1.5px solid #f1f5f9;gap:.5rem;">
                    <span style="font-size:.75rem;color:#94a3b8;flex:1;">Browser mungkin memblokir popup — izinkan untuk buka semua sekaligus.</span>
                    <button onclick="openAllWa()" class="btn btn-sm fw-bold"
                            style="background:#25d366;color:#fff;border-radius:10px;font-size:.78rem;">
                        <i class="bi bi-whatsapp me-1"></i> Buka Semua WA
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
// ── Seed abandoned confirm ───────────────────────────────────────────────────
function confirmSeedAbandoned(e) {
    e.preventDefault();
    const n = prompt('Berapa abandoned cart yang ingin dibuat? (5–100)', '20');
    if (n === null) return;
    const count = parseInt(n);
    if (isNaN(count) || count < 5 || count > 100) { alert('Angka harus antara 5–100.'); return; }
    if (!confirm(`Buat ${count} cart abandonment demo?`)) return;
    document.getElementById('seedAbandonedCount').value = count;
    document.getElementById('seedAbandonedForm').submit();
}

// ── Blast logic ──────────────────────────────────────────────────────────────
const appUrl   = '{{ config('app.url') }}';
const blastBar = document.getElementById('blastBar');

document.getElementById('selectAll').addEventListener('change', function () {
    document.querySelectorAll('.prospect-cb').forEach(cb => cb.checked = this.checked);
    updateBar();
});
document.querySelectorAll('.prospect-cb').forEach(cb => cb.addEventListener('change', updateBar));

function updateBar() {
    const n = document.querySelectorAll('.prospect-cb:checked').length;
    document.getElementById('blastCount').textContent = n;
    blastBar.classList.toggle('hidden', n === 0);
}

function clearAll() {
    document.querySelectorAll('.prospect-cb, #selectAll').forEach(cb => cb.checked = false);
    blastBar.classList.add('hidden');
}

function buildWaUrl(phone, name, product) {
    const sapaan  = name && name !== 'Anonymous' && name !== 'Kak' ? name : 'Kak';
    const prodText = product && product !== '-'
        ? `tertarik dengan produk *${product}*`
        : 'sudah memasukkan produk ke keranjang';
    const msg = `Halo ${sapaan}, kami dari Garuda Fashion Indonesia 👋\n\nKami lihat kamu ${prodText} tapi belum menyelesaikan pembelian. Apakah ada yang bisa kami bantu? 😊\n\nKlik link berikut untuk lanjut belanja: ${appUrl}`;
    return `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
}

function openBlastModal() {
    const cbs = document.querySelectorAll('.prospect-cb:checked');
    document.getElementById('modalCount').textContent = cbs.length;
    let html = '';
    cbs.forEach(cb => {
        const waUrl = buildWaUrl(cb.value, cb.dataset.name, cb.dataset.product);
        html += `<div style="display:flex;align-items:center;gap:.75rem;padding:.65rem 1.25rem;border-bottom:1px solid #f8fafc;">
            <div style="font-size:.75rem;flex:1;min-width:0;">
                <div class="fw-bold">${cb.dataset.name !== 'Kak' ? cb.dataset.name : '—'}</div>
                <div style="color:#64748b;">${cb.value}</div>
            </div>
            <div style="font-size:.72rem;color:#94a3b8;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${cb.dataset.product}</div>
            <a href="${waUrl}" target="_blank" onclick="markSent(this)"
               class="btn btn-sm" style="background:#25d366;color:#fff;border-radius:8px;font-size:.72rem;padding:.2rem .6rem;white-space:nowrap;flex-shrink:0;">
                <i class="bi bi-whatsapp"></i> Blast
            </a>
        </div>`;
    });
    document.getElementById('blastList').innerHTML = html || '<div style="padding:1.5rem;text-align:center;color:#94a3b8;">Tidak ada prospect dipilih</div>';
    new bootstrap.Modal(document.getElementById('blastModal')).show();
}

function markSent(el) {
    setTimeout(() => { el.style.background = '#94a3b8'; el.textContent = '✓ Terkirim'; }, 500);
}

function openAllWa() {
    document.querySelectorAll('.prospect-cb:checked').forEach((cb, i) => {
        setTimeout(() => window.open(buildWaUrl(cb.value, cb.dataset.name, cb.dataset.product), '_blank'), i * 800);
    });
}

// ── LIVE POLLING ─────────────────────────────────────────────────────────────
(function () {
    const POLL_MS  = 15000;
    const DAYS     = {{ $days }};
    const liveUrl  = '{{ route('admin.crm.prospects.live') }}';
    let lastCartAt = '{{ $latestCartAt }}';
    let pollTimer  = null;

    function fmtRupiah(n) {
        return 'Rp' + Number(n).toLocaleString('id-ID');
    }

    function updateStats(stats) {
        document.getElementById('stat-total').textContent = stats.total;
        document.getElementById('stat-phone').textContent = stats.with_phone;
        document.getElementById('stat-hot').textContent   = stats.hot;
        document.getElementById('stat-value').textContent = fmtRupiah(stats.potential_total);
    }

    function checkNew(latestAt) {
        if (!lastCartAt || latestAt > lastCartAt) {
            const banner = document.getElementById('new-prospect-banner');
            const text   = document.getElementById('new-prospect-text');
            text.textContent = 'Prospect baru meninggalkan keranjang!';
            banner.style.display = '';
            lastCartAt = latestAt;
        }
    }

    async function poll() {
        try {
            const res  = await fetch(`${liveUrl}?days=${DAYS}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            updateStats(data.stats);
            if (data.latest_cart_at) checkNew(data.latest_cart_at);
            document.getElementById('last-updated').textContent = 'diperbarui ' + data.fetched_at;
        } catch (e) {
            console.warn('[prospects-live] poll error:', e);
        }
    }

    function start() {
        if (pollTimer) return;
        poll();
        pollTimer = setInterval(poll, POLL_MS);
        document.getElementById('live-dot')?.classList.remove('paused');
    }

    function stop() {
        clearInterval(pollTimer);
        pollTimer = null;
        document.getElementById('live-dot')?.classList.add('paused');
        document.getElementById('last-updated').textContent = 'polling dijeda';
    }

    document.addEventListener('visibilitychange', () => document.hidden ? stop() : start());
    start();
})();
</script>
@endpush
@endsection
