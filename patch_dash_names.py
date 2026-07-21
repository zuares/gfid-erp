import os

files = {
    'resources/views/dashboard/partials/admin.blade.php': ('Kebutuhan WH-RTS', 'Stok Gudang', 'Rekomendasi stok RTS'),
    'resources/views/dashboard/partials/operating.blade.php': ('Prioritas WH-PRD', 'Stok Gudang', 'Prioritas packing & jahit'),
    'resources/views/dashboard/partials/owner.blade.php': ('Wh. Intelligence', 'Stok Gudang', 'Rekomendasi stok')
}

for file, (old_name, new_name, desc) in files.items():
    if os.path.exists(file):
        with open(file, 'r') as f:
            content = f.read()
        
        content = content.replace(f'<span class="t">{old_name}<small>{desc}</small></span>', f'<span class="t">{new_name}<small>{desc}</small></span>')
        
        with open(file, 'w') as f:
            f.write(content)

