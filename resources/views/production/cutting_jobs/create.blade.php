{{-- resources/views/production/cutting_jobs/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Cutting Job Baru')

@push('head')
    <style>
        .cutting-create-page {
            min-height: 100vh;
        }

        .cutting-create-page .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
            padding: 1rem 1rem 4rem;
        }

        body[data-theme="light"] .cutting-create-page .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, .12) 0,
                    rgba(45, 212, 191, .10) 26%,
                    #f9fafb 60%);
        }

        body[data-theme="dark"] .cutting-create-page .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, .20) 0,
                    rgba(45, 212, 191, .18) 30%,
                    #020617 70%);
        }

        @media (max-width: 767.98px) {
            html, body { overflow-x: hidden; }

            .cutting-create-page,
            .cutting-create-page .page-wrap { overflow-x: hidden; }

            .cutting-create-page .page-wrap {
                padding: .5rem .5rem 3rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="cutting-create-page">
        <div class="page-wrap">
            {{-- FLASH --}}
            @if (session('success') && !session('dev_rollback_result'))
                <div class="alert alert-success py-2 px-3 mb-2">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger py-2 px-3 mb-2">
                    <div class="small fw-semibold mb-1">Terjadi kesalahan:</div>
                    <ul class="mb-0 small">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- FORM DIPISAH KE PARTIAL --}}
            @include('production.cutting_jobs._form')
        </div>
    </div>
@endsection

@if (session('dev_rollback_result'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (!window.Swal) return;

                const result = @json(session('dev_rollback_result'));
                const fmt = new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });

                Swal.fire({
                    icon: 'success',
                    title: 'Simulasi Berhasil',
                    html: `
                        <div style="text-align:left;font-size:.9rem;line-height:1.65">
                            <div><b>Job:</b> <code>${result.code || '-'}</code></div>
                            <div><b>Bundle:</b> ${fmt.format(result.bundle_count || 0)}</div>
                            <div><b>Total pcs:</b> ${fmt.format(result.qty_pcs || 0)}</div>
                            <div><b>Kain terpakai:</b> ${fmt.format(result.used_fabric || 0)}</div>
                            <div><b>LOT:</b> ${fmt.format(result.lot_count || 0)}</div>
                            <hr>
                            <div>Tidak ada data atau stok yang berubah karena mode rollback aktif.</div>
                        </div>
                    `,
                    confirmButtonText: 'Oke',
                    confirmButtonColor: '#2563eb'
                });
            });
        </script>
    @endpush
@endif
