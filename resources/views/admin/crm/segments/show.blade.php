@extends('layouts.app')
@section('title', $def['label'] . ' — Segment')

@push('head')
<style>
.crm-table th { font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;border-bottom:1.5px solid #e8ecf0;padding:.5rem .75rem;background:#f8fafc;white-space:nowrap; }
.crm-table td { font-size:.8rem;vertical-align:middle;padding:.55rem .75rem;border-bottom:1px solid #f1f5f9; }
.crm-table tr:last-child td { border-bottom:0; }
.crm-table tr.hidden-row { display:none; }
.blast-bar { position:fixed;bottom:1.25rem;left:50%;transform:translateX(-50%);background:#0f172a;color:#fff;border-radius:16px;padding:.65rem 1.25rem;display:flex;align-items:center;gap:1rem;z-index:1050;box-shadow:0 4px 24px rgba(0,0,0,.25);white-space:nowrap;transition:opacity .2s; }
.blast-bar.hidden { opacity:0;pointer-events:none; }
.bar-wrap { background:#f1f5f9;border-radius:4px;height:5px;overflow:hidden;min-width:60px; }
.bar-fill  { height:5px;border-radius:4px; }
.acct-badge { display:inline-flex;align-items:center;gap:.2rem;font-size:.6rem;font-weight:800;padding:.1rem .35rem;border-radius:20px;background:#eef2ff;color:#6366f1;margin-left:.35rem;vertical-align:middle; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
        <a href="{{ route('admin.crm.segments') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.75rem;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2">
                <div style="width:36px;height:36px;border-radius:10px;background:{{ $def['bg'] }};color:{{ $def['color'] }};display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
                    <i class="bi {{ $def['icon'] }}"></i>
                </div>
                <div>
                    <h5 class="fw-black mb-0" style="font-size:1.05rem;color:{{ $def['color'] }};">{{ $def['label'] }}</h5>
                    <div style="font-size:.72rem;color:#94a3b8;">{{ $def['desc'] }}</div>
                </div>
            </div>
        </div>
        <div style="font-size:.75rem;color:#64748b;text-align:right;">
            <div style="font-size:1.3rem;font-weight:900;color:#0f172a;">{{ $customers->count() }}</div>
            customer dalam segment ini
        </div>
    </div>

    {{-- Action hint --}}
    <div style="background:{{ $def['bg'] }};border:1.5px solid {{ $def['color'] }}22;border-radius:12px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.78rem;color:#334155;">
        <i class="bi bi-lightbulb me-1" style="color:{{ $def['color'] }};"></i>
        <strong>Saran action:</strong> {{ $def['action'] }}
    </div>

    @if($customers->isEmpty())
    <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;padding:3rem;text-align:center;color:#94a3b8;font-size:.85rem;">
        <i class="bi {{ $def['icon'] }}" style="font-size:2rem;display:block;margin-bottom:.75rem;color:{{ $def['color'] }};opacity:.4;"></i>
        Tidak ada customer dalam segment ini saat ini
    </div>
    @else

    {{-- Summary stats (5 cards) --}}
    @php
        $cnt        = $customers->count();
        $withPhone  = $customers->filter(fn($c) => $c->customer_phone)->count();
        $withAcct   = $customers->filter(fn($c) => $c->has_account)->count();
        $avgDays    = $cnt > 0 ? (int) $customers->avg('days_since') : 0;
    @endphp
    <div class="row g-2 mb-3">
        <div class="col-6 col-md">
            <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:12px;padding:.75rem 1rem;">
                <div style="font-size:.65rem;font-weight:800;text-transform:uppercase;color:#94a3b8;">Total Customer</div>
                <div style="font-size:1.4rem;font-weight:900;">{{ $cnt }}</div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:12px;padding:.75rem 1rem;">
                <div style="font-size:.65rem;font-weight:800;text-transform:uppercase;color:#94a3b8;">Total Revenue</div>
                <div style="font-size:1rem;font-weight:900;">Rp{{ number_format($customers->sum('total_spent')) }}</div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:12px;padding:.75rem 1rem;">
                <div style="font-size:.65rem;font-weight:800;text-transform:uppercase;color:#94a3b8;">Avg CLV</div>
                <div style="font-size:1rem;font-weight:900;">Rp{{ $cnt > 0 ? number_format($customers->avg('total_spent')) : 0 }}</div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:12px;padding:.75rem 1rem;">
                <div style="font-size:.65rem;font-weight:800;text-transform:uppercase;color:#94a3b8;">Avg Terakhir Order</div>
                <div style="font-size:1.4rem;font-weight:900;">{{ $avgDays }}<span style="font-size:.72rem;font-weight:600;color:#94a3b8;margin-left:.2rem;">hari</span></div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div style="background:#fff;border:1.5px solid {{ $withAcct > 0 ? '#6366f1' : '#e8ecf0' }};border-radius:12px;padding:.75rem 1rem;">
                <div style="font-size:.65rem;font-weight:800;text-transform:uppercase;color:#94a3b8;">Akun Terdaftar</div>
                <div style="font-size:1.4rem;font-weight:900;color:{{ $withAcct > 0 ? '#6366f1' : '#0f172a' }};">{{ $withAcct }}</div>
            </div>
        </div>
    </div>

    {{-- Table toolbar --}}
    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
        <input type="text" id="segSearch" placeholder="Cari nama / kota…"
               style="flex:1;min-width:180px;max-width:300px;font-size:.78rem;border:1.5px solid #e2e8f0;border-radius:10px;padding:.35rem .75rem;outline:none;"
               oninput="filterTable(this.value)">
        <button onclick="selectAllVisible()" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.75rem;">
            <i class="bi bi-check2-all me-1"></i>Pilih Semua
        </button>
        <button onclick="clearAll()" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.75rem;">
            Batal Pilih
        </button>
        <span id="visibleCount" style="font-size:.72rem;color:#94a3b8;margin-left:.25rem;">{{ $cnt }} customer</span>
    </div>

    {{-- Table --}}
    <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table class="table mb-0 crm-table" id="segTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" style="cursor:pointer;"></th>
                        <th>#</th>
                        <th>Customer</th>
                        <th>No. HP</th>
                        <th>Kota</th>
                        <th style="text-align:right;">Orders</th>
                        <th style="text-align:right;">CLV</th>
                        <th>Terakhir Order</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @php $maxSpent = $customers->max('total_spent'); @endphp
                @foreach($customers as $c)
                @php
                    $rowName = $c->customer_name ?? 'Kak';
                    $rowCity = $c->city ? strtolower($c->city) : '';
                @endphp
                <tr data-search="{{ strtolower($rowName . ' ' . $rowCity) }}">
                    <td>
                        @if($c->wa_phone)
                        <input type="checkbox" class="seg-cb"
                               value="{{ $c->wa_phone }}"
                               data-name="{{ $rowName }}"
                               style="cursor:pointer;">
                        @endif
                    </td>
                    <td style="color:#94a3b8;font-size:.72rem;">{{ $loop->index + 1 }}</td>
                    <td>
                        <div>
                            <a href="{{ route('admin.crm.customers.show', $c->customer_phone) }}"
                               style="font-size:.82rem;font-weight:700;color:#0f172a;text-decoration:none;">
                                {{ $c->customer_name ?? '—' }}
                            </a>
                            @if($c->has_account)
                            <span class="acct-badge"><i class="bi bi-person-check-fill"></i> Akun</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if($c->wa_phone)
                        <a href="https://wa.me/{{ $c->wa_phone }}" target="_blank"
                           style="font-size:.78rem;font-weight:700;color:#16a34a;text-decoration:none;">
                            <i class="bi bi-whatsapp me-1"></i>{{ $c->customer_phone }}
                        </a>
                        @else
                        <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td style="font-size:.78rem;color:#334155;text-transform:capitalize;">{{ $rowCity ?: '—' }}</td>
                    <td style="text-align:right;font-weight:800;">{{ $c->order_count }}×</td>
                    <td style="text-align:right;">
                        <div style="font-size:.8rem;font-weight:800;">Rp{{ number_format($c->total_spent) }}</div>
                        <div class="bar-wrap mt-1">
                            <div class="bar-fill" style="width:{{ $maxSpent > 0 ? round($c->total_spent/$maxSpent*100) : 0 }}%;background:{{ $def['color'] }};"></div>
                        </div>
                    </td>
                    <td style="font-size:.72rem;color:#64748b;white-space:nowrap;">
                        {{ \Carbon\Carbon::parse($c->last_order_at)->diffForHumans() }}<br>
                        <span style="font-size:.65rem;">{{ \Carbon\Carbon::parse($c->last_order_at)->format('d M Y') }}</span>
                    </td>
                    <td>
                        @if($c->wa_phone)
                        @php
                            $appUrl = config('app.url');
                            $msg    = str_replace('{name}', $rowName, $def['message']) . $appUrl;
                            $waUrl  = 'https://wa.me/' . $c->wa_phone . '?text=' . urlencode($msg);
                        @endphp
                        <a href="{{ $waUrl }}" target="_blank"
                           class="btn btn-sm" style="background:#25d366;color:#fff;border-radius:8px;font-size:.68rem;font-weight:700;padding:.2rem .5rem;">
                            <i class="bi bi-whatsapp"></i> WA
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Floating blast bar --}}
    <div class="blast-bar hidden" id="blastBar">
        <span><strong id="blastCount">0</strong> customer dipilih</span>
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
                    <div>
                        <h6 class="modal-title fw-bold mb-0">Blast WA — <span id="modalCount">0</span> Customer</h6>
                        <div style="font-size:.72rem;color:#94a3b8;margin-top:2px;">Segment: {{ $def['label'] }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                {{-- Editable message template --}}
                <div style="padding:.75rem 1rem;background:#f8fafc;border-bottom:1.5px solid #f1f5f9;">
                    <div style="font-size:.72rem;font-weight:700;color:#64748b;margin-bottom:.4rem;">
                        <i class="bi bi-pencil me-1"></i>Template Pesan (bisa diedit):
                    </div>
                    <textarea id="msgTemplate" rows="4"
                              style="width:100%;font-size:.78rem;border:1.5px solid #e2e8f0;border-radius:8px;padding:.5rem .75rem;resize:vertical;font-family:inherit;">{{ str_replace('{name}', '{name}', $def['message']) }}{{ config('app.url') }}</textarea>
                    <div style="font-size:.68rem;color:#94a3b8;margin-top:.3rem;">
                        <code>{name}</code> akan diganti otomatis dengan nama masing-masing customer
                    </div>
                </div>
                <div class="modal-body p-0">
                    <div id="blastList" style="max-height:50vh;overflow-y:auto;"></div>
                </div>
                <div class="modal-footer" style="border-top:1.5px solid #f1f5f9;gap:.5rem;">
                    <span style="font-size:.73rem;color:#94a3b8;flex:1;">Browser mungkin blokir popup — izinkan untuk buka semua sekaligus.</span>
                    <button onclick="openAllWa()" class="btn btn-sm fw-bold"
                            style="background:#25d366;color:#fff;border-radius:10px;font-size:.78rem;">
                        <i class="bi bi-whatsapp me-1"></i> Buka Semua WA
                    </button>
                </div>
            </div>
        </div>
    </div>

    @endif

</div>

@push('scripts')
<script>
const blastBar   = document.getElementById('blastBar');
const blastCount = document.getElementById('blastCount');
const modalCount = document.getElementById('modalCount');
const visibleCnt = document.getElementById('visibleCount');

// ── Select all (header checkbox) ──────────────────────────────────────────
document.getElementById('selectAll')?.addEventListener('change', function () {
    document.querySelectorAll('#segTable tbody tr:not(.hidden-row) .seg-cb').forEach(cb => cb.checked = this.checked);
    updateBar();
});

document.querySelectorAll('.seg-cb').forEach(cb => cb.addEventListener('change', updateBar));

function updateBar() {
    const n = document.querySelectorAll('.seg-cb:checked').length;
    blastCount.textContent = n;
    blastBar.classList.toggle('hidden', n === 0);
}

function clearAll() {
    document.querySelectorAll('.seg-cb, #selectAll').forEach(cb => cb.checked = false);
    blastBar.classList.add('hidden');
}

function selectAllVisible() {
    document.querySelectorAll('#segTable tbody tr:not(.hidden-row) .seg-cb').forEach(cb => cb.checked = true);
    updateBar();
}

// ── Search filter ─────────────────────────────────────────────────────────
function filterTable(q) {
    const term = q.toLowerCase().trim();
    const rows = document.querySelectorAll('#segTable tbody tr');
    let visible = 0;
    rows.forEach(tr => {
        const hay = tr.dataset.search || '';
        const show = !term || hay.includes(term);
        tr.classList.toggle('hidden-row', !show);
        if (show) visible++;
    });
    visibleCnt.textContent = visible + ' customer';
    // Deselect hidden rows
    document.querySelectorAll('#segTable tbody tr.hidden-row .seg-cb').forEach(cb => cb.checked = false);
    updateBar();
}

// ── WA blast ──────────────────────────────────────────────────────────────
// cb.value is already wa_phone (628xxx) — no normalisation needed
function buildWaUrl(waPhone, name) {
    const template = document.getElementById('msgTemplate')?.value || '';
    const msg = template.replace(/\{name\}/g, name || 'Kak');
    return `https://wa.me/${waPhone}?text=${encodeURIComponent(msg)}`;
}

function openBlastModal() {
    const cbs = document.querySelectorAll('.seg-cb:checked');
    modalCount.textContent = cbs.length;
    let html = '';
    cbs.forEach(cb => {
        const waPhone = cb.value;
        const name    = cb.dataset.name || 'Kak';
        const url     = buildWaUrl(waPhone, name);
        html += `
        <div style="display:flex;align-items:center;gap:.75rem;padding:.6rem 1.25rem;border-bottom:1px solid #f8fafc;">
            <div style="flex:1;font-size:.78rem;">
                <div class="fw-bold">${name}</div>
                <div style="color:#64748b;">+${waPhone}</div>
            </div>
            <a href="${url}" target="_blank" onclick="markSent(this)"
               class="btn btn-sm" style="background:#25d366;color:#fff;border-radius:8px;font-size:.72rem;padding:.2rem .6rem;white-space:nowrap;">
                <i class="bi bi-whatsapp"></i> Blast
            </a>
        </div>`;
    });
    document.getElementById('blastList').innerHTML = html || '<div style="padding:2rem;text-align:center;color:#94a3b8;">Tidak ada yang dipilih</div>';
    new bootstrap.Modal(document.getElementById('blastModal')).show();
}

function markSent(el) {
    setTimeout(() => { el.style.background = '#94a3b8'; el.textContent = '✓ Terkirim'; }, 500);
}

function openAllWa() {
    const cbs = document.querySelectorAll('.seg-cb:checked');
    cbs.forEach((cb, i) => {
        setTimeout(() => {
            window.open(buildWaUrl(cb.value, cb.dataset.name), '_blank');
        }, i * 800);
    });
}
</script>
@endpush

@endsection
