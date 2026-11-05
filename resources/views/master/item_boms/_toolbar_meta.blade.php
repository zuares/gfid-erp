@if($q !== '')
    <span class="bom-filter-chip"><i class="bi bi-search" aria-hidden="true"></i><strong>{{ $q }}</strong></span>
@endif
@if($status !== '')
    <span class="bom-filter-chip"><i class="bi bi-activity" aria-hidden="true"></i><strong>{{ $status === 'active' ? 'Aktif' : 'Nonaktif' }}</strong></span>
@endif
@if($structure !== '')
    <span class="bom-filter-chip"><i class="bi bi-diagram-3" aria-hidden="true"></i><strong>{{ ['main' => 'Bahan utama', 'sewing' => 'Bahan jahit', 'packing' => 'Bahan packing', 'complete' => 'Struktur lengkap'][$structure] }}</strong></span>
@endif
@if($selectedCategory)
    <span class="bom-filter-chip"><i class="bi bi-tags" aria-hidden="true"></i><strong>{{ $selectedCategory->name }}</strong></span>
@endif
@if(!$hasFilters)
    <span><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Filter realtime aktif</span>
@else
    <span><i class="bi bi-funnel me-1" aria-hidden="true"></i>{{ collect([$q !== '', $status !== '', $structure !== '', $categoryId > 0])->filter()->count() }} filter aktif</span>
@endif
