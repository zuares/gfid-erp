{{-- resources/views/components/bundle-suggest.blade.php --}}
@props([
    'name', // name input (wajib)
    'value' => null, // nilai awal (id bundle)
    'placeholder' => 'ID Bundle',
])

@php
    // bikin id HTML yang aman dari karakter [ ]
    $fieldId = 'bundle_' . str_replace(['[', ']'], '_', $name);
    $currentValue = old($name, $value);
@endphp

<div class="input-group input-group-sm">
    <input type="number" name="{{ $name }}" id="{{ $fieldId }}" class="form-control form-control-sm"
        value="{{ $currentValue }}" placeholder="{{ $placeholder }}" min="1" step="1" />
</div>
