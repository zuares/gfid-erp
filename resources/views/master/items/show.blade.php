@extends('layouts.app')

@section('title', 'Master Item • ' . $item->code)

@push('head')
    <style>
        .item-show-wrap {
            max-width: 900px;
            margin-inline: auto;
            padding: 1rem .9rem 3rem;
        }

        body[data-theme="light"] .item-show-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.08) 0,
                    rgba(148, 163, 184, 0.08) 30%,
                    #f9fafb 70%);
        }

        .item-card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            box-shadow:
                0 8px 20px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }
    </style>
@endpush

@section('content')
    <div class="item-show-wrap">
        <div class="mb-3 flex items-center justify-between gap-2">
            <div>
                <h1 class="text-lg font-semibold tracking-tight">
                    Master Item — {{ $item->code }}
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    {{ $item->name }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('master.items.edit', $item->id) }}"
                    class="inline-flex items-center px-3 py-1.5 rounded-md text-sm border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700">
                    Edit Item
                </a>

                <a href="{{ route('master.items.hpp_temp.edit', $item->id) }}"
                    class="inline-flex items-center px-3 py-1.5 rounded-md text-sm bg-emerald-600 text-white hover:bg-emerald-700">
                    Set HPP Sementara
                </a>
            </div>
        </div>

        <div class="item-card p-4 sm:p-5 space-y-4">
            {{-- Info dasar item --}}
            <div class="grid sm:grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Kode</p>
                    <p class="font-medium">{{ $item->code }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Nama</p>
                    <p class="font-medium">{{ $item->name }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Kategori</p>
                    <p class="font-medium">{{ $item->category->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Satuan</p>
                    <p class="font-medium">{{ $item->uom ?? '-' }}</p>
                </div>
            </div>

            <hr class="border-dashed border-slate-200 dark:border-slate-700">

            {{-- HPP Aktif --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase mb-1">HPP Aktif (Sementara)</p>
                    <p class="text-xl font-semibold">
                        Rp {{ number_format($item->active_unit_cost, 0) }}
                    </p>
                    @if (optional($item->costSnapshots()->active()->latest('snapshot_date')->latest('id')->first())->reference_type)
                        <p class="text-xs text-slate-500 mt-1">
                            Sumber:
                            {{ $item->costSnapshots()->active()->latest('snapshot_date')->latest('id')->first()->reference_type }}
                        </p>
                    @else
                        <p class="text-xs text-slate-400 mt-1">
                            Belum ada HPP aktif — klik "Set HPP Sementara".
                        </p>
                    @endif
                </div>

                <div class="inline-flex items-center gap-2">
                    <span
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                    @if ($item->active_unit_cost > 0) bg-emerald-50 text-emerald-700 border border-emerald-200
                    @else
                        bg-amber-50 text-amber-700 border border-amber-200 @endif
                    ">
                        @if ($item->active_unit_cost > 0)
                            HPP aktif tersedia
                        @else
                            HPP belum di-set
                        @endif
                    </span>
                </div>
            </div>

            {{-- (Opsional) bisa tambah section lain di bawah sini --}}
        </div>
    </div>
@endsection
