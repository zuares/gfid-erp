<?php

use App\Services\Pricing\PricingCalculatorService;

beforeEach(function () {
    $this->svc = new PricingCalculatorService();
});

it('CASE 1 — menghitung harga jual rekomendasi ~Rp82.500', function () {
    $sp = $this->svc->calculateSellingPrice(45000, 21.17, 10, 7);

    expect($sp)->not->toBeNull();
    // Toleransi ±100 rupiah dari ekspektasi 82.500
    expect(round($sp))->toBeGreaterThan(82400)->toBeLessThan(82600);
});

it('CASE 2 — menghitung minimum ROAS ~6.07x', function () {
    $sp = 85950;
    $maxAd = $this->svc->calculateMaxAdSpend($sp, 21.17, 45000, 10);
    $minRoas = $this->svc->calculateMinimumRoas($sp, $maxAd);

    expect($minRoas)->not->toBeNull();
    expect(round($minRoas, 2))->toBe(6.07);
});

it('suggestSellingPrices memprioritaskan ending 950', function () {
    expect($this->svc->suggestSellingPrices(82433))->toBe([82950, 83950, 84950, 85950]);
    expect($this->svc->suggestSellingPrices(82501.77, 1)[0])->toBe(82950);
});

it('menandai target tidak feasible bila fee+profit+1/roas >= 100%', function () {
    // fee 60% + profit 30% + 1/5 (20%) = 110% -> tidak feasible
    expect($this->svc->isTargetFeasible(60, 30, 5))->toBeFalse();
    expect($this->svc->calculateSellingPrice(45000, 60, 30, 5))->toBeNull();
});

it('status: aman / mepet / rugi', function () {
    // Rugi: harga di bawah HPP
    $r = $this->svc->analyze(40000, 45000, 21.17, 10, 7);
    expect($r['status'])->toBe('rugi');

    // Aman: harga rekomendasi mode 1
    $sp = $this->svc->calculateSellingPrice(45000, 21.17, 10, 7);
    $r2 = $this->svc->analyze($sp, 45000, 21.17, 10, 7);
    expect($r2['status'])->toBe('aman');
});

it('break even ROAS terhitung', function () {
    $be = $this->svc->calculateBreakEvenRoas(85950, 21.17, 45000);
    expect(round($be, 2))->toBe(3.78);
});

it('fee nominal (Rp) menghasilkan persen yang konsisten — mode analisa', function () {
    $sp = 85950;
    $feeRp = 18195.62; // = 21.17% dari 85.950
    expect(round($this->svc->feePctFromNominal($feeRp, $sp), 2))->toBe(21.17);
});

it('fee nominal (Rp) — mode tentukan harga konsisten dengan fee persen', function () {
    // Ambil fee nominal dari harga rekomendasi versi persen, lalu pastikan
    // harga versi nominal menghasilkan angka yang sama.
    $spPct = $this->svc->calculateSellingPrice(45000, 21.17, 10, 7);
    $feeRp = $this->svc->calculateMarketplaceFee($spPct, 21.17);

    $spNominal = $this->svc->calculateSellingPriceWithFeeNominal(45000, $feeRp, 10, 7);

    expect(round($spNominal))->toBe(round($spPct));
});

it('PPN 11% menaikkan harga jual rekomendasi', function () {
    $tanpaPpn = $this->svc->calculateSellingPrice(45000, 21.17, 10, 7);          // ~82.502
    $denganPpn = $this->svc->calculateSellingPrice(45000, 21.17, 10, 7, 11);     // ~84.950

    expect(round($denganPpn))->toBeGreaterThan(round($tanpaPpn));
    // Faktor 1.11 pada 1/ROAS -> harga rekomendasi ~Rp84.950
    expect(round($denganPpn))->toBeGreaterThan(84800)->toBeLessThan(85100);
});

it('ROAS setelah PPN = ROAS / 1.11', function () {
    expect(round($this->svc->roasAfterVat(7, 11), 2))->toBe(6.31);
});

it('PPN menaikkan minimum ROAS platform', function () {
    $sp = 85950;
    $maxAd = $this->svc->calculateMaxAdSpend($sp, 21.17, 45000, 10);
    $tanpa = $this->svc->calculateMinimumRoas($sp, $maxAd);        // ~6.07
    $dengan = $this->svc->calculateMinimumRoas($sp, $maxAd, 11);   // ~6.74

    expect(round($dengan, 2))->toBe(round($tanpa * 1.11, 2));
});
