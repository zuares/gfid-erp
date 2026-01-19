@extends('layouts.app')

@section('title', 'Saldo Awal (Opening Balance)')

@section('content')
    <div class="container">

        {{-- ===== Header (mirip journal index) ===== --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h4 class="mb-0">Saldo Awal</h4>
                <div class="text-muted small">Isi saldo awal kas / bank di tanggal tertentu.</div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('accounting.opening-balances.create') }}" class="btn btn-primary">
                    + Tambah
                </a>
                <a href="{{ route('accounting.journals.index') }}" class="btn btn-light">
                    Semua Jurnal
                </a>
            </div>
        </div>

        {{-- Flash message --}}
        @if (session('message'))
            <div class="alert alert-{{ session('status') === 'ok' ? 'success' : 'danger' }} mb-3">
                {{ session('message') }}
            </div>
        @endif

        {{-- ===== Filters (simple, awam) ===== --}}
        <form method="GET" class="card mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1 small text-muted">Dari Tanggal</label>
                        <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1 small text-muted">Sampai</label>
                        <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1 small text-muted">Status</label>
                        <select name="status" class="form-select">
                            <option value="" @selected(request('status') === null || request('status') === '')>Semua</option>
                            <option value="active" @selected(request('status') === 'active')>Aktif</option>
                            <option value="void" @selected(request('status') === 'void')>Void</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button class="btn btn-outline-secondary w-100">Filter</button>
                        <a href="{{ route('accounting.opening-balances.index') }}" class="btn btn-light w-100">
                            Reset
                        </a>
                    </div>
                </div>
            </div>
        </form>

        {{-- ===== Table (mirip journal index) ===== --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:140px;">Tanggal</th>
                            <th>Saldo Awal</th>
                            <th style="width:240px;">Kas / Bank</th>
                            <th class="text-end" style="width:180px;">Nominal</th>
                            <th class="text-end" style="width:220px;">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($openingJournals as $j)
                            @php
                                // opening format: debit cash, credit equity
                                $cashLine = $j->lines->first(fn($l) => (float) $l->debit > 0);
                                $amount = (float) ($cashLine?->debit ?? 0);
                                $cashName = $cashLine?->account?->name ?? '-';
                                $isVoided = !is_null($j->voided_at);
                            @endphp

                            <tr class="{{ $isVoided ? 'table-secondary' : '' }}">
                                <td class="text-nowrap">
                                    {{ $j->date?->format('Y-m-d') ?? \Illuminate\Support\Carbon::parse($j->date)->format('Y-m-d') }}
                                </td>

                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="{{ route('accounting.journals.show', $j) }}"
                                            class="text-decoration-none fw-semibold">
                                            {{ $j->description ?: 'Saldo Awal' }}
                                        </a>

                                        @if ($isVoided)
                                            <span class="badge bg-danger">VOID</span>
                                        @else
                                            <span class="badge bg-success">AKTIF</span>
                                        @endif
                                    </div>

                                    <div class="text-muted small">
                                        {{ $isVoided ? 'Saldo awal ini dibatalkan (ada jurnal pembalik).' : 'Saldo awal ini sedang berlaku.' }}
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-semibold">{{ $cashName }}</div>
                                    <div class="text-muted small">Rekening yang diisi saldo awal</div>
                                </td>

                                <td class="text-end">
                                    <div class="fw-semibold">{{ number_format($amount, 0, ',', '.') }}</div>
                                    <div class="text-muted small">Rupiah</div>
                                </td>

                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('accounting.journals.show', $j) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Detail
                                        </a>

                                        @if (!$isVoided)
                                            <form method="POST"
                                                action="{{ route('accounting.opening-balances.void', $j) }}"
                                                class="d-inline"
                                                onsubmit="return confirm('Batalkan saldo awal ini? Sistem akan membuat jurnal pembalik (reversal).')">
                                                @csrf
                                                <input type="hidden" name="reason" value="Manual void">
                                                <button class="btn btn-sm btn-outline-danger">
                                                    Void
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Belum ada data saldo awal.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-3">
            {{ $openingJournals->links() }}
        </div>

    </div>
@endsection
