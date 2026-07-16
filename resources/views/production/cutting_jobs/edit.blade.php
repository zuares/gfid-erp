{{-- resources/views/production/cutting_jobs/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Cutting Job ' . $job->code)

@php
    // UI tidak lagi dikunci, bisa merubah LOT saat edit
    $isLotsLocked = false;
    $selectedLotsExisting = $selectedLotsExisting ?? [];

    $rowsExisting = $rows ?? [];
    if (empty($rowsExisting)) {
        $rowsExisting = [
            [
                'id' => null,
                'bundle_no' => 1,
                'lot_id' => null,
                'finished_item_id' => null,
                'finished_item_code' => null,
                'finished_item_name' => null,
                'item_category_id' => null,
                'qty_pcs' => null,
                'qty_used_fabric' => 0,
                'notes' => '',
            ],
        ];
    }
    
    // Determine the default warehouse (display only, as we use hidden input)
    $selectedWarehouseId = old('warehouse_id', $job->warehouse_id ?? ($defaultWarehouse->id ?? null));
    $defaultOperatorId = old('operator_id', optional($job->bundles->first())->operator_id);

    // Build map LOT untuk JS (kode + planned)
    $lockedLotInfo = collect($selectedLotSummaries ?? [])
        ->mapWithKeys(function ($s) {
            $lotId = (int) ($s['lot_id'] ?? 0);
            return $lotId
                ? [
                    $lotId => [
                        'lot_id' => $lotId,
                        'code' => $s['code'] ?? 'LOT#' . $lotId,
                        'planned' => (float) ($s['planned'] ?? 0),
                        'used' => (float) ($s['used'] ?? 0),
                    ],
                ]
                : [];
        })
        ->all();
@endphp

@section('content')
    <div class="page-wrap">
        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="mb-0 fw-semibold text-gray-800" style="letter-spacing: -0.02em;">Edit Cutting Job</h4>
            <a href="{{ route('production.cutting_jobs.index') }}" class="btn btn-light btn-sm fw-medium shadow-sm">
                &larr; Batal
            </a>
        </div>
        
        @include('production.cutting_jobs._form', [
            'isEdit' => true,
            'job' => $job,
        ])
    </div>
@endsection
