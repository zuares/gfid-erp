<?php
$content = file_get_contents('resources/views/inventory/warehouse_intelligence/partials/_rts.blade.php');

// Replace the inline styles and btn classes for filters
$content = preg_replace('/class="btn btn-sm btn-outline-primary active btn-filter-rts" data-filter="all" style="[^"]+"/', 'class="btn-filter-rts active sd-btn sd-primary" data-filter="all"', $content);
$content = preg_replace('/class="btn btn-sm btn-outline-danger btn-filter-rts" data-filter="kritis" style="[^"]+"/', 'class="btn-filter-rts sd-btn" data-filter="kritis"', $content);
$content = preg_replace('/class="btn btn-sm btn-outline-success btn-filter-rts" data-filter="tarik_prd" style="[^"]+"/', 'class="btn-filter-rts sd-btn" data-filter="tarik_prd"', $content);
$content = preg_replace('/class="btn btn-sm btn-outline-warning btn-filter-rts" data-filter="beli_jadi" style="[^"]+"/', 'class="btn-filter-rts sd-btn" data-filter="beli_jadi"', $content);

// Replace the bulk action buttons
$content = preg_replace('/class="btn btn-sm" id="btn-bulk-minta" style="background: #10b981; color: #fff; border: none; font-weight: 600; font-size: .75rem; border-radius: 20px; padding: .25rem .75rem; display: none; align-items: center; gap: 4px; box-shadow: 0 2px 4px rgba\(16, 185, 129, 0.2\);"/', 'class="sd-btn sd-primary" id="btn-bulk-minta" style="display: none;"', $content);
$content = preg_replace('/class="btn btn-sm" id="btn-bulk-pr" style="background: #f59e0b; color: #fff; border: none; font-weight: 600; font-size: .75rem; border-radius: 20px; padding: .25rem .75rem; display: none; align-items: center; gap: 4px; box-shadow: 0 2px 4px rgba\(245, 158, 11, 0.2\);"/', 'class="sd-btn sd-primary" id="btn-bulk-pr" style="display: none;"', $content);

// Replace the JS toggle logic
$js_search = <<<JS
                document.querySelectorAll('.btn-filter-rts').forEach(b => {
                    b.classList.remove('active');
                    // Reset to outline
                    if(b.classList.contains('btn-primary')) { b.classList.replace('btn-primary', 'btn-outline-primary'); }
                    if(b.classList.contains('btn-danger')) { b.classList.replace('btn-danger', 'btn-outline-danger'); }
                    if(b.classList.contains('btn-success')) { b.classList.replace('btn-success', 'btn-outline-success'); }
                    if(b.classList.contains('btn-warning')) { b.classList.replace('btn-warning', 'btn-outline-warning'); }
                });
                
                this.classList.add('active');
                if(this.classList.contains('btn-outline-primary')) { this.classList.replace('btn-outline-primary', 'btn-primary'); }
                if(this.classList.contains('btn-outline-danger')) { this.classList.replace('btn-outline-danger', 'btn-danger'); }
                if(this.classList.contains('btn-outline-success')) { this.classList.replace('btn-outline-success', 'btn-success'); }
                if(this.classList.contains('btn-outline-warning')) { this.classList.replace('btn-outline-warning', 'btn-warning'); }
JS;

$js_replace = <<<JS
                document.querySelectorAll('.btn-filter-rts').forEach(b => {
                    b.classList.remove('active', 'sd-primary');
                });
                this.classList.add('active', 'sd-primary');
JS;

$content = str_replace($js_search, $js_replace, $content);

file_put_contents('resources/views/inventory/warehouse_intelligence/partials/_rts.blade.php', $content);
?>
