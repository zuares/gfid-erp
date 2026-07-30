<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class CodeGenerator
{
    /**
     * Generate kode aman dari race-condition:
     *  PREFIX-YYYYMMDD-###
     *
     * Contoh:
     *  PO-20251121-001
     *  INV-20251121-002
     *
     * @param  string      $prefix   PO / INV / LOT / TRF / FIN / dll
     * @param  string|null $forDate  Tanggal bisnis (Y-m-d). Jika null, pakai today().
     * @return string
     *
     * @throws \Throwable
     */
    public static function make(string $prefix = 'PO', ?string $forDate = null): string
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $lockKey = sprintf('code-generator:%s:%s', $prefix, $forDate ?: now()->toDateString());

                return Cache::store('file')->lock($lockKey, 15)->block(10, function () use ($prefix, $forDate) {
                    return DB::transaction(function () use ($prefix, $forDate) {
                        $now = now();

                        // Jika user kasih tanggal (mis: dari form finishing), pakai itu sebagai "tanggal bisnis"
                        // tapi tetap pakai $now untuk created_at / updated_at.
                        $dateCarbon = $forDate ? Carbon::parse($forDate) : $now;

                        $date = $dateCarbon->toDateString(); // 2025-11-21
                        $dateYmd = $dateCarbon->format('Ymd'); // 20251121

                        // Satu generator satu waktu untuk prefix+date ini.
                        $row = DB::table('running_numbers')
                            ->where('prefix', $prefix)
                            ->where('date', $date)
                            ->first();

                        if (!$row) {
                            $number = 1;

                            DB::table('running_numbers')->insert([
                                'prefix' => $prefix,
                                'date' => $date,
                                'last_number' => $number,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        } else {
                            $number = $row->last_number + 1;

                            DB::table('running_numbers')
                                ->where('id', $row->id)
                                ->update([
                                    'last_number' => $number,
                                    'updated_at' => $now,
                                ]);
                        }

                        $numberFormatted = str_pad($number, 3, '0', STR_PAD_LEFT);

                        return "{$prefix}-{$dateYmd}-{$numberFormatted}";
                    }, 3);
                });
            } catch (Throwable $e) {
                $message = strtolower($e->getMessage());
                $isLockedDb = str_contains($message, 'database is locked')
                    || str_contains($message, 'deadlock')
                    || str_contains($message, 'lock wait timeout')
                    || str_contains($message, 'busy');

                // Retry beberapa kali kalau lagi tabrakan / deadlock / transient lock
                if (!$isLockedDb || $attempt === $maxAttempts) {
                    throw $e;
                }

                // Backoff singkat, naik perlahan supaya SQLite sempat lepas lock
                if ($attempt === $maxAttempts) {
                    throw $e;
                }

                usleep(75_000 * $attempt);
            }
        }

        // praktiknya tidak akan sampai sini
        throw new \RuntimeException('Gagal generate kode.');
    }

    /**
     * Backward compatible helper.
     * Sama seperti sebelumnya, tapi sekarang cuma wrapper ke make()
     * dengan tanggal = hari ini.
     */
    public static function generate(string $prefix = 'PO'): string
    {
        return static::make($prefix);
    }
}
