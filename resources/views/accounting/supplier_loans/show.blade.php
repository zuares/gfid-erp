@extends('layouts.app')

@section('title', 'Accounting • Detail Dana Supplier')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $repaid = (float) $supplierLoan->repayments->where('status', 'posted')->sum('amount');
    $remaining = max(0, (float) $supplierLoan->principal_amount - $repaid);
    $labels = ['draft' => 'Draft', 'posted' => 'Posted', 'settled' => 'Lunas', 'void' => 'Void'];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .sl-detail{display:grid;gap:1rem}.sl-actions{display:flex;justify-content:flex-end;gap:.5rem;flex-wrap:wrap}.sl-btn{display:inline-flex;align-items:center;min-height:40px;padding:.55rem .95rem;border:1px solid #dbe3ee;border-radius:999px;background:#fff;color:#0f172a;text-decoration:none;font-size:.82rem;font-weight:850}.sl-btn-primary{color:#fff;background:#0f172a;border-color:#0f172a}.sl-btn-danger{color:#991b1b;background:#fff5f5;border-color:#fecaca}.sl-status{display:inline-flex;border-radius:999px;padding:.22rem .6rem;font-size:.72rem;font-weight:850}.sl-status-draft{color:#92400e;background:#fef3c7}.sl-status-posted{color:#166534;background:#dcfce7}.sl-status-settled{color:#075985;background:#e0f2fe}.sl-status-void{color:#991b1b;background:#fee2e2}.sl-info{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem}.sl-card{border:1px solid #e2e8f0;border-radius:14px;background:#fff;padding:.85rem}.sl-label{color:#64748b;font-size:.7rem;font-weight:900;text-transform:uppercase}.sl-value{margin-top:.2rem;color:#0f172a;font-weight:900}.sl-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem}.sl-field{display:grid;gap:.3rem}.sl-field span{color:#64748b;font-size:.72rem;font-weight:900;text-transform:uppercase}.sl-control{min-height:40px;border-radius:11px;border-color:#dbe3ee;box-shadow:none}.sl-table th{color:#64748b;font-size:.7rem;text-transform:uppercase;white-space:nowrap}@media(max-width:800px){.sl-info{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:650px){.sl-form-grid{grid-template-columns:1fr}.sl-actions .sl-btn{flex:1;justify-content:center}}
    </style>
@endpush

@section('content')
    <x-gf.page eyebrow="Accounting" title="Detail Dana Supplier" description="{{ $supplierLoan->supplier?->name }} · {{ $supplierLoan->description ?: 'Pinjaman supplier' }}">
        <x-slot:actions><div class="sl-actions">
            <a class="sl-btn" href="{{ route('accounting.supplier-loans.index') }}">Daftar</a>
            @if($supplierLoan->status === 'draft')<a class="sl-btn" href="{{ route('accounting.supplier-loans.edit', $supplierLoan) }}">Edit</a><form method="POST" action="{{ route('accounting.supplier-loans.post', $supplierLoan) }}">@csrf<button class="sl-btn sl-btn-primary" type="submit">Post Dana</button></form>@endif
            @if($supplierLoan->status === 'draft')<form method="POST" action="{{ route('accounting.supplier-loans.destroy', $supplierLoan) }}" onsubmit="return confirm('Hapus draft dana supplier ini?')">@csrf @method('DELETE')<button class="sl-btn sl-btn-danger" type="submit">Hapus</button></form>@endif
            @if($supplierLoan->status === 'posted')<form method="POST" action="{{ route('accounting.supplier-loans.void', $supplierLoan) }}" onsubmit="return confirm('Void dana supplier ini?')">@csrf<input type="hidden" name="reason" value="Void dari detail"><button class="sl-btn sl-btn-danger" type="submit">Void</button></form>@endif
        </div></x-slot:actions>
        <div class="sl-detail">
            @if(session('message'))<div class="alert alert-{{ session('status') === 'error' ? 'danger' : 'success' }} mb-0">{{ session('message') }}</div>@endif
            <div class="sl-info">
                <div class="sl-card"><div class="sl-label">Status</div><div class="sl-value"><span class="sl-status sl-status-{{ $supplierLoan->status }}">{{ $labels[$supplierLoan->status] ?? $supplierLoan->status }}</span></div></div>
                <div class="sl-card"><div class="sl-label">Dana</div><div class="sl-value">Rp {{ $fmt($supplierLoan->principal_amount) }}</div></div>
                <div class="sl-card"><div class="sl-label">Sudah Kembali</div><div class="sl-value">Rp {{ $fmt($repaid) }}</div></div>
                <div class="sl-card"><div class="sl-label">Sisa Piutang</div><div class="sl-value">Rp {{ $fmt($remaining) }}</div></div>
            </div>

            <x-gf.panel title="Ringkasan" subtitle="{{ optional($supplierLoan->date)->format('d/m/Y') }}{{ $supplierLoan->reference ? ' · '.$supplierLoan->reference : '' }}">
                <div class="table-responsive"><table class="table mb-0"><tbody>
                    <tr><th>Supplier</th><td>{{ $supplierLoan->supplier?->code }} · {{ $supplierLoan->supplier?->name }}</td></tr>
                    <tr><th>Jatuh tempo</th><td>{{ optional($supplierLoan->due_date)->format('d/m/Y') ?: '—' }}</td></tr>
                    <tr><th>Kas/Bank</th><td>{{ $supplierLoan->cashAccount?->code }} · {{ $supplierLoan->cashAccount?->name }}</td></tr>
                    <tr><th>Akun piutang</th><td>{{ $supplierLoan->receivableAccount?->code }} · {{ $supplierLoan->receivableAccount?->name }}</td></tr>
                    <tr><th>Jurnal</th><td>@if($supplierLoan->journal)<a href="{{ route('accounting.journals.show', $supplierLoan->journal) }}">#{{ $supplierLoan->journal_id }}</a>@else Belum dibuat @endif</td></tr>
                    @if($supplierLoan->notes)<tr><th>Catatan</th><td>{{ $supplierLoan->notes }}</td></tr>@endif
                </tbody></table></div>
            </x-gf.panel>

            @if(in_array($supplierLoan->status, ['posted', 'settled'], true) && $remaining > 0.009)
                <x-gf.panel title="Catat Pengembalian Supplier" subtitle="Jurnal: Debit Kas/Bank — Kredit Piutang Pinjaman Supplier.">
                    <form method="POST" action="{{ route('accounting.supplier-loans.repayments.store', $supplierLoan) }}">@csrf
                        <div class="sl-form-grid">
                            <label class="sl-field"><span>Tanggal</span><input class="form-control sl-control" type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required></label>
                            <label class="sl-field"><span>Nominal Pengembalian</span><input class="form-control sl-control" type="number" name="amount" min="0.01" max="{{ $remaining }}" step="0.01" required></label>
                            <label class="sl-field"><span>Diterima ke</span><select class="form-select sl-control" name="cash_account_id" required>@foreach($cashAccounts as $account)<option value="{{ $account->id }}">{{ $account->code }} · {{ $account->name }}</option>@endforeach</select></label>
                            <label class="sl-field"><span>No. Referensi</span><input class="form-control sl-control" type="text" name="reference" maxlength="100"></label>
                            <label class="sl-field" style="grid-column:1/-1"><span>Catatan</span><textarea class="form-control sl-control" name="notes" rows="2"></textarea></label>
                        </div>
                        <div class="sl-actions mt-3"><button class="sl-btn sl-btn-primary" type="submit">Simpan Pengembalian Draft</button></div>
                    </form>
                </x-gf.panel>
            @endif

            <x-gf.panel title="Riwayat Pengembalian" subtitle="Pengembalian diposting satu per satu agar saldo supplier tetap terlacak.">
                <div class="table-responsive"><table class="table sl-table align-middle mb-0"><thead><tr><th>Tanggal</th><th>Referensi</th><th class="text-end">Nominal</th><th>Kas/Bank</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                    @forelse($supplierLoan->repayments as $repayment)<tr><td>{{ optional($repayment->date)->format('d/m/Y') }}</td><td>{{ $repayment->reference ?: '—' }}</td><td class="text-end">Rp {{ $fmt($repayment->amount) }}</td><td>{{ $repayment->cashAccount?->name }}</td><td><span class="sl-status sl-status-{{ $repayment->status }}">{{ $labels[$repayment->status] ?? $repayment->status }}</span></td><td class="text-end">@if($repayment->status === 'draft')<form method="POST" action="{{ route('accounting.supplier-loans.repayments.post', $repayment) }}" class="d-inline">@csrf<button class="sl-btn sl-btn-primary" type="submit">Post</button></form>@elseif($repayment->status === 'posted')<form method="POST" action="{{ route('accounting.supplier-loans.repayments.void', $repayment) }}" class="d-inline" onsubmit="return confirm('Void pengembalian ini?')">@csrf<input type="hidden" name="reason" value="Void dari detail"><button class="sl-btn sl-btn-danger" type="submit">Void</button></form>@endif</td></tr>@empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada pengembalian.</td></tr>
                    @endforelse
                </tbody></table></div>
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
