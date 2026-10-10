<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Marketplace\AmsPerformanceReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AmsPerformanceReportController extends Controller
{
    private const PAGE_SIZES = [20, 50, 100, 500];

    private const REPORTS = [
        'overview',
        'product',
        'affiliate',
        'content',
        'open_campaign',
        'targeted_campaign',
        'conversion',
        'validation',
    ];

    public function index(Request $request, AmsPerformanceReportService $service)
    {
        $stores = Store::query()
            ->select('id', 'name', 'external_shop_id', 'channel_id', 'status', 'is_active', 'token_expires_at', 'credentials')
            ->with('channel:id,code,name')
            ->whereHas('channel', fn ($query) => $query->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $today = now()->subDay();
        $defaultFrom = $today->copy()->subDays(29)->toDateString();
        $defaultTo = $today->toDateString();

        $filters = [
            'store_id' => (string) $request->input('store_id', $stores->first()?->id ?? ''),
            'report' => (string) $request->input('report', 'overview'),
            'period_type' => (string) $request->input('period_type', 'Last30d'),
            'date_from' => (string) $request->input('date_from', $defaultFrom),
            'date_to' => (string) $request->input('date_to', $defaultTo),
            'order_type' => (string) $request->input('order_type', 'ConfirmedOrder'),
            'channel' => (string) $request->input('channel', 'AllChannel'),
            'page_no' => (int) $request->input('page_no', 1),
            'page_size' => (int) $request->input('page_size', 20),
            'item_id' => trim((string) $request->input('item_id', '')),
            'affiliate_id' => trim((string) $request->input('affiliate_id', '')),
            'campaign_id' => trim((string) $request->input('campaign_id', '')),
            'order_sn' => trim((string) $request->input('order_sn', '')),
            'item_name' => trim((string) $request->input('item_name', '')),
            'l1_category_id' => trim((string) $request->input('l1_category_id', '')),
            'l2_category_id' => trim((string) $request->input('l2_category_id', '')),
            'l3_category_id' => trim((string) $request->input('l3_category_id', '')),
            'validation_id' => trim((string) $request->input('validation_id', '')),
            'validation_month' => trim((string) $request->input('validation_month', '')),
            'campaign_source' => (string) $request->input('campaign_source', 'ShopeeManaged'),
            'order_status' => (string) $request->input('order_status', ''),
            'verified_status' => (string) $request->input('verified_status', ''),
            'seller_campaign_type' => (string) $request->input('seller_campaign_type', ''),
            'deduction_status' => (string) $request->input('deduction_status', ''),
            'deduction_method' => (string) $request->input('deduction_method', ''),
        ];

        if (! in_array($filters['report'], self::REPORTS, true)) {
            $filters['report'] = 'overview';
        }

        if (! in_array($filters['page_size'], self::PAGE_SIZES, true)) {
            $filters['page_size'] = self::PAGE_SIZES[0];
        }

        $loaded = $request->boolean('load');
        $response = [];
        $rows = [];
        $overview = [];
        $validationList = [];
        $validationReport = [];
        $latestReportDate = null;
        $error = null;

        if ($loaded && $stores->isNotEmpty()) {
            if ($filters['report'] === 'content' && ! in_array($filters['channel'], ['ShopeeVideo', 'LiveStreaming'], true)) {
                $request->merge(['channel' => 'ShopeeVideo']);
                $filters['channel'] = 'ShopeeVideo';
            }
            $validated = $this->validateFilters($request, $filters['report']);
            $filters = array_merge($filters, $validated);
            $store = $stores->firstWhere('id', (int) $filters['store_id']);

            if (! $store) {
                $error = 'Toko Shopee yang dipilih tidak ditemukan atau tidak aktif.';
            } else {
                try {
                    if ($filters['report'] === 'validation') {
                        $validationListPayload = $service->fetch($store, 'validation_list');
                        $validationList = array_values((array) data_get($validationListPayload, 'response.validation_list', []));
                        $this->setErrorFromPayload($validationListPayload, $error);

                        if ($filters['validation_id'] !== '') {
                            $validationParams = $this->validationParams($filters);
                            $validationPayload = $service->fetch($store, 'validation_report', $validationParams);
                            $validationReport = array_values((array) data_get($validationPayload, 'response.list', []));
                            $response = (array) data_get($validationPayload, 'response', []);
                            $rows = $validationReport;
                            $this->setErrorFromPayload($validationPayload, $error);
                        }
                    } elseif ($filters['report'] === 'overview') {
                        $payload = $service->overview($store, $this->performanceParams($filters));
                        $latestReportDate = data_get($payload, 'marker.response.last_report_date');
                        $overview = [
                            'shop' => (array) data_get($payload, 'shop.response', []),
                            'campaign_metrics' => (array) data_get($payload, 'campaign_metrics.response', []),
                        ];
                        $response = $overview;
                        $this->setErrorFromPayload(data_get($payload, 'shop', []), $error);
                        $this->setErrorFromPayload(data_get($payload, 'campaign_metrics', []), $error);
                    } else {
                        $payload = $service->fetch($store, $this->serviceReport($filters['report']), $this->reportParams($filters));
                        $response = (array) data_get($payload, 'response', []);
                        $rows = $this->rowsFor($filters['report'], $response);
                        $latestReportDate = data_get($service->marker($store), 'response.last_report_date');
                        $this->setErrorFromPayload($payload, $error);
                    }
                } catch (\Throwable $exception) {
                    report($exception);
                    $error = 'Gagal mengambil data AMS Shopee: ' . $exception->getMessage();
                }
            }
        }

        return view('marketplace.ams_performance_reports', compact(
            'stores',
            'filters',
            'loaded',
            'response',
            'rows',
            'overview',
            'validationList',
            'validationReport',
            'latestReportDate',
            'error'
        ));
    }

    private function validateFilters(Request $request, string $report): array
    {
        $channelRules = $report === 'content'
            ? ['ShopeeVideo', 'LiveStreaming']
            : ['AllChannel', 'SocialMedia', 'ShopeeVideo', 'LiveStreaming'];

        return $request->validate([
            'store_id' => ['required', 'integer'],
            'report' => ['required', Rule::in(self::REPORTS)],
            'period_type' => ['required', Rule::in(['Day', 'Week', 'Month', 'Last7d', 'Last30d'])],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'order_type' => ['required', Rule::in(['PlacedOrder', 'ConfirmedOrder'])],
            'channel' => ['required', Rule::in($channelRules)],
            'page_no' => ['nullable', 'integer', 'min:1', 'max:500'],
            'page_size' => ['nullable', 'integer', Rule::in(self::PAGE_SIZES)],
            'item_id' => ['nullable', 'regex:/^\d+$/', 'max:32'],
            'affiliate_id' => ['nullable', 'regex:/^\d+$/', 'max:32'],
            'campaign_id' => ['nullable', 'regex:/^\d+$/', 'max:32'],
            'order_sn' => ['nullable', 'string', 'max:64'],
            'item_name' => ['nullable', 'string', 'max:255'],
            'l1_category_id' => ['nullable', 'regex:/^\d+$/', 'max:32'],
            'l2_category_id' => ['nullable', 'regex:/^\d+$/', 'max:32'],
            'l3_category_id' => ['nullable', 'regex:/^\d+$/', 'max:32'],
            'validation_id' => ['nullable', 'string', 'max:64'],
            'validation_month' => ['nullable', 'regex:/^\d{6}$/'],
            'campaign_source' => ['nullable', Rule::in(['ShopeeManaged', 'Seller'])],
            'order_status' => ['nullable', Rule::in(['', 'Unpaid', 'Pending', 'Completed', 'Cancelled'])],
            'verified_status' => ['nullable', Rule::in(['', 'Unverified', 'Valid', 'Invalid'])],
            'seller_campaign_type' => ['nullable', Rule::in(['', 'TargetCampaign', 'OpenCampaign', 'MCNCampaign'])],
            'deduction_status' => ['nullable', Rule::in(['', 'PendingDeduction', 'Deducted'])],
            'deduction_method' => ['nullable', Rule::in(['', 'OrderEscrow', 'SellerWallet', 'AutoAdjustment', 'SVSPaymentLink', 'OfflineSettlement', 'AMSCredit'])],
        ]);
    }

    private function performanceParams(array $filters): array
    {
        return [
            'period_type' => $filters['period_type'],
            'start_date' => Carbon::parse($filters['date_from'])->format('Ymd'),
            'end_date' => Carbon::parse($filters['date_to'])->format('Ymd'),
            'order_type' => $filters['order_type'],
            'channel' => $filters['channel'],
        ];
    }

    private function reportParams(array $filters): array
    {
        $params = $this->performanceParams($filters);
        $report = $filters['report'];

        if (in_array($report, ['product', 'affiliate', 'content', 'open_campaign', 'targeted_campaign'], true)) {
            $params['page_no'] = max(1, (int) $filters['page_no']);
            $params['page_size'] = min(max(self::PAGE_SIZES), max(1, (int) $filters['page_size']));
        }

        if ($report === 'product' && $filters['item_id'] !== '') {
            $params['item_id'] = (int) $filters['item_id'];
        }
        if (in_array($report, ['affiliate', 'content'], true) && $filters['affiliate_id'] !== '') {
            $params['affiliate_id'] = (int) $filters['affiliate_id'];
        }
        if (in_array($report, ['content'], true) && $filters['item_id'] !== '') {
            $params['item_id'] = (int) $filters['item_id'];
        }
        if ($report === 'open_campaign' && $filters['item_id'] !== '') {
            $params['item_id'] = (int) $filters['item_id'];
        }
        if ($report === 'targeted_campaign' && $filters['campaign_id'] !== '') {
            $params['campaign_id'] = (int) $filters['campaign_id'];
        }

        if ($report === 'conversion') {
            $params = [
                'page_no' => max(1, (int) $filters['page_no']),
                'page_size' => min(500, max(1, (int) $filters['page_size'])),
            ];

            $optional = [
                'order_sn', 'item_name', 'order_status', 'verified_status',
                'seller_campaign_type', 'deduction_status', 'deduction_method',
            ];
            foreach ($optional as $field) {
                if (($filters[$field] ?? '') !== '') {
                    $params[$field] = $filters[$field];
                }
            }
            foreach (['item_id', 'affiliate_id', 'l1_category_id', 'l2_category_id', 'l3_category_id'] as $field) {
                if ($filters[$field] !== '') {
                    $params[$field] = (int) $filters[$field];
                }
            }

            $params['place_order_time_start'] = Carbon::parse($filters['date_from'])->startOfDay()->timestamp;
            $params['place_order_time_end'] = Carbon::parse($filters['date_to'])->endOfDay()->timestamp;
        }

        return $params;
    }

    private function validationParams(array $filters): array
    {
        return [
            'page_no' => max(1, (int) $filters['page_no']),
            'page_size' => min(500, max(1, (int) $filters['page_size'])),
            'validation_id' => $filters['validation_id'],
            'validation_month' => (int) $filters['validation_month'],
            'campaign_source' => $filters['campaign_source'],
            'place_order_time_start' => Carbon::parse($filters['date_from'])->startOfDay()->timestamp,
            'place_order_time_end' => Carbon::parse($filters['date_to'])->endOfDay()->timestamp,
        ];
    }

    private function serviceReport(string $report): string
    {
        return $report === 'overview' ? 'shop' : $report;
    }

    private function rowsFor(string $report, array $response): array
    {
        return match ($report) {
            'conversion' => array_values((array) ($response['list'] ?? [])),
            default => array_values((array) ($response['list'] ?? [])),
        };
    }

    private function setErrorFromPayload(mixed $payload, ?string &$error): void
    {
        if (! is_array($payload) || empty($payload['error']) || $error !== null) {
            return;
        }

        $error = trim((string) ($payload['message'] ?? $payload['error']));
    }
}
