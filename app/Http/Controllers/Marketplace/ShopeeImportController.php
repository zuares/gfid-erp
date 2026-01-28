<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Marketplace\Shopee\ImportShopeeIncomeService;
use App\Services\Marketplace\Shopee\ImportShopeeOrdersService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShopeeImportController extends Controller
{
    // ============================
    // INDEX (redirect)
    // ============================
    public function index(): RedirectResponse
    {
        return redirect()->route('marketplace.shopee.import_orders.form');
    }

    // ============================
    // IMPORT INCOME
    // ============================
    public function incomeForm()
    {
        $stores = Store::query()->orderBy('name')->get(['id', 'name']);
        return view('marketplace.shopee.import-income', compact('stores'));
    }

    public function importIncome(Request $request, ImportShopeeIncomeService $service)
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'store_id' => 'required|integer|min:1',
            'on_missing' => 'nullable|in:skip,stop',
            'dry_run' => 'nullable|boolean',
        ]);

        $result = $service->run(
            file: $request->file('file'),
            storeId: (int) $data['store_id'],
            channel: 'shopee',
            onMissing: (string) ($data['on_missing'] ?? 'skip'),
            dryRun: (bool) ($data['dry_run'] ?? true),
        );

        return back()->with('result', $result);
    }

    // ============================
    // IMPORT ORDERS (Preview & Confirm)
    // ============================
    public function ordersForm()
    {
        $stores = Store::query()->orderBy('name')->get(['id', 'name']);

        $types = [
            'shipping' => 'Shipping (Pesanan Dikirim)',
            'completed' => 'Completed (Pesanan Selesai)',
        ];

        $preview = session('shopee_orders_preview');

        return view('marketplace.shopee.import-orders', compact('stores', 'types', 'preview'));
    }

    /**
     * STEP 1: Preview
     */
    public function importOrdersPreview(Request $request, ImportShopeeOrdersService $service)
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'store_id' => 'required|integer|min:1',
            'type' => 'required|in:shipping,completed',
            'on_missing' => 'nullable|in:stop,skip,create',
        ]);

        $this->cleanupOrdersPreviewIfAny();

        $token = (string) Str::uuid();
        $ext = $request->file('file')->getClientOriginalExtension() ?: 'xlsx';
        $tmpPath = $request->file('file')->storeAs('tmp/shopee', "{$token}.{$ext}");

        $result = $service->run(
            file: $request->file('file'),
            storeId: (int) $data['store_id'],
            type: (string) $data['type'],
            channel: 'shopee',
            onMissing: (string) ($data['on_missing'] ?? 'stop'),
            dryRun: true,
        );

        session([
            'shopee_orders_preview' => [
                'token' => $token,
                'tmp_path' => $tmpPath,
                'params' => [
                    'store_id' => (int) $data['store_id'],
                    'type' => (string) $data['type'],
                    'on_missing' => (string) ($data['on_missing'] ?? 'stop'),
                ],
                'result' => $result,
            ],
        ]);

        return back()->with('result', $result);
    }

    /**
     * STEP 2: Confirm (Write)
     */
    public function importOrdersConfirm(Request $request, ImportShopeeOrdersService $service)
    {
        $data = $request->validate([
            'token' => 'required|string',
        ]);

        $preview = session('shopee_orders_preview');
        if (!$preview || ($preview['token'] ?? null) !== $data['token']) {
            return back()->with('result', [
                'ok' => false,
                'message' => 'Preview tidak ditemukan / token tidak valid. Silakan preview ulang.',
            ]);
        }

        if ((bool) data_get($preview, 'result.ok') !== true) {
            return back()->with('result', [
                'ok' => false,
                'message' => 'Preview gagal. Tidak bisa confirm. Silakan preview ulang.',
            ]);
        }

        $tmpPath = (string) ($preview['tmp_path'] ?? '');
        if ($tmpPath === '' || !Storage::exists($tmpPath)) {
            return back()->with('result', [
                'ok' => false,
                'message' => 'File preview sudah tidak ada. Silakan preview ulang.',
            ]);
        }

        $abs = Storage::path($tmpPath);
        $uploaded = new \Illuminate\Http\UploadedFile(
            $abs,
            basename($abs),
            null,
            null,
            true
        );

        $params = (array) ($preview['params'] ?? []);

        $result = $service->run(
            file: $uploaded,
            storeId: (int) ($params['store_id'] ?? 0),
            type: (string) ($params['type'] ?? 'shipping'),
            channel: 'shopee',
            onMissing: (string) ($params['on_missing'] ?? 'stop'),
            dryRun: false,
        );

        Storage::delete($tmpPath);
        session()->forget('shopee_orders_preview');

        return back()->with('result', $result);
    }

    public function resetOrdersPreview(): RedirectResponse
    {
        $this->cleanupOrdersPreviewIfAny();
        return redirect()->route('marketplace.shopee.import_orders.form');
    }

    private function cleanupOrdersPreviewIfAny(): void
    {
        $preview = session('shopee_orders_preview');
        if (!$preview) {
            return;
        }

        $tmpPath = $preview['tmp_path'] ?? null;
        if ($tmpPath && Storage::exists($tmpPath)) {
            Storage::delete($tmpPath);
        }

        session()->forget('shopee_orders_preview');
    }
}
