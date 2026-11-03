<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Services\Developer\DeveloperLedgerResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeveloperLedgerResetController extends Controller
{
    public function store(Request $request, DeveloperLedgerResetService $resetService): RedirectResponse
    {
        abort_unless($request->user()?->isDeveloper(), 403);

        $request->validate([
            'confirmation' => ['required', 'string', Rule::in(['RESET SALDO'])],
        ]);

        $result = $resetService->reset();

        return back()
            ->with('status', 'success')
            ->with('message', 'Reset saldo selesai. '.$result['total'].' baris transaksi accounting, inventory, produksi, dan purchasing dihapus; master data tetap dipertahankan.');
    }
}
