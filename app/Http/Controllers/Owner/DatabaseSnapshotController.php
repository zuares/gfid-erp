<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DatabaseSnapshotController extends Controller
{
    protected string $snapshotDir;

    public function __construct()
    {
        $this->snapshotDir = storage_path('backups/snapshots');
        File::ensureDirectoryExists($this->snapshotDir);
    }

    /**
     * Daftar semua snapshot, diurutkan terbaru dulu.
     */
    public function index()
    {
        $this->authorizeOwner();

        $currentDb = $this->currentDbPath();
        $files = File::files($this->snapshotDir);

        $snapshots = collect($files)
            ->filter(fn($f) => str_ends_with($f->getFilename(), '.sqlite'))
            ->map(function ($f) {
                $name = $f->getFilename();
                // Format: snap_YYYYMMDD_HHMMSS_label.sqlite
                preg_match('/^snap_(\d{8})_(\d{6})(?:_(.+))?\.sqlite$/', $name, $m);
                $date  = isset($m[1], $m[2])
                    ? \Carbon\Carbon::createFromFormat('Ymd His', $m[1] . ' ' . $m[2])
                    : null;
                $label = isset($m[3]) ? str_replace('_', ' ', $m[3]) : '-';

                return [
                    'filename' => $name,
                    'label'    => $label,
                    'date'     => $date,
                    'size_mb'  => round($f->getSize() / 1024 / 1024, 2),
                    'size_raw' => $f->getSize(),
                ];
            })
            ->sortByDesc(fn($s) => $s['date']?->timestamp ?? 0)
            ->values();

        return view('owner.snapshots.index', compact('snapshots', 'currentDb'));
    }

    /**
     * Buat snapshot dari DB yang sedang aktif.
     */
    public function store(Request $request)
    {
        $this->authorizeOwner();

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:60', 'regex:/^[a-zA-Z0-9 _\-]+$/'],
        ]);

        $currentDb = $this->currentDbPath();

        if (! File::exists($currentDb) || File::size($currentDb) <= 0) {
            return back()->with('error', 'Database aktif kosong atau tidak ditemukan, tidak bisa dibuat snapshot.');
        }

        $labelSlug = isset($data['label']) && $data['label'] !== ''
            ? '_' . preg_replace('/\s+/', '_', trim($data['label']))
            : '';

        $timestamp = now()->format('Ymd_His');
        $filename  = "snap_{$timestamp}{$labelSlug}.sqlite";
        $target    = $this->snapshotDir . '/' . $filename;

        File::copy($currentDb, $target);

        $size = round(File::size($target) / 1024 / 1024, 2);

        return back()->with('success', "Snapshot dibuat: {$filename} ({$size} MB)");
    }

    /**
     * Restore database dari snapshot.
     * DB aktif di-backup dulu sebelum ditimpa.
     */
    public function restore(Request $request, string $filename)
    {
        $this->authorizeOwner();

        // Validasi nama file aman
        if (! preg_match('/^snap_[\w\-]+\.sqlite$/', $filename)) {
            abort(400, 'Nama file snapshot tidak valid.');
        }

        $snapshotPath = $this->snapshotDir . '/' . $filename;

        if (! File::exists($snapshotPath)) {
            return back()->with('error', 'Snapshot tidak ditemukan: ' . $filename);
        }

        if (File::size($snapshotPath) <= 0) {
            return back()->with('error', 'Snapshot kosong, tidak bisa di-restore.');
        }

        $currentDb = $this->currentDbPath();

        // Backup DB aktif sebelum ditimpa
        if (File::exists($currentDb) && File::size($currentDb) > 0) {
            $backupName = 'snap_' . now()->format('Ymd_His') . '_sebelum_restore.sqlite';
            File::copy($currentDb, $this->snapshotDir . '/' . $backupName);
        }

        // Timpa DB aktif dengan snapshot
        File::copy($snapshotPath, $currentDb);

        return back()->with('success', "Database berhasil di-rollback ke snapshot: {$filename}. Backup DB sebelumnya disimpan otomatis.");
    }

    /**
     * Hapus snapshot.
     */
    public function destroy(string $filename)
    {
        $this->authorizeOwner();

        if (! preg_match('/^snap_[\w\-]+\.sqlite$/', $filename)) {
            abort(400, 'Nama file snapshot tidak valid.');
        }

        $path = $this->snapshotDir . '/' . $filename;

        if (! File::exists($path)) {
            return back()->with('error', 'Snapshot tidak ditemukan.');
        }

        File::delete($path);

        return back()->with('success', "Snapshot {$filename} dihapus.");
    }

    // ----------------------------------------------------------------

    protected function currentDbPath(): string
    {
        return (string) config('database.connections.sqlite.database');
    }

    protected function authorizeOwner(): void
    {
        $user = auth()->user();
        $isOwner = (bool) ($user?->is_owner ?? false)
            || (($user?->role ?? null) === 'owner')
            || ($user?->email === env('OWNER_EMAIL', ''))
            || ($user?->isDeveloper() ?? false);

        abort_unless($isOwner, 403, 'Hanya owner yang boleh mengakses fitur ini.');
    }
}
