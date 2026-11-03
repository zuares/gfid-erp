<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pengaturan Sistem — termasuk cut-off date.
 *
 * Routes:
 *   GET  /settings/system         → index()
 *   POST /settings/system/cutoff  → storeCutoff()
 *   POST /settings/system/cutoff/clear → clearCutoff()
 */
class SystemSettingController extends Controller
{
    public function index()
    {
        $cutoffDate  = SystemSetting::cutoffDateString();
        $cutoffNotes = SystemSetting::get(SystemSetting::KEY_CUTOFF_NOTES);
        $cutoffSetting = SystemSetting::query()
            ->with('updater')
            ->find(SystemSetting::KEY_CUTOFF_DATE);

        // Statistik cepat: berapa mutasi sebelum vs sesudah cut-off
        $stats = null;
        if ($cutoffDate) {
            $stats = DB::table('inventory_mutations')
                ->selectRaw("
                    COUNT(CASE WHEN date < ? THEN 1 END) as legacy_count,
                    COUNT(CASE WHEN date >= ? THEN 1 END) as new_count,
                    COALESCE(SUM(CASE WHEN date < ? THEN ABS(total_cost) ELSE 0 END), 0) as legacy_value,
                    COALESCE(SUM(CASE WHEN date >= ? THEN ABS(total_cost) ELSE 0 END), 0) as new_value
                ", [$cutoffDate, $cutoffDate, $cutoffDate, $cutoffDate])
                ->first();

            $statsLegacyJournal = DB::table('journals')
                ->whereNull('voided_at')
                ->selectRaw("
                    COUNT(CASE WHEN date < ? THEN 1 END) as legacy_count
                ", [$cutoffDate])
                ->first();

            $statsNewJournal = DB::table('standalone_accounting_journals')
                ->whereNull('voided_at')
                ->whereDate('date', '>=', $cutoffDate)
                ->selectRaw('COUNT(*) as new_count')
                ->first();

            $stats->journal_legacy = $statsLegacyJournal->legacy_count ?? 0;
            $stats->journal_new = $statsNewJournal->new_count ?? 0;
            $stats->dummy_opening_count = DB::table('standalone_accounting_journals')
                ->whereNull('voided_at')
                ->where('reference', 'OB-UMKM-DUMMY-001')
                ->count();
        }

        return view('settings.system.index', compact(
            'cutoffDate',
            'cutoffNotes',
            'cutoffSetting',
            'stats',
        ));
    }

    /**
     * Simpan atau update cut-off date.
     */
    public function storeCutoff(Request $request)
    {
        $data = $request->validate([
            'cutoff_date'  => ['required', 'date', 'before_or_equal:today'],
            'cutoff_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'cutoff_date.before_or_equal' => 'Cut-off date tidak boleh di masa depan.',
        ]);

        $date  = Carbon::parse($data['cutoff_date'])->toDateString();
        $notes = trim($data['cutoff_notes'] ?? '');

        SystemSetting::set(
            SystemSetting::KEY_CUTOFF_DATE,
            $date,
            'Tanggal cut-off produksi baru',
            auth()->id()
        );

        if ($notes !== '') {
            SystemSetting::set(
                SystemSetting::KEY_CUTOFF_NOTES,
                $notes,
                'Catatan cut-off',
                auth()->id()
            );
        } else {
            SystemSetting::remove(SystemSetting::KEY_CUTOFF_NOTES);
        }

        return redirect()
            ->route('settings.system.index')
            ->with('success', "Tanggal mulai sistem baru disimpan: {$date}.");
    }

    /**
     * Hapus cut-off date (kembali ke mode tampilkan semua).
     */
    public function clearCutoff(Request $request)
    {
        SystemSetting::remove(SystemSetting::KEY_CUTOFF_DATE);
        SystemSetting::remove(SystemSetting::KEY_CUTOFF_NOTES);

        return redirect()
            ->route('settings.system.index')
            ->with('success', 'Tampilan semua data dikembalikan. Tidak ada data yang dihapus.');
    }
}
