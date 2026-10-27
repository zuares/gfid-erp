@extends('layouts.app')

@section('title', 'Akuntansi Baru • Buat Jurnal')

@push('head')
    <style>
        .new-accounting-btn { display:inline-flex; align-items:center; justify-content:center; min-height:38px; padding:.45rem .85rem; border:1px solid #dbe3ee; border-radius:10px; background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:700; }
        .new-accounting-btn:hover { background:#f8fafc; color:#0f172a; }
        .new-accounting-btn.primary { background:#0f172a; border-color:#0f172a; color:#fff; }
        .new-accounting-btn.primary:hover { background:#1e293b; color:#fff; }
        .journal-line-grid { display:grid; grid-template-columns:minmax(240px, 1fr) 180px 180px; gap:.5rem; align-items:end; }
        @media(max-width:768px) { .journal-line-grid { grid-template-columns:1fr; } }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
        <div><h4 class="mb-1">Akuntansi Baru — Buat Jurnal</h4><div class="text-muted small">Jurnal harus seimbang sebelum bisa diposting.</div></div>
        <a class="new-accounting-btn" href="{{ route('standalone-accounting.journals.index') }}">Kembali ke Jurnal</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('standalone-accounting.journals.store') }}">
                @csrf
                <div class="row g-3 mb-4">
                    <div class="col-md-3"><label class="form-label" for="date">Tanggal</label><input id="date" name="date" type="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" required></div>
                    <div class="col-md-3"><label class="form-label" for="reference">Referensi</label><input id="reference" name="reference" class="form-control" value="{{ old('reference') }}" placeholder="JV-0001"></div>
                    <div class="col-md-6"><label class="form-label" for="description">Keterangan</label><input id="description" name="description" class="form-control" value="{{ old('description') }}" placeholder="Saldo awal kas"></div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">Baris Jurnal</h6><span class="text-muted small">Isi debit atau kredit pada setiap baris.</span></div>
                <div id="journal-lines" class="d-grid gap-2">
                    @for ($i = 0; $i < 4; $i++)
                        <div class="journal-line-grid journal-line">
                            <div><label class="form-label small">Akun</label><select name="lines[{{ $i }}][account_id]" class="form-select"><option value="">Pilih akun</option>@foreach ($accounts as $account)<option value="{{ $account->id }}" @selected(old("lines.$i.account_id") == $account->id)>{{ $account->code }} — {{ $account->name }}</option>@endforeach</select></div>
                            <div><label class="form-label small">Debit</label><input name="lines[{{ $i }}][debit]" type="number" min="0" step="0.01" class="form-control" value="{{ old("lines.$i.debit") }}"></div>
                            <div><label class="form-label small">Kredit</label><input name="lines[{{ $i }}][credit]" type="number" min="0" step="0.01" class="form-control" value="{{ old("lines.$i.credit") }}"></div>
                        </div>
                    @endfor
                </div>

                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mt-3">
                    <button type="button" id="add-line" class="new-accounting-btn">+ Tambah Baris</button>
                    <div class="d-flex gap-2"><a href="{{ route('standalone-accounting.journals.index') }}" class="new-accounting-btn">Batal</a><button class="new-accounting-btn primary" type="submit">Posting Jurnal</button></div>
                </div>
            </form>
        </div>
    </div>

    <template id="journal-line-template">
        <div class="journal-line-grid journal-line">
            <div><label class="form-label small">Akun</label><select name="lines[__INDEX__][account_id]" class="form-select"><option value="">Pilih akun</option>@foreach ($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>@endforeach</select></div>
            <div><label class="form-label small">Debit</label><input name="lines[__INDEX__][debit]" type="number" min="0" step="0.01" class="form-control"></div>
            <div><label class="form-label small">Kredit</label><input name="lines[__INDEX__][credit]" type="number" min="0" step="0.01" class="form-control"></div>
        </div>
    </template>
    <script>
        document.getElementById('add-line')?.addEventListener('click', () => {
            const container = document.getElementById('journal-lines');
            const template = document.getElementById('journal-line-template').innerHTML;
            container.insertAdjacentHTML('beforeend', template.replaceAll('__INDEX__', container.children.length));
        });
    </script>
@endsection
