@extends('layouts.app')

@section('title', 'Marketplace • Shopee • Import Income')

@section('content')
<div class="container py-3" style="max-width: 820px;">
    <h4 class="mb-3">Import Income Shopee (Dana Dilepaskan)</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Terjadi error:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('marketplace.shopee.import-income.run') }}" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label class="form-label">Store</label>
            <select name="store_id" class="form-control" required>
                @foreach($stores as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">File Income (.xlsx)</label>
            <input type="file" name="file" class="form-control" accept=".xlsx" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Mode jika invoice tidak ketemu</label>
            <select name="on_missing" class="form-control">
                <option value="skip">Skip (lewati)</option>
                <option value="stop">Stop (gagalkan import)</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Dry-run</label>
            <select name="dry_run" class="form-control">
                <option value="1">Ya (preview, tidak tulis DB)</option>
                <option value="0">Tidak (write ke DB)</option>
            </select>
        </div>

        <button class="btn btn-primary">
            Jalankan Import
        </button>
    </form>

    @if(session('result'))
        <hr>
        <pre class="p-2 bg-light border rounded">{{ print_r(session('result'), true) }}</pre>
    @endif
</div>
@endsection
