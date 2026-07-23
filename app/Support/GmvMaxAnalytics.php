<?php

namespace App\Support;

/**
 * Helper kalkulasi analitik GMV Max — murni, tanpa side-effect, mudah diuji.
 * Semua pembagian aman-nol (mengembalikan null, bukan Infinity/NaN).
 *
 * PENTING (grain & double-count):
 *  - Attributed GMV utama = broad_gmv. direct_gmv = pembanding konservatif.
 *  - JANGAN menjumlahkan broad + direct (bukan penjumlahan; direct ⊂ broad).
 *  - Agregasi selalu dari grain harian per (store_id, channel_campaign_id, date).
 */
class GmvMaxAnalytics
{
    /** Pembagian aman: den=0 → null. */
    public static function safeDiv($num, $den, int $round = 2): ?float
    {
        $den = (float) $den;
        if ($den == 0.0) return null;
        return round((float) $num / $den, $round);
    }

    /** actual ROAS = gmv / spend (broad atau direct, tergantung argumen). */
    public static function roas($gmv, $spend): ?float
    {
        return self::safeDiv($gmv, $spend, 2);
    }

    /**
     * Status pencapaian target per campaign:
     *  - target_roas > 0 : 'above' | 'below' | 'no_data'
     *  - target_roas = 0 & bidding auto : 'maximize' (Maksimalkan GMV)
     *  - selain itu : 'unknown'
     */
    public static function targetStatus($targetRoas, $actualRoas, ?string $biddingMethod): string
    {
        if ($targetRoas !== null && (float) $targetRoas > 0) {
            if ($actualRoas === null) return 'no_data';
            return (float) $actualRoas >= (float) $targetRoas ? 'above' : 'below';
        }
        if ($targetRoas !== null && (float) $targetRoas === 0.0 && $biddingMethod === 'auto') {
            return 'maximize';
        }
        return 'unknown';
    }

    /**
     * Weighted target ROAS = Σ(target_roas × spend) / Σ(spend),
     * HANYA campaign target_roas>0 & spend>0 (campaign target=0 "Maksimalkan GMV"
     * tidak dimasukkan agar tidak menurunkan rata-rata custom target). Null bila
     * tak ada spend yang memenuhi.
     *
     * @param iterable<array{target_roas?:mixed,spend?:mixed}> $rows
     */
    public static function weightedTargetRoas(iterable $rows): ?float
    {
        $weighted = 0.0;
        $spendSum = 0.0;
        foreach ($rows as $r) {
            $t = $r['target_roas'] ?? null;
            $s = (float) ($r['spend'] ?? 0);
            if ($t !== null && (float) $t > 0 && $s > 0) {
                $weighted += (float) $t * $s;
                $spendSum += $s;
            }
        }
        return $spendSum > 0 ? round($weighted / $spendSum, 2) : null;
    }
}
