import re

with open('resources/views/purchasing/purchase_orders/show.blade.php', 'r') as f:
    content = f.read()

correct_dropdown = """    <div class="dropdown d-inline-block">
        <button class="po-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Opsi Lainnya"><i class="bi bi-three-dots-vertical"></i></button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="font-size: .85rem; border-radius: 12px; padding: .5rem 0;">
            @if ($canSeeMoney)
                <li>
                    <a class="dropdown-item py-2" href="{{ route('purchasing.purchase_orders.print_dot_matrix', $order->id) }}">
                        <i class="bi bi-printer me-2 text-muted"></i> Cetak (Dot Matrix)
                    </a>
                </li>
            @endif
            @if ($status === 'draft' && (!$order->isLocked() || ($user && $user->canSeePurchasePrices())))
                <li>
                    <a class="dropdown-item py-2" href="{{ route('purchasing.purchase_orders.edit', $order->id) }}">
                        <i class="bi bi-pencil me-2 text-muted"></i> Edit PO
                    </a>
                </li>
            @endif
            @if ($canSeeMoney && $canPay && $hasAp)
                <li>
                    <button type="button" class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#modalApplyDp" @if (!$canApplyDp) disabled @endif>
                        <i class="bi bi-arrow-left-right me-2 text-muted"></i> Offset DP
                    </button>
                </li>
            @endif
            @if ($status === 'approved' && $user && ($user->isOwner() || in_array($user->role ?? '', ['accounting', 'developer'])))
                @if (\Illuminate\Support\Facades\Route::has('purchasing.supplier_invoices.create'))
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('purchasing.supplier_invoices.create', ['purchase_order_id' => $order->id]) }}">
                            <i class="bi bi-receipt me-2 text-muted"></i> Buat Invoice Supplier
                        </a>
                    </li>
                @endif
            @endif
            @if ($status === 'draft' && $user && in_array($user->role, ['owner','admin']))
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('purchasing.purchase_orders.cancel', $order->id) }}" method="POST"
                          onsubmit="return confirm('Cancel PO ini?');">
                        @csrf
                        <button type="submit" class="dropdown-item py-2 text-danger">
                            <i class="bi bi-x-circle me-2"></i> Cancel PO
                        </button>
                    </form>
                </li>
            @endif
        </ul>
    </div>"""

# Find the dropdown block in content to replace
pattern = re.compile(r'<div class="dropdown d-inline-block">.*?</ul>\s*</div>', re.DOTALL)
content = pattern.sub(correct_dropdown, content)

with open('resources/views/purchasing/purchase_orders/show.blade.php', 'w') as f:
    f.write(content)

print("Fixed dropdown.")
