{{-- resources/views/purchasing/purchase_orders/_table_rows.blade.php --}}
@push('head')
    <style>
        /* =======================================
                         TABLE ROW THEME STYLES
                         (Light & Dark Mode Friendly)
                       ======================================= */

        .index-table-row {
            cursor: pointer;
            transition: background .16s ease, transform .08s ease;
        }

        .index-table-row:hover {
            background: color-mix(in srgb, var(--accent-soft) 60%, var(--card) 40%);
            transform: translateY(-1px);
        }

        .index-table-row td {
            color: var(--text);
            border-bottom-color: var(--line);
            vertical-align: middle;
            padding-top: .55rem;
            padding-bottom: .55rem;
        }

        /* Subtext (kode supplier dsb) */
        .index-row-subtext {
            font-size: .78rem;
            color: color-mix(in srgb, var(--muted) 85%, var(--text) 15%);
        }

        :root[data-theme="dark"] .index-row-subtext {
            color: color-mix(in srgb, var(--muted) 40%, var(--text) 60%);
        }

        /* Link di baris tabel */
        .index-table-row a {
            color: var(--accent);
            text-decoration: none;
        }

        .index-table-row a:hover {
            opacity: .85;
        }

        /* Badge kode PO (mobile) */
        .index-code-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid var(--line);
            padding: .12rem .65rem;
            font-size: .72rem;
            background: color-mix(in srgb, var(--card) 85%, var(--bg) 15%);
            color: var(--text);
        }

        :root[data-theme="dark"] .index-code-badge {
            background: color-mix(in srgb, var(--card) 80%, #020617 20%);
            border-color: var(--line);
            color: var(--text);
        }

        /* Status badge mengikuti index-page theme */
        .status-badge {
            font-size: .75rem;
            border-radius: 999px;
            padding: .18rem .55rem;
            font-weight: 500;
        }

        .status-badge-draft {
            background: color-mix(in srgb, var(--muted) 18%, transparent);
            color: var(--muted);
            border: 1px solid color-mix(in srgb, var(--muted) 35%, transparent);
        }

        .status-badge-approved {
            background: rgba(34, 197, 94, .12);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, .26);
        }

        .status-badge-closed {
            background: rgba(15, 23, 42, .14);
            color: #0f172a;
            border: 1px solid rgba(15, 23, 42, .3);
        }

        :root[data-theme="dark"] .status-badge-closed {
            background: rgba(15, 23, 42, .75);
            color: #e5e7eb;
            border-color: rgba(15, 23, 42, .9);
        }
    </style>
@endpush

@php
    use Illuminate\Support\Carbon;
    $canSeeMoney = auth()->user()?->canSeePurchasePrices() ?? false;
@endphp

@forelse ($orders as $order)
    @php
        $date = $order->date ? Carbon::parse($order->date)->format('d-m-Y') : '-';

        $statusClass = match ($order->status) {
            'draft' => 'status-badge status-badge-draft',
            'approved' => 'status-badge status-badge-approved',
            'closed' => 'status-badge status-badge-closed',
            default => 'status-badge status-badge-draft',
        };
        $statusLabel = match ((string) $order->status) {
            'approved' => 'Posted',
            'closed' => 'Closed',
            'cancelled' => 'Cancelled',
            default => 'Draft',
        };
    @endphp

    <tr class="index-table-row">
        {{-- KODE (Desktop) --}}
        <td class="mono d-none d-md-table-cell">
            <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}">
                {{ $order->code }}
            </a>
        </td>

        {{-- TANGGAL --}}
        <td class="mono">
            {{ $date }}
        </td>

        {{-- SUPPLIER --}}
        <td>
            {{-- Desktop --}}
            <div class="d-none d-md-block">
                <div class="fw-semibold">
                    {{ optional($order->supplier)->name ?? '—' }}
                </div>
                <div class="index-row-subtext mono">
                    {{ optional($order->supplier)->code ?? '-' }}
                </div>
            </div>

            {{-- Mobile --}}
            <div class="d-block d-md-none">
                <div class="fw-semibold">
                    {{ optional($order->supplier)->name ?? '—' }}
                </div>
                <div class="index-row-subtext mono">
                    {{ optional($order->supplier)->code ?? '-' }}
                </div>
                <div class="small mt-1">
                    <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}" class="index-code-badge mono">
                        {{ $order->code }}
                    </a>
                </div>
            </div>
        </td>

        @if ($canSeeMoney)
            <td class="text-end mono">
                {{ rupiah($order->grand_total) }}
            </td>
        @endif

        {{-- STATUS --}}
        <td>
            <span class="{{ $statusClass }}">
                {{ $statusLabel }}
            </span>
        </td>

        {{-- ACTION --}}
        <td class="text-nowrap">
            <a href="{{ route('purchasing.purchase_orders.edit', $order->id) }}"
                class="btn btn-sm btn-outline-primary">
                Edit
            </a>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="{{ $canSeeMoney ? 6 : 5 }}" class="text-center index-row-subtext py-3">
            Belum ada data PO.
        </td>
    </tr>
@endforelse
