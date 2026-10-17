<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\MarketplaceCrmController;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(Request $request, MarketplaceCrmController $marketplaceCrm): View
    {
        $search = trim($request->string('q')->toString());
        $filter = $request->string('filter')->toString();
        $filter = $filter === 'whatsapp' ? 'phone' : $filter;
        $filter = in_array($filter, ['all', 'phone', 'email'], true) ? $filter : 'all';
        $storeId = $request->integer('store_id') ?: null;

        $profiles = $marketplaceCrm->customerProfileQuery($storeId);
        $profileStats = fn () => DB::query()->fromSub(clone $profiles, 'contact_profiles');
        $stats = [
            'total' => $profileStats()->count(),
            'whatsapp' => $profileStats()->whereRaw("TRIM(COALESCE(contact_profiles.phone, '')) <> '' AND contact_profiles.phone NOT LIKE '%*%' AND LOWER(contact_profiles.phone) NOT LIKE '%x%'")->count(),
            'email' => $profileStats()->whereRaw("TRIM(COALESCE(contact_profiles.email, '')) <> ''")->count(),
        ];

        if ($search !== '') {
            $needle = '%'.mb_strtolower($search).'%';
            $profiles->havingRaw(
                "(LOWER(COALESCE(name, '')) LIKE ? OR LOWER(COALESCE(code, '')) LIKE ? OR LOWER(COALESCE(phone, '')) LIKE ? OR LOWER(COALESCE(email, '')) LIKE ? OR LOWER(COALESCE(buyer_username, '')) LIKE ? OR LOWER(COALESCE(city, '')) LIKE ?)",
                [$needle, $needle, $needle, $needle, $needle, $needle],
            );
        }
        if ($filter === 'phone') {
            $profiles->havingRaw("TRIM(COALESCE(phone, '')) <> '' AND phone NOT LIKE '%*%' AND LOWER(phone) NOT LIKE '%x%'");
        } elseif ($filter === 'email') {
            $profiles->havingRaw("TRIM(COALESCE(email, '')) <> ''");
        }

        $perPage = 24;
        $contacts = $profiles
            ->orderBy('name')
            ->paginate($perPage)
            ->withPath($request->url())
            ->appends($request->query());
        $stores = Store::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('contacts.index', compact('contacts', 'search', 'filter', 'stats', 'stores', 'storeId'));
    }
}
