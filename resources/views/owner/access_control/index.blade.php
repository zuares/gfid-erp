@extends('layouts.app')

@section('title', 'Owner • Akses Login')

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .ac-page { display: grid; gap: 1rem; }
        .ac-table-wrap { overflow: auto; -webkit-overflow-scrolling: touch; }
        .ac-table { min-width: 980px; }
        .ac-table th {
            color: #64748b; font-size: .72rem; font-weight: 900;
            text-transform: uppercase; letter-spacing: .06em; white-space: nowrap;
        }
        .ac-user { min-width: 220px; }
        .ac-name { color: #0f172a; font-weight: 900; line-height: 1.2; }
        .ac-meta { color: #64748b; font-size: .78rem; margin-top: .15rem; }
        .ac-role {
            display: inline-flex; align-items: center; border-radius: 999px;
            padding: .18rem .55rem; background: #f1f5f9; color: #334155;
            font-size: .72rem; font-weight: 850; text-transform: uppercase;
        }
        .ac-check { display: flex; justify-content: center; }
        .ac-check input {
            width: 1.1rem; height: 1.1rem; cursor: pointer;
        }
        .ac-actions {
            display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap;
        }
        .ac-btn {
            display: inline-flex; align-items: center; justify-content: center;
            min-height: 42px; padding: .58rem 1rem; border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, .1); background: #fff;
            color: #0f172a; text-decoration: none; font-weight: 850;
        }
        .ac-btn-primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .ac-btn-primary:hover { color: #fff; background: #1e293b; }
        .ac-note {
            border: 1px solid rgba(59, 130, 246, .18); background: #eff6ff;
            color: #1e3a8a; border-radius: 12px; padding: .75rem .9rem;
            font-size: .86rem; font-weight: 750;
        }
        @media (max-width: 768px) {
            .gf-master-header { padding: 12px 14px; border-radius: 14px; }
            .gf-master-title { font-size: 18px; }
            .gf-master-desc { font-size: 11.5px; }
            .ac-actions .ac-btn { flex: 1 1 0; }
        }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Owner"
        title="Akses Login"
        description="Atur user mana yang boleh membuka modul tertentu. Owner selalu punya akses penuh.">
        <div class="ac-page">
            @if (session('message'))
                <div class="gf-mpl-insight">
                    <span class="gf-mpl-insight-ico">✓</span>
                    <b>{{ session('message') }}</b>
                </div>
            @endif

            <div class="ac-note">
                Default akses mengikuti role lama kalau belum pernah diatur. Setelah disimpan, checklist di halaman ini menjadi patokan akses modul.
            </div>

            <x-gf.panel title="Pengaturan Akses" subtitle="Centang modul yang boleh dibuka oleh setiap user.">
                <form method="POST" action="{{ route('owner.access-control.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="ac-table-wrap">
                        <table class="table table-hover align-middle mb-0 gf-clean-table ac-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    @foreach ($modules as $module => $label)
                                        <th class="text-center">{{ $label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    @php
                                        $explicit = $user->moduleAccesses->keyBy('module');
                                        $defaults = \App\Models\User::defaultModulesForRole((string) $user->role);
                                    @endphp
                                    <tr>
                                        <td class="ac-user">
                                            <input type="hidden" name="user_ids[]" value="{{ $user->id }}">
                                            <div class="ac-name">{{ $user->name }}</div>
                                            <div class="ac-meta">
                                                {{ $user->employee_code }} · <span class="ac-role">{{ $user->role }}</span>
                                            </div>
                                        </td>
                                        @foreach ($modules as $module => $label)
                                            @php
                                                $checked = $user->isOwner()
                                                    ? true
                                                    : (isset($explicit[$module])
                                                        ? (bool) $explicit[$module]->can_access
                                                        : in_array($module, $defaults, true));
                                            @endphp
                                            <td>
                                                <label class="ac-check" title="{{ $label }}">
                                                    <input type="checkbox" name="access[{{ $user->id }}][]"
                                                        value="{{ $module }}" @checked($checked) @disabled($user->isOwner())>
                                                </label>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="ac-actions mt-3">
                        <a href="{{ route('dashboard') }}" class="ac-btn">Kembali</a>
                        <button type="submit" class="ac-btn ac-btn-primary">Simpan Akses</button>
                    </div>
                </form>
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
