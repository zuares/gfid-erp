<?php

use App\Support\GmvMaxAnalytics;

/*
|--------------------------------------------------------------------------
| Fase 2.5 — helper analitik GMV Max (murni, tanpa DB/HTTP)
|--------------------------------------------------------------------------
*/

it('divides safely and returns null on zero denominator', function () {
    expect(GmvMaxAnalytics::safeDiv(10, 2))->toBe(5.0);
    expect(GmvMaxAnalytics::safeDiv(0, 5))->toBe(0.0);
    expect(GmvMaxAnalytics::safeDiv(1, 0))->toBeNull();      // tak Infinity/NaN
    expect(GmvMaxAnalytics::safeDiv(5, null))->toBeNull();
});

it('computes actual ROAS (gmv/spend) safely', function () {
    expect(GmvMaxAnalytics::roas(100, 25))->toBe(4.0);
    expect(GmvMaxAnalytics::roas(0, 25))->toBe(0.0);
    expect(GmvMaxAnalytics::roas(100, 0))->toBeNull();       // spend nol → —
});

it('classifies target status correctly', function () {
    expect(GmvMaxAnalytics::targetStatus(4, 5, 'auto'))->toBe('above');
    expect(GmvMaxAnalytics::targetStatus(4, 3, 'auto'))->toBe('below');
    expect(GmvMaxAnalytics::targetStatus(4, null, 'auto'))->toBe('no_data'); // ada target, tak ada actual
    expect(GmvMaxAnalytics::targetStatus(0, 5, 'auto'))->toBe('maximize');   // target 0 + auto = Maksimalkan GMV
    expect(GmvMaxAnalytics::targetStatus(0, 5, 'manual'))->toBe('unknown');  // target 0 tapi manual
    expect(GmvMaxAnalytics::targetStatus(null, 5, 'auto'))->toBe('unknown'); // target null
});

it('computes weighted target ROAS by spend and excludes target=0', function () {
    $rows = [
        ['target_roas' => 4, 'spend' => 100],
        ['target_roas' => 6, 'spend' => 300],
        ['target_roas' => 0, 'spend' => 500], // Maksimalkan GMV → TIDAK dihitung
        ['target_roas' => 5, 'spend' => 0],   // spend 0 → TIDAK dihitung
        ['target_roas' => null, 'spend' => 200],
    ];
    // (4*100 + 6*300) / (100+300) = 2200/400 = 5.5
    expect(GmvMaxAnalytics::weightedTargetRoas($rows))->toBe(5.5);
});

it('returns null weighted target when no eligible spend', function () {
    expect(GmvMaxAnalytics::weightedTargetRoas([]))->toBeNull();
    expect(GmvMaxAnalytics::weightedTargetRoas([
        ['target_roas' => 0, 'spend' => 100],
        ['target_roas' => 4, 'spend' => 0],
    ]))->toBeNull();
});
