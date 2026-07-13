import re

with open('resources/views/purchasing/purchase_orders/show.blade.php', 'r') as f:
    content = f.read()

# Extract Payment block (starts near "Riwayat Pembayaran" and ends at "</div>")
# Wait, I can just use a regex to replace everything between `<div class="page-wrap py-4">` and `<div class="modal fade" id="modalAddPayment"`

start_tag = '<div class="page-wrap py-4">'
end_tag = '    <div class="modal fade" id="modalAddPayment"'

start_idx = content.find(start_tag)
# We want to find the exact end block. The last div before the modals is `</div>\n\n    @if ($canSeeMoney)\n    {{-- ======================`
# Let's just find the first modal Add Payment
end_idx = content.find('{{-- =========================================================\n    MODAL: ADD PAYMENT')
if end_idx == -1:
    end_idx = content.find('<div class="modal fade" id="modalAddPayment"')

print("Start:", start_idx, "End:", end_idx)
