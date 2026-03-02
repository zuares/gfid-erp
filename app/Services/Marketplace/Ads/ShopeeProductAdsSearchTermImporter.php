<?php

namespace App\Services\Marketplace\Ads;

use App\Models\MpAdsRow;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ShopeeProductAdsSearchTermImporter
{
    /**
     * Parse CSV report seperti file kamu:
     * "Laporan Iklan Produk - Shopee Indonesia" + metadata + tabel mulai header "Urutan,..."
     */
    public function parse(string $absPath): array
    {
        $content = file_get_contents($absPath);
        if ($content === false) {
            throw new \RuntimeException('Gagal membaca file.');
        }

        // hash untuk anti-duplikat file sama persis
        $fileHash = hash('sha256', $content);

        // pecah baris, bersihin BOM
        $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];
        if (isset($lines[0])) {
            $lines[0] = preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]); // remove BOM
        }

        $meta = $this->extractMeta($lines);

        // cari header "Urutan,"
        $headerIdx = $this->findHeaderIndex($lines);
        if ($headerIdx === null) {
            throw new \RuntimeException('Header tabel tidak ditemukan (tidak ada "Urutan,").');
        }

        // parse csv dari headerIdx
        $csvText = implode("\n", array_slice($lines, $headerIdx));
        $rows = $this->parseCsvText($csvText);

        $normalized = [];
        $sumSpend = 0.0;
        $sumClicks = 0;
        $sumImpr = 0;
        $sumGmv = 0.0;

        foreach ($rows as $r) {
            $n = $this->normalizeRow($r);

            // fingerprint anti-duplikat row dalam import yang sama
            $n['row_fingerprint'] = MpAdsRow::makeFingerprint($n);

            $normalized[] = $n;

            $sumSpend += (float) ($n['spend'] ?? 0);
            $sumClicks += (int) ($n['clicks'] ?? 0);
            $sumImpr += (int) ($n['impressions'] ?? 0);
            $sumGmv += (float) ($n['gmv'] ?? 0);
        }

        $summary = [
            'rows' => count($normalized),
            'spend' => $sumSpend,
            'clicks' => $sumClicks,
            'impressions' => $sumImpr,
            'gmv' => $sumGmv,
            'roas' => $sumSpend > 0 ? ($sumGmv / $sumSpend) : null,
        ];

        return [
            'file_hash' => $fileHash,
            'meta' => array_merge([
                'channel' => 'shopee',
                'report_type' => 'product_ads_search_term_ranking',
            ], $meta),
            'summary' => $summary,
            'rows' => $normalized,
        ];
    }

    private function extractMeta(array $lines): array
    {
        $meta = [
            'shop_name' => null,
            'shop_platform_id' => null,
            'report_generated_at' => null,
            'period_start' => null,
            'period_end' => null,
        ];

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            // format: Key,Value
            $parts = str_getcsv($line);
            if (count($parts) < 2) {
                continue;
            }

            $key = trim($parts[0]);
            $val = trim($parts[1] ?? '');

            if ($key === 'Nama Toko') {
                $meta['shop_name'] = $val;
            } elseif ($key === 'ID Toko') {
                $meta['shop_platform_id'] = $val;
            } elseif ($key === 'Waktu Laporan Dibuat') {
                $meta['report_generated_at'] = $this->parseIdDatetime($val);
            } elseif ($key === 'Periode') {
                // "19/02/2026 - 19/02/2026"
                [$ps, $pe] = array_map('trim', explode('-', $val . '-', 2));
                $meta['period_start'] = $this->parseIdDate($ps);
                $meta['period_end'] = $this->parseIdDate($pe);
            }
        }

        return $meta;
    }

    private function findHeaderIndex(array $lines): ?int
    {
        foreach ($lines as $i => $line) {
            if (Str::startsWith(trim((string) $line), 'Urutan,')) {
                return $i;
            }
        }
        return null;
    }

    private function parseCsvText(string $csvText): array
    {
        $fp = fopen('php://temp', 'r+');
        fwrite($fp, $csvText);
        rewind($fp);

        $header = null;
        $rows = [];

        while (($data = fgetcsv($fp)) !== false) {
            if ($header === null) {
                $header = $data;
                continue;
            }
            if (count($data) === 1 && trim((string) $data[0]) === '') {
                continue;
            }

            // map header -> value
            $row = [];
            foreach ($header as $idx => $h) {
                $row[$h] = $data[$idx] ?? null;
            }
            $rows[] = $row;
        }

        fclose($fp);
        return $rows;
    }

    private function normalizeRow(array $r): array
    {
        // helper ambil kolom aman
        $get = function (string $k) use ($r) {
            return isset($r[$k]) ? trim((string) $r[$k]) : null;
        };

        $endRaw = $get('Tanggal Selesai');

        $startAt = $this->parseIdDatetime($get('Tanggal Mulai'));
        $endAt = null;
        if ($endRaw && mb_strtolower($endRaw) !== 'tidak terbatas') {
            $endAt = $this->parseIdDatetime($endRaw);
        }

        $out = [
            'row_no' => $this->toInt($get('Urutan')),

            'ad_name' => $get('Nama Iklan'),
            'ad_status' => $get('Status'),
            'product_code' => $get('Kode Produk'),
            'bidding_mode' => $get('Mode Bidding'),
            'placement' => $get('Penempatan Iklan'),

            'search_term' => $get('Kata Pencarian/Penempatan'),
            'match_type' => $get('Tipe Pencocokan'),

            'start_at' => $startAt,
            'end_at' => $endAt,
            'end_at_raw' => $endRaw,

            'impressions' => $this->toInt($get('Dilihat')),
            'clicks' => $this->toInt($get('Jumlah Klik')),
            'ctr' => $this->toRatio($get('Persentase Klik')),

            'conversions' => $this->toInt($get('Konversi')),
            'conversions_direct' => $this->toInt($get('Konversi Langsung')),
            'cvr' => $this->toRatio($get('Tingkat konversi')),
            'cvr_direct' => $this->toRatio($get('Tingkat Konversi Langsung')),

            'cpa' => $this->toFloat($get('Biaya per Konversi')),
            'cpa_direct' => $this->toFloat($get('Biaya per Konversi Langsung')),

            'items_sold' => $this->toInt($get('Produk Terjual')),
            'items_sold_direct' => $this->toInt($get('Terjual Langsung')),

            'gmv' => $this->toFloat($get('Omzet Penjualan')),
            'gmv_direct' => $this->toFloat($get('Penjualan Langsung (GMV Langsung)')),
            'spend' => $this->toFloat($get('Biaya')),

            'roas' => $this->toFloat($get('Efektifitas Iklan')),
            'roas_direct' => $this->toFloat($get('Efektivitas Langsung')),

            'acos' => $this->toRatio($get('Persentase Biaya Iklan terhadap Penjualan dari Iklan (ACOS)')),
            'acos_direct' => $this->toRatio($get('Persentase Biaya Iklan terhadap Penjualan dari Iklan Langsung (ACOS Langsung)')),

            'raw_json' => json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        return $out;
    }

    private function parseIdDate(?string $s): ?string
    {
        $s = trim((string) $s);
        if ($s === '') {
            return null;
        }

        // format: dd/mm/yyyy
        try {
            $dt = Carbon::createFromFormat('d/m/Y', $s);
            return $dt->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseIdDatetime(?string $s): ?string
    {
        $s = trim((string) $s);
        if ($s === '') {
            return null;
        }

        // format umum: dd/mm/yyyy HH:MM atau dd/mm/yyyy HH:MM:SS
        foreach (['d/m/Y H:i:s', 'd/m/Y H:i'] as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, $s);
                return $dt->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                // try next
            }
        }

        return null;
    }

    private function toInt(?string $s): ?int
    {
        $s = trim((string) $s);
        if ($s === '' || $s === '-') {
            return null;
        }

        $s = str_replace([',', ' '], '', $s);
        if (!is_numeric($s)) {
            return null;
        }

        return (int) $s;
    }

    private function toFloat(?string $s): ?float
    {
        $s = trim((string) $s);
        if ($s === '' || $s === '-') {
            return null;
        }

        // remove thousand separators if any
        $s = str_replace([',', ' '], '', $s);

        if (!is_numeric($s)) {
            return null;
        }

        return (float) $s;
    }

    /**
     * "13.62%" => 0.1362
     * "4.47%"  => 0.0447
     * "0.00%"  => 0
     */
    private function toRatio(?string $s): ?float
    {
        $s = trim((string) $s);
        if ($s === '' || $s === '-') {
            return null;
        }

        $s = str_replace(' ', '', $s);
        $isPercent = str_ends_with($s, '%');
        if ($isPercent) {
            $s = rtrim($s, '%');
        }

        $s = str_replace([','], '', $s);

        if (!is_numeric($s)) {
            return null;
        }

        $v = (float) $s;

        return $isPercent ? ($v / 100.0) : $v;
    }
}
