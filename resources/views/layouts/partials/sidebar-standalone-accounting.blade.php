@if ($hasStandaloneAccounting)
    <x-sidebar.label text="Akuntansi Baru" />
    <li class="mb-1">
        <button class="sidebar-link sidebar-toggle {{ $openStandaloneAccounting ? 'is-open' : '' }}" type="button"
            data-bs-toggle="collapse" data-bs-target="#navStandaloneAccounting"
            aria-expanded="{{ $openStandaloneAccounting ? 'true' : 'false' }}" aria-controls="navStandaloneAccounting">
            <span class="icon"><i class="bi bi-journal-richtext"></i></span>
            <span>Akuntansi Baru</span>
            <span class="chevron">▸</span>
        </button>
        <div class="collapse {{ $openStandaloneAccounting ? 'show' : '' }}" id="navStandaloneAccounting">
            <x-sidebar.sub-link href="{{ route('standalone-accounting.journal-menu.index') }}" icon="bi bi-grid-1x2"
                :active="request()->routeIs('standalone-accounting.journal-menu.*')">
                Menu Jurnal
            </x-sidebar.sub-link>
            <x-sidebar.sub-link href="{{ route('standalone-accounting.trial-balance.index') }}" icon="bi bi-scale"
                :active="request()->routeIs('standalone-accounting.trial-balance.*')">
                Neraca Saldo
            </x-sidebar.sub-link>
            <x-sidebar.sub-link href="{{ route('standalone-accounting.journals.index') }}" icon="bi bi-journal-text"
                :active="request()->routeIs('standalone-accounting.journals.*')">
                Jurnal Baru
            </x-sidebar.sub-link>
            <x-sidebar.sub-link href="{{ route('standalone-accounting.accounts.index') }}" icon="bi bi-folder2-open"
                :active="request()->routeIs('standalone-accounting.accounts.*')">
                Daftar Akun
            </x-sidebar.sub-link>
        </div>
    </li>
@endif
