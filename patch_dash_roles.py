import re

# Update admin dashboard
with open('resources/views/dashboard/partials/admin.blade.php', 'r') as f:
    admin_content = f.read()

admin_shortcut = """    @if($u = $r('inventory.warehouse_intelligence.index'))
        <a class="act" href="{{ $u }}"><span class="ico indigo" style="color: #4f46e5; background: #e0e7ff;"><i class="bi bi-cpu"></i></span><span class="t">Kebutuhan WH-RTS<small>Rekomendasi stok RTS</small></span></a>
    @endif
"""
admin_content = admin_content.replace('<div class="dash-actions">', '<div class="dash-actions">\n' + admin_shortcut)

with open('resources/views/dashboard/partials/admin.blade.php', 'w') as f:
    f.write(admin_content)


# Update operating dashboard
with open('resources/views/dashboard/partials/operating.blade.php', 'r') as f:
    opr_content = f.read()

opr_shortcut = """    @if($u = $r('inventory.warehouse_intelligence.index'))
        <a class="act" href="{{ $u }}"><span class="ico indigo" style="color: #4f46e5; background: #e0e7ff;"><i class="bi bi-cpu"></i></span><span class="t">Prioritas WH-PRD<small>Prioritas packing & jahit</small></span></a>
    @endif
"""
opr_content = opr_content.replace('<div class="dash-actions">', '<div class="dash-actions">\n' + opr_shortcut)

with open('resources/views/dashboard/partials/operating.blade.php', 'w') as f:
    f.write(opr_content)


# Update owner dashboard
try:
    with open('resources/views/dashboard/partials/owner.blade.php', 'r') as f:
        owner_content = f.read()
    
    owner_shortcut = """    @if($u = $r('inventory.warehouse_intelligence.index'))
        <a class="act" href="{{ $u }}"><span class="ico indigo" style="color: #4f46e5; background: #e0e7ff;"><i class="bi bi-cpu"></i></span><span class="t">Wh. Intelligence<small>Rekomendasi stok</small></span></a>
    @endif
"""
    owner_content = owner_content.replace('<div class="dash-actions">', '<div class="dash-actions">\n' + owner_shortcut)
    
    with open('resources/views/dashboard/partials/owner.blade.php', 'w') as f:
        f.write(owner_content)
except FileNotFoundError:
    pass

