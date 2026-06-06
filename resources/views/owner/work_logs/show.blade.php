@extends('layouts.app')

@section('title', $log->title)

@include('owner.work_logs._style')

@section('content')
    <x-gf.page class="owl-owner-page" eyebrow="Owner" title="Detail Log">
        <x-slot:actions>
            <div class="owl-actions">
                <a class="owl-btn" href="{{ route('owner.work-logs.index') }}">Daftar Log</a>

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
            </div>
        </x-slot:actions>

        <div class="owl-page">
            <x-gf.panel title="Ringkasan Log">
                <div class="owl-log-card {{ $log->is_done ? 'is-done' : '' }}">
                    <div class="owl-log-main">
                        <div class="owl-log-title-row">
                            <div class="owl-log-title">{{ $log->title }}</div>
                            <span class="owl-badge owl-badge-status-{{ $log->status }}">{{ $log->status_label }}</span>

                            @if ($log->priority)
                                <span class="owl-badge owl-badge-priority-{{ $log->priority }}">{{ $log->priority_label }}</span>
                            @endif
                        </div>

                        <div class="owl-log-meta">
                            {{ optional($log->work_date)->format('d/m/Y') ?: '-' }}
                            · {{ $log->category ?: 'Other' }}
                            @if ($log->page_url)
                                · <a href="{{ $log->page_url }}" target="_blank" rel="noopener">{{ $log->page_url }}</a>
                            @endif
                        </div>

                        @if ($log->description)
                            <div class="owl-log-desc" style="white-space: pre-wrap;">{{ $log->description }}</div>
                        @endif

                        @if ($log->done_at)
                            <div class="owl-log-meta">Selesai: {{ $log->done_at->format('d/m/Y H:i') }}</div>
                        @endif
                    </div>
                </div>
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
