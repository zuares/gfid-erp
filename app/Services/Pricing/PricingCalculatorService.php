<?php

namespace App\Services\Pricing;

/**
 * PricingCalculatorService
 * ------------------------------------------------------------------
 * Sumber kebenaran tunggal untuk perhitungan Pricing & ROAS Calculator.
 * Logika yang sama juga direplikasi di frontend (vanilla JS) untuk live
 * calculation. Service ini dipakai oleh:
 *   - Feature test (Case 1 & Case 2)
 *   - Kebutuhan masa depan: save calculation, ambil HPP dari master item,
 *     ambil fee berdasarkan marketplace/store, dsb.
 *
 * Konvensi input persen: dipakai nilai "human" (mis. 21.17 = 21,17%,
 * 10 = 10%). ROAS dipakai apa adanya (mis. 7 = 7x). Semua nominal rupiah.
 * Stateless — tidak menyentuh database.
 */
class PricingCalculatorService
{
    /**
     * MODE 1 — "Tentukan Harga Jual".
     * Harga Jual = HPP / (1 - fee - profit - (1 / ROAS))
     *
     * @return float|null  null jika target tidak memungkinkan (denominator <= 0)
     */
    public function calculateSellingPrice(float $hpp, float $feePct, float $profitPct, float $roas, float $adVatPct = 0): ?float
    {
        $fee = $this->pctToFraction($feePct);
        $profit = $this->pctToFraction($profitPct);
        $roasShare = $roas > 0 ? ($this->adFactor($adVatPct) / $roas) : 1.0;

        $denominator = 1 - $fee - $profit - $roasShare;

        if ($denominator <= 0) {
            return null;
        }

        return $hpp / $denominator;
    }

    /** Fee marketplace dalam rupiah. */
    public function calculateMarketplaceFee(float $sellingPrice, float $feePct): float
    {
        return $sellingPrice * $this->pctToFraction($feePct);
    }

    /**
     * Kebalikannya: hitung persentase fee dari nominal rupiah + harga jual.
     * Dipakai bila user mengisi fee marketplace dalam Rupiah.
     * fee% = feeRp / hargaJual * 100
     */
    public function feePctFromNominal(float $feeRp, float $sellingPrice): float
    {
        if ($sellingPrice <= 0) {
            return 0.0;
        }

        return $feeRp / $sellingPrice * 100;
    }

    /**
     * MODE 1 varian: fee marketplace sebagai NOMINAL tetap (bukan persen).
     * Turunan aljabar dari rumus utama (feeRp konstan):
     *   Harga Jual = (HPP + feeRp) / (1 - profit - (1 / ROAS))
     *
     * @return float|null  null jika target tidak memungkinkan.
     */
    public function calculateSellingPriceWithFeeNominal(float $hpp, float $feeRp, float $profitPct, float $roas, float $adVatPct = 0): ?float
    {
        $roasShare = $roas > 0 ? ($this->adFactor($adVatPct) / $roas) : 1.0;
        $denominator = 1 - $this->pctToFraction($profitPct) - $roasShare;

        if ($denominator <= 0) {
            return null;
        }

        return ($hpp + $feeRp) / $denominator;
    }

    /** Pendapatan bersih setelah dipotong fee marketplace. */
    public function calculateNetAfterFee(float $sellingPrice, float $feePct): float
    {
        return $sellingPrice - $this->calculateMarketplaceFee($sellingPrice, $feePct);
    }

    /**
     * Maksimal biaya iklan / order (dengan mempertahankan target profit).
     * Max Ad = Harga Jual - Fee - HPP - Target Profit (rupiah)
     */
    public function calculateMaxAdSpend(float $sellingPrice, float $feePct, float $hpp, float $profitPct): float
    {
        $fee = $this->calculateMarketplaceFee($sellingPrice, $feePct);
        $profitRp = $sellingPrice * $this->pctToFraction($profitPct);

        return $sellingPrice - $fee - $hpp - $profitRp;
    }

    /**
     * Minimum ROAS = Harga Jual / Maksimal Biaya Iklan.
     * null jika tidak ada ruang iklan (maxAdSpend <= 0).
     */
    public function calculateMinimumRoas(float $sellingPrice, float $maxAdSpend, float $adVatPct = 0): ?float
    {
        if ($maxAdSpend <= 0) {
            return null;
        }

        // Biaya iklan riil = spend x (1 + PPN). ROAS platform (ex-PPN) yang
        // dibutuhkan jadi lebih tinggi: sp x faktor / maxAdSpend.
        return $sellingPrice * $this->adFactor($adVatPct) / $maxAdSpend;
    }

    /**
     * Break Even ROAS = Harga Jual / (Harga Jual - Fee - HPP).
     * Titik di mana profit = 0 (belum termasuk target profit).
     * null jika denominator <= 0 (harga jual tak menutup fee + HPP).
     */
    public function calculateBreakEvenRoas(float $sellingPrice, float $feePct, float $hpp, float $adVatPct = 0): ?float
    {
        $denominator = $this->calculateNetAfterFee($sellingPrice, $feePct) - $hpp;

        if ($denominator <= 0) {
            return null;
        }

        return $sellingPrice * $this->adFactor($adVatPct) / $denominator;
    }

    /**
     * Estimasi net profit / order pada ROAS tertentu.
     * Net = Harga Jual - Fee - HPP - Biaya Iklan
     * Biaya iklan per order = Harga Jual / ROAS (jika roas > 0).
     */
    public function calculateNetProfit(float $sellingPrice, float $hpp, float $feePct, ?float $roas, float $adVatPct = 0): float
    {
        $fee = $this->calculateMarketplaceFee($sellingPrice, $feePct);
        // Biaya iklan riil per order = (harga jual / ROAS) x (1 + PPN).
        $adSpend = ($roas !== null && $roas > 0)
            ? ($sellingPrice / $roas) * $this->adFactor($adVatPct)
            : 0.0;

        return $sellingPrice - $fee - $hpp - $adSpend;
    }

    /**
     * ROAS efektif atas biaya iklan riil (termasuk PPN).
     * ROAS setelah PPN = ROAS platform / (1 + PPN).
     */
    public function roasAfterVat(?float $roas, float $adVatPct = 11): ?float
    {
        if ($roas === null || $roas <= 0) {
            return null;
        }

        return $roas / $this->adFactor($adVatPct);
    }

    /** Net profit margin (fraksi, mis. 0.12 = 12%). */
    public function calculateNetMargin(float $netProfit, float $sellingPrice): float
    {
        if ($sellingPrice <= 0) {
            return 0.0;
        }

        return $netProfit / $sellingPrice;
    }

    /**
     * Quick psychological pricing — prioritaskan ending 950.
     * Contoh: 82.433 -> [82.950, 83.950, 84.950, 85.950]
     *
     * @return array<int,int>
     */
    public function suggestSellingPrices(float $price, int $count = 4): array
    {
        if ($price <= 0 || $count < 1) {
            return [];
        }

        // Pembulatan ke ribuan terdekat lalu tambah 950.
        $base = floor($price / 1000) * 1000 + 950;

        // Pastikan tidak lebih rendah dari harga matematis.
        if ($base < $price) {
            $base += 1000;
        }

        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = (int) ($base + $i * 1000);
        }

        return $out;
    }

    /**
     * Cek apakah kombinasi target valid untuk MODE 1.
     * Invalid bila fee + profit + (1/ROAS) >= 100%.
     */
    public function isTargetFeasible(float $feePct, float $profitPct, float $roas, float $adVatPct = 0): bool
    {
        $roasShare = $roas > 0 ? ($this->adFactor($adVatPct) / $roas) : 1.0;

        return ($this->pctToFraction($feePct) + $this->pctToFraction($profitPct) + $roasShare) < 1;
    }

    /**
     * MODE 2 — "Analisa Harga Jual".
     * Mengembalikan seluruh metrik + status (aman/mepet/rugi) untuk satu
     * kombinasi input. Dipakai controller/test; frontend mereplikasi ini.
     *
     * @return array<string,mixed>
     */
    public function analyze(float $sellingPrice, float $hpp, float $feePct, float $profitPct, ?float $roas = null, float $adVatPct = 0): array
    {
        $feeRp = $this->calculateMarketplaceFee($sellingPrice, $feePct);
        $netAfterFee = $this->calculateNetAfterFee($sellingPrice, $feePct);
        $targetProfitRp = $sellingPrice * $this->pctToFraction($profitPct);
        $maxAdSpend = $this->calculateMaxAdSpend($sellingPrice, $feePct, $hpp, $profitPct);
        $minRoas = $this->calculateMinimumRoas($sellingPrice, $maxAdSpend, $adVatPct);
        $breakEvenRoas = $this->calculateBreakEvenRoas($sellingPrice, $feePct, $hpp, $adVatPct);

        $netProfit = $this->calculateNetProfit($sellingPrice, $hpp, $feePct, $roas, $adVatPct);
        $netMargin = $this->calculateNetMargin($netProfit, $sellingPrice);

        return [
            'selling_price'      => $sellingPrice,
            'hpp'                => $hpp,
            'fee_pct'            => $feePct,
            'fee_rp'            => $feeRp,
            'net_after_fee'      => $netAfterFee,
            'target_profit_pct'  => $profitPct,
            'target_profit_rp'   => $targetProfitRp,
            'max_ad_spend'       => $maxAdSpend,
            'min_roas'           => $minRoas,
            'min_roas_after_vat' => $this->roasAfterVat($minRoas, $adVatPct),
            'break_even_roas'    => $breakEvenRoas,
            'roas_after_vat'     => $this->roasAfterVat($roas, $adVatPct),
            'ad_vat_pct'         => $adVatPct,
            'net_profit'         => $netProfit,
            'net_margin'         => $netMargin,
            'status'             => $this->status($sellingPrice, $hpp, $netProfit, $targetProfitRp),
        ];
    }

    /**
     * Simulasi ROAS: profit & margin per level ROAS.
     *
     * @param  array<int,float>  $roasLevels
     * @return array<int,array<string,float>>
     */
    public function simulateRoas(float $sellingPrice, float $hpp, float $feePct, array $roasLevels = [4, 5, 6, 7, 8, 9, 10], float $adVatPct = 0): array
    {
        $rows = [];
        foreach ($roasLevels as $roas) {
            $profit = $this->calculateNetProfit($sellingPrice, $hpp, $feePct, (float) $roas, $adVatPct);
            $rows[] = [
                'roas'           => (float) $roas,
                'roas_after_vat' => $this->roasAfterVat((float) $roas, $adVatPct),
                'profit'         => $profit,
                'margin'         => $this->calculateNetMargin($profit, $sellingPrice),
            ];
        }

        return $rows;
    }

    /**
     * Status penilaian:
     *   rugi  -> profit <= 0 atau harga di bawah HPP
     *   mepet -> profit positif tapi < target profit
     *   aman  -> profit >= target profit
     */
    public function status(float $sellingPrice, float $hpp, float $netProfit, float $targetProfitRp): string
    {
        // Toleransi 1 rupiah agar pembulatan float pada titik pas target
        // tidak salah menilai "aman" menjadi "mepet".
        $tolerance = 1.0;

        if ($sellingPrice < $hpp || $netProfit <= 0) {
            return 'rugi';
        }

        if ($netProfit < $targetProfitRp - $tolerance) {
            return 'mepet';
        }

        return 'aman';
    }

    private function pctToFraction(float $pct): float
    {
        return $pct / 100;
    }

    /** Faktor pengali biaya iklan akibat PPN. adVatPct=11 -> 1.11. */
    private function adFactor(float $adVatPct): float
    {
        return 1 + $this->pctToFraction($adVatPct);
    }
}
