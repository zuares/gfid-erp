@extends('layouts.app')
@section('title', 'Prospects – Cart Abandonment')

@push('head')
<style>
.tab-pill { border: 1.5px solid #e2e8f0; border-radius: 999px; padding: .25rem .7rem; font-size: .72rem; font-weight: 700; text-decoration: none; color: #64748b; background: #fff; white-space: nowrap; }
.tab-pill.active, .tab-pill:hover { background: #0f172a; color: #fff; border-color: #0f172a; }
.crm-table th { font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; border-bottom: 1.5px solid #e8ecf0; padding: .5rem .75rem; background: #f8fafc; white-space: nowrap; }
.crm-table td { font-size: .8rem; vertical-align: middle; padding: .55rem .75rem; border-bottom: 1px solid #f1f5f9; }
.crm-table tr:last-child td { border-bottom: 0; }
.prospect-tag { font-size: .65rem; font-weight: 800; padding: .15rem .45rem; border-radius: 999px; background: #fef3c7; color: #92400e; }
.blast-bar { position:fixed;bottom:1.25rem;left:50%;transform:translateX(-50%);background:#0f172a;color:#fff;border-radius:16px;padding:.65rem 1.25rem;display:flex;align-items:center;gap:1rem;z-index:1050;box-shadow:0 4px 24px rgba(0,0,0,.25);white-space:nowrap;transition:opacity .2s; }
.blast-bar.hidden { opacity:0;pointer-events:none; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-black mb-0" style="font-size:1.05rem;">Cart Abandonment</h5>
            <div style="font-size:.75rem;color:#94a3b8;margin-top:2px;">Visitor yang add to cart tapi belum order</div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('admin.crm.dashboard') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.75rem;">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
            <a href="{{ route('admin.crm.prospects.export', array_merge(request()->query())) }}"
               class="btn btn-sm btn-success fw-bold" style="border-radius:10px;font-size:.75rem;">
                <i class="bi bi-download me-1"></i> Export CSV (HP saja)
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="d-flex gap-2 flex-wrap align-items-center mb-3">
        {{-- Period --}}
        @foreach([1=>'Hari ini', 7=>'7 Hari', 30=>'30 Hari', 90=>'90 Hari'] as $d => $label)
        <a href="{{ route('admin.crm.prospects', array_merge(request()->query(), ['days' => $d])) }}"
           class="tab-pill {{ $days == $d ? 'active' : '' }}">{{ $label }}</a>
        @endforeach

        <div class="ms-auto">
            <form method="GET" action="{{ route('admin.crm.prospects') }}" class="d-flex align-items-center gap-2">
                <input type="hidden" name="days" value="{{ $days }}">
                <div class="form-check form-switch mb-0 d-flex align-items-center gap-2" style="font-size:.78rem;">
                    <input class="form-check-input" type="checkbox" role="switch" id="onlyId" name="only_identified" value="1"
                           {{ $onlyId ? 'checked' : '' }} onchange="this.form.submit()">
                    <label class="form-check-label fw-bold" for="onlyId">Yang ada nomor HP saja</label>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary --}}
    <div class="d-flex gap-3 mb-3">
        <div style="font-size:.8rem;color:#64748b;">
            <span class="fw-bold text-dark">{{ $prospects->count() }}</span> prospects ditemukan
        </div>
        <div style="font-size:.8rem;color:#64748b;">
            <span class="fw-bold text-dark">{{ $prospects->filter(fn($p) => $p->phone)->count() }}</span> sudah teridentifikasi (ada HP)
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
                        <th>Add to Cart</th>
                        <th>Terakhir Aktif</th>
                        <th>Blast WA</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prospects as $p)
                    <tr>
                        <td>
                            @if($p->phone)
                            <input type="checkbox" class="prospect-cb" value="{{ $p->phone }}"
                                   data-name="{{ $p->name ?? 'Kak' }}"
                                   data-product="{{ $p->last_product }}"
                                   style="cursor:pointer;">
                            @endif
                        </td>
                        <td style="color:#94a3b8;font-size:.72rem;">{{ $loop->index + 1 }}</td>
                        <td>
                            @if($p->name)
                            <div class="fw-bold">{{ $p->name }}</div>
                            <div style="font-size:.68rem;font-family:monospace;color:#94a3b8;">{{ substr($p->visitor_token, 0, 16) }}…</div>
                            @else
                            <div style="font-size:.72rem;font-family:monospace;color:#94a3b8;">{{ substr($p->visitor_token, 0, 20) }}…</div>
                            <span class="prospect-tag">Anonymous</span>
                            @endif
                        </td>
                        <td>
                            @if($p->phone)
                            <a href="https://wa.me/62{{ ltrim($p->phone, '0') }}" target="_blank"
                               style="font-size:.8rem;font-weight:700;color:#16a34a;text-decoration:none;">
                                <i class="bi bi-whatsapp me-1"></i>{{ $p->phone }}
                            </a>
                            @else
                            <span style="color:#cbd5e1;font-size:.75rem;">—</span>
                            @endif
                        </td>
                        <td style="font-size:.78rem;color:#334155;text-transform:capitalize;">{{ $p->city ? strtolower($p->city) : '—' }}</td>
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
                        <td style="text-align:center;">
                            <span style="font-size:.75rem;font-weight:800;color:#0f172a;">{{ $p->cart_count }}×</span>
                        </td>
                        <td style="font-size:.72rem;color:#64748b;white-space:nowrap;">
                            {{ \Carbon\Carbon::parse($p->last_cart_at)->diffForHumans() }}<br>
                            <span style="font-size:.68rem;">{{ \Carbon\Carbon::parse($p->last_cart_at)->format('d M H:i') }}</span>
                        </td>
                        <td>
                            @if($p->phone)
                            @php
                                $wa = config('app.wa_number', '6281234567890');
                                $prodText = $p->last_product !== '-' ? "tertarik dengan produk *{$p->last_product}*" : "sudah memasukkan produk ke keranjang";
                                $msg = urlencode("Halo {$p->name}, kami dari Garuda Fashion Indonesia 👋\n\nKami lihat kamu {$prodText} tapi belum menyelesaikan pembelian. Apakah ada yang bisa kami bantu? 😊\n\nKlik link berikut untuk lanjut belanja: " . config('app.url'));
                            @endphp
                            <a href="https://wa.me/62{{ ltrim($p->phone, '0') }}?text={{ $msg }}" target="_blank"
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
                        <td colspan="8" style="text-align:center;padding:2.5rem;color:#94a3b8;font-size:.85rem;">
                            Tidak ada prospect untuk periode ini
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
                    <span style="font-size:.75rem;color:#94a3b8;flex:1;">Klik tombol WA di setiap baris untuk buka chat. Browser mungkin memblokir popup — izinkan untuk buka semua sekaligus.</span>
                    <button onclick="openAllWa()" class="btn btn-sm fw-bold"
                            style="background:#25d366;color:#fff;border-radius:10px;font-size:.78rem;">
                        <i class="bi bi-whatsapp me-1"></i> Buka Semua WA
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3" style="font-size:.72rem;color:#94a3b8;">
        <i class="bi bi-info-circle me-1"></i>
        Hanya visitor yang add to cart tanpa melanjutkan ke order dalam periode yang dipilih. CSV export hanya menyertakan yang sudah ada nomor HP-nya.
    </div>

</div>
@push('scripts')
<script>
const appUrl  = '{{ config('app.url') }}';
const blastBar = document.getElementById('blastBar');
const blastCount = document.getElementById('blastCount');
const modalCount = document.getElementById('modalCount');

// Select All
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.prospect-cb').forEach(cb => cb.checked = this.checked);
    updateBar();
});

document.querySelectorAll('.prospect-cb').forEach(cb => {
    cb.addEventListener('change', updateBar);
});

function updateBar() {
    const selected = document.querySelectorAll('.prospect-cb:checked').length;
    blastCount.textContent = selected;
    blastBar.classList.toggle('hidden', selected === 0);
}

function clearAll() {
    document.querySelectorAll('.prospect-cb, #selectAll').forEach(cb => cb.checked = false);
    blastBar.classList.add('hidden');
}

function buildWaUrl(phone, name, product) {
    const sapaan = name && name !== 'Anonymous' ? name : 'Kak';
    const prodText = product && product !== '-'
        ? `tertarik dengan produk *${product}*`
        : 'sudah memasukkan produk ke keranjang';
    const msg = `Halo ${sapaan}, kami dari Garuda Fashion Indonesia 👋\n\nKami lihat kamu ${prodText} tapi belum menyelesaikan pembelian. Apakah ada yang bisa kami bantu? 😊\n\nKlik link berikut untuk lanjut belanja: ${appUrl}`;
    const num = phone.replace(/^0/, '62');
    return `https://wa.me/${num}?text=${encodeURIComponent(msg)}`;
}

function openBlastModal() {
    const cbs = document.querySelectorAll('.prospect-cb:checked');
    modalCount.textContent = cbs.length;

    let html = '';
    cbs.forEach((cb, i) => {
        const phone   = cb.value;
        const name    = cb.dataset.name || 'Kak';
        const product = cb.dataset.product || '-';
        const waUrl   = buildWaUrl(phone, name, product);
        html += `
        <div style="display:flex;align-items:center;gap:.75rem;padding:.65rem 1.25rem;border-bottom:1px solid #f8fafc;">
            <div style="font-size:.75rem;flex:1;min-width:0;">
                <div class="fw-bold">${name !== 'Kak' ? name : '—'}</div>
                <div style="color:#64748b;">${phone}</div>
            </div>
            <div style="font-size:.72rem;color:#94a3b8;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${product}</div>
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
    setTimeout(() => {
        el.style.background = '#94a3b8';
        el.textContent = '✓ Terkirim';
    }, 500);
}

let waUrls = [];
function openAllWa() {
    const cbs = document.querySelectorAll('.prospect-cb:checked');
    cbs.forEach((cb, i) => {
        setTimeout(() => {
            window.open(buildWaUrl(cb.value, cb.dataset.name, cb.dataset.product), '_blank');
        }, i * 800); // delay 800ms antar tab supaya tidak ke-block browser
    });
}
</script>
@endpush

@endsection
