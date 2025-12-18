@props([
    'req' => 0,
    'dispatched' => 0,
    'received' => 0,
    'picked' => 0,
])

@php
    $req = (float) $req;
    $dispatched = (float) $dispatched;
    $received = (float) $received;
    $picked = (float) $picked;

    $outRts = max($req - $received - $picked, 0);
    $inTransit = max($dispatched - $received, 0);
    $needPrdDispatch = max($req - $dispatched - $received - $picked, 0);

    if ($outRts <= 0.0000001) {
        $label = 'Fulfilled';
        $cls = 'lp lp-ok';
    } elseif ($received > 0 || $picked > 0) {
        $label = 'Sebagian Sampai';
        $cls = 'lp lp-partial';
    } elseif ($dispatched > 0) {
        $label = 'Di Transit';
        $cls = 'lp lp-transit';
    } else {
        $label = 'Menunggu PRD';
        $cls = 'lp lp-wait';
    }
@endphp

<div class="lp-wrap">
    <span class="{{ $cls }}">{{ $label }}</span>
    <div class="lp-meta">
        <span>REQ: <b>{{ $req }}</b></span>
        <span>PRD→TRN: <b>{{ $dispatched }}</b></span>
        <span>TRN→RTS: <b>{{ $received }}</b></span>
        <span>PICK: <b>{{ $picked }}</b></span>
        <span style="opacity:.85">Sisa RTS: <b>{{ $outRts }}</b></span>
        <span style="opacity:.85">Transit: <b>{{ $inTransit }}</b></span>
        <span style="opacity:.85">Butuh PRD: <b>{{ $needPrdDispatch }}</b></span>
    </div>
</div>

@once
    <style>
        .lp-wrap {
            display: flex;
            flex-direction: column;
            gap: .4rem;
            align-items: flex-end
        }

        .lp {
            display: inline-flex;
            width: max-content;
            padding: .18rem .5rem;
            border-radius: 999px;
            font-size: .76rem;
            border: 1px solid rgba(148, 163, 184, .35)
        }

        .lp-ok {
            background: rgba(45, 212, 191, .14);
            border-color: rgba(45, 212, 191, .35)
        }

        .lp-partial {
            background: rgba(16, 185, 129, .14);
            border-color: rgba(16, 185, 129, .35)
        }

        .lp-transit {
            background: rgba(245, 158, 11, .14);
            border-color: rgba(245, 158, 11, .35)
        }

        .lp-wait {
            background: rgba(59, 130, 246, .12);
            border-color: rgba(59, 130, 246, .35)
        }

        .lp-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem .8rem;
            font-size: .78rem;
            color: rgba(100, 116, 139, 1);
            justify-content: flex-end;
            text-align: right
        }

        .lp-meta b {
            color: inherit
        }
    </style>
@endonce
