<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJobBundle;
use App\Models\WipOpnameLine;
use App\Models\WipOpnamePeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WipOpnameController extends Controller
{
    // ── Index ────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $periods = WipOpnamePeriod::cutting()
            ->with('openedBy')
            ->latest()
            ->paginate(20);

        $activePeriod = WipOpnamePeriod::cutting()->active()->latest()->first();

        return view('production.wip_opname.index', compact('periods', 'activePeriod'));
    }

    // ── Buka Periode Baru ────────────────────────────────────────────────────

    public function create(): View
    {
        // Cegah buka period baru jika masih ada yang aktif
        $activePeriod = WipOpnamePeriod::cutting()->active()->first();

        // Preview: bundle yang akan di-snapshot
        $bundles = CuttingJobBundle::with(['cuttingJob', 'finishedItem'])
            ->where('cut_wip_qty', '>', 0)
            ->orderBy('bundle_code')
            ->get();

        return view('production.wip_opname.create', compact('activePeriod', 'bundles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        // Blok jika masih ada period aktif
        if (WipOpnamePeriod::cutting()->active()->exists()) {
            return back()->with('error', 'Masih ada periode opname WIP yang sedang berjalan. Selesaikan dulu sebelum membuka yang baru.');
        }

        $bundles = CuttingJobBundle::with(['cuttingJob', 'finishedItem'])
            ->where('cut_wip_qty', '>', 0)
            ->get();

        if ($bundles->isEmpty()) {
            return back()->with('error', 'Tidak ada bundle WIP cutting yang bisa di-opname saat ini.');
        }

        DB::transaction(function () use ($request, $bundles) {
            $code = $this->generateCode();

            $period = WipOpnamePeriod::create([
                'code'      => $code,
                'scope'     => 'cutting',
                'date'      => today(),
                'notes'     => $request->notes,
                'status'    => WipOpnamePeriod::STATUS_OPEN,
                'opened_by' => auth()->id(),
                'opened_at' => now(),
            ]);

            $lines = $bundles->map(fn ($b) => [
                'wip_opname_period_id'   => $period->id,
                'cutting_job_bundle_id'  => $b->id,
                'bundle_code'            => $b->bundle_code,
                'item_code'              => $b->finishedItem?->code ?? '',
                'item_name'              => $b->finishedItem?->name ?? '',
                'cutting_job_code'       => $b->cuttingJob?->code ?? '',
                'qty_system'             => $b->cut_wip_qty,
                'qty_physical'           => null,
                'difference'             => null,
                'is_counted'             => false,
                'created_at'             => now(),
                'updated_at'             => now(),
            ])->toArray();

            WipOpnameLine::insert($lines);
        });

        return redirect()
            ->route('production.wip_opname.index')
            ->with('success', 'Periode opname WIP cutting dibuka. Transaksi cutting dibekukan sementara.');
    }

    // ── Detail & Input Fisik ─────────────────────────────────────────────────

    public function show(WipOpnamePeriod $wipOpnamePeriod): View
    {
        $period = $wipOpnamePeriod->load('openedBy', 'approvedBy');

        $lines = $period->lines()
            ->orderBy('bundle_code')
            ->get();

        $totalLines   = $lines->count();
        $countedLines = $lines->where('is_counted', true)->count();
        $withDiff     = $lines->filter(fn ($l) => $l->is_counted && abs($l->difference ?? 0) > 0.01)->count();

        return view('production.wip_opname.show', compact(
            'period', 'lines', 'totalLines', 'countedLines', 'withDiff'
        ));
    }

    // ── Update Qty Fisik (AJAX / single line) ────────────────────────────────

    public function updateLine(Request $request, WipOpnamePeriod $wipOpnamePeriod, WipOpnameLine $line): RedirectResponse
    {
        abort_if(!$wipOpnamePeriod->canEdit(), 403, 'Periode tidak bisa diedit lagi.');
        abort_if($line->wip_opname_period_id !== $wipOpnamePeriod->id, 403);

        $request->validate([
            'qty_physical' => 'required|numeric|min:0',
            'notes'        => 'nullable|string|max:255',
        ]);

        $qtyPhysical = (float) $request->qty_physical;
        $diff        = $qtyPhysical - $line->qty_system;

        $line->update([
            'qty_physical' => $qtyPhysical,
            'difference'   => $diff,
            'is_counted'   => true,
            'notes'        => $request->notes,
            'counted_by'   => auth()->id(),
            'counted_at'   => now(),
        ]);

        // Auto-update period status ke counting jika belum
        if ($wipOpnamePeriod->status === WipOpnamePeriod::STATUS_OPEN) {
            $wipOpnamePeriod->update(['status' => WipOpnamePeriod::STATUS_COUNTING]);
        }

        return back()->with('success', "Bundle {$line->bundle_code} berhasil dicatat.");
    }

    // ── Submit untuk Approval ────────────────────────────────────────────────

    public function submitForApproval(WipOpnamePeriod $wipOpnamePeriod): RedirectResponse
    {
        abort_if(!$wipOpnamePeriod->canEdit(), 403);

        $uncounted = $wipOpnamePeriod->lines()->where('is_counted', false)->count();
        if ($uncounted > 0) {
            return back()->with('error', "Masih ada {$uncounted} bundle yang belum dihitung secara fisik.");
        }

        $wipOpnamePeriod->update(['status' => WipOpnamePeriod::STATUS_PENDING_APPROVAL]);

        return back()->with('success', 'Opname dikirim ke Owner untuk disetujui.');
    }

    // ── Approve (Owner) ──────────────────────────────────────────────────────

    public function approve(WipOpnamePeriod $wipOpnamePeriod): RedirectResponse
    {
        abort_if(!$wipOpnamePeriod->canApprove(), 403);
        abort_if(!auth()->user()->isOwner() && !auth()->user()->isDeveloper(), 403);

        DB::transaction(function () use ($wipOpnamePeriod) {
            $wipOpnamePeriod->update([
                'status'      => WipOpnamePeriod::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Koreksi cut_wip_qty pada bundle yang ada selisih
            $wipOpnamePeriod->lines()
                ->where('is_counted', true)
                ->whereNotNull('difference')
                ->get()
                ->each(function (WipOpnameLine $line) {
                    if (abs($line->difference) > 0.01) {
                        $bundle = $line->bundle;
                        if ($bundle) {
                            $bundle->update([
                                'cut_wip_qty' => max(0, $line->qty_physical),
                            ]);
                        }
                    }
                });

            // Tutup periode
            $wipOpnamePeriod->update([
                'status'    => WipOpnamePeriod::STATUS_CLOSED,
                'closed_by' => auth()->id(),
                'closed_at' => now(),
            ]);
        });

        return redirect()
            ->route('production.wip_opname.show', $wipOpnamePeriod)
            ->with('success', 'Opname WIP cutting disetujui. Stok bundle dikoreksi sesuai hitungan fisik.');
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    private function generateCode(): string
    {
        $prefix = 'WOP-CUT-' . now()->format('Ymd');
        $last   = WipOpnamePeriod::where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $seq = $last ? ((int) substr($last, -3)) + 1 : 1;

        return $prefix . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }
}
