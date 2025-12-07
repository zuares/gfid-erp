@extends('layouts.app')

@section('title', 'Produksi • Cutting Job Baru')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .lot-card {
            border-radius: 10px;
            border: 1px solid var(--line);
            padding: .75rem .9rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .lot-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: .25rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .15rem .6rem;
            font-size: .75rem;
        }

        @media (max-width: 767.98px) {
            .table-wrap {
                overflow-x: auto;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- Tahap 1: pilih LOT dulu --}}
        @if (!$selectedLotRow)
            @include('production.cutting_jobs._pick_lot')
        @endif

        {{-- Tahap 2: setelah LOT dipilih, tampilkan form --}}
        @if ($selectedLotRow)
            @php
                $mode = 'create';
            @endphp

            @include('production.cutting_jobs._form')
        @endif

    </div>
@endsection
