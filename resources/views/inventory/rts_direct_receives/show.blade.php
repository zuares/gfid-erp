@extends('layouts.app')
@section('title', 'RTS • Dadakan • Detail')

@section('content')
    <div style="max-width:1100px;margin-inline:auto;padding:1rem .9rem 4rem;">
        <div
            style="display:flex;justify-content:space-between;gap:.75rem;flex-wrap:wrap;align-items:center;margin-bottom:.75rem;">
            <div>
                <h1 style="margin:0;font-weight:900;">{{ $doc->code }}</h1>
                <div style="opacity:.75;font-size:.9rem;">
                    {{ \Illuminate\Support\Carbon::parse($doc->date)->format('d M Y') }}
                    · {{ $doc->fromWarehouse->code ?? '-' }} → {{ $doc->toWarehouse->code ?? '-' }}
                    · Operator: {{ $doc->operator->name ?? '-' }}
                </div>
            </div>
            <a class="btn btn-outline" href="{{ route('rts.direct-receives.index') }}">← List</a>
        </div>

        <div class="card" style="border:1px solid rgba(148,163,184,.25);border-radius:14px;padding:.85rem;">
            <div style="font-weight:900;margin-bottom:.4rem;">Lines</div>
            <table style="width:100%;border-collapse:collapse;min-width:820px;">
                <thead style="opacity:.7;font-size:.8rem;">
                    <tr>
                        <th style="text-align:left;padding:.4rem;width:60px;">#</th>
                        <th style="text-align:left;padding:.4rem;">Item</th>
                        <th style="text-align:right;padding:.4rem;width:160px;">Qty</th>
                        <th style="text-align:left;padding:.4rem;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($doc->lines as $l)
                        <tr style="border-top:1px dashed rgba(148,163,184,.25);">
                            <td style="padding:.45rem;">{{ $l->line_no }}</td>
                            <td style="padding:.45rem;font-weight:700;">{{ $l->item->code ?? '-' }} —
                                {{ $l->item->name ?? '-' }}</td>
                            <td style="padding:.45rem;text-align:right;font-weight:900;">
                                {{ rtrim(rtrim(number_format((float) $l->qty, 2, '.', ''), '0'), '.') }}
                            </td>
                            <td style="padding:.45rem;">{{ $l->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($doc->notes)
                <div style="margin-top:.75rem;opacity:.8;"><b>Notes:</b> {{ $doc->notes }}</div>
            @endif
        </div>
    </div>
@endsection
