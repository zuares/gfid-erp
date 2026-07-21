import re

with open('resources/views/inventory/rts_stock_requests/show.blade.php', 'r') as f:
    content = f.read()

# Add CSS for footer if it doesn't exist
css_addition = """
    .sd-btn-row { display: flex; justify-content: space-between; gap: .75rem; flex-wrap: wrap; margin-top: .9rem; align-items: center; padding-inline: .2rem; }
    .sd-muted { color: #64748b; font-size: .8rem; }
    .sd-actions-bottom { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; justify-content: flex-end; }
"""
if '.sd-btn-row' not in content:
    content = content.replace('</style>', css_addition + '</style>')


# Remove the buttons from the top bar
# Pattern to match the "Terima Jadi" button block
terima_jadi_pattern = r"\s*@if \(\$canManage && \$canReceive\)\s*<a href=\"\{\{ route\('rts\.stock-requests\.confirm', \$stockRequest\) \}\}\" class=\"sd-btn sd-primary\">Terima Jadi</a>\s*@endif"
content = re.sub(terima_jadi_pattern, "", content)

# Pattern to match the "Batalkan" block
batalkan_pattern = r"\s*@if \(\$canManage && \$stockRequest->status !== 'cancelled'\)\s*<form action=\"\{\{ route\('rts\.stock-requests\.destroy', \$stockRequest\) \}\}\" method=\"POST\" style=\"margin: 0;\" onsubmit=\"return confirm\('Apakah Anda yakin ingin membatalkan Stock Request ini\?'\);\">(?:.|\n)*?</form>\s*@endif"
content = re.sub(batalkan_pattern, "", content)

# Now, add the footer just before the closing </div> of the main page (above @endsection)
footer_html = """
        <div class="sd-btn-row">
            <div class="sd-muted">Pilih aksi untuk memproses permintaan ini.</div>
            <div class="sd-actions-bottom">
                @if ($canManage && $stockRequest->status !== 'cancelled')
                    <form action="{{ route('rts.stock-requests.destroy', $stockRequest) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan Stock Request ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="sd-btn" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.3);">
                            <i class="bi bi-x-circle"></i> Batalkan
                        </button>
                    </form>
                @endif
                
                @if ($canManage && $canReceive)
                    <a href="{{ route('rts.stock-requests.confirm', $stockRequest) }}" class="sd-btn sd-primary">
                        Terima Jadi
                    </a>
                @endif
            </div>
        </div>
"""
# insert before the last </div> before @endsection
last_div_pattern = r"(</div>\s*@endsection)"
content = re.sub(last_div_pattern, footer_html + r"\1", content)

with open('resources/views/inventory/rts_stock_requests/show.blade.php', 'w') as f:
    f.write(content)

