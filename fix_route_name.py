import os

files = [
    'resources/views/dashboard/partials/admin.blade.php',
    'resources/views/dashboard/partials/operating.blade.php',
    'resources/views/dashboard/partials/owner.blade.php'
]

for file in files:
    if os.path.exists(file):
        with open(file, 'r') as f:
            content = f.read()
        
        content = content.replace("inventory.warehouse_intelligence.index", "inventory.warehouse_intelligence")
        
        with open(file, 'w') as f:
            f.write(content)

