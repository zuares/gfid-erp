<?php
$content = file_get_contents('resources/views/inventory/warehouse_intelligence/partials/_rts.blade.php');

// For btn-bulk-minta
$minta_search = <<<JS
                    if (typeof Toast !== 'undefined') Toast.fire({ icon: 'success', title: 'Berhasil request ' + lines.length + ' item!' });
                    document.getElementById('bulk-action-container').style.display = 'none';
JS;

$minta_replace = <<<JS
                    if (typeof Toast !== 'undefined') Toast.fire({ icon: 'success', title: 'Berhasil request ' + lines.length + ' item!' });
                    
                    // Update the bulk button to be a link to the draft
                    this.outerHTML = '<a href="'+showUrl+'" class="sd-btn sd-primary" style="background:#10b981!important;border-color:#10b981!important;color:#fff!important;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Lihat Detail</a>';
                    
                    const btnPr = document.getElementById('btn-bulk-pr');
                    if(btnPr) btnPr.style.display = 'none';
JS;

$content = str_replace($minta_search, $minta_replace, $content);

// For btn-bulk-pr
$pr_search = <<<JS
                    if (typeof Toast !== 'undefined') Toast.fire({ icon: 'success', title: 'Berhasil buat PR untuk ' + lines.length + ' item!' });
                    document.getElementById('bulk-action-container').style.display = 'none';
JS;

$pr_replace = <<<JS
                    if (typeof Toast !== 'undefined') Toast.fire({ icon: 'success', title: 'Berhasil buat PR untuk ' + lines.length + ' item!' });
                    
                    // Update the bulk button to be a link to the draft
                    this.outerHTML = '<a href="'+showUrl+'" class="sd-btn sd-primary" style="background:#f59e0b!important;border-color:#f59e0b!important;color:#fff!important;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Lihat Detail</a>';
                    
                    const btnMinta = document.getElementById('btn-bulk-minta');
                    if(btnMinta) btnMinta.style.display = 'none';
JS;

$content = str_replace($pr_search, $pr_replace, $content);

file_put_contents('resources/views/inventory/warehouse_intelligence/partials/_rts.blade.php', $content);
?>
