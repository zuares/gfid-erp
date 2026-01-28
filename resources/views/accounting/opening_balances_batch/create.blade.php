@extends('layouts.app')

@section('title', 'Opening Balance (Batch)')

@section('content')
<div class="container py-3" style="max-width:1100px">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-0">Opening Balance (Batch)</h1>
            <div class="small text-muted">
                Saldo awal multi-akun (Total Debit harus sama dengan Total Credit)
            </div>
        </div>
        <a href="{{ route('accounting.opening-balances.index') }}"
           class="btn btn-outline-secondary btn-sm">
            &larr; Kembali
        </a>
    </div>

    {{-- ERROR --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('accounting.opening-balances.store') }}"
          id="ob-form">
        @csrf

        {{-- INFO --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date"
                               name="date"
                               class="form-control"
                               value="{{ old('date', now()->toDateString()) }}"
                               required>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Deskripsi</label>
                        <input type="text"
                               name="description"
                               class="form-control"
                               value="{{ old('description') }}"
                               placeholder="Opening balance awal sistem">
                    </div>
                </div>
            </div>
        </div>

        {{-- LINES --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Detail Akun</span>
                <button type="button"
                        class="btn btn-sm btn-outline-primary"
                        id="btn-add">
                    + Tambah Baris
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:42%">Akun</th>
                            <th style="width:18%" class="text-end">Debit</th>
                            <th style="width:18%" class="text-end">Credit</th>
                            <th style="width:18%">Catatan</th>
                            <th style="width:4%"></th>
                        </tr>
                    </thead>

                    <tbody id="lines-body">
                        @php
                            $oldAcc = old('account_id', []);
                            $oldD   = old('debit', []);
                            $oldC   = old('credit', []);
                            $oldN   = old('line_note', []);
                            $useOld = is_array($oldAcc) && count($oldAcc);
                        @endphp

                        @if($useOld)
                            @foreach($oldAcc as $i => $aid)
                                <tr>
                                    <td>
                                        <x-account-suggest
                                            name="account_id[]"
                                            :value="$aid"
                                            :display="$accounts->firstWhere('id',(int)$aid)
                                                ? $accounts->firstWhere('id',(int)$aid)->code.' — '.$accounts->firstWhere('id',(int)$aid)->name
                                                : ''"
                                            :required="true"
                                        />
                                    </td>
                                    <td>
                                        <input name="debit[]"
                                               class="form-control form-control-sm text-end js-debit"
                                               value="{{ $oldD[$i] ?? 0 }}">
                                    </td>
                                    <td>
                                        <input name="credit[]"
                                               class="form-control form-control-sm text-end js-credit"
                                               value="{{ $oldC[$i] ?? 0 }}">
                                    </td>
                                    <td>
                                        <input name="line_note[]"
                                               class="form-control form-control-sm"
                                               value="{{ $oldN[$i] ?? '' }}"
                                               placeholder="-">
                                    </td>
                                    <td class="text-center">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger js-del">
                                            &times;
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            @for($i=0;$i<2;$i++)
                                <tr>
                                    <td>
                                        <x-account-suggest
                                            name="account_id[]"
                                            :required="true"
                                        />
                                    </td>
                                    <td>
                                        <input name="debit[]"
                                               class="form-control form-control-sm text-end js-debit"
                                               value="0">
                                    </td>
                                    <td>
                                        <input name="credit[]"
                                               class="form-control form-control-sm text-end js-credit"
                                               value="0">
                                    </td>
                                    <td>
                                        <input name="line_note[]"
                                               class="form-control form-control-sm"
                                               placeholder="-">
                                    </td>
                                    <td class="text-center">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger js-del">
                                            &times;
                                        </button>
                                    </td>
                                </tr>
                            @endfor
                        @endif
                    </tbody>

                    <tfoot class="table-light">
                        <tr class="fw-semibold">
                            <td class="text-end">Total</td>
                            <td class="text-end" id="sum-debit">0</td>
                            <td class="text-end" id="sum-credit">0</td>
                            <td colspan="2" id="balance-indicator" class="text-muted">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- ACTION --}}
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('accounting.opening-balances.index') }}"
               class="btn btn-outline-secondary">
                Batal
            </a>
            <button class="btn btn-primary">
                Post Opening
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const body = document.getElementById('lines-body');
    const btnAdd = document.getElementById('btn-add');
    const sumDebitEl = document.getElementById('sum-debit');
    const sumCreditEl = document.getElementById('sum-credit');
    const balEl = document.getElementById('balance-indicator');
    const form = document.getElementById('ob-form');

    const toNum = v => {
        v = (v ?? '').toString().replace(/,/g,'.').replace(/[^\d.\-]/g,'');
        const n = parseFloat(v || '0');
        return isNaN(n) ? 0 : n;
    };
    const fmt = n => new Intl.NumberFormat('id-ID').format(n);

    function recalc(){
        let d=0, c=0;
        body.querySelectorAll('tr').forEach(tr=>{
            d += toNum(tr.querySelector('.js-debit')?.value);
            c += toNum(tr.querySelector('.js-credit')?.value);
        });
        sumDebitEl.textContent = fmt(d);
        sumCreditEl.textContent = fmt(c);

        const ok = d > 0 && Math.round(d*100) === Math.round(c*100);
        balEl.textContent = ok ? 'Balance OK' : 'Tidak balance';
        balEl.className = ok ? 'text-success' : 'text-danger';
        return ok;
    }

    function bindRow(tr){
        tr.querySelectorAll('.js-debit,.js-credit').forEach(inp=>{
            inp.addEventListener('input',()=>{
                if (inp.classList.contains('js-debit') && toNum(inp.value)>0)
                    tr.querySelector('.js-credit').value='0';
                if (inp.classList.contains('js-credit') && toNum(inp.value)>0)
                    tr.querySelector('.js-debit').value='0';
                recalc();
            });
        });

        tr.querySelector('.js-del')?.addEventListener('click',()=>{
            const rows = body.querySelectorAll('tr');
            if (rows.length>2) tr.remove();
            else {
                tr.querySelectorAll('input').forEach(i=>i.value='0');
                tr.querySelectorAll('.js-acc-input,.js-acc-id').forEach(i=>i.value='');
            }
            recalc();
        });
    }

    body.querySelectorAll('tr').forEach(bindRow);

    btnAdd.addEventListener('click',()=>{
        const first = body.querySelector('tr');
        if(!first) return;

        const clone = first.cloneNode(true);
        clone.querySelectorAll('input').forEach(i=>{
            if(i.classList.contains('js-debit') || i.classList.contains('js-credit')) i.value='0';
            else i.value='';
        });
        clone.querySelectorAll('.acc-suggest-wrap').forEach(w=>w.removeAttribute('data-init'));

        body.appendChild(clone);
        bindRow(clone);
        window.initAccountSuggest?.(clone);
        recalc();
    });

    form.addEventListener('submit',e=>{
        if(!recalc()){
            e.preventDefault();
            alert('Total Debit harus sama dengan Total Credit dan tidak boleh 0.');
        }
    });

    recalc();
})();
</script>
@endpush

