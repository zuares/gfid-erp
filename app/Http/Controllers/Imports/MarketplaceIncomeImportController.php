<?php

namespace App\Http\Controllers\Imports;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Marketplace\Income\MpIncomeImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MarketplaceIncomeImportController extends Controller
{
    /** Disk untuk simpan file upload income (konsisten, anti nyasar) */
    private string $disk = 'local';

    public function create(): View
    {
        $stores = Store::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $draft = session('mp_income_preview');

        return view('imports.marketplace_income.create', compact('stores', 'draft'));
    }

    public function preview(Request $request, MpIncomeImportService $svc)
    {
        $data = $request->validate([
            'channel' => 'required|in:shopee,tiktok',
            'store_id' => 'required|integer|min:1',
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $channel = strtolower(trim($data['channel']));
        $storeId = (int) $data['store_id'];

        $file = $request->file('file');

        // simpan file ke disk explicit
        $path = $file->store("imports/marketplace_income/{$channel}", $this->disk);
        $abs = Storage::disk($this->disk)->path($path);

        if (!is_file($abs) || !is_readable($abs)) {
            abort(500, "Upload tersimpan tapi file tidak ditemukan / tidak bisa dibaca. disk={$this->disk} rel={$path} abs={$abs} default_disk=" . config('filesystems.default'));
        }

        $sourceFile = $file->getClientOriginalName();

        // dry-run: parse only
        $res = $svc->import($channel, $abs, $sourceFile, $storeId, true);

        $stats = $res['stats'] ?? [];
        $sample = $res['sample'] ?? [];

        session([
            'mp_income_preview' => [
                'mode' => 'income',
                'disk' => $this->disk,

                'channel' => $channel,
                'store_id' => $storeId,
                'source_file' => $sourceFile,

                'stored_path' => $path,
                'stats' => $stats,
                'sample' => $sample,
            ],
        ]);

        return view('imports.marketplace_income.preview', [
            'channel' => $channel,
            'store_id' => $storeId,
            'source_file' => $sourceFile,
            'stats' => $stats,
            'sample' => $sample,
            'stored_path' => $path,
        ]);
    }

    public function commit(Request $request, MpIncomeImportService $svc)
    {
        $draft = session('mp_income_preview');

        if (!$draft || ($draft['mode'] ?? '') !== 'income') {
            return redirect()
                ->route('imports.marketplace_income.create')
                ->with('error', 'Tidak ada draft preview untuk di-commit.');
        }

        $channel = (string) ($draft['channel'] ?? '');
        $storeId = (int) ($draft['store_id'] ?? 0);
        $path = (string) ($draft['stored_path'] ?? '');
        $sourceFile = (string) ($draft['source_file'] ?? '');
        $disk = (string) ($draft['disk'] ?? $this->disk);

        if ($channel === '' || $storeId <= 0 || $path === '') {
            return redirect()
                ->route('imports.marketplace_income.create')
                ->with('error', 'Draft tidak lengkap. Upload ulang.');
        }

        $abs = Storage::disk($disk)->path($path);

        if (!is_file($abs) || !is_readable($abs)) {
            return redirect()
                ->route('imports.marketplace_income.create')
                ->with('error', "File draft tidak ditemukan di storage. disk={$disk} rel={$path}. Upload ulang.");
        }

        // real import (DB write)
        $res = $svc->import($channel, $abs, $sourceFile, $storeId, false);
        $stats = $res['stats'] ?? [];

        session()->forget('mp_income_preview');

        return redirect()
            ->route('imports.marketplace_income.create')
            ->with(
                'success',
                "Import income selesai. Orders=" . (int) ($stats['orders_parsed'] ?? 0)
                . " incomes=" . (int) ($stats['incomes_upserted'] ?? 0)
                . " matched=" . (int) ($stats['orders_matched_shipments'] ?? 0)
                . " updatedShip=" . (int) ($stats['shipments_updated'] ?? 0)
                . " batch=" . ($stats['batch'] ?? '-')
            );
    }

    public function cancel()
    {
        $draft = session('mp_income_preview');

        if ($draft) {
            $disk = (string) ($draft['disk'] ?? $this->disk);
            $path = (string) ($draft['stored_path'] ?? '');

            if ($path !== '') {
                Storage::disk($disk)->delete($path);
            }
        }

        session()->forget('mp_income_preview');

        return redirect()
            ->route('imports.marketplace_income.create')
            ->with('success', 'Draft income dibatalkan.');
    }
}
