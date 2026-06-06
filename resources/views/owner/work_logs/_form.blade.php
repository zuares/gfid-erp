@csrf

<div class="owl-form-grid">
    <label class="owl-field">
        <span>Tanggal</span>
        <input class="form-control" type="date" name="work_date" value="{{ old('work_date', optional($log->work_date)->toDateString() ?: now()->toDateString()) }}">
    </label>

    <label class="owl-field">
        <span>Kategori</span>
        <select class="form-select" name="category">
            <option value="">Pilih kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected(old('category', $log->category) === $category)>{{ $category }}</option>
            @endforeach
        </select>
    </label>

    <label class="owl-field owl-field-full">
        <span>Judul</span>
        <input class="form-control" type="text" name="title" value="{{ old('title', $log->title) }}" required maxlength="255" placeholder="contoh: Update modal cash receipts">
    </label>

    <label class="owl-field">
        <span>Prioritas</span>
        <select class="form-select" name="priority">
            @foreach ($priorities as $value => $label)
                <option value="{{ $value }}" @selected(old('priority', $log->priority ?: 'medium') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>

    <label class="owl-field">
        <span>Halaman Terkait</span>
        <input class="form-control" type="text" name="page_url" value="{{ old('page_url', $log->page_url) }}" maxlength="255" placeholder="/accounting/cash-receipts">
    </label>

    <label class="owl-field owl-field-full">
        <span>Deskripsi Singkat</span>
        <textarea class="form-control" name="description" rows="3" placeholder="Ringkasan pengerjaan">{{ old('description', $log->description) }}</textarea>
    </label>

    @if ($errors->any())
        <div class="alert alert-danger owl-field-full">
            <b>Periksa lagi:</b>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
