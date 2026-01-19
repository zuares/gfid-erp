@php
    $acc = $account ?? null;
@endphp

<div class="mb-3">
    <label class="form-label">Code</label>
    <input type="text" name="code" class="form-control" value="{{ old('code', $acc->code ?? '') }}" required>
    @error('code')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $acc->name ?? '') }}" required>
    @error('name')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Type</label>
    <select name="type" class="form-control" required>
        @foreach ($types as $t)
            <option value="{{ $t }}" @selected(old('type', $acc->type ?? '') === $t)>
                {{ strtoupper($t) }}
            </option>
        @endforeach
    </select>
    @error('type')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" name="is_cash" value="1" id="is_cash"
        @checked(old('is_cash', $acc->is_cash ?? false))>
    <label class="form-check-label" for="is_cash">Is Cash/Bank</label>
</div>

<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
        @checked(old('is_active', $acc->is_active ?? true))>
    <label class="form-check-label" for="is_active">Active</label>
</div>
