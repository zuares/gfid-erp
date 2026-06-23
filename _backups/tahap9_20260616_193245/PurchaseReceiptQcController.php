<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptQc;
use Illuminate\Http\Request;

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
}
