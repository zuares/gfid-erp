@extends('layouts.app')

@section('title', 'Owner Work Log')

@include('owner.work_logs._style')

@section('content')
    <x-gf.page class="owl-owner-page" eyebrow="Owner" title="Log Pengerjaan Web">
        <x-slot:actions>
            <div class="owl-actions">
                <button class="owl-btn owl-btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#ownerWorkLogCreateModal">
                    + Tambah Log
                </button>
            </div>
        </x-slot:actions>

        <div class="owl-page">
            @if (session('message'))
                <div class="gf-mpl-insight">
                    <span class="gf-mpl-insight-ico">✓</span>
                    <b>{{ session('message') }}</b>
                </div>
            @endif

            <x-gf.panel title="Ringkasan">
                <div class="owl-dashboard">
                    <div class="owl-kpi-grid">
                        <div class="owl-kpi">
                            <div class="owl-kpi-label">Total Log</div>
                            <div class="owl-kpi-value">{{ number_format($summary['total'], 0, ',', '.') }}</div>
                        </div>

                        <div class="owl-kpi">
                            <div class="owl-kpi-label">Progress</div>
                            <div class="owl-kpi-value">{{ number_format($summary['progress'], 0, ',', '.') }}</div>
                        </div>

                        <div class="owl-kpi">
                            <div class="owl-kpi-label">Done</div>
                            <div class="owl-kpi-value">{{ number_format($summary['done'], 0, ',', '.') }}</div>
                        </div>
                    </div>


                    <div class="owl-tabs">
                        <a class="owl-tab {{ ($activeTab ?? 'progress') === 'progress' ? 'is-active' : '' }}"
                            href="{{ route('owner.work-logs.index', array_merge(request()->except(['page', 'tab']), ['tab' => 'progress'])) }}">
                            <span>Progress</span>
                            <b>{{ number_format($summary['progress'], 0, ',', '.') }}</b>
                        </a>

                        <a class="owl-tab {{ ($activeTab ?? 'progress') === 'done' ? 'is-active' : '' }}"
                            href="{{ route('owner.work-logs.index', array_merge(request()->except(['page', 'tab']), ['tab' => 'done'])) }}">
                            <span>Done</span>
                            <b>{{ number_format($summary['done'], 0, ',', '.') }}</b>
                        </a>
                    </div>

                    <form class="owl-filter" method="GET" action="{{ route('owner.work-logs.index') }}">
                        <input type="hidden" name="tab" value="{{ $activeTab ?? 'progress' }}">
                        <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Cari log atau halaman...">

                        <select class="form-select" name="category">
                            <option value="">Semua kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                            @endforeach
                        </select>

                        <button class="owl-btn" type="submit">Filter</button>
                    </form>
                </div>
            </x-gf.panel>

            <x-gf.panel title="Daftar Log">

                <div class="owl-log-list">
                    @forelse ($logs as $log)
                        <div class="owl-log-card {{ $log->is_done ? 'is-done' : '' }}">
                            <div class="owl-log-main">
                                <div class="owl-log-title-row">
                                    <a class="owl-log-title" href="{{ route('owner.work-logs.show', $log) }}">
                                        {{ $log->title }}
                                    </a>

                                    <span class="owl-badge owl-badge-status-{{ $log->status }}">{{ $log->status_label }}</span>

                                    @if ($log->priority)
                                        <span class="owl-badge owl-badge-priority-{{ $log->priority }}">{{ $log->priority_label }}</span>
                                    @endif
                                </div>

                                <div class="owl-log-meta">
                                    {{ optional($log->work_date)->format('d/m/Y') ?: '-' }}
                                    · {{ $log->category ?: 'Other' }}
                                    @if ($log->page_url)
                                        · <a class="owl-related-link"
                                            href="{{ \Illuminate\Support\Str::startsWith($log->page_url, ['http://', 'https://']) ? $log->page_url : url($log->page_url) }}"
                                            target="_blank"
                                            rel="noopener">
                                            {{ $log->page_url }}
                                        </a>
                                    @endif
                                </div>

                                @if ($log->description)
                                    <div class="owl-log-desc">{{ \Illuminate\Support\Str::limit($log->description, 180) }}</div>
                                @endif
                            </div>

                            <div class="owl-log-actions">
                                @if (! $log->is_done)
                                    <form method="POST" action="{{ route('owner.work-logs.mark-done', $log) }}">
                                        @csrf
                                        <button class="owl-btn owl-btn-success" type="submit">Done</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('owner.work-logs.reopen', $log) }}">
                                        @csrf
                                        <button class="owl-btn" type="submit">Buka Lagi</button>
                                    </form>
                                @endif

                                <button class="owl-btn" type="button" data-bs-toggle="modal" data-bs-target="#ownerWorkLogEditModal{{ $log->id }}">
                                    Edit
                                </button>

                                <form method="POST" action="{{ route('owner.work-logs.destroy', $log) }}" onsubmit="return confirm('Hapus log ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="owl-btn owl-btn-danger" type="submit">Hapus</button>
                                </form>
                            </div>
                        </div>

                        <div class="modal fade owl-modal" id="ownerWorkLogEditModal{{ $log->id }}" tabindex="-1" aria-labelledby="ownerWorkLogEditModalLabel{{ $log->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <form class="modal-content" method="POST" action="{{ route('owner.work-logs.update', $log) }}">
                                    @method('PUT')

                                    <div class="modal-header">
                                        <div>
                                            <div class="owl-modal-sub">Edit Log</div>
                                            <h5 class="modal-title" id="ownerWorkLogEditModalLabel{{ $log->id }}">Update Log</h5>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                    </div>

                                    <div class="modal-body">
                                        @include('owner.work_logs._form', ['log' => $log])
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="owl-btn" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="owl-btn owl-btn-primary">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted text-center py-4">
                            @if (($activeTab ?? 'progress') === 'done')
                                Belum ada log yang selesai.
                            @else
                                Belum ada log progress.
                            @endif
                        </div>
                    @endforelse
                </div>

                <div class="mt-3">
                    {{ $logs->links() }}
                </div>
            </x-gf.panel>
        </div>

        <div class="modal fade owl-modal" id="ownerWorkLogCreateModal" tabindex="-1" aria-labelledby="ownerWorkLogCreateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <form class="modal-content" method="POST" action="{{ route('owner.work-logs.store') }}">
                    <div class="modal-header">
                        <div>
                            <div class="owl-modal-sub">Log Baru</div>
                            <h5 class="modal-title" id="ownerWorkLogCreateModalLabel">Tambah Log</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        @include('owner.work_logs._form', [
                            'log' => new \App\Models\OwnerWorkLog([
                                'work_date' => now()->toDateString(),
                                'priority' => 'medium',
                            ])
                        ])
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="owl-btn" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="owl-btn owl-btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </x-gf.page>
@endsection
