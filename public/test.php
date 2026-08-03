<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
$s = \App\Models\Store::find(5);
$d = app(\App\Services\Marketplace\Ads\AdsDashboardService::class)->buildDashboardData(collect([$s]), $s->id, '2026-07-29', '2026-07-31', 'prev_period', app(\App\Services\Marketplace\Ads\AdsAnalyticsService::class));
echo "Overall Net Profit: " . $d['kpi']['current']->net_profit . "\n";
echo "GMV: " . $d['kpi']['current']->gmv . "\n";
echo "COGS: " . $d['kpi']['current']->total_cogs . "\n";
echo "Spend: " . $d['kpi']['current']->spend . "\n";
echo "Net Revenue: " . $d['kpi']['current']->net_revenue . "\n";
