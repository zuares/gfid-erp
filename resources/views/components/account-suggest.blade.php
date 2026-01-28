@props([
    'name',
    'value' => '',
    'display' => '',
    'placeholder' => 'Kode / nama akun',
    'required' => false,
    'minChars' => 1,
    'maxResults' => 5,
])

@php
    use Illuminate\Support\Str;
    $uid = 'acc-suggest-'.Str::random(6);
@endphp

<div class="acc-suggest-wrap"
     data-min="{{ $minChars }}"
     data-max="{{ $maxResults }}"
     data-required="{{ $required ? 1 : 0 }}">

    <input type="text"
           id="{{ $uid }}"
           class="form-control form-control-sm js-acc-input"
           value="{{ strtoupper($display) }}"
           placeholder="{{ $placeholder }}"
           autocomplete="off">

    <input type="hidden"
           name="{{ $name }}"
           value="{{ $value }}"
           class="js-acc-id">

    <div class="acc-suggest-dropdown"></div>
</div>

@once
@push('head')
<style>
.acc-suggest-wrap{position:relative}
.acc-suggest-dropdown{
    position:absolute;left:0;right:0;top:calc(100% + 4px);
    background:var(--card,#fff);
    border:1px solid rgba(148,163,184,.35);
    border-radius:14px;
    max-height:240px;overflow-y:auto;
    z-index:9999;
    box-shadow:0 18px 44px rgba(15,23,42,.12);
    display:none;
}
.acc-opt{padding:.55rem .65rem;cursor:pointer}
.acc-opt:hover,.acc-opt.is-active{background:rgba(59,130,246,.10)}
.acc-opt-code{font-weight:700}
.acc-opt-name{font-size:.78rem;color:#6b7280}
.js-acc-input.is-invalid{border-color:#dc3545}
.table-responsive, table td, table th {overflow: visible !important}
</style>
@endpush

@push('scripts')
<script>
(function(){
    function init(scope=document){
        scope.querySelectorAll('.acc-suggest-wrap:not([data-init])').forEach(setup);
    }

    function setup(wrap){
        wrap.dataset.init = 1;

        const input  = wrap.querySelector('.js-acc-input');
        const hidden = wrap.querySelector('.js-acc-id');
        const drop   = wrap.querySelector('.acc-suggest-dropdown');

        const minChars = parseInt(wrap.dataset.min || 1);
        const maxRes   = parseInt(wrap.dataset.max || 5);
        const required = wrap.dataset.required === '1';

        let timer=null, items=[], idx=-1, selecting=false;
        let lastFetchAbort=null;

        const isOpen = ()=> drop.style.display === 'block';
        const show = ()=> drop.style.display='block';
        const hide = ()=>{ drop.style.display='none'; idx=-1; };

        const fire = el => {
            el.dispatchEvent(new Event('change',{bubbles:true}));
            el.dispatchEvent(new Event('input',{bubbles:true}));
        };

        function highlight(){
            const opts = drop.querySelectorAll('.acc-opt');
            opts.forEach((o,i)=>o.classList.toggle('is-active', i===idx));
            if (idx>=0) opts[idx]?.scrollIntoView({block:'nearest'});
        }

        function render(list){
            drop.innerHTML='';
            if(!list.length){
                drop.innerHTML='<div class="p-2 text-muted">Tidak ada hasil</div>';
                show(); return;
            }

            items = list.slice(0, maxRes);
            idx = 0; // default highlight pertama biar TAB langsung pilih
            items.forEach((a,i)=>{
                const opt = document.createElement('div');
                opt.className='acc-opt' + (i===0 ? ' is-active' : '');
                opt.innerHTML=`
                    <div class="acc-opt-code">${(a.code||'').toUpperCase()}</div>
                    <div class="acc-opt-name">${a.name||''}</div>
                `;
                opt.onmousedown = e => { e.preventDefault(); select(a, true); };
                drop.appendChild(opt);
            });
            show();
        }

        function select(a, moveNext){
            selecting = true;

            input.value = ((a.code||'')+' — '+(a.name||'')).toUpperCase();
            hidden.value = a.id || '';
            input.classList.remove('is-invalid');
            fire(hidden);

            hide();

            setTimeout(()=>{
                selecting = false;
                if (moveNext) focusNextField();
            }, 0);
        }

        // Fokus ke input berikutnya dalam baris table
        function focusNextField(){
            // cari parent TR terdekat, lalu fokus ke kolom debit / next input
            const tr = wrap.closest('tr');
            if(!tr) return;

            // urutan fokus yang umum di form kamu:
            // account -> debit -> credit -> note -> (opsional) next row account
            const debit  = tr.querySelector('.js-debit');
            const credit = tr.querySelector('.js-credit');
            const note   = tr.querySelector('input[name="line_note[]"]');

            // cari target pertama yang masih kosong / default:
            if (debit) { debit.focus(); debit.select?.(); return; }
            if (credit){ credit.focus(); credit.select?.(); return; }
            if (note)  { note.focus(); note.select?.(); return; }

            // fallback: cari input focusable berikutnya dalam form
            const form = wrap.closest('form');
            if(!form) return;
            const focusables = Array.from(form.querySelectorAll('input,select,textarea,button,a[href]'))
                .filter(el => !el.disabled && el.tabIndex !== -1 && el.offsetParent !== null);

            const i = focusables.indexOf(input);
            if(i>=0 && focusables[i+1]) focusables[i+1].focus();
        }

        function fetchData(q, force){
            q=(q||'').trim();
            if(!force && q.length < minChars){ hide(); return; }

            drop.innerHTML='<div class="p-2 text-muted">Memuat...</div>';
            show();

            // Abort previous
            if (lastFetchAbort) lastFetchAbort.abort();
            lastFetchAbort = new AbortController();

            fetch(`/api/v1/accounts/suggest?q=${encodeURIComponent(q)}`,{
                headers:{'Accept':'application/json'},
                signal: lastFetchAbort.signal
            })
            .then(r=>{
                if(!r.ok) throw new Error('HTTP '+r.status);
                return r.json();
            })
            .then(j=>render(j.data||[]))
            .catch(()=>render([]));
        }

        // ============= EVENTS =============
        input.addEventListener('input',()=>{
            const up = input.value.toUpperCase();
            if(up !== input.value) input.value = up;

            if(!selecting){
                hidden.value='';
                fire(hidden);
                if(required) input.classList.add('is-invalid');
            }

            clearTimeout(timer);
            timer = setTimeout(()=>fetchData(input.value,false),180);
        });

        input.addEventListener('focus',()=>{
            input.select?.();
            fetchData(input.value,true);
        });

        input.addEventListener('keydown', (e)=>{
            const opts = drop.querySelectorAll('.acc-opt');

            // SHIFT+TAB => biarkan normal (balik ke field sebelumnya)
            if (e.key === 'Tab' && e.shiftKey) {
                hide();
                return;
            }

            // TAB => kalau dropdown ada item, pilih yang aktif lalu pindah field
            if (e.key === 'Tab') {
                if (isOpen() && items.length) {
                    e.preventDefault();
                    const picked = items[idx >= 0 ? idx : 0];
                    select(picked, true);
                    return;
                }
                // kalau dropdown tidak open / tidak ada item, biarkan tab normal
                hide();
                return;
            }

            // ENTER => pilih aktif (tetap di field berikutnya juga biar cepat)
            if (e.key === 'Enter') {
                if (isOpen() && items.length) {
                    e.preventDefault();
                    const picked = items[idx >= 0 ? idx : 0];
                    select(picked, true);
                }
                return;
            }

            if (!opts.length) return;

            if (e.key === 'ArrowDown') { e.preventDefault(); idx = Math.min(opts.length-1, idx+1); highlight(); }
            if (e.key === 'ArrowUp')   { e.preventDefault(); idx = Math.max(0, idx-1); highlight(); }
            if (e.key === 'Escape')    { hide(); }
        });

        input.addEventListener('blur',()=>{
            // Delay biar klik option via mousedown tidak ketutup duluan
            setTimeout(()=>{
                if(required && !hidden.value) input.classList.add('is-invalid');
                hide();
            }, 120);
        });

        document.addEventListener('mousedown',e=>{
            if(!wrap.contains(e.target)) hide();
        });
    }

    // AUTO BOOTSTRAP (NO PARENT CODE)
    document.addEventListener('DOMContentLoaded', () => init());

    const observer = new MutationObserver(muts=>{
        muts.forEach(m=>{
            m.addedNodes.forEach(n=>{
                if(n.nodeType === 1){
                    init(n);
                }
            });
        });
    });
    observer.observe(document.body, { childList:true, subtree:true });
})();
</script>
@endpush
@endonce
