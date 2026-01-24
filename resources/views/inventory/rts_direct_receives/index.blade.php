@extends('layouts.app')
@section('title', 'RTS • Dadakan')

@section('content')
    <div style="max-width:1100px;margin-inline:auto;padding:1rem .9rem 4rem;">
        <div
            style="display:flex;justify-content:space-between;gap:.75rem;flex-wrap:wrap;align-items:center;margin-bottom:.75rem;">
            <h1 style="margin:0;font-weight:900;">RTS • Dadakan</h1>
            <a class="btn btn-primary" href="{{ route('rts.direct-receives.create') }}">Buat Dadakan</a>
        </div>

        <form method="GET" style="margin-bottom:.75rem;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
            <input name="q" value="{{ $q }}" placeholder="Cari code / notes"
                style="padding:.5rem .6rem;border:1px solid rgba(148,163,184,.35);border-radius:10px;">
            <button class="btn btn-outline">Cari</button>
        </form>

        <div class="card" style="border:1px solid rgba(148,163,184,.25);border-radius:14px;padding:.75rem;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="opacity:.7;font-size:.8rem;">
                        <th style="text-align:left;padding:.4rem;">Tanggal</th>
                        <th style="text-align:left;padding:.4rem;">Code</th>
                        <th style="text-align:left;padding:.4rem;">From → To</th>
                        <th style="text-align:left;padding:.4rem;">Operator</th>
                        <th style="text-align:right;padding:.4rem;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr style="border-top:1px dashed rgba(148,163,184,.25);">
                            <td style="padding:.45rem;">{{ \Illuminate\Support\Carbon::parse($r->date)->format('d M Y') }}
                            </td>
                            <td style="padding:.45rem;font-weight:900;">{{ $r->code }}</td>
                            <td style="padding:.45rem;">{{ $r->fromWarehouse->code ?? '-' }} →
                                {{ $r->toWarehouse->code ?? '-' }}</td>
                            <td style="padding:.45rem;">{{ $r->operator->name ?? '-' }}</td>
                            <td style="padding:.45rem;text-align:right;">
                                <a class="btn btn-outline" href="{{ route('rts.direct-receives.show', $r) }}">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:1rem;opacity:.75;text-align:center;">Belum ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:1rem">{{ $rows->links() }}</div>
    </div>
@endsection
