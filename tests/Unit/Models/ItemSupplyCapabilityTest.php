<?php

use App\Models\Item;

it('mengenali item hybrid dan prioritas pasoknya', function () {
    $item = new Item([
        'type' => 'finished_good',
        'production_source' => Item::PRODUCTION_BUY,
        'can_buy' => true,
        'can_make' => true,
        'default_supply_source' => Item::SUPPLY_MAKE,
    ]);

    expect($item->canBuy())->toBeTrue()
        ->and($item->canMake())->toBeTrue()
        ->and($item->isHybrid())->toBeTrue()
        ->and($item->supply_mode_label)->toBe('Hybrid: produksi / beli jadi')
        ->and($item->effectiveSupplySource())->toBe(Item::SUPPLY_MAKE)
        ->and($item->default_supply_source_label)->toBe('Produksi sendiri');
});

it('tetap membaca item legacy yang belum mengisi capability baru', function () {
    $makeItem = new Item([
        'type' => 'finished_good',
        'production_source' => Item::PRODUCTION_IN_HOUSE,
    ]);
    $buyItem = new Item([
        'type' => 'finished_good',
        'production_source' => Item::PRODUCTION_BUY,
    ]);

    expect($makeItem->canMake())->toBeTrue()
        ->and($makeItem->canBuy())->toBeFalse()
        ->and($makeItem->effectiveSupplySource())->toBe(Item::SUPPLY_MAKE)
        ->and($buyItem->canMake())->toBeFalse()
        ->and($buyItem->canBuy())->toBeTrue()
        ->and($buyItem->effectiveSupplySource())->toBe(Item::SUPPLY_BUY);
});
