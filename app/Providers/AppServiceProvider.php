<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Carbon::setLocale('id');

        // Share 5 snapshot terbaru ke layout (untuk quick rollback di navbar)
        View::composer('layouts.app', function ($view) {
            $snapshotDir = storage_path('backups/snapshots');
            $snapshots = [];

            if (File::isDirectory($snapshotDir)) {
                $files = collect(File::files($snapshotDir))
                    ->filter(fn($f) => str_ends_with($f->getFilename(), '.sqlite'))
                    ->sortByDesc(fn($f) => $f->getMTime())
                    ->take(5);

                foreach ($files as $f) {
                    $name = $f->getFilename();
                    preg_match('/^snap_(\d{8})_(\d{6})(?:_(.+))?\.sqlite$/', $name, $m);
                    $date  = isset($m[1], $m[2])
                        ? Carbon::createFromFormat('Ymd His', $m[1] . ' ' . $m[2])
                        : null;
                    $label = isset($m[3]) ? str_replace('_', ' ', $m[3]) : null;

                    $snapshots[] = [
                        'filename' => $name,
                        'label'    => $label,
                        'date'     => $date,
                        'size_mb'  => round($f->getSize() / 1024 / 1024, 2),
                    ];
                }
            }

            $view->with('gfSnapshots', $snapshots);
        });
    }
}
