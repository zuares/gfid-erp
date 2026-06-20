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

            $statsJournal = DB::table('journals')
                ->whereNull('voided_at')
                ->selectRaw("
                    COUNT(CASE WHEN date < ? THEN 1 END) as legacy_count,
                    COUNT(CASE WHEN date >= ? THEN 1 END) as new_count
                ", [$cutoffDate, $cutoffDate])
                ->first();

            $stats->journal_legacy = $statsJournal->legacy_count ?? 0;
            $stats->journal_new    = $statsJournal->new_count ?? 0;
        }

        return view('settings.system.index', compact('cutoffDate', 'cutoffNotes', 'stats'));
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
        }

        return redirect()
            ->route('settings.system.index')
            ->with('success', "Cut-off date berhasil disimpan: {$date}. Laporan baru akan default mulai dari tanggal ini.");
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
            ->with('success', 'Cut-off date dihapus. Laporan sekarang menampilkan semua data termasuk data lama.');
    }
}
