<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = UserActivityLog::with('user')->orderBy('created_at', 'desc');

        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                  ->orWhere('target_element', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(50)->withQueryString();
        $users = \App\Models\User::orderBy('name')->get();
        
        // Distinct roles for dropdown
        $roles = \App\Models\UserActivityLog::select('role')->distinct()->whereNotNull('role')->pluck('role');

        // Generate Insights (Saran Keputusan)
        $insights = [];
        
        // 1. Halaman dengan durasi rata-rata terlama (threshold lowered for testing)
        $longestDwell = UserActivityLog::select('url', \DB::raw('AVG(duration_ms) as avg_duration'), \DB::raw('COUNT(*) as total_visits'))
            ->where('action', 'visit')
            ->whereNotNull('duration_ms')
            ->groupBy('url')
            ->having('total_visits', '>=', 1) 
            ->orderBy('avg_duration', 'desc')
            ->first();

        if ($longestDwell && $longestDwell->avg_duration > 10000) { // Lebih dari 10 detik (diturunkan untuk testing)
            $seconds = round($longestDwell->avg_duration / 1000);
            $path = str_replace(url('/'), '', $longestDwell->url) ?: '/';
            $insights[] = [
                'type' => 'warning',
                'title' => 'Waktu Tunggu Lama',
                'message' => "Pengguna menghabiskan rata-rata <b>{$seconds} detik</b> di halaman <code>{$path}</code>. Jika ini adalah form input, pertimbangkan untuk menyederhanakan alurnya. (Total kunjungan: {$longestDwell->total_visits})"
            ];
        }

        // 2. Daftar Top 3 Tombol/Elemen yang paling sering diklik
        $topClicked = UserActivityLog::select('target_element', 'url', \DB::raw('COUNT(*) as total_clicks'))
            ->where('action', 'click')
            ->groupBy('target_element', 'url')
            ->orderBy('total_clicks', 'desc')
            ->limit(3)
            ->get();

        if ($topClicked->isNotEmpty()) {
            $listHtml = "<ul class='mb-0 ps-3'>";
            foreach($topClicked as $click) {
                $path = str_replace(url('/'), '', $click->url) ?: '/';
                $listHtml .= "<li><b>{$click->target_element}</b> di <code>{$path}</code> ({$click->total_clicks} klik)</li>";
            }
            $listHtml .= "</ul>";

            $insights[] = [
                'type' => 'info',
                'title' => 'Elemen Paling Sering Diklik',
                'message' => "Berikut elemen UI yang paling banyak berinteraksi dengan pengguna:<br>" . $listHtml . "<br><small class='text-muted'>Pastikan elemen ini memiliki ukuran dan warna yang cukup menonjol.</small>"
            ];
        }

        // 3. Halaman yang sering dibuka tutup cepat (Bouncing)
        $bouncing = UserActivityLog::select('url', \DB::raw('AVG(duration_ms) as avg_duration'), \DB::raw('COUNT(*) as total_visits'))
            ->where('action', 'visit')
            ->whereNotNull('duration_ms')
            ->groupBy('url')
            ->having('total_visits', '>=', 2)
            ->having('avg_duration', '<', 5000) // Kurang dari 5 detik
            ->orderBy('avg_duration', 'asc')
            ->first();

        if ($bouncing) {
            $path = str_replace(url('/'), '', $bouncing->url) ?: '/';
            $insights[] = [
                'type' => 'danger',
                'title' => 'Halaman Sering Ditinggalkan (Bounce)',
                'message' => "Pengguna rata-rata hanya berada <b>" . round($bouncing->avg_duration / 1000, 1) . " detik</b> di <code>{$path}</code>. Evaluasi apakah halaman ini salah klik, membingungkan, atau memang hanya halaman *redirect*."
            ];
        }

        if (empty($insights)) {
            $insights[] = [
                'type' => 'secondary',
                'title' => 'Mengumpulkan Data...',
                'message' => 'Sistem sedang mempelajari perilaku pengguna. Saran keputusan (UI/UX) akan muncul secara otomatis di sini setelah data aktivitas terkumpul cukup banyak (biasanya setelah 1-2 hari penggunaan).'
            ];
        }

        return view('owner.activity-logs.index', compact('logs', 'users', 'insights', 'roles'));
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = Auth::user();
        
        // Exclude owner/dev from logging
        if (in_array($user->role, ['owner', 'developer'])) {
            return response()->json(['message' => 'Ignored'], 200);
        }

        $validated = $request->validate([
            'url' => 'required|string|max:500',
            'action' => 'required|string|max:50',
            'target_element' => 'nullable|string|max:500',
            'duration_ms' => 'nullable|integer',
        ]);

        defer(function () use ($user, $validated) {
            UserActivityLog::create([
                'user_id' => $user->id,
                'role' => $user->role,
                'url' => $validated['url'],
                'action' => $validated['action'],
                'target_element' => $validated['target_element'],
                'duration_ms' => $validated['duration_ms'],
            ]);
        });

        return response()->json(['message' => 'Logged successfully']);
    }
}
