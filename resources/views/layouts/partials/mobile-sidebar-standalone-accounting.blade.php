@if ($hasStandaloneAccounting)
    <div class="mobile-sidebar-section-label">Akuntansi Baru</div>
    <li class="mb-1">
        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $standaloneAccountingOpen ? 'is-open' : '' }}"
                type="button" data-bs-toggle="collapse" data-bs-target="#navStandaloneAccountingMobile"
                aria-expanded="{{ $standaloneAccountingOpen ? 'true' : 'false' }}" aria-controls="navStandaloneAccountingMobile">
            <span class="icon"><i class="bi bi-journal-richtext"></i></span>
            <span>Akuntansi Baru</span>
            <span class="chevron">▸</span>
        </button>
        <div class="collapse {{ $standaloneAccountingOpen ? 'show' : '' }}" id="navStandaloneAccountingMobile">
            <a href="{{ route('standalone-accounting.journal-menu.index') }}"
               class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('standalone-accounting.journal-menu.*') ? 'active' : '' }}">
                <span class="icon"><i class="bi bi-grid-1x2"></i></span><span>Menu Jurnal</span>
            </a>
            <a href="{{ route('standalone-accounting.trial-balance.index') }}"
               class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('standalone-accounting.trial-balance.*') ? 'active' : '' }}">
                <span class="icon"><i class="bi bi-scale"></i></span><span>Neraca Saldo</span>
            </a>
            <a href="{{ route('standalone-accounting.journals.index') }}"
               class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('standalone-accounting.journals.*') ? 'active' : '' }}">
                <span class="icon"><i class="bi bi-journal-text"></i></span><span>Jurnal Baru</span>
            </a>
            <a href="{{ route('standalone-accounting.journals.create', ['mode' => 'opening']) }}"
               class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('standalone-accounting.journals.create') && request('mode') === 'opening' ? 'active' : '' }}">
                <span class="icon"><i class="bi bi-wallet2"></i></span><span>Saldo Awal</span>
            </a>
            <a href="{{ route('standalone-accounting.stock-opname-variance.index') }}"
               class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('standalone-accounting.stock-opname-variance.*') ? 'active' : '' }}">
                <span class="icon"><i class="bi bi-clipboard2-data"></i></span><span>Stock Opname &amp; Variance</span>
            </a>
            <a href="{{ route('standalone-accounting.opening-migration.index') }}"
               class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('standalone-accounting.opening-migration.*') ? 'active' : '' }}">
                <span class="icon"><i class="bi bi-box-arrow-in-right"></i></span><span>Opening Migrasi</span>
            </a>
            <a href="{{ route('standalone-accounting.accounts.index') }}"
               class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('standalone-accounting.accounts.*') ? 'active' : '' }}">
                <span class="icon"><i class="bi bi-folder2-open"></i></span><span>Daftar Akun</span>
            </a>
        </div>
    </li>
@endif
