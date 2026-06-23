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
        ]);

        $mode = (string) $data['mode'];
        $arguments = ['mode' => $mode];

        if ($mode === 'dev') {
            $arguments['--no-copy'] = true;
        }

        $code = Artisan::call('app:mode', $arguments);
        $output = trim(Artisan::output());

        if ($code !== 0) {
            return back()->with('error', $output ?: 'Gagal mengganti mode database.');
        }

        return back()->with('success', $mode === 'dev'
            ? 'Mode database sekarang DEV.'
            : 'Mode database sekarang OPERASIONAL.');
    }
}
