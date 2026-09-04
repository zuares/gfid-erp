@extends('layouts.app')

@section('title', 'Tambah Penerimaan Marketplace')

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .mp-form-card { background: #fff; border: 1px solid rgba(15,23,42,.08); border-radius: 14px; padding: 1.4rem; }
        .mp-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: .85rem; }
        .mp-form-full { grid-column: 1 / -1; }
        .mp-btn {
            display: inline-flex; align-items: center; gap: .45rem; min-height: 40px;
            padding: .55rem .95rem; border-radius: 999px; border: 1px solid rgba(15,23,42,.10);
            background: #fff; color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850;
        }
        .mp-btn-primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .mp-btn-primary:hover { background: #1e293b; color: #fff; }
        .form-label { font-size: .8rem; font-weight: 850; color: #334155; margin-bottom: .3rem; }
        .form-control, .form-select { border-radius: 8px; font-size: .88rem; }
        .mp-hint { font-size: .75rem; color: #94a3b8; margin-top: .2rem; }
        .mp-marketplace-list { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .4rem; }
        .mp-marketplace-chip {
            padding: .25rem .65rem; border-radius: 999px; border: 1px solid rgba(15,23,42,.12);
            font-size: .78rem; font-weight: 700; cursor: pointer; background: #f8fafc; color: #334155;
        }
        .mp-marketplace-chip:hover { background: #0f172a; color: #fff; border-color: #0f172a; }
    </style>
@endpush

@section('content')
<div style="display:grid; gap:1rem; max-width:640px">

    <div>
        <a href="{{ route('accounting.marketplace-payouts.index') }}" class="mp-btn" style="margin-bottom:.5rem">
            ← Kembali
        </a>
        <h5 class="mb-0 fw-black">Tambah Penerimaan Marketplace</h5>
        <div class="text-muted" style="font-size:.8rem">Jurnal: Dr Bank / Cr 1302 Saldo Marketplace / Clearing</div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('accounting.marketplace-payouts.store') }}">
        @csrf
        <div class="mp-form-card">
            <div class="mp-form-grid">

                <div>
                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                        value="{{ old('date', date('Y-m-d')) }}" required>
                    @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Marketplace <span class="text-danger">*</span></label>
                    <input type="text" name="marketplace_name" id="marketplace_name"
                        class="form-control @error('marketplace_name') is-invalid @enderror"
                        value="{{ old('marketplace_name') }}"
                        placeholder="Shopee, Tokopedia, TikTok..." required>
                    @error('marketplace_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="mp-marketplace-list">
                        @foreach(['Shopee','Tokopedia','TikTok Shop','Lazada'] as $mp)
                            <span class="mp-marketplace-chip" onclick="document.getElementById('marketplace_name').value='{{ $mp }}'">
                                {{ $mp }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text" style="border-radius:8px 0 0 8px; font-size:.82rem; font-weight:700">Rp</span>
                        <input type="text" name="amount" inputmode="numeric" pattern="[0-9]*"
                            data-marketplace-payout-amount
                            class="form-control @error('amount') is-invalid @enderror"
                            value="{{ old('amount') }}" placeholder="1000000" autocomplete="off" required
                            style="border-radius:0 8px 8px 0">
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mp-hint">Masukkan angka langsung tanpa titik/koma. Contoh: 1000000</div>
                </div>

                <div>
                    <label class="form-label">Masuk ke Akun Bank <span class="text-danger">*</span></label>
                    <select name="bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Akun --</option>
                        @foreach($bankAccounts as $acc)
                            <option value="{{ $acc->id }}" @selected(old('bank_account_id') == $acc->id)>
                                {{ $acc->code }} – {{ $acc->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('bank_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">No. Referensi / Disbursement</label>
                    <input type="text" name="reference"
                        class="form-control @error('reference') is-invalid @enderror"
                        value="{{ old('reference') }}" placeholder="opsional">
                    @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="description"
                        class="form-control @error('description') is-invalid @enderror"
                        value="{{ old('description') }}" placeholder="opsional">
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mp-form-full">
                    <label class="form-label">Catatan Internal</label>
                    <textarea name="notes" class="form-control" rows="2"
                        placeholder="opsional">{{ old('notes') }}</textarea>
                </div>

            </div>

            <div class="d-flex gap-2 justify-content-end mt-3 pt-3 border-top">
                <a href="{{ route('accounting.marketplace-payouts.index') }}" class="mp-btn">Batal</a>
                <button type="submit" class="mp-btn mp-btn-primary">Simpan sebagai Draft</button>
            </div>
        </div>
    </form>

</div>
@endsection

@push('scripts')
    <script>
        (() => {
            const input = document.querySelector('[data-marketplace-payout-amount]');
            if (!input) return;

            const cleanAmount = () => {
                input.value = input.value.replace(/[^0-9]/g, '');
            };

            cleanAmount();
            input.addEventListener('input', cleanAmount);
            input.form?.addEventListener('submit', cleanAmount);
        })();
    </script>
@endpush
