@extends('layouts.app')

@section('title', 'Snapshot & Rollback Database')

@section('content')
<div class="container-fluid py-4">

  <div class="d-flex align-items-center gap-3 mb-4">
    <div>
      <h4 class="mb-0 fw-bold">Snapshot & Rollback Database</h4>
      <small class="text-muted">DB aktif: <code>{{ basename($currentDb) }}</code></small>
    </div>
  </div>

  {{-- Create Snapshot --}}
  <div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
      <h6 class="fw-semibold mb-3">Buat Snapshot Baru</h6>
      <form action="{{ route('owner.snapshots.store') }}" method="POST" class="d-flex gap-2 align-items-end">
        @csrf
        <div class="flex-grow-1">
          <label class="form-label small text-muted mb-1">Label (opsional)</label>
          <input type="text" name="label" class="form-control form-control-sm"
            placeholder="cth: sebelum-import-bom, setelah-setting-hpp"
            pattern="[a-zA-Z0-9 _\-]+" maxlength="60">
        </div>
        <button type="submit" class="btn btn-primary btn-sm px-4">
          <i class="bi bi-camera me-1"></i> Ambil Snapshot
        </button>
      </form>
    </div>
  </div>

  {{-- Daftar Snapshot --}}
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <span class="fw-semibold">Daftar Snapshot</span>
      <span class="badge bg-secondary">{{ $snapshots->count() }} file</span>
    </div>

    @if($snapshots->isEmpty())
      <div class="card-body text-center text-muted py-5">
        <i class="bi bi-archive fs-2 d-block mb-2"></i>
        Belum ada snapshot. Klik "Ambil Snapshot" untuk menyimpan kondisi database sekarang.
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Waktu</th>
              <th>Label</th>
              <th>Ukuran</th>
              <th>File</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($snapshots as $snap)
            <tr>
              <td>
                <span class="fw-medium">
                  {{ $snap['date'] ? $snap['date']->format('d M Y') : '-' }}
                </span><br>
                <small class="text-muted">{{ $snap['date'] ? $snap['date']->format('H:i:s') : '' }}</small>
              </td>
              <td>
                @if(str_contains($snap['label'], 'sebelum_restore'))
                  <span class="badge bg-warning text-dark">auto-backup</span>
                @else
                  {{ $snap['label'] }}
                @endif
              </td>
              <td><small class="text-muted">{{ $snap['size_mb'] }} MB</small></td>
              <td><code class="small">{{ $snap['filename'] }}</code></td>
              <td class="text-end">
                <form action="{{ route('owner.snapshots.restore', $snap['filename']) }}"
                  method="POST" class="d-inline"
                  data-gf-confirm
                  data-gf-confirm-title="Rollback ke snapshot ini?"
                  data-gf-confirm-text="Database aktif akan ditimpa. DB sekarang dibackup otomatis sebelum rollback."
                  data-gf-confirm-icon="warning"
                  data-gf-confirm-ok="Ya, Rollback">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="btn btn-sm btn-outline-warning">
                    <i class="bi bi-arrow-counterclockwise"></i> Rollback
                  </button>
                </form>
                <form action="{{ route('owner.snapshots.destroy', $snap['filename']) }}"
                  method="POST" class="d-inline ms-1"
                  data-gf-confirm
                  data-gf-confirm-title="Hapus snapshot?"
                  data-gf-confirm-text="File snapshot ini akan dihapus permanen."
                  data-gf-confirm-icon="question"
                  data-gf-confirm-ok="Hapus">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

</div>
@endsection
