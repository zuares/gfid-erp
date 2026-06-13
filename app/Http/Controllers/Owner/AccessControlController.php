<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserModuleAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccessControlController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(
            User::moduleAccessTableExists(),
            503,
            'Tabel akses login belum tersedia. Jalankan php artisan migrate di production.'
        );

        $users = User::query()
            ->with(['employee', 'moduleAccesses'])
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 WHEN 'operating' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->get();

        return view('owner.access_control.index', [
            'users' => $users,
            'modules' => UserModuleAccess::MODULES,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(
            User::moduleAccessTableExists(),
            503,
            'Tabel akses login belum tersedia. Jalankan php artisan migrate di production.'
        );

        $data = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'access' => ['array'],
            'access.*' => ['array'],
            'access.*.*' => ['string', Rule::in(array_keys(UserModuleAccess::MODULES))],
        ]);

        $modules = array_keys(UserModuleAccess::MODULES);
        $accessInput = $data['access'] ?? [];
        $users = User::query()->whereIn('id', $data['user_ids'])->get();

        foreach ($users as $user) {
            foreach ($modules as $module) {
                UserModuleAccess::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'module' => $module,
                    ],
                    [
                        'can_access' => $user->isOwner() || in_array($module, $accessInput[$user->id] ?? [], true),
                        'updated_by' => $request->user()?->id,
                    ]
                );
            }
        }

        return back()->with('message', 'Akses user berhasil diperbarui.');
    }
}
