<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
                return DB::transaction(function () use ($prefix, $forDate) {
                    $now = now();

                    // Jika user kasih tanggal (mis: dari form finishing), pakai itu sebagai "tanggal bisnis"
                    // tapi tetap pakai $now untuk created_at / updated_at.
                    if ($forDate) {
                        $dateCarbon = Carbon::parse($forDate);
                    } else {
                        $dateCarbon = $now;
                    }

                    $date = $dateCarbon->toDateString(); // 2025-11-21
                    $dateYmd = $dateCarbon->format('Ymd'); // 20251121

                    // Lock baris running_numbers untuk prefix+date ini
                    $row = DB::table('running_numbers')
                        ->where('prefix', $prefix)
                        ->where('date', $date)
                        ->lockForUpdate()
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
                }, 3); // 3x attempt internal transaction (kalau DB error)
            } catch (\Throwable $e) {
                // Retry beberapa kali kalau lagi "tabrakan" / deadlock / transient error
                if ($attempt === $maxAttempts) {
                    throw $e;
                }

                // Tidur sebentar sebelum coba lagi (50ms)
                usleep(50_000);
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
