<?php

use App\Services\Channels\Shopee\ShopeeChannel;
use App\Services\MarketplaceSyncService;

/*
|--------------------------------------------------------------------------
| Fase 2 — pembacaan setting GMV Max (Shopee Ads)
|--------------------------------------------------------------------------
| Menguji dua perbaikan inti tanpa memanggil API production:
|  1. Normalisasi info_type_list → comma-string (bug: parameter wajib hilang).
|  2. Parser setting pakai path NYATA common_info.* (bug: path item_id salah).
| Ditaruh di Feature agar app ter-boot (helper config() dipakai parser).
*/

it('normalizes info_type_list into a comma string', function () {
    expect(ShopeeChannel::normalizeInfoTypeList([1, 2, 3, 4]))->toBe('1,2,3,4');
    expect(ShopeeChannel::normalizeInfoTypeList([1, 1, 3]))->toBe('1,3');          // dedup
    expect(ShopeeChannel::normalizeInfoTypeList('1,3'))->toBe('1,3');             // string in
    expect(ShopeeChannel::normalizeInfoTypeList('1, 2 ,3'))->toBe('1,2,3');       // trim
    expect(ShopeeChannel::normalizeInfoTypeList([]))->toBe('1,2,3,4');            // default
    expect(ShopeeChannel::normalizeInfoTypeList(['', ' ', '2']))->toBe('2');      // buang kosong
    expect(ShopeeChannel::normalizeInfoTypeList([0, -1, 3]))->toBe('3');          // hanya positif
});

it('parses the real GMV Max setting fixture (campaign 477707399)', function () {
    $node = [
        'campaign_id' => 477707399,
        'common_info' => [
            'ad_type'            => 'manual',
            'ad_name'            => 'GOODFIT | Cargo Pants Wanita',
            'campaign_status'    => 'closed',
            'bidding_method'     => 'auto',
            'campaign_placement' => 'all',
            'campaign_budget'    => 25000,
            'campaign_duration'  => ['start_time' => 1780938000, 'end_time' => 0],
            'item_id_list'       => [28944692968],
        ],
        'manual_bidding_info'   => null,
        'auto_bidding_info'     => ['roas_target' => 0],
        'auto_product_ads_info' => null,
    ];

    $p = MarketplaceSyncService::parseCampaignSetting($node);

    expect($p['channel_item_id'])->toBe(28944692968);   // path benar: common_info.item_id_list.0
    expect($p['ad_type'])->toBe('manual');
    expect($p['bidding_method'])->toBe('auto');
    expect($p['campaign_status'])->toBe('closed');
    expect($p['campaign_placement'])->toBe('all');
    expect($p['campaign_budget'])->toBe(25000);
    expect($p['target_roas'])->toBe(0);                 // 0 valid, TIDAK dijadikan null
    expect($p['started_at'])->not->toBeNull();          // start_time>0 → Carbon
    expect($p['ended_at'])->toBeNull();                 // end_time=0 → null
    expect($p['raw_setting_payload'])->toBeArray();
});

it('parses a manual campaign with null bidding nodes without failing', function () {
    $node = [
        'campaign_id' => 1,
        'common_info' => [
            'ad_type'        => 'manual',
            'bidding_method' => 'manual',
            'item_id_list'   => [999],
        ],
        'manual_bidding_info'   => ['some' => 'data'],
        'auto_bidding_info'     => null,   // bukan auto bidding
        'auto_product_ads_info' => null,
    ];

    $p = MarketplaceSyncService::parseCampaignSetting($node);

    expect($p['channel_item_id'])->toBe(999);
    expect($p['bidding_method'])->toBe('manual');
    expect($p['target_roas'])->toBeNull();              // tak ada auto_bidding_info → null
    expect($p['ended_at'])->toBeNull();
});

it('falls back gracefully when item_id_list is absent', function () {
    $p = MarketplaceSyncService::parseCampaignSetting([
        'campaign_id' => 2,
        'common_info' => ['ad_type' => 'manual'],
    ]);
    expect($p['channel_item_id'])->toBeNull();
    expect($p['ad_type'])->toBe('manual');
});

it('strips credentials from raw setting payload recursively', function () {
    $in = [
        'campaign_id'  => 1,
        'access_token' => 'SECRET',
        'sign'         => 'SIG',
        'nested'       => ['refresh_token' => 'R', 'partner_key' => 'K', 'ok' => 1],
    ];

    $out = MarketplaceSyncService::stripSensitive($in);

    expect($out)->not->toHaveKey('access_token');
    expect($out)->not->toHaveKey('sign');
    expect($out['nested'])->not->toHaveKey('refresh_token');
    expect($out['nested'])->not->toHaveKey('partner_key');
    expect($out['nested']['ok'])->toBe(1);              // data non-sensitif tetap
    expect($out['campaign_id'])->toBe(1);
});
