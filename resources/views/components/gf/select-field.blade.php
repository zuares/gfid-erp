@props([
    'id',
    'label',
    'icon' => 'bi-list-ul',
    'hint' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'wrapperClass' => '',
])

@php
    $hintId = $hint ? $id . '-hint' : null;
    $errorId = $error ? $id . '-error' : null;
    $describedBy = collect([$hintId, $errorId])->filter()->implode(' ');
    $fieldClass = trim('ds-select-field gf-select-field ' . $wrapperClass . ' ' . ($error ? 'is-error' : '') . ' ' . ($disabled ? 'is-disabled' : ''));
@endphp

<div class="{{ $fieldClass }}">
    <label for="{{ $id }}">{{ $label }}@if ($required)<span aria-hidden="true"> *</span>@endif</label>
    <div class="ds-select-control">
        <i class="bi {{ $icon }} ds-select-leading-icon" aria-hidden="true"></i>
        <select
            id="{{ $id }}"
            @if ($required) required @endif
            @if ($disabled) disabled @endif
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes }}
        >{{ $slot }}</select>
        <i class="bi bi-chevron-down ds-select-chevron" aria-hidden="true"></i>
    </div>
    @if ($hint)
        <span class="ds-select-hint" id="{{ $hintId }}"><i class="bi bi-info-circle" aria-hidden="true"></i> {{ $hint }}</span>
    @endif
    @if ($error)
        <span class="ds-select-error" id="{{ $errorId }}" role="alert"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $error }}</span>
    @endif
</div>
