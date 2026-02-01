<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class MarketplaceViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('imports.marketplace.*', function ($view) {
            $tz = 'Asia/Jakarta';

            $view->with('money', fn($n) =>
                'Rp ' . number_format((float) ($n ?? 0), 0, ',', '.')
            );

            $view->with('fmtDate', function ($v, $fmt = 'd M Y') use ($tz) {
                if (!$v) {
                    return '-';
                }

                try {
                    return Carbon::parse($v)->timezone($tz)->format($fmt);
                } catch (\Throwable $e) {
                    return (string) $v;
                }
            });

            $view->with('dPct', function ($v) {
                $v = (float) ($v ?? 0);
                return ($v > 0 ? '+' : '') . number_format($v, 1) . '%';
            });

            $view->with('deltaClass', function ($v) {
                $v = (float) ($v ?? 0);
                return $v > 0
                ? 'text-success'
                : ($v < 0 ? 'text-danger' : 'text-muted');
            });

            $view->with('statusLabel', function ($s) {
                return match ((string) $s) {
                    'delivered' => ['Delivered', 'success'],
                    'in_transit' => ['In Transit', 'primary'],
                    'canceled' => ['Canceled', 'danger'],
                    default => ['Unknown', 'secondary'],
                };
            });
        });
    }
}
