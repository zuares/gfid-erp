<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DatabaseModeController extends Controller
{
    public function switch(Request $request)
    {
        $user = $request->user();
        $ownerEmail = env('OWNER_EMAIL', 'ciciadeliamardani@gmail.com');

        $isOwner =
            (bool) ($user->is_owner ?? false) ||
            (($user->role ?? null) === 'owner') ||
            (($user->email ?? null) === $ownerEmail) ||
            ($user?->isDeveloper());

        abort_unless($isOwner, 403, 'Hanya owner yang boleh mengganti mode database.');

        $data = $request->validate([
            'mode' => ['required', 'in:dev,ops'],
            'action' => ['nullable', 'in:init_from_current'],
        ]);

        $mode = (string) $data['mode'];
        $arguments = ['mode' => $mode];

        if ($mode === 'dev') {
            $arguments['--no-copy'] = true;
            $arguments['--force'] = true; // izinkan devDb kosong, user bisa migrate:fresh --seed setelah ini
        }

        if ($mode === 'ops' && ($data['action'] ?? null) === 'init_from_current') {
            $arguments['--init-from-current'] = true;
        }

        $code = Artisan::call('app:mode', $arguments);
        $output = trim(Artisan::output());

        if ($code !== 0) {
            // Ambil baris terakhir yang bermakna dari output artisan (hindari raw multi-line di toast)
            $lines = array_filter(array_map('trim', explode("\n", $output)));
            $lastLine = end($lines) ?: 'Gagal mengganti mode database.';
            return back()->with('error', $lastLine);
        }

        return back()->with('success', $mode === 'dev'
            ? 'Mode database sekarang DEV.'
            : 'Mode database sekarang OPERASIONAL.');
    }
}
