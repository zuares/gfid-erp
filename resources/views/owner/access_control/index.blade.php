@extends('layouts.app')

@section('title', 'Owner • Akses Login')

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .ac-page { display: grid; gap: 1rem; }

        /* ---------- Toolbar ---------- */
        .ac-toolbar {
            display: flex; flex-wrap: wrap; gap: .6rem; align-items: center;
            margin-bottom: .25rem;
        }
        .ac-search { position: relative; flex: 1 1 240px; min-width: 200px; }
        .ac-search input {
            width: 100%; height: 42px; border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, .12); background: #fff;
            padding: 0 1rem 0 2.4rem; font-weight: 650; color: #0f172a;
        }
        .ac-search .bi {
            position: absolute; left: .95rem; top: 50%; transform: translateY(-50%);
            color: #94a3b8;
        }
        .ac-filter {
            height: 42px; border-radius: 999px; border: 1px solid rgba(15, 23, 42, .12);
            background: #fff; padding: 0 1rem; font-weight: 750; color: #0f172a;
        }
        .ac-count {
            margin-left: auto; color: #64748b; font-size: .82rem; font-weight: 750;
            white-space: nowrap;
        }

        /* ---------- Table ---------- */
        .ac-table-wrap {
            overflow: auto; -webkit-overflow-scrolling: touch;
            border: 1px solid rgba(15, 23, 42, .08); border-radius: 14px;
            max-height: 72vh;
        }
        .ac-table { min-width: 1040px; border-collapse: separate; border-spacing: 0; margin: 0; }
        .ac-table th, .ac-table td { border-bottom: 1px solid rgba(15, 23, 42, .06); }

        .ac-table thead th {
            position: sticky; top: 0; z-index: 3; background: #f8fafc;
            color: #475569; font-size: .72rem; font-weight: 900;
            text-transform: uppercase; letter-spacing: .04em; white-space: nowrap;
            padding: .5rem .55rem;
        }
        .ac-table thead tr.ac-modrow th { top: 34px; text-align: center; }

        .ac-grouphead {
            text-align: center; border-left: 1px solid rgba(15, 23, 42, .08);
            color: #0f172a; background: #eef2f7 !important;
        }

        /* sticky first column (user) */
        .ac-col-user { position: sticky; left: 0; z-index: 4; background: #f8fafc; text-align: left !important; min-width: 230px; }
        .ac-table tbody td.ac-col-user { background: #fff; z-index: 2; box-shadow: 1px 0 0 rgba(15,23,42,.06); }
        .ac-table thead th.ac-col-user { z-index: 6; box-shadow: 1px 0 0 rgba(15,23,42,.06); }

        .ac-modhead { cursor: pointer; user-select: none; }
        .ac-modhead:hover { background: #eef2f7; }
        .ac-modhead .ac-modlabel { display: block; font-size: .68rem; margin-top: .15rem; }
        .ac-modhead .bi { font-size: .95rem; color: #334155; }
        .ac-modhead .ac-coltoggle {
            display: block; margin: .2rem auto 0; font-size: .6rem; color: #2563eb;
            font-weight: 800; text-transform: none; letter-spacing: 0;
        }

        .ac-table tbody td { padding: .55rem; vertical-align: middle; }
        .ac-table tbody tr:hover td { background: #f8fafc; }
        .ac-table tbody tr:hover td.ac-col-user { background: #f8fafc; }

        .ac-name { color: #0f172a; font-weight: 900; line-height: 1.2; }
        .ac-meta { color: #64748b; font-size: .78rem; margin-top: .2rem; display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
        .ac-role {
            display: inline-flex; align-items: center; border-radius: 999px;
            padding: .12rem .5rem; background: #f1f5f9; color: #334155;
            font-size: .66rem; font-weight: 850; text-transform: uppercase;
        }
        .ac-role.owner { background: #fef3c7; color: #92400e; }
        .ac-role.admin { background: #dbeafe; color: #1e40af; }
        .ac-role.operating { background: #dcfce7; color: #166534; }

        .ac-rowtools { display: flex; gap: .3rem; margin-top: .45rem; flex-wrap: wrap; }
        .ac-mini {
            border: 1px solid rgba(15, 23, 42, .12); background: #fff; color: #334155;
            border-radius: 999px; font-size: .66rem; font-weight: 800; padding: .12rem .5rem;
            cursor: pointer; line-height: 1.4;
        }
        .ac-mini:hover { background: #f1f5f9; }

        .ac-badge {
            display: inline-flex; align-items: center; gap: .25rem; border-radius: 999px;
            padding: .1rem .5rem; font-size: .66rem; font-weight: 850;
            background: #ecfdf5; color: #047857;
        }
        .ac-badge.zero { background: #fef2f2; color: #b91c1c; }

        .ac-cell { text-align: center; }
        .ac-cell input[type="checkbox"] { width: 1.15rem; height: 1.15rem; cursor: pointer; }
        .ac-cell.locked { position: relative; }
        .ac-lock {
            display: inline-flex; align-items: center; justify-content: center;
            width: 1.35rem; height: 1.35rem; border-radius: 6px;
        }
        .ac-lock.on { background: #ecfdf5; color: #059669; }
        .ac-lock.off { background: #f1f5f9; color: #94a3b8; }
        .ac-cell.dirty input { outline: 2px solid #f59e0b; outline-offset: 2px; border-radius: 4px; }

        .ac-empty { text-align: center; color: #64748b; font-weight: 700; padding: 2rem; }

        /* ---------- Save bar ---------- */
        .ac-note {
            border: 1px solid rgba(59, 130, 246, .18); background: #eff6ff;
            color: #1e3a8a; border-radius: 12px; padding: .75rem .9rem;
            font-size: .84rem; font-weight: 700;
        }
        .ac-savebar {
            position: sticky; bottom: 0; z-index: 5;
            display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
            background: rgba(255, 255, 255, .92); backdrop-filter: blur(6px);
            border: 1px solid rgba(15, 23, 42, .1); border-radius: 14px;
            padding: .7rem .9rem; margin-top: .25rem;
        }
        .ac-dirty-info { color: #b45309; font-weight: 800; font-size: .84rem; display: none; }
        .ac-dirty-info.show { display: inline; }
        .ac-savebar .spacer { margin-left: auto; }
        .ac-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
            min-height: 42px; padding: .55rem 1.1rem; border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, .1); background: #fff;
            color: #0f172a; text-decoration: none; font-weight: 850; cursor: pointer;
        }
        .ac-btn[disabled] { opacity: .45; cursor: not-allowed; }
        .ac-btn-primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .ac-btn-primary:hover { color: #fff; background: #1e293b; }

        @media (max-width: 768px) {
            .gf-master-header { padding: 12px 14px; border-radius: 14px; }
            .gf-master-title { font-size: 18px; }
            .ac-count { margin-left: 0; width: 100%; }
            .ac-savebar .ac-btn { flex: 1 1 0; }
        }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Owner"
        title="Akses Login & Sidebar"
        description="Atur modul (menu sidebar) yang boleh dibuka tiap user. Centang untuk menampilkan, hapus centang untuk menyembunyikan. Owner selalu punya akses penuh.">
        <div class="ac-page">
            @if (session('message'))
                <div class="gf-mpl-insight">
                    <span class="gf-mpl-insight-ico">✓</span>
                    <b>{{ session('message') }}</b>
                </div>
            @endif

            <div class="ac-note">
                <i class="bi bi-info-circle"></i>
                Centang = menu muncul di sidebar user. Modul bertanda <i class="bi bi-lock-fill"></i> dikunci oleh role
                (Owner penuh; Admin wajib Produksi/Pembelian/Marketplace, dan tidak boleh Accounting/Imports) sehingga tidak bisa diubah di sini.
            </div>

            <x-gf.panel title="Pengaturan Akses" subtitle="Cari user, atur per kolom atau per baris, lalu simpan.">
                <form method="POST" action="{{ route('owner.access-control.update') }}" id="acForm">
                    @csrf
                    @method('PUT')

                    <div class="ac-toolbar">
                        <div class="ac-search">
                            <i class="bi bi-search"></i>
                            <input type="search" id="acSearch" placeholder="Cari nama, kode, atau role user…" autocomplete="off">
                        </div>
                        <select class="ac-filter" id="acRoleFilter">
                            <option value="">Semua role</option>
                            @foreach ($users->pluck('role')->unique()->filter()->values() as $r)
                                <option value="{{ strtolower($r) }}">{{ ucfirst($r) }}</option>
                            @endforeach
                        </select>
                        <span class="ac-count" id="acCount"></span>
                    </div>

                    <div class="ac-table-wrap">
                        <table class="table gf-clean-table ac-table" id="acTable">
                            <thead>
                                <tr class="ac-grouprow">
                                    <th class="ac-col-user" rowspan="2">User</th>
                                    @foreach ($moduleGroups as $group)
                                        <th class="ac-grouphead" colspan="{{ count($group['modules']) }}">{{ $group['label'] }}</th>
                                    @endforeach
                                </tr>
                                <tr class="ac-modrow">
                                    @foreach ($moduleGroups as $group)
                                        @foreach ($group['modules'] as $module)
                                            @php $meta = $moduleMeta[$module] ?? ['icon' => 'bi-square', 'desc' => '']; @endphp
                                            <th class="ac-modhead" data-module="{{ $module }}"
                                                title="{{ $meta['desc'] }} — klik untuk centang/hapus satu kolom">
                                                <i class="bi {{ $meta['icon'] }}"></i>
                                                <span class="ac-modlabel">{{ $modules[$module] }}</span>
                                                <span class="ac-coltoggle">semua ⇅</span>
                                            </th>
                                        @endforeach
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    @php
                                        $effective = $user->effectiveModuleAccess();
                                        $defaults = \App\Models\User::defaultModulesForRole((string) $user->role);
                                        $editableDefaults = collect($effective)
                                            ->filter(fn ($e, $m) => !$e['locked'] && in_array($m, $defaults, true))
                                            ->keys()->implode(',');
                                        $roleLower = strtolower((string) $user->role);
                                    @endphp
                                    <tr class="ac-row"
                                        data-search="{{ strtolower($user->name . ' ' . $user->employee_code . ' ' . $user->role) }}"
                                        data-role="{{ $roleLower }}"
                                        data-default="{{ $editableDefaults }}">
                                        <td class="ac-col-user">
                                            <input type="hidden" name="user_ids[]" value="{{ $user->id }}">
                                            <div class="ac-name">{{ $user->name }}</div>
                                            <div class="ac-meta">
                                                <span>{{ $user->employee_code }}</span> ·
                                                <span class="ac-role {{ $roleLower }}">{{ $user->role }}</span>
                                                <span class="ac-badge" data-count-badge>0</span>
                                            </div>
                                            <div class="ac-rowtools">
                                                <button type="button" class="ac-mini" data-row-all>Semua</button>
                                                <button type="button" class="ac-mini" data-row-none>Kosong</button>
                                                <button type="button" class="ac-mini" data-row-default title="Kembalikan ke default role">Default</button>
                                            </div>
                                        </td>
                                        @foreach ($moduleGroups as $group)
                                            @foreach ($group['modules'] as $module)
                                                @php $e = $effective[$module]; @endphp
                                                @if ($e['locked'])
                                                    <td class="ac-cell locked" title="{{ $e['reason'] }}">
                                                        <span class="ac-lock {{ $e['on'] ? 'on' : 'off' }}">
                                                            <i class="bi {{ $e['on'] ? 'bi-lock-fill' : 'bi-lock' }}"></i>
                                                        </span>
                                                    </td>
                                                @else
                                                    <td class="ac-cell" data-module="{{ $module }}">
                                                        <input type="checkbox"
                                                            name="access[{{ $user->id }}][]"
                                                            value="{{ $module }}"
                                                            data-access
                                                            @checked($e['on'])>
                                                    </td>
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="ac-empty" id="acEmpty" style="display:none;">Tidak ada user yang cocok dengan pencarian.</div>

                    <div class="ac-savebar">
                        <a href="{{ route('dashboard') }}" class="ac-btn"><i class="bi bi-arrow-left"></i> Kembali</a>
                        <span class="ac-dirty-info" id="acDirty"><i class="bi bi-exclamation-triangle-fill"></i> <span id="acDirtyCount">0</span> perubahan belum disimpan</span>
                        <span class="spacer"></span>
                        <button type="button" class="ac-btn" id="acReset" disabled><i class="bi bi-arrow-counterclockwise"></i> Batalkan</button>
                        <button type="submit" class="ac-btn ac-btn-primary"><i class="bi bi-check2-circle"></i> Simpan Akses</button>
                    </div>
                </form>
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('acForm');
    if (!form) return;
    const table  = document.getElementById('acTable');
    const rows   = Array.from(table.querySelectorAll('tbody tr.ac-row'));
    const boxes  = Array.from(table.querySelectorAll('input[data-access]'));
    const search = document.getElementById('acSearch');
    const roleF  = document.getElementById('acRoleFilter');
    const countEl= document.getElementById('acCount');
    const emptyEl= document.getElementById('acEmpty');
    const dirtyEl= document.getElementById('acDirty');
    const dirtyCountEl = document.getElementById('acDirtyCount');
    const resetBtn = document.getElementById('acReset');

    // snapshot awal untuk deteksi perubahan
    const initial = new Map();
    boxes.forEach((b, i) => { b.dataset.k = i; initial.set(i, b.checked); });

    function refreshBadge(row) {
        const badge = row.querySelector('[data-count-badge]');
        if (!badge) return;
        const lockedOn = row.querySelectorAll('.ac-lock.on').length;
        const checked  = row.querySelectorAll('input[data-access]:checked').length;
        const total = lockedOn + checked;
        badge.textContent = total;
        badge.classList.toggle('zero', total === 0);
    }

    function refreshDirty() {
        let n = 0;
        boxes.forEach(b => {
            const changed = initial.get(+b.dataset.k) !== b.checked;
            b.closest('.ac-cell').classList.toggle('dirty', changed);
            if (changed) n++;
        });
        dirtyCountEl.textContent = n;
        dirtyEl.classList.toggle('show', n > 0);
        resetBtn.disabled = n === 0;
        return n;
    }

    function markChanged(row) {
        if (row) refreshBadge(row);
        refreshDirty();
    }

    // ---- filter ----
    function applyFilter() {
        const q = (search.value || '').trim().toLowerCase();
        const role = roleF.value;
        let shown = 0;
        rows.forEach(row => {
            const okQ = !q || row.dataset.search.includes(q);
            const okR = !role || row.dataset.role === role;
            const vis = okQ && okR;
            row.style.display = vis ? '' : 'none';
            if (vis) shown++;
        });
        countEl.textContent = shown + ' dari ' + rows.length + ' user';
        emptyEl.style.display = shown === 0 ? 'block' : 'none';
    }
    search.addEventListener('input', applyFilter);
    roleF.addEventListener('change', applyFilter);

    // ---- column toggle (hanya baris yang terlihat) ----
    table.querySelectorAll('th.ac-modhead').forEach(th => {
        th.addEventListener('click', () => {
            const mod = th.dataset.module;
            const targets = rows.filter(r => r.style.display !== 'none')
                .flatMap(r => Array.from(r.querySelectorAll('.ac-cell[data-module="' + mod + '"] input[data-access]')));
            if (!targets.length) return;
            const turnOn = targets.some(b => !b.checked); // ada yg off → nyalakan semua
            targets.forEach(b => { b.checked = turnOn; refreshBadge(b.closest('tr')); });
            refreshDirty();
        });
    });

    // ---- row tools ----
    rows.forEach(row => {
        const setRow = (val) => {
            row.querySelectorAll('input[data-access]').forEach(b => b.checked = val);
            markChanged(row);
        };
        row.querySelector('[data-row-all]')?.addEventListener('click', () => setRow(true));
        row.querySelector('[data-row-none]')?.addEventListener('click', () => setRow(false));
        row.querySelector('[data-row-default]')?.addEventListener('click', () => {
            const defs = (row.dataset.default || '').split(',').filter(Boolean);
            row.querySelectorAll('input[data-access]').forEach(b => { b.checked = defs.includes(b.value); });
            markChanged(row);
        });
        refreshBadge(row);
    });

    boxes.forEach(b => b.addEventListener('change', () => markChanged(b.closest('tr'))));

    // ---- reset all ----
    resetBtn.addEventListener('click', () => {
        boxes.forEach(b => b.checked = initial.get(+b.dataset.k));
        rows.forEach(refreshBadge);
        refreshDirty();
    });

    // ---- unsaved guard ----
    let submitting = false;
    form.addEventListener('submit', () => { submitting = true; });
    window.addEventListener('beforeunload', (e) => {
        if (!submitting && refreshDirty() > 0) { e.preventDefault(); e.returnValue = ''; }
    });

    applyFilter();
    refreshDirty();
})();
</script>
@endpush
