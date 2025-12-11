@extends('layouts.app')

@section('title', 'HPP Sementara • ' . $item->code)

@push('head')
    <style>
        .hpp-form-wrap {
            max-width: 480px;
            margin-inline: auto;
            padding: 1rem .9rem 3rem;
        }
    </style>
@endpush

@section('content')
    <div class="hpp-form-wrap">
        <div class="mb-3">
            <h1 class="text-lg font-semibold tracking-tight">
                Set HPP Sementara
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ $item->code }} — {{ $item->name }}
            </p>
        </div>

        <div
            class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm p-4 sm:p-5">
            <form method="POST" action="{{ route('master.items.hpp_temp.store', $item->id) }}">
                @csrf

                <div class="mb-3">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                        HPP Sementara (Rp / unit)
                    </label>
                    <input type="number" step="0.01" name="unit_cost"
                        value="{{ old('unit_cost', $snapshot->unit_cost ?? '') }}"
                        class="w-full rounded-md border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    @error('unit_cost')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                        Catatan (opsional)
                    </label>
                    <input type="text" name="notes" value="{{ old('notes', $snapshot->notes ?? '') }}"
                        class="w-full rounded-md border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <button type="submit"
                    class="w-full inline-flex justify-center items-center px-4 py-2 rounded-md text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700">
                    Simpan HPP Sementara
                </button>
            </form>

            @if ($snapshot)
                <p class="mt-3 text-xs text-slate-500">
                    HPP aktif saat ini:
                    <strong>Rp {{ number_format($snapshot->unit_cost, 0) }}</strong>
                    (snapshot {{ $snapshot->snapshot_date?->format('d/m/Y') ?? '-' }})
                </p>
            @endif
        </div>
    </div>
@endsection
