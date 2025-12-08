@props([
    'title' => 'Operator',
    'label' => 'Pilih Operator',
    'description' => null,
    'name' => null,
    'selectId' => null,
    'operators' => collect(),
    'selected' => null,
    'required' => false,
])

@php
    $selectId = $selectId ?: ($name ?: 'operator_select');
@endphp

@push('head')
    <style>
        /* --------------------------------------------------------
                   OPERATOR FOCUS CARD — GFID Paten Style
                   Dipakai oleh cutting, sewing, finishing
                -------------------------------------------------------- */
        .operator-focus-card {
            border-radius: 14px;
            border: 1px solid rgba(59, 130, 246, 0.35);
            background: linear-gradient(to right,
                    rgba(59, 130, 246, .08),
                    rgba(59, 130, 246, .02));
            padding: .85rem 1rem;
            transition: background-color .2s ease, border-color .2s ease;
        }

        /* Hover effect halus */
        .operator-focus-card:hover {
            border-color: rgba(59, 130, 246, 0.55);
            background: linear-gradient(to right,
                    rgba(59, 130, 246, .10),
                    rgba(59, 130, 246, .04));
        }

        /* DARK MODE */
        body[data-theme="dark"] .operator-focus-card {
            background: linear-gradient(to right,
                    rgba(37, 99, 235, 0.35),
                    rgba(15, 23, 42, 0.85));
            border-color: rgba(96, 165, 250, .70);
        }

        body[data-theme="dark"] .operator-focus-card:hover {
            background: linear-gradient(to right,
                    rgba(37, 99, 235, 0.45),
                    rgba(15, 23, 42, 0.9));
            border-color: rgba(147, 197, 253, .9);
        }

        /* Label + title spacing */
        .operator-focus-card .operator-title {
            font-size: .78rem;
            letter-spacing: .06em;
        }

        .operator-focus-card select.form-select-sm {
            cursor: pointer;
        }
    </style>
@endpush

<div class="operator-focus-card mb-3">
    <div class="operator-title text-uppercase fw-semibold text-primary mb-2">
        {{ $title }}
    </div>

    @if ($description)
        <div class="text-muted small mb-2">
            {!! $description !!}
        </div>
    @endif

    <label class="form-label small mb-1">
        {{ $label }}
        @if ($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    <select id="{{ $selectId }}" @if ($name) name="{{ $name }}" @endif
        class="form-select form-select-sm">
        <option value="">- Pilih Operator -</option>
        @foreach ($operators as $op)
            <option value="{{ $op->id }}" @selected((string) $selected === (string) $op->id)>
                {{ $op->code }} - {{ $op->name }}
            </option>
        @endforeach
    </select>
</div>
