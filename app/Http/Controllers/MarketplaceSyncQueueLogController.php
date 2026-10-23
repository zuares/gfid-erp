<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceSyncLog;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class MarketplaceSyncQueueLogController extends Controller
{
    public function index(): View
    {
        $stores = Store::with('channel:id,code,name')
            ->orderBy('name')
            ->get(['id', 'name', 'channel_id', 'is_active', 'connection_status']);

        $modules = [
            'sync_orders'        => 'Sync Orders',
            'sync_orders_detail' => 'Sync Orders Detail',
            'sync_orders_by_sn'  => 'Sync Orders By SN',
            'sync_settlements'   => 'Sync Settlements',
            'sync_ad_campaigns'  => 'Sync Ads Campaign',
            'sync_returns'       => 'Sync Returns',
            'sync_bookings'      => 'Sync Bookings',
        ];

        return view('marketplace.sync-queue-logs', compact('stores', 'modules'));
    }

    public function getSyncLogs(Request $request): JsonResponse
    {
        $query = MarketplaceSyncLog::with('store:id,name,channel_id', 'store.channel:id,code,name');

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('payload', 'like', "%{$search}%");
            });
        }

        $since24h = now()->subHours(24);
        $stats24h = [
            'total'   => MarketplaceSyncLog::where('created_at', '>=', $since24h)->count(),
            'success' => MarketplaceSyncLog::where('created_at', '>=', $since24h)->where('status', 'success')->count(),
            'partial' => MarketplaceSyncLog::where('created_at', '>=', $since24h)->where('status', 'partial_success')->count(),
            'failed'  => MarketplaceSyncLog::where('created_at', '>=', $since24h)->where('status', 'failed')->count(),
        ];

        $perPage = min(max((int) $request->input('per_page', 25), 10), 100);
        $logs = $query->latest('id')->paginate($perPage);

        $items = collect($logs->items())->map(function ($log) {
            return [
                'id'         => $log->id,
                'store_id'   => $log->store_id,
                'store_name' => $log->store?->name ?? '—',
                'channel'    => $log->store?->channel?->name ?? 'Marketplace',
                'action'     => $log->action,
                'status'     => $log->status,
                'message'    => $log->message,
                'payload'    => $log->payload ?? [],
                'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
                'time_ago'   => $log->created_at?->diffForHumans(),
            ];
        });

        return response()->json([
            'success'     => true,
            'stats_24h'   => $stats24h,
            'data'        => $items,
            'pagination'  => [
                'total'        => $logs->total(),
                'per_page'     => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
            ],
        ]);
    }

    public function getQueueStatus(): JsonResponse
    {
        $pendingCount = 0;
        $failedCount = 0;
        $queueBreakdown = [];
        $activeJobs = [];
        $failedJobs = [];

        if (Schema::hasTable('jobs')) {
            $pendingCount = DB::table('jobs')->count();
            $queueBreakdown = DB::table('jobs')
                ->select('queue', DB::raw('COUNT(*) as total'))
                ->groupBy('queue')
                ->get()
                ->pluck('total', 'queue')
                ->toArray();

            $rawJobs = DB::table('jobs')
                ->orderBy('id', 'asc')
                ->limit(50)
                ->get();

            $activeJobs = $rawJobs->map(function ($job) {
                $payload = json_decode((string) $job->payload, true) ?: [];
                $commandName = $payload['displayName'] ?? ($payload['data']['commandName'] ?? 'Unknown Job');

                return [
                    'id'           => $job->id,
                    'queue'        => $job->queue,
                    'attempts'     => $job->attempts,
                    'is_reserved'  => ! empty($job->reserved_at),
                    'reserved_at'  => $job->reserved_at ? Carbon::createFromTimestamp($job->reserved_at)->format('H:i:s') : null,
                    'available_at' => Carbon::createFromTimestamp($job->available_at)->format('H:i:s'),
                    'created_at'   => Carbon::createFromTimestamp($job->created_at)->format('Y-m-d H:i:s'),
                    'age'          => Carbon::createFromTimestamp($job->created_at)->diffForHumans(),
                    'job_name'     => class_basename($commandName),
                    'full_name'    => $commandName,
                ];
            });
        }

        if (Schema::hasTable('failed_jobs')) {
            $failedCount = DB::table('failed_jobs')->count();

            $rawFailed = DB::table('failed_jobs')
                ->orderBy('failed_at', 'desc')
                ->limit(50)
                ->get();

            $failedJobs = $rawFailed->map(function ($f) {
                $payload = json_decode((string) $f->payload, true) ?: [];
                $commandName = $payload['displayName'] ?? ($payload['data']['commandName'] ?? 'Unknown Job');
                $lines = explode("\n", (string) $f->exception);
                $firstError = trim($lines[0] ?? 'Unknown Exception');

                return [
                    'id'         => $f->id,
                    'uuid'       => $f->uuid,
                    'queue'      => $f->queue,
                    'failed_at'  => Carbon::parse($f->failed_at)->format('Y-m-d H:i:s'),
                    'time_ago'   => Carbon::parse($f->failed_at)->diffForHumans(),
                    'job_name'   => class_basename($commandName),
                    'full_name'  => $commandName,
                    'error_msg'  => $firstError,
                    'exception'  => (string) $f->exception,
                ];
            });
        }

        return response()->json([
            'success'         => true,
            'pending_count'   => $pendingCount,
            'failed_count'    => $failedCount,
            'queue_breakdown' => $queueBreakdown,
            'active_jobs'     => $activeJobs,
            'failed_jobs'     => $failedJobs,
        ]);
    }

    public function getStreamLogs(Request $request): JsonResponse
    {
        $logFile = storage_path('logs/laravel.log');
        if (! File::exists($logFile)) {
            return response()->json([
                'success' => true,
                'lines'   => ['Log file not found.'],
            ]);
        }

        $filter = strtolower(trim((string) $request->input('filter', '')));
        $lines = $this->tailFile($logFile, 200);

        if ($filter !== '') {
            $lines = array_values(array_filter($lines, function ($line) use ($filter) {
                return str_contains(strtolower($line), $filter);
            }));
        }

        return response()->json([
            'success' => true,
            'lines'   => array_slice($lines, -120),
        ]);
    }

    public function retryFailedJob(string $uuid): JsonResponse
    {
        $user = auth()->user();
        if (! ($user?->isOwner() || $user?->isDeveloper())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        try {
            $exitCode = Artisan::call('queue:retry', ['id' => [$uuid]]);

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0 ? "Job {$uuid} berhasil dikirim ulang ke antrean." : "Gagal mencoba ulang job.",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteFailedJob(string $uuid): JsonResponse
    {
        $user = auth()->user();
        if (! ($user?->isOwner() || $user?->isDeveloper())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        try {
            DB::table('failed_jobs')->where('uuid', $uuid)->delete();

            return response()->json([
                'success' => true,
                'message' => "Job {$uuid} berhasil dihapus dari daftar kegagalan.",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function retryAllFailedJobs(): JsonResponse
    {
        $user = auth()->user();
        if (! ($user?->isOwner() || $user?->isDeveloper())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        try {
            $exitCode = Artisan::call('queue:retry', ['id' => ['all']]);

            return response()->json([
                'success' => $exitCode === 0,
                'message' => 'Semua pekerjaan gagal berhasil dikirim ulang ke antrean.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function flushFailedJobs(): JsonResponse
    {
        $user = auth()->user();
        if (! ($user?->isOwner() || $user?->isDeveloper())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        try {
            $exitCode = Artisan::call('queue:flush');

            return response()->json([
                'success' => $exitCode === 0,
                'message' => 'Semua riwayat pekerjaan gagal telah dibersihkan.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Efficiently reads the tail of large files without reading the entire file into memory.
     *
     * @return array<int, string>
     */
    private function tailFile(string $filePath, int $maxLines = 150): array
    {
        $handle = @fopen($filePath, 'rb');
        if (! $handle) {
            return [];
        }

        $maxBytes = 250000; // Baca maksimal 250KB dari ujung file
        fseek($handle, 0, SEEK_END);
        $fileSize = ftell($handle);

        $seekPos = max(0, $fileSize - $maxBytes);
        fseek($handle, $seekPos, SEEK_SET);

        $content = fread($handle, $fileSize - $seekPos);
        fclose($handle);

        if (! is_string($content)) {
            return [];
        }

        $allLines = explode("\n", $content);
        // Trim line pertama jika seekPos bukan 0 (karena kemungkinan terpotong di tengah baris)
        if ($seekPos > 0 && count($allLines) > 1) {
            array_shift($allLines);
        }

        $cleaned = [];
        foreach ($allLines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $cleaned[] = $line;
            }
        }

        return array_slice($cleaned, -$maxLines);
    }
}
