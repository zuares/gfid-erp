<nav class="mpcrm-module-nav mb-4" aria-label="Marketplace CRM navigation"><div class="mpcrm-nav-links">
    <a href="{{ route('admin.crm.marketplace.dashboard') }}" class="mpcrm-nav-link {{ request()->routeIs('admin.crm.marketplace.dashboard') ? 'is-active' : '' }}">
        <i class="bi bi-grid-1x2 me-1"></i> Beranda
    </a>
    <a href="{{ route('admin.crm.marketplace.orders') }}" class="mpcrm-nav-link {{ request()->routeIs('admin.crm.marketplace.orders*') ? 'is-active' : '' }}">
        <i class="bi bi-bag-check me-1"></i> Pesanan
    </a>
    <a href="{{ route('admin.crm.marketplace.customers') }}" class="mpcrm-nav-link {{ request()->routeIs('admin.crm.marketplace.customers') ? 'is-active' : '' }}">
        <i class="bi bi-people me-1"></i> Customer
    </a>
    <a href="{{ route('admin.crm.marketplace.prospects') }}" class="mpcrm-nav-link {{ request()->routeIs('admin.crm.marketplace.prospects') ? 'is-active' : '' }}">
        <i class="bi bi-person-lines-fill me-1"></i> Prospects
    </a>
    <a href="{{ route('admin.crm.marketplace.segments') }}" class="mpcrm-nav-link {{ request()->routeIs('admin.crm.marketplace.segments*') ? 'is-active' : '' }}">
        <i class="bi bi-diagram-3 me-1"></i> Segments
    </a>
    <a href="{{ route('admin.crm.marketplace.import') }}" class="mpcrm-nav-link mpcrm-nav-import {{ request()->routeIs('admin.crm.marketplace.import*') ? 'is-active' : '' }}">
        <i class="bi bi-upload me-1"></i> Import Order
    </a>
    </div><span class="mpcrm-nav-context"><i class="bi bi-shop me-1"></i> CRM Marketplace</span>
</nav>
