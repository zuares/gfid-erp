<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('mp_incomes') || ! DB::getSchemaBuilder()->hasTable('marketplace_imported_ad_spends')) {
            return;
        }

        $money = static function (mixed $value): float {
            if ($value === null || $value === '') {
                return 0.0;
            }
            if (is_numeric($value)) {
                return abs((float) $value);
            }

            $text = trim((string) $value);
            $text = preg_replace('/^\((.*)\)$/', '$1', $text) ?? $text;
            $text = str_replace(['Rp', 'rp', 'IDR', 'idr', ' '], '', $text);
            $text = preg_replace('/[^0-9.,-]/', '', $text) ?? '';
            $text = str_replace([',', '.'], '', $text);

            return abs((float) ($text !== '' && $text !== '-' ? $text : 0));
        };

        $normalise = static function (string $value): string {
            $value = strtolower(trim($value));
            $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

            return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
        };

        $aggregates = [];
        DB::table('mp_incomes')
            ->select(['id', 'store_id', 'channel', 'released_date', 'source_file', 'import_batch_id', 'raw_payload'])
            ->whereNotNull('raw_payload')
            ->orderBy('id')
            ->chunkById(500, function ($incomes) use (&$aggregates, $money, $normalise): void {
                foreach ($incomes as $income) {
                    $payload = json_decode((string) $income->raw_payload, true) ?: [];
                    $rawRows = data_get($payload, 'income.raw', []);
                    if (! is_array($rawRows)) {
                        continue;
                    }

                    foreach ($rawRows as $rawRow) {
                        if (! is_array($rawRow)) {
                            continue;
                        }

                        $channel = strtolower(trim((string) $income->channel));
                        $transactionType = $normalise((string) ($rawRow['Jenis transaksi'] ?? $rawRow['Type'] ?? ''));
                        $isTiktokGmvAds = $channel === 'tiktok'
                            && $transactionType === 'pembayaran gmv untuk iklan tiktok';

                        // TikTok's order-level "Komisi Iklan ... Afiliasi" is
                        // not the GMV Max spend used by the sales dashboard.
                        // Only the explicit GMV advertising payment is valid
                        // for TikTok.
                        if ($channel === 'tiktok' && ! $isTiktokGmvAds) {
                            continue;
                        }

                        $spend = 0.0;
                        if ($isTiktokGmvAds) {
                            $spend = $money($rawRow['Jumlah penyesuaian'] ?? $rawRow['Adjustment amount'] ?? 0);
                        } else {
                            foreach ($rawRow as $header => $value) {
                                $header = $normalise((string) $header);
                                if (str_contains($header, 'iklan') || str_contains($header, 'ads') || str_contains($header, 'advert')) {
                                    $spend += $money($value);
                                }
                            }
                        }

                        $date = (string) ($income->released_date ?? '');
                        if ($spend <= 0 || $date === '') {
                            continue;
                        }

                        $key = implode('|', [(int) $income->store_id, (string) $income->channel, $date]);
                        $aggregates[$key]['store_id'] = (int) $income->store_id;
                        $aggregates[$key]['channel'] = (string) $income->channel;
                        $aggregates[$key]['date'] = $date;
                        $aggregates[$key]['spend'] = (float) ($aggregates[$key]['spend'] ?? 0) + $spend;
                        $aggregates[$key]['source_file'] = $income->source_file;
                        $aggregates[$key]['import_batch_id'] = $income->import_batch_id;
                        $aggregates[$key]['raw_json'] = json_encode([
                            'source' => 'income_raw_backfill',
                            'source_file' => $income->source_file,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                }
            });

        if ($aggregates === []) {
            return;
        }

        $now = now();
        $rows = array_map(static fn (array $row): array => $row + [
            'created_at' => $now,
            'updated_at' => $now,
        ], array_values($aggregates));

        DB::table('marketplace_imported_ad_spends')->upsert(
            $rows,
            ['store_id', 'channel', 'date'],
            ['spend', 'source_file', 'import_batch_id', 'raw_json', 'updated_at'],
        );
    }

    public function down(): void
    {
        // The source income rows remain intact; imported ad snapshots are
        // removed together with the table in the preceding migration.
    }
};
