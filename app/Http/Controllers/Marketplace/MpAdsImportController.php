<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MpAdsImport;
use App\Models\MpAdsRow;
use App\Services\Marketplace\Ads\ShopeeProductAdsSearchTermImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // optional
use Illuminate\View\View;

class MpAdsImportController extends Controller
{
    private string $disk = 'local';

    public function create(Request $request): View
    {
        return view('imports.marketplace_ads.create', [
            'defaultChannel' => 'shopee',
        ]);
    }

    public function preview(Request $request, ShopeeProductAdsSearchTermImporter $importer): View
    {
        $validated = $request->validate([
            'channel' => ['required', 'in:shopee'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'], // 20MB
        ]);

        $file = $request->file('file');
        if (!$file) {
            return back()->withErrors(['file' => 'File tidak ditemukan.']);
        }

        // simpan file upload di disk local (konsisten)
        $safeName = preg_replace('/[^a-zA-Z0-9\.\-_]/', '_', $file->getClientOriginalName());
        $storedPath = $file->storeAs(
            'imports/mp_ads',
            now()->format('Ymd_His') . '_' . Str::random(6) . '_' . $safeName,
            $this->disk
        );

        $absPath = Storage::disk($this->disk)->path($storedPath);
        if (!is_file($absPath)) {
            // kalau ini kejadian, berarti disk config/permission bermasalah
            return back()->withErrors([
                'file' => "Upload tersimpan tapi tidak bisa ditemukan: {$absPath}",
            ]);
        }

        // parse csv
        $parsed = $importer->parse($absPath);

        // simpan hasil parse ke JSON tmp (biar commit gak bawa payload besar)
        $tmpKey = 'tmp/mp_ads_preview_' . now()->format('Ymd_His') . '_' . Str::random(10) . '.json';

        Storage::disk($this->disk)->put($tmpKey, json_encode([
            'disk' => $this->disk,
            'channel' => $validated['channel'],
            'stored_path' => $storedPath,
            'file_name' => $file->getClientOriginalName(),
            'file_hash' => $parsed['file_hash'] ?? null,
            'meta' => $parsed['meta'] ?? [],
            'summary' => $parsed['summary'] ?? [],
            'rows' => $parsed['rows'] ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $rows = (array) ($parsed['rows'] ?? []);

        return view('imports.marketplace_ads.preview', [
            'tmpKey' => $tmpKey,
            'channel' => $validated['channel'],
            'storedPath' => $storedPath,
            'fileName' => $file->getClientOriginalName(),
            'meta' => $parsed['meta'] ?? [],
            'summary' => $parsed['summary'] ?? [],
            'rowsPreview' => array_slice($rows, 0, 50),
            'rowsCount' => count($rows),
        ]);
    }

    public function commit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tmp_key' => ['required', 'string'],
        ]);

        $tmpKey = $validated['tmp_key'];

        if (!Storage::disk($this->disk)->exists($tmpKey)) {
            return back()->withErrors(['tmp_key' => 'Preview tidak ditemukan / sudah kadaluarsa. Silakan preview ulang.']);
        }

        $payload = json_decode(Storage::disk($this->disk)->get($tmpKey), true);

        if (!is_array($payload)) {
            return back()->withErrors(['tmp_key' => 'Data preview rusak. Silakan preview ulang.']);
        }

        $channel = (string) ($payload['channel'] ?? 'shopee');
        $fileHash = (string) ($payload['file_hash'] ?? '');
        $fileName = (string) ($payload['file_name'] ?? null);
        $storedPath = (string) ($payload['stored_path'] ?? '');
        $meta = (array) ($payload['meta'] ?? []);
        $rows = (array) ($payload['rows'] ?? []);

        $reportType = (string) ($meta['report_type'] ?? 'product_ads_search_term_ranking');
        $shopPlatformId = $meta['shop_platform_id'] ?? null;
        $shopName = $meta['shop_name'] ?? null;
        $periodStart = $meta['period_start'] ?? null; // Y-m-d
        $periodEnd = $meta['period_end'] ?? null; // Y-m-d
        $generatedAt = $meta['report_generated_at'] ?? null; // Y-m-d H:i:s

        if ($fileHash === '') {
            return back()->withErrors(['file_hash' => 'File hash kosong. Silakan preview ulang.']);
        }

        // anti duplikat file sama persis
        if (MpAdsImport::query()->where('file_hash', $fileHash)->exists()) {
            return back()->withErrors(['file' => 'File yang sama persis sudah pernah diimport (anti-duplikat aktif).']);
        }

        $userId = Auth::id();

        try {
            DB::transaction(function () use (
                $channel, $reportType, $shopPlatformId, $shopName, $periodStart, $periodEnd, $generatedAt,
                $fileName, $fileHash, $rows, $userId
            ) {
                // dataset unik per periode: REPLACE
                $existing = MpAdsImport::query()
                    ->dataset($channel, $shopPlatformId, $reportType, $periodStart, $periodEnd)
                    ->first();

                if ($existing) {
                    MpAdsRow::query()->where('import_id', $existing->id)->delete();

                    $existing->update([
                        'shop_name' => $shopName,
                        'report_generated_at' => $generatedAt,
                        'file_name' => $fileName,
                        'file_hash' => $fileHash,
                        'status' => 'committed',
                        'created_by' => $userId,
                    ]);

                    $import = $existing;
                } else {
                    $import = MpAdsImport::query()->create([
                        'channel' => $channel,
                        'report_type' => $reportType,
                        'shop_platform_id' => $shopPlatformId,
                        'shop_name' => $shopName,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'report_generated_at' => $generatedAt,
                        'file_name' => $fileName,
                        'file_hash' => $fileHash,
                        'status' => 'committed',
                        'created_by' => $userId,
                    ]);
                }

                $now = now()->toDateTimeString();
                $batch = [];

                foreach ($rows as $r) {
                    // pastikan fingerprint ada & konsisten
                    $fingerprint = $r['row_fingerprint'] ?? MpAdsRow::makeFingerprint((array) $r);

                    $batch[] = [
                        'import_id' => $import->id,
                        'row_no' => $r['row_no'] ?? null,

                        'ad_name' => $r['ad_name'] ?? null,
                        'ad_status' => $r['ad_status'] ?? null,
                        'product_code' => $r['product_code'] ?? null,
                        'bidding_mode' => $r['bidding_mode'] ?? null,
                        'placement' => $r['placement'] ?? null,

                        'search_term' => $r['search_term'] ?? null,
                        'match_type' => $r['match_type'] ?? null,

                        'start_at' => $r['start_at'] ?? null,
                        'end_at' => $r['end_at'] ?? null,
                        'end_at_raw' => $r['end_at_raw'] ?? null,

                        'impressions' => $r['impressions'] ?? null,
                        'clicks' => $r['clicks'] ?? null,
                        'ctr' => $r['ctr'] ?? null,

                        'conversions' => $r['conversions'] ?? null,
                        'conversions_direct' => $r['conversions_direct'] ?? null,
                        'cvr' => $r['cvr'] ?? null,
                        'cvr_direct' => $r['cvr_direct'] ?? null,

                        'cpa' => $r['cpa'] ?? null,
                        'cpa_direct' => $r['cpa_direct'] ?? null,

                        'items_sold' => $r['items_sold'] ?? null,
                        'items_sold_direct' => $r['items_sold_direct'] ?? null,

                        'gmv' => $r['gmv'] ?? null,
                        'gmv_direct' => $r['gmv_direct'] ?? null,
                        'spend' => $r['spend'] ?? null,

                        'roas' => $r['roas'] ?? null,
                        'roas_direct' => $r['roas_direct'] ?? null,
                        'acos' => $r['acos'] ?? null,
                        'acos_direct' => $r['acos_direct'] ?? null,

                        'row_fingerprint' => $fingerprint,
                        'raw_json' => $r['raw_json'] ?? null,

                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($batch, 500) as $chunk) {
                    MpAdsRow::query()->insert($chunk);
                }
            });

            // cleanup tmp json
            Storage::disk($this->disk)->delete($tmpKey);

            // optional: cleanup file csv upload biar storage gak numpuk
            if ($storedPath !== '' && Storage::disk($this->disk)->exists($storedPath)) {
                Storage::disk($this->disk)->delete($storedPath);
            }

            return redirect()
                ->route('imports.marketplace_ads.create')
                ->with('status', 'Import iklan berhasil ✅ (anti-duplikat aktif, periode sama di-REPLACE tanpa duplikasi).');

        } catch (\Throwable $e) {
            // jangan hapus tmpKey agar bisa retry / debug
            return back()->withErrors([
                'commit' => 'Gagal commit import: ' . $e->getMessage(),
            ]);
        }
    }

    public function index(Request $request): View
    {
        $filters = [
            'date' => trim((string) $request->query('date', '')), // YYYY-MM-DD
            'channel' => trim((string) $request->query('channel', 'shopee')),
            'shop' => trim((string) $request->query('shop', '')), // shop_platform_id / nama
            'q' => trim((string) $request->query('q', '')), // search term contains
        ];

        // default date = hari ini
        if ($filters['date'] === '') {
            $filters['date'] = now()->toDateString();
        }

        $date = $filters['date'];

        // 1) pilih import yang period-nya overlap tanggal tsb
        $importsQ = MpAdsImport::query()
            ->where('channel', $filters['channel'])
            ->whereNotNull('period_start')
            ->whereNotNull('period_end')
            ->whereDate('period_start', '<=', $date)
            ->whereDate('period_end', '>=', $date);

        if ($filters['shop'] !== '') {
            $importsQ->where(function ($q) use ($filters) {
                $q->where('shop_platform_id', 'like', "%{$filters['shop']}%")
                    ->orWhere('shop_name', 'like', "%{$filters['shop']}%");
            });
        }

        $imports = $importsQ
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->get(['id', 'shop_platform_id', 'shop_name', 'period_start', 'period_end', 'report_type']);

        $importIds = $imports->pluck('id')->all();

        // kalau tidak ada import yang cover tanggal tsb
        $topTerms = collect();
        $kpi = [
            'spend' => 0,
            'clicks' => 0,
            'impressions' => 0,
            'gmv' => 0,
            'roas' => null,
        ];

        if (!empty($importIds)) {
            $rowsQ = MpAdsRow::query()->whereIn('import_id', $importIds);

            if ($filters['q'] !== '') {
                $rowsQ->where('search_term', 'like', "%{$filters['q']}%");
            }

            // KPI global (agregat semua rows yang match)
            $agg = (clone $rowsQ)->selectRaw('
            COALESCE(SUM(spend),0) as spend,
            COALESCE(SUM(clicks),0) as clicks,
            COALESCE(SUM(impressions),0) as impressions,
            COALESCE(SUM(gmv),0) as gmv
        ')->first();

            $kpi['spend'] = (float) ($agg->spend ?? 0);
            $kpi['clicks'] = (int) ($agg->clicks ?? 0);
            $kpi['impressions'] = (int) ($agg->impressions ?? 0);
            $kpi['gmv'] = (float) ($agg->gmv ?? 0);
            $kpi['roas'] = $kpi['spend'] > 0 ? ($kpi['gmv'] / $kpi['spend']) : null;

            // Top search terms (harian versi “snapshot dari import yang cover tanggal”)
            $topTerms = (clone $rowsQ)
                ->selectRaw('
                search_term,
                COALESCE(SUM(impressions),0) as impressions,
                COALESCE(SUM(clicks),0) as clicks,
                COALESCE(SUM(spend),0) as spend,
                COALESCE(SUM(gmv),0) as gmv
            ')
                ->groupBy('search_term')
                ->orderByDesc('gmv')
                ->orderByDesc('clicks')
                ->limit(200)
                ->get()
                ->map(function ($r) {
                    $spend = (float) $r->spend;
                    $gmv = (float) $r->gmv;
                    return [
                        'search_term' => (string) $r->search_term,
                        'impressions' => (int) $r->impressions,
                        'clicks' => (int) $r->clicks,
                        'spend' => $spend,
                        'gmv' => $gmv,
                        'roas' => $spend > 0 ? ($gmv / $spend) : null,
                    ];
                });
        }

        return view('imports.marketplace_ads.index', [
            'filters' => $filters,
            'imports' => $imports,
            'kpi' => $kpi,
            'topTerms' => $topTerms,
        ]);
    }

/**
 * Optional: endpoint JSON kalau kamu mau bikin table ajax.
 * Sekarang aku keep sederhana: return top terms as json.
 */
    public function data(Request $request)
    {
        $date = (string) $request->query('date', now()->toDateString());
        $q = trim((string) $request->query('q', ''));
        $channel = trim((string) $request->query('channel', 'shopee'));

        $imports = MpAdsImport::query()
            ->where('channel', $channel)
            ->whereDate('period_start', '<=', $date)
            ->whereDate('period_end', '>=', $date)
            ->pluck('id')
            ->all();

        if (empty($imports)) {
            return response()->json(['data' => []]);
        }

        $rowsQ = MpAdsRow::query()->whereIn('import_id', $imports);

        if ($q !== '') {
            $rowsQ->where('search_term', 'like', "%{$q}%");
        }

        $data = $rowsQ->selectRaw('
            search_term,
            COALESCE(SUM(impressions),0) as impressions,
            COALESCE(SUM(clicks),0) as clicks,
            COALESCE(SUM(spend),0) as spend,
            COALESCE(SUM(gmv),0) as gmv
        ')
            ->groupBy('search_term')
            ->orderByDesc('gmv')
            ->limit(500)
            ->get();

        return response()->json(['data' => $data]);
    }
}
