import re

with open('resources/views/components/index-layout.blade.php', 'r') as f:
    content = f.read()

# Add props at the top if not exists
if '@props' not in content:
    content = """@props([
    'title',
    'subtitle' => null,
    'tableClass' => 'table table-hover align-middle table-list mb-0'
])
""" + content

# Replace table class
content = content.replace(
    '<table class="table table-hover align-middle table-list mb-0">', 
    '<table class="{{ $tableClass }}">'
)

with open('resources/views/components/index-layout.blade.php', 'w') as f:
    f.write(content)

