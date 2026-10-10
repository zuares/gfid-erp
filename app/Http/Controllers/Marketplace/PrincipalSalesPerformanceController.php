<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Marketplace\PrincipalSalesPerformanceService;
use Illuminate\Http\Request;

class PrincipalSalesPerformanceController extends Controller
{
    public function index(Request $request, PrincipalSalesPerformanceService $service)
    {
        $stores = Store::query()
            ->select('id', 'name', 'channel_id', 'status', 'is_active', 'token_expires_at', 'credentials')
            ->with('channel:id,code,name')
            ->whereHas('channel', fn ($query) => $query->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $defaultFrom = now()->startOfMonth()->toDateString();
        // Shopee only exposes completed data; the requested end date cannot be today.
        $latestDate = now()->subDay()->toDateString();
        $defaultTo = $latestDate;

        $filters = [
            'store_id' => (string) $request->input('store_id', $stores->first()?->id ?? ''),
            'principal_id' => trim((string) $request->input('principal_id', config('shopee.principal_id', ''))),
            'start_date' => (string) $request->input('start_date', $defaultFrom),
            'end_date' => (string) $request->input('end_date', $defaultTo),
            'timezone' => (string) $request->input('timezone', 'GMT+7'),
            'granularity' => (string) $request->input('granularity', 'customize'),
            'currency' => (string) $request->input('currency', 'USD'),
            'regions' => (string) $request->input('regions', ''),
        ];

        $summary = [];
        $details = [];
        $error = null;
        $loaded = $request->boolean('load');

        if ($loaded) {
            $validated = $request->validate([
                'store_id' => ['required', 'integer'],
                'principal_id' => ['required', 'regex:/^\d+$/', 'max:32'],
                'start_date' => ['required', 'date_format:Y-m-d'],
                'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:' . $latestDate],
                'timezone' => ['required', 'in:GMT+7,GMT+8,GMT-3'],
                'granularity' => ['required', 'in:customize,day,week,month,quarter,year'],
                'currency' => ['required', 'in:LOCAL,USD'],
                'regions' => ['nullable', 'string', 'max:500'],
            ]);

            $filters = array_merge($filters, $validated);
            $store = $stores->firstWhere('id', (int) $validated['store_id']);

            if (! $store) {
                $error = 'Koneksi Shopee yang dipilih tidak ditemukan atau tidak aktif.';
            } elseif (! filled($store->credential('access_token'))) {
                $error = 'Koneksi Shopee belum memiliki access token yang valid.';
            } else {
                $regionList = collect(preg_split('/[,\s]+/', strtoupper($validated['regions']), -1, PREG_SPLIT_NO_EMPTY))
                    ->unique()
                    ->map(fn (string $region) => [
                        'region' => $region,
                        'currency' => $validated['currency'],
                    ])
                    ->values()
                    ->all();

                try {
                    $payload = [
                        'start_date' => $validated['start_date'],
                        'end_date' => $validated['end_date'],
                        'timezone' => $validated['timezone'],
                        'granularity' => $validated['granularity'],
                    ];

                    if ($regionList !== []) {
                        $payload['region_list'] = $regionList;
                    }

                    $result = $service->fetch($store, $validated['principal_id'], $payload);

                    if (! empty($result['error'])) {
                        $error = trim((string) ($result['message'] ?? $result['error']));
                    } else {
                        $summary = data_get($result, 'response.summary', []);
                        $summary = is_array($summary) && array_is_list($summary)
                            ? (array) ($summary[0] ?? [])
                            : (array) $summary;
                        $details = array_values((array) data_get($result, 'response.details', []));
                    }
                } catch (\Throwable $exception) {
                    report($exception);
                    $error = 'Gagal mengambil data Shopee: ' . $exception->getMessage();
                }
            }
        }

        return view('marketplace.principal_sales_performance', compact(
            'stores',
            'filters',
            'summary',
            'details',
            'error',
            'loaded'
        ));
    }
}
