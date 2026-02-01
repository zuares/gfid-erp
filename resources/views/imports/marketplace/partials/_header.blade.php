{{-- resources/views/imports/marketplace/partials/_header.blade.php --}}
<div class="d-flex justify-content-between align-items-start gap-2 mb-3">
  <div>
    <h1 class="h4 mb-1 fw-bold">Marketplace Shipments</h1>

    <div class="text-muted small d-flex flex-wrap gap-1">
      @if($period)
        <span class="chip">
          {{ $fmtDate($period['from'] ?? null, 'd/m/Y') }} – {{ $fmtDate($period['to'] ?? null, 'd/m/Y') }} (GMT+7)
        </span>
      @endif

      @if(!empty($draft))
        <span class="chip" style="border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.10);">
          Draft import tersedia
        </span>
      @endif
    </div>
  </div>

  <div class="d-flex gap-2 align-items-center">
    {{-- Export --}}
    <button type="button"
      class="btn btn-outline-secondary btn-sm px-3"
      data-bs-toggle="modal"
      data-bs-target="#exportModal">
      Export
    </button>

    {{-- Import --}}
    <a class="btn btn-success btn-sm px-3" href="{{ route('imports.marketplace.create') }}">
      + Import
    </a>
  </div>
</div>
