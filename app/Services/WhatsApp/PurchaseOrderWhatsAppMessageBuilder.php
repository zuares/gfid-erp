<?php

namespace App\Services\WhatsApp;

use App\Models\PurchaseOrder;
use App\Models\WhatsAppTemplate;

class PurchaseOrderWhatsAppMessageBuilder
{
    public function build(PurchaseOrder $order): array
    {
        $order->loadMissing(['supplier', 'lines.item']);
        $supplier = $order->supplier;

        $lines = $order->lines->map(function ($line) {
            $item = $line->item;
            $qty = rtrim(rtrim(number_format((float) $line->qty, 2, ',', '.'), '0'), ',');

            return '• '
                . ($item?->code ? $item->code . ' - ' : '')
                . ($item?->name ?? 'Item')
                . " × {$qty}";
        })->implode("\n");

        if ($lines === '') {
            $lines = '• Belum ada item pada PO';
        }

        $date = $order->date?->format('d/m/Y') ?? '-';
        $total = (float) $order->grand_total > 0
            ? 'Rp' . number_format((float) $order->grand_total, 0, ',', '.')
            : '-';
        $template = WhatsAppTemplate::query()
            ->where('key', 'purchase_order_supplier')
            ->where('is_active', true)
            ->first();

        $variables = [
            'supplier_name' => $supplier?->name ?? '-',
            'po_code' => $order->code,
            'po_date' => $date,
            'details' => $lines,
            'total' => $total,
        ];

        $message = $template?->render($variables) ?? "Halo {$variables['supplier_name']},\n\n"
            . "Berikut Purchase Order dari Great Fit Indonesia:\n\n"
            . "PO: {$variables['po_code']}\n"
            . "Tanggal: {$variables['po_date']}\n\n"
            . "Detail:\n{$variables['details']}\n\n"
            . "Total: {$variables['total']}\n\n"
            . "Mohon konfirmasi penerimaan PO ini. Terima kasih.";

        return [
            'phone' => trim((string) ($supplier?->phone ?? '')),
            'recipient_name' => $supplier?->name,
            'message' => $message,
            'template_key' => $template?->key,
            'variables' => $variables,
            'module' => 'purchasing',
            'reference_type' => PurchaseOrder::class,
            'reference_id' => $order->id,
            'reference_label' => $order->code,
        ];
    }
}
