import re

with open('/Users/ariefmuhamad/Herd/gfid-dev/app/Http/Controllers/Purchasing/PurchaseReturnController.php', 'r') as f:
    content = f.read()

method_html = """
    public function searchByItem(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        
        if ($term === '') {
            return response()->json(['results' => []]);
        }

        // Cari item yang namanya/kodenya cocok
        $itemIds = \App\Models\Item::query()
            ->where('name', 'LIKE', "%{$term}%")
            ->orWhere('code', 'LIKE', "%{$term}%")
            ->pluck('id');

        if ($itemIds->isEmpty()) {
            return response()->json(['results' => []]);
        }

        // Cari GRN line yang item_id-nya ada di list itemIds dan GRN-nya posted
        $lines = \App\Models\PurchaseReceiptLine::query()
            ->with(['grn.supplier', 'item'])
            ->whereIn('item_id', $itemIds)
            ->whereHas('grn', function ($q) {
                $q->where('status', 'posted');
            })
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        if ($lines->isEmpty()) {
            return response()->json(['results' => []]);
        }

        // Hitung total retur yang sudah diposting untuk setiap line
        $lineIds = $lines->pluck('id');
        $returnedQty = \App\Models\PurchaseReturnLine::query()
            ->whereIn('purchase_receipt_line_id', $lineIds)
            ->whereHas('return', function($q) {
                $q->whereNull('voided_at')->where('status', 'posted');
            })
            ->groupBy('purchase_receipt_line_id')
            ->selectRaw('purchase_receipt_line_id, SUM(qty) as total')
            ->pluck('total', 'purchase_receipt_line_id');

        $results = [];
        $addedGrns = [];

        foreach ($lines as $line) {
            $rem = max(0, $line->qty_received - (float)($returnedQty[$line->id] ?? 0));
            if ($rem <= 0.0001) continue;

            $grn = $line->grn;
            
            // Hindari memunculkan GRN yang sama berulang kali di pencarian yang sama, 
            // cukup munculkan item pertama yang match di GRN tersebut
            if (isset($addedGrns[$grn->id])) continue;
            
            $addedGrns[$grn->id] = true;

            $date = $grn->date ? \Illuminate\Support\Carbon::parse($grn->date)->format('d/m/Y') : '';
            $text = "{$line->item->name} · {$grn->code} ({$date}) · Sisa bisa diretur: " . rtrim(rtrim(number_format($rem, 4, ',', '.'), '0'), ',') . " pcs";
            
            $results[] = [
                'id' => $grn->id,
                'text' => $text
            ];
        }

        return response()->json(['results' => $results]);
    }

"""

# Insert after searchGrnForReturn
content = content.replace('return response()->json([\'results\' => $results]);\n    }\n\n    public function createFromGrn', 'return response()->json([\'results\' => $results]);\n    }\n\n' + method_html + '    public function createFromGrn')

with open('/Users/ariefmuhamad/Herd/gfid-dev/app/Http/Controllers/Purchasing/PurchaseReturnController.php', 'w') as f:
    f.write(content)

