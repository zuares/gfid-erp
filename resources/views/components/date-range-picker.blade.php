@props([
    'dateFrom' => request('date_from', ''),
    'dateTo' => request('date_to', ''),
    'period' => request('period', 'all'),
    'formId' => 'filterForm',
    'nameFrom' => 'date_from',
    'nameTo' => 'date_to',
    'namePeriod' => 'period',
])

<input type="hidden" name="{{ $nameFrom }}" id="hid-from" value="{{ $dateFrom }}" data-gf-date="off">
<input type="hidden" name="{{ $nameTo }}" id="hid-to" value="{{ $dateTo }}" data-gf-date="off">
<input type="hidden" name="{{ $namePeriod }}" id="hid-period" value="{{ $period }}">

<div class="date-section">
    <div class="ds-presets">
        <button type="button" class="ds-preset-btn {{ $period==='today' ? 'active':'' }}"
            data-period="today">Hari ini</button>
        <button type="button" class="ds-preset-btn {{ $period==='week' ? 'active':'' }}"
            data-period="week">Minggu ini</button>
        <button type="button" class="ds-preset-btn {{ $period==='month' ? 'active':'' }}"
            data-period="month">Bulan ini</button>
    </div>
    <div class="ds-divider"></div>
    <div style="display: flex; align-items: center; padding-left: .65rem; color: #94a3b8;">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
    </div>
    <input type="text" id="inp-date" class="f-input rts-date-picker" placeholder="Pilih tanggal…" readonly autocomplete="off" data-gf-date="off">
    @if($dateFrom || $dateTo || $period !== 'all')
        <button type="button" class="ds-clear" id="btn-clear-date" title="Hapus filter tanggal">✕</button>
    @endif
</div>

@once
@push('head')
<style>
    /* === Date Range Component CSS === */
    .date-section{
        display:inline-flex;
        align-items:center;
        background:var(--card);
        border:1px solid var(--border);
        border-radius:7px;
        padding:0;
        height:32px;
        overflow:hidden;
        min-width: fit-content;
    }
    .ds-presets{
        display:flex;
        align-items:center;
    }
    .ds-preset-btn{
        background:transparent;
        border:0;
        color:inherit;
        font-size:.7rem!important;
        font-weight:600!important;
        padding:0 .5rem;
        height:32px!important;
        cursor:pointer;
        opacity:.72!important;
        transition:all .15s;
    }
    .ds-preset-btn:hover{ opacity:1!important; background:rgba(100,116,139,.1); }
    .ds-preset-btn.active{
        color:var(--primary);
        opacity:1!important;
        background:rgba(var(--primary-rgb, 59,130,246),.1);
    }
    .ds-divider{
        width:1px;
        height:18px;
        background:var(--border);
        margin:0 .2rem;
    }
    .rts-date-picker.flatpickr-input{
        width: 170px !important; flex: 1;
        min-width: 170px !important; flex: 1;
        height:32px!important;
        border:0!important;
        background:transparent!important;
        border-radius:0!important;
        padding:0 .58rem!important;
        font-size:.8rem!important;
        cursor:pointer!important;
    }
    .rts-date-picker.flatpickr-input:focus{
        box-shadow:none!important;
    }
    .ds-clear{
        background:transparent;
        border:0;
        color:var(--danger, #ef4444);
        padding:0 .5rem;
        height:32px;
        cursor:pointer;
        opacity:.7;
        font-size:.8rem;
    }
    .ds-clear:hover{ opacity:1; }

    @media(max-width:767.98px){
        .date-section{
            width:100%!important;
            min-width:0!important;
            flex-wrap: wrap;
            height: auto;
        }
        .ds-presets {
            width: 100%;
            border-bottom: 1px solid var(--border);
        }
        .ds-preset-btn {
            flex: 1;
        }
        .ds-divider {
            display: none;
        }
        .rts-date-picker.flatpickr-input{
            width:100%!important;
            min-width:0!important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const hidFrom   = document.getElementById('hid-from');
    const hidTo     = document.getElementById('hid-to');
    const hidPeriod = document.getElementById('hid-period');
    const form      = document.getElementById('{{ $formId }}');
    
    if (!form || !hidFrom || !hidTo || !hidPeriod) return;

    function submitDate({ from='', to='', period='all' } = {}) {
        hidFrom.value   = from;
        hidTo.value     = to;
        hidPeriod.value = period;
        form.submit();
    }

    try {
        const fp = flatpickr('#inp-date', {
            mode: 'range',
            dateFormat: 'j M Y',
            locale: 'id',
            altInput: false,
            defaultDate: [
                hidFrom.value ? flatpickr.parseDate(hidFrom.value, 'Y-m-d') : null,
                hidTo.value ? flatpickr.parseDate(hidTo.value, 'Y-m-d') : null
            ].filter(Boolean),
            onClose(dates) {
                if (dates.length === 1) {
                    const d = flatpickr.formatDate(dates[0], 'Y-m-d');
                    submitDate({ from: d, to: d });
                } else if (dates.length === 2) {
                    submitDate({
                        from: flatpickr.formatDate(dates[0], 'Y-m-d'),
                        to:   flatpickr.formatDate(dates[1], 'Y-m-d'),
                    });
                }
            },
        });
    } catch (e) {
        console.error('Flatpickr init failed:', e);
    }

    document.querySelectorAll('.ds-preset-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            submitDate({ period: btn.dataset.period });
        });
    });

    document.getElementById('btn-clear-date')?.addEventListener('click', () => {
        submitDate({ period: 'all' });
    });

    const periodNow = hidPeriod.value;
    if (periodNow && periodNow !== 'all') {
        const labels = { today: 'Hari ini', week: 'Minggu ini', month: 'Bulan ini' };
        const pickerInput = document.getElementById('inp-date');
        if (pickerInput) pickerInput.value = labels[periodNow] || '';
    }
});
</script>
@endpush
@endonce
