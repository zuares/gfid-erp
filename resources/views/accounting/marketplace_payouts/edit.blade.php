@extends('layouts.app')

@section('title', 'Edit Penerimaan Marketplace')

@php $p = $marketplacePayout; @endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .mp-form-card { background:#fff; border:1px solid rgba(15,23,42,.08); border-radius:14px; padding:1.4rem; }
        .mp-form-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:.85rem; }
        .mp-form-full { grid-column:1/-1; }
        .mp-btn {
            display:inline-flex; align-items:center; gap:.45rem; min-height:40px;
            padding:.55rem .95rem; border-radius:999px; border:1px solid rgba(15,23,42,.10);
            background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:850;
        }
        .mp-btn-primary { background:#0f172a; border-color:#0f172a; color:#fff; }
        .mp-btn-primary:hover { background:#1e293b; color:#fff; }
        .form-label { font-size:.8rem; font-weight:850; color:#334155; margin-bottom:.3rem; }
        .form-control, .form-select { border-radius:8px; font-size:.88rem; }
        .mp-marketplace-chip {
            padding:.25rem .65rem; border-radius:999px; border:1px solid rgba(15,23,42,.12);
            font-size:.78rem; font-weight:700; cursor:pointer; background:#f8fafc; color:#334155;
        }
        .mp-marketplace-chip:hover { background:#0f172a; color:#fff; border-color:#0f172a; }
    </style>
@endpush

@section('content')
<div style="display:grid; gap:1rem; max-width:640px">

    <div>
        <a href="{{ route('accounting.marketplace-payouts.show', $p) }}" class="mp-btn" style="margin-bottom:.5rem">
            ← Kembali
        </a>
        <h5 class="mb-0 fw-black">Edit Penerimaan #{{ $p->id }}</h5>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('accounting.marketplace-payouts.update', $p) }}">
        @csrf @method('PATCH')
        <div class="mp-form-card">
            <div class="mp-form-grid">

                <div>
                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                        value="{{ old('date', $p->date->format('Y-m-d')) }}" required>
                    @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Marketplace <span class="text-danger">*</span></label>
                    <input type="text" name="marketplace_name" id="marketplace_name"
                        class="form-control @error('marketplace_name') is-invalid @enderror"
                        value="{{ old('marketplace_name', $p->marketplace_name) }}" required>
                    @error('marketplace_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        @foreach(['Shopee','Tokopedia','TikTok Shop','Lazada'] as $mp)
                            <span class="mp-marketplace-chip" onclick="document.getElementById('marketplace_name').value='{{ $mp }}'">{{ $mp }}</span>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text" style="border-radius:8px 0 0 8px; font-size:.82rem; font-weight:700">Rp</span>
                        <input type="number" name="amount" step="1" min="0.01"
                            class="form-control @error('amount') is-invalid @enderror"
                            value="{{ old('amount', $p->amount) }}" required
                            style="border-radius:0 8px 8px 0">
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div>
                    <label class="form-label">Akun Bank <span class="text-danger">*</span></label>
                    <select name="bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Akun --</option>
                        @foreach($bankAccounts as $acc)
                            <option value="{{ $acc->id }}" @selected(old('bank_account_id', $p->bank_account_id) == $acc->id)>
                                {{ $acc->code }} – {{ $acc->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('bank_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Referensi</label>
                    <input type="text" name="reference" class="form-control"
                        value="{{ old('reference', $p->reference) }}">
                </div>

                <div>
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="description" class="form-control"
                        value="{{ old('description', $p->description) }}">
                </div>

                <div class="mp-form-full">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes', $p->notes) }}</textarea>
                </div>

            </div>

            <div class="d-flex gap-2 justify-content-end mt-3 pt-3 border-top">
                <a href="{{ route('accounting.marketplace-payouts.show', $p) }}" class="mp-btn">Batal</a>
                <button type="submit" class="mp-btn mp-btn-primary">Simpan</button>
            </div>
        </div>
    </form>

</div>
@endsection
