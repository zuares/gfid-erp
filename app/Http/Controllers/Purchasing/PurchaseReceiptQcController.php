<?php

namespace App\Http\Controllers\Purchasing;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptQc;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReceiptQcController extends Controller
{
    // Siapa yang boleh input/edit QC
    // Owner, admin, gudang — accounting hanya lihat (dikontrol di view)
    private function canInputQc(Request $request): bool
    {
        $user = $request->user();
        if (!$user) return false;
        if (method_exists($user, 'isOwner') && $user->isOwner()) return true;
        return in_array($user->role ?? '', ['admin', 'gudang', 'warehouse'], true);
    }

    // =========================================================
    // CREATE — form QC baru untuk GRN
    // =========================================================

    public function create(Request $request, PurchaseReceipt $purchase_receipt)
    {
        if (!$this->canInputQc($request)) {
            abort(403, 'Hanya owner, admin, atau gudang yang bisa input QC.');
        }

        // Hanya GRN posted yang boleh di-QC
        if ($purchase_receipt->status !== 'posted') {
            return redirect()
                ->route('purchasing.purchase_receipts.show', $purchase_receipt->id)
                ->with('error', 'QC hanya bisa diinput untuk GRN yang sudah di-post.');
        }

        // Jika sudah ada QC, langsung ke edit
        if ($purchase_receipt->qc()->exists()) {
            return redirect()
                ->route('purchasing.purchase_receipts.qc.edit', $purchase_receipt->id)
                ->with('info', 'QC sudah ada. Silakan edit di bawah ini.');
        }

        $purchase_receipt->load(['supplier', 'lines.item']);

        // Default qty_checked = total qty diterima semua line
        $totalQtyReceived = $purchase_receipt->lines->sum('qty_received');

        return view('purchasing.purchase_receipts.qc.create', compact(
            'purchase_receipt',
            'totalQtyReceived',
        ));
    }

    // =========================================================
    // STORE — simpan QC baru
    // =========================================================

    public function store(Request $request, PurchaseReceipt $purchase_receipt)
    {
        if (!$this->canInputQc($request)) {
            abort(403, 'Hanya owner, admin, atau gudang yang bisa input QC.');
        }

        if ($purchase_receipt->status !== 'posted') {
            return redirect()
                ->route('purchasing.purchase_receipts.show', $purchase_receipt->id)
                ->with('error', 'QC hanya bisa diinput untuk GRN yang sudah di-post.');
        }

        if ($purchase_receipt->qc()->exists()) {
            return redirect()
                ->route('purchasing.purchase_receipts.show', $purchase_receipt->id)
                ->with('error', 'QC untuk GRN ini sudah ada. Gunakan form edit.');
        }

        $data = $request->validate([
            'status'      => ['required', 'in:passed,issue,rejected'],
            'qty_checked' => ['required', 'numeric', 'min:0'],
            'qty_ok'      => ['required', 'numeric', 'min:0'],
            'qty_issue'   => ['required', 'numeric', 'min:0'],
            'issue_type'  => ['nullable', 'string', 'max:50'],
            'notes'       => ['nullable', 'string', 'max:2000'],
        ]);

        // Guard: qty_ok + qty_issue tidak boleh melebihi qty_checked
        if ((float) $data['qty_ok'] + (float) $data['qty_issue'] > (float) $data['qty_checked']) {
            return back()->withInput()->withErrors([
                'qty_ok' => 'Qty OK + Qty Masalah tidak boleh melebihi Qty Diperiksa (' . $data['qty_checked'] . ').',
            ]);
        }

        PurchaseReceiptQc::create([
            'purchase_receipt_id' => $purchase_receipt->id,
            'checked_by'          => $request->user()->id,
            'checked_at'          => now(),
            'status'              => $data['status'],
            'qty_checked'         => (float) $data['qty_checked'],
            'qty_ok'              => (float) $data['qty_ok'],
            'qty_issue'           => (float) $data['qty_issue'],
            'issue_type'          => $data['status'] !== 'passed' ? ($data['issue_type'] ?? null) : null,
            'notes'               => $data['notes'] ?? null,
        ]);

        $msg = match ($data['status']) {
            'passed'   => 'QC Lolos — barang diterima dalam kondisi baik.',
            'issue'    => 'QC Ada Masalah — silakan tindak lanjut jika perlu.',
            'rejected' => 'QC Ditolak — pertimbangkan untuk membuat Retur Supplier.',
            default    => 'QC berhasil disimpan.',
        };

        return redirect()
            ->route('purchasing.purchase_receipts.show', $purchase_receipt->id)
            ->with('success', $msg);
    }

    // =========================================================
    // EDIT — form edit QC yang sudah ada
    // =========================================================

    public function edit(Request $request, PurchaseReceipt $purchase_receipt)
    {
        if (!$this->canInputQc($request)) {
            abort(403, 'Hanya owner, admin, atau gudang yang bisa edit QC.');
        }

        $qc = $purchase_receipt->qc;

        if (!$qc) {
            return redirect()
                ->route('purchasing.purchase_receipts.qc.create', $purchase_receipt->id)
                ->with('info', 'Belum ada QC. Silakan buat dulu.');
        }

        if ($qc->isCancelled()) {
            return redirect()
                ->route('purchasing.purchase_receipts.show', $purchase_receipt->id)
                ->with('error', 'QC sudah dibatalkan dan tidak bisa diedit.');
        }

        $purchase_receipt->load(['supplier', 'lines.item']);
        $totalQtyReceived = $purchase_receipt->lines->sum('qty_received');

        return view('purchasing.purchase_receipts.qc.edit', compact(
            'purchase_receipt',
            'qc',
            'totalQtyReceived',
        ));
    }

    // =========================================================
    // UPDATE — simpan perubahan QC
    // =========================================================

    public function update(Request $request, PurchaseReceipt $purchase_receipt)
    {
        if (!$this->canInputQc($request)) {
            abort(403, 'Hanya owner, admin, atau gudang yang bisa edit QC.');
        }

        $qc = $purchase_receipt->qc;

        if (!$qc) {
            return redirect()
                ->route('purchasing.purchase_receipts.show', $purchase_receipt->id)
                ->with('error', 'QC tidak ditemukan.');
        }

        if ($qc->isCancelled()) {
            return redirect()
                ->route('purchasing.purchase_receipts.show', $purchase_receipt->id)
                ->with('error', 'QC sudah dibatalkan.');
        }

        $data = $request->validate([
            'status'      => ['required', 'in:passed,issue,rejected'],
            'qty_checked' => ['required', 'numeric', 'min:0'],
            'qty_ok'      => ['required', 'numeric', 'min:0'],
            'qty_issue'   => ['required', 'numeric', 'min:0'],
            'issue_type'  => ['nullable', 'string', 'max:50'],
            'notes'       => ['nullable', 'string', 'max:2000'],
        ]);

        // Guard: qty_ok + qty_issue tidak boleh melebihi qty_checked
        if ((float) $data['qty_ok'] + (float) $data['qty_issue'] > (float) $data['qty_checked']) {
            return back()->withInput()->withErrors([
                'qty_ok' => 'Qty OK + Qty Masalah tidak boleh melebihi Qty Diperiksa (' . $data['qty_checked'] . ').',
            ]);
        }

        $qc->update([
            'status'      => $data['status'],
            'qty_checked' => (float) $data['qty_checked'],
            'qty_ok'      => (float) $data['qty_ok'],
            'qty_issue'   => (float) $data['qty_issue'],
            'issue_type'  => $data['status'] !== 'passed' ? ($data['issue_type'] ?? null) : null,
            'notes'       => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('purchasing.purchase_receipts.show', $purchase_receipt->id)
            ->with('success', 'QC berhasil diperbarui.');
    }

    // =========================================================
    // CANCEL — batalkan QC (soft cancel, tidak hapus record)
    // =========================================================

    public function cancel(Request $request, PurchaseReceipt $purchase_receipt)
    {
        $user = $request->user();
        // Cancel hanya owner/admin
        $canCancel = ($user && (
            (method_exists($user, 'isOwner') && $user->isOwner())
            || in_array($user->role ?? '', ['admin'], true)
        ));

        if (!$canCancel) {
            abort(403, 'Hanya owner atau admin yang bisa membatalkan QC.');
        }

        $qc = $purchase_receipt->qc;

        if (!$qc) {
            return redirect()
                ->route('purchasing.purchase_receipts.show', $purchase_receipt->id)
                ->with('error', 'QC tidak ditemukan.');
        }

        if ($qc->isCancelled()) {
            return redirect()
                ->route('purchasing.purchase_receipts.show', $purchase_receipt->id)
                ->with('info', 'QC sudah dibatalkan sebelumnya.');
        }

        $qc->update(['status' => 'cancelled']);

        return redirect()
            ->route('purchasing.purchase_receipts.show', $purchase_receipt->id)
            ->with('success', 'QC berhasil dibatalkan.');
    }

    // =========================================================
    // RESOLVE — Tahap 9
    // Catat penyelesaian QC issue/rejected
    // Route: POST /purchasing/purchase-receipt-qcs/{qc}/resolve
    // Middleware: role:owner,admin
    // =========================================================

    public function resolve(Request $request, PurchaseReceiptQc $qc)
    {
        // Hanya QC issue/rejected yang bisa di-resolve
        if (!$qc->hasIssue()) {
            return back()->with('error', 'Hanya QC dengan status Issue atau Ditolak yang bisa ditindaklanjuti.');
        }

        if ($qc->isResolved()) {
            return back()->with('error', 'QC ini sudah pernah ditindaklanjuti. Jika perlu ubah, hubungi admin.');
        }

        $data = $request->validate([
            'resolution_type'  => ['required', 'in:retur,klaim_invoice,terima_selisih,write_off'],
            'resolution_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $resolutionType  = $data['resolution_type'];
        $resolutionNotes = $data['resolution_notes'] ?? null;

        // ── RETUR: buat Purchase Return draft dari GRN ──────────
        if ($resolutionType === 'retur') {
            // Load GRN dengan relasinya
            $receipt = $qc->purchaseReceipt;
            if (!$receipt) {
                return back()->with('error', 'GRN tidak ditemukan untuk QC ini.');
            }

            $receipt->loadMissing(['lines.item', 'order', 'supplier', 'warehouse']);

            if ($receipt->status !== 'posted') {
                return back()->with('error', 'Return hanya bisa dibuat dari GRN yang sudah POSTED.');
            }

            // Hitung remaining qty per GRN line (pakai logic yang sama dengan PurchaseReturnController)
            $remainingMap = $this->remainingByGrnLine($receipt);

            // Cek apakah ada qty yang bisa diretur
            $hasReturnable = collect($remainingMap)->filter(fn($r) => $r > 0.0001)->isNotEmpty();
            if (!$hasReturnable) {
                return back()->with('error', 'Semua qty di GRN ini sudah habis di-return. Tidak bisa membuat return baru.');
            }

            DB::transaction(function () use ($qc, $receipt, $remainingMap, $resolutionNotes) {
                // Buat draft return
                $ret = PurchaseReturn::create([
                    'code'                => CodeGenerator::generate('PRTN'),
                    'date'                => now()->toDateString(),
                    'purchase_receipt_id' => (int) $receipt->id,
                    'purchase_order_id'   => (int) ($receipt->purchase_order_id ?? $receipt->order?->id ?? 0) ?: null,
                    'supplier_id'         => (int) ($receipt->supplier_id ?? $receipt->supplier?->id ?? 0) ?: null,
                    'status'              => 'draft',
                    'created_by'          => (int) auth()->id(),
                    // Tahap 9 — link ke QC
                    'qc_id'               => (int) $qc->id,
                    'return_reason'       => $qc->issue_type
                        ? \App\Models\PurchaseReceiptQc::issueTypeLabel($qc->issue_type)
                        : null,
                ]);

                // Pre-fill lines: gunakan qty_issue jika memungkinkan, batasi oleh remaining
                $qtyIssue = (float) ($qc->qty_issue ?? 0);
                $totalReceived = (float) $receipt->lines->sum('qty_received');
                // Hitung proporsi jika qty_issue < total (masalah sebagian)
                $ratio = ($totalReceived > 0.0001 && $qtyIssue > 0.0001)
                    ? min(1.0, $qtyIssue / $totalReceived)
                    : 1.0;

                foreach ($receipt->lines as $ln) {
                    $remaining = (float) ($remainingMap[(int) $ln->id] ?? 0);
                    if ($remaining <= 0.0001) {
                        continue;
                    }

                    // Prefill qty berdasarkan proporsi qty_issue, tidak melebihi remaining
                    $suggestedQty = ($qtyIssue > 0.0001)
                        ? min($remaining, round($remaining * $ratio, 4))
                        : $remaining;

                    $suggestedQty = max(0, round($suggestedQty, 4));
                    if ($suggestedQty <= 0.0001) {
                        $suggestedQty = $remaining; // fallback: isi semua remaining
                    }

                    PurchaseReturnLine::create([
                        'purchase_return_id'       => $ret->id,
                        'purchase_receipt_line_id' => (int) $ln->id,
                        'item_id'                  => (int) $ln->item_id,
                        'lot_id'                   => $ln->lot_id ? (int) $ln->lot_id : null,
                        'qty'                      => $suggestedQty,
                        'unit_price'               => (float) $ln->unit_price,
                        'line_total'               => round($suggestedQty * (float) $ln->unit_price, 2),
                        'notes'                    => null,
                    ]);
                }

                // Sync total return
                $ret->total = round((float) $ret->lines()->sum('line_total'), 2);
                $ret->save();

                // Update QC — catat resolusi
                $qc->update([
                    'resolution_type'   => 'retur',
                    'resolution_notes'  => $resolutionNotes,
                    'resolved_at'       => now(),
                    'purchase_return_id'=> $ret->id,
                ]);
            });

            // Ambil return yang baru dibuat untuk redirect
            $qc->refresh();
            $returnId = $qc->purchase_return_id;

            return redirect()
                ->route('purchasing.purchase_returns.show', $returnId)
                ->with('success', 'Draft Return dibuat dari QC. Silakan cek qty dan klik Post jika sudah benar.');
        }

        // ── KLAIM INVOICE / TERIMA SELISIH / WRITE OFF ──────────
        // Hanya simpan resolusi, tidak ada aksi otomatis
        $qc->update([
            'resolution_type'  => $resolutionType,
            'resolution_notes' => $resolutionNotes,
            'resolved_at'      => now(),
        ]);

        $receipt = $qc->purchaseReceipt;

        // Jika klaim_invoice, arahkan ke invoice terkait PO jika ada
        if ($resolutionType === 'klaim_invoice' && $receipt) {
            $receipt->loadMissing(['order.supplierInvoices']);
            $latestInvoice = $receipt->order?->supplierInvoices
                ?->whereNotIn('status', ['void'])
                ->sortByDesc('id')
                ->first();

            if ($latestInvoice && \Illuminate\Support\Facades\Route::has('purchasing.supplier_invoices.show')) {
                return redirect()
                    ->route('purchasing.supplier_invoices.show', $latestInvoice->id)
                    ->with('info', 'Resolusi QC disimpan sebagai Klaim Invoice. Silakan isi Potongan Retur/Klaim di bawah.');
            }
        }

        return redirect()
            ->route('purchasing.purchase_receipts.show', $qc->purchase_receipt_id)
            ->with('success', 'Penyelesaian QC berhasil disimpan: ' . PurchaseReceiptQc::resolutionLabel($resolutionType) . '.');
    }

    // =========================================================
    // Helper — hitung remaining qty per GRN line
    // (mirror dari PurchaseReturnController, tanpa inject service)
    // =========================================================

    private function remainingByGrnLine(PurchaseReceipt $receipt): array
    {
        $receipt->loadMissing(['lines']);

        $received = [];
        foreach ($receipt->lines as $ln) {
            $received[(int) $ln->id] = (float) $ln->qty_received;
        }

        $returned = PurchaseReturnLine::query()
            ->join('purchase_returns as pr', 'pr.id', '=', 'purchase_return_lines.purchase_return_id')
            ->whereNull('pr.voided_at')
            ->where('pr.status', 'posted')
            ->where('pr.purchase_receipt_id', (int) $receipt->id)
            ->selectRaw('purchase_receipt_line_id, COALESCE(SUM(qty),0) as qty')
            ->groupBy('purchase_receipt_line_id')
            ->pluck('qty', 'purchase_receipt_line_id')
            ->map(fn($v) => (float) $v)
            ->all();

        $remaining = [];
        foreach ($received as $grnLineId => $qtyRecv) {
            $qtyRet = (float) ($returned[$grnLineId] ?? 0);
            $remaining[$grnLineId] = max(0, round($qtyRecv - $qtyRet, 4));
        }

        return $remaining;
    }
}
