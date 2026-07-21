import re

with open('resources/views/inventory/warehouse_intelligence/index.blade.php', 'r') as f:
    content = f.read()

old_php = """@php
    $tabs = [
        'rts' => 'Kebutuhan WH-RTS',
        'prd' => 'Prioritas WH-PRD',
    ];
    $tabDesc = [
        'rts' => 'Daftar item yang stoknya menipis di area display/packing (WH-RTS).',
        'prd' => 'Daftar prioritas packing/transfer & jahit dari sudut pandang Gudang Produksi.',
    ];
@endphp"""

new_php = """@php
    $role = strtolower((string)(auth()->user()?->role ?? 'owner'));
    $allTabs = [
        'rts' => 'Kebutuhan WH-RTS',
        'prd' => 'Prioritas WH-PRD',
    ];
    $allTabDesc = [
        'rts' => 'Daftar item yang stoknya menipis di area display/packing (WH-RTS).',
        'prd' => 'Daftar prioritas packing/transfer & jahit dari sudut pandang Gudang Produksi.',
    ];

    $tabs = [];
    $tabDesc = [];

    if ($role === 'admin') {
        $tabs['rts'] = $allTabs['rts'];
        $tabDesc['rts'] = $allTabDesc['rts'];
    } elseif ($role === 'operating') {
        $tabs['prd'] = $allTabs['prd'];
        $tabDesc['prd'] = $allTabDesc['prd'];
    } else {
        $tabs = $allTabs;
        $tabDesc = $allTabDesc;
    }
@endphp"""

content = content.replace(old_php, new_php)

with open('resources/views/inventory/warehouse_intelligence/index.blade.php', 'w') as f:
    f.write(content)

